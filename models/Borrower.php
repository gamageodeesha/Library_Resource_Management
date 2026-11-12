<?php
// The "borrowers" table (the people who borrow resources)
class Borrower extends Model
{
    // ---------- Finding borrowers ----------
    public function find($borrower_id)
    {
        return $this->getRow('SELECT * FROM borrowers WHERE borrower_id = ?', [$borrower_id]);
    }

    // The borrower_id that belongs to a sign-in account (false if there is none)
    public function idForUser($user_id)
    {
        return $this->getValue('SELECT borrower_id FROM borrowers WHERE user_id = ?', [$user_id]);
    }

    // Short info about the borrower record linked to a sign-in account
    public function linkedToUser($user_id)
    {
        return $this->getRow(
            'SELECT borrower_id, borrower_code, first_name, last_name FROM borrowers WHERE user_id = ?',
            [$user_id]
        );
    }

    public function summary($borrower_id)
    {
        return $this->getRow(
            'SELECT borrower_id, borrower_code, first_name, last_name FROM borrowers WHERE borrower_id = ?',
            [$borrower_id]
        );
    }

    public function countAll()
    {
        return (int)$this->getValue('SELECT COUNT(*) FROM borrowers');
    }

    // Does this email already belong to another borrower?
    public function emailTaken($email, $except_borrower_id)
    {
        return $this->getValue('SELECT COUNT(*) FROM borrowers WHERE email = ? AND borrower_id <> ?', [$email, $except_borrower_id]) > 0;
    }

    // ---------- Linking a borrower to a sign-in account ----------
    // Is this borrower record still without a sign-in account?
    public function isUnlinked($borrower_id)
    {
        return $this->getValue('SELECT COUNT(*) FROM borrowers WHERE borrower_id = ? AND user_id IS NULL', [$borrower_id]) > 0;
    }

    // Borrower records that do not have a sign-in account yet
    public function unlinked()
    {
        return $this->getRows(
            'SELECT borrower_id, borrower_code, first_name, last_name, email
             FROM borrowers WHERE user_id IS NULL ORDER BY first_name, last_name'
        );
    }

    public function linkUser($borrower_id, $user_id)
    {
        $this->run('UPDATE borrowers SET user_id = ? WHERE borrower_id = ? AND user_id IS NULL', [$user_id, $borrower_id]);
    }

    // ---------- Lists ----------
    // The staff "Borrowers" directory. $filter: all, active, inactive or with_loans
    public function search($search, $filter)
    {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(b.first_name LIKE ? OR b.last_name LIKE ? OR CONCAT(b.first_name, ' ', b.last_name) LIKE ? OR b.email LIKE ? OR b.borrower_code LIKE ?)";
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($filter === 'active') {
            $where[] = "b.membership_status = 'active'";
        } elseif ($filter === 'inactive') {
            $where[] = "b.membership_status = 'inactive'";
        } elseif ($filter === 'with_loans') {
            $where[] = "EXISTS (SELECT 1 FROM loans l WHERE l.borrower_id = b.borrower_id AND l.status = 'active')";
        }

        $sql = "SELECT b.*,
                       (SELECT COUNT(*) FROM loans l WHERE l.borrower_id = b.borrower_id AND l.status = 'active') AS active_loans
                FROM borrowers b";
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY b.first_name, b.last_name';

        return $this->getRows($sql, $params);
    }

    // Only ACTIVE members can borrow (used on the "Register a loan" page)
    public function activeMembers()
    {
        return $this->getRows(
            "SELECT b.borrower_id, b.borrower_code, b.first_name, b.last_name, b.email,
                    (SELECT COUNT(*) FROM loans l WHERE l.borrower_id = b.borrower_id AND l.status = 'active') AS active_loans
             FROM borrowers b
             WHERE b.membership_status = 'active'
             ORDER BY b.first_name, b.last_name"
        );
    }

    // Everybody, just names (for a drop-down)
    public function names()
    {
        return $this->getRows('SELECT borrower_id, first_name, last_name FROM borrowers ORDER BY first_name, last_name');
    }

    // The four numbers shown next to a borrower: current, overdue, returned and pending renewals
    public function stats($borrower_id)
    {
        return [
            'current'  => (int)$this->getValue("SELECT COUNT(*) FROM loans WHERE borrower_id = ? AND status = 'active'", [$borrower_id]),
            'overdue'  => (int)$this->getValue("SELECT COUNT(*) FROM loans WHERE borrower_id = ? AND status = 'active' AND due_date < CURDATE()", [$borrower_id]),
            'returned' => (int)$this->getValue("SELECT COUNT(*) FROM loans WHERE borrower_id = ? AND status = 'returned'", [$borrower_id]),
            'pending'  => (int)$this->getValue("SELECT COUNT(*) FROM renewal_requests WHERE borrower_id = ? AND status = 'pending'", [$borrower_id]),
        ];
    }

    // ---------- Add and edit ----------
    // $v holds: first_name, last_name, email, phone, address, membership_status, staff_notes
    // Empty phone / address / notes are saved as NULL.
    // Returns the new code and id, like ['code' => 'B007', 'id' => 7]
    public function create($v)
    {
        $code = $this->nextCode('borrowers', 'borrower_code', 'B', 3);
        $this->run(
            'INSERT INTO borrowers (borrower_code, first_name, last_name, email, phone, address, membership_status, staff_notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$code, $v['first_name'], $v['last_name'], $v['email'], $this->nullIfEmpty($v['phone']),
             $this->nullIfEmpty($v['address']), $v['membership_status'], $this->nullIfEmpty($v['staff_notes'])]
        );
        return ['code' => $code, 'id' => (int)$this->pdo->lastInsertId()];
    }

    public function update($borrower_id, $v)
    {
        $this->run(
            'UPDATE borrowers
             SET first_name = ?, last_name = ?, email = ?, phone = ?, membership_status = ?, address = ?, staff_notes = ?
             WHERE borrower_id = ?',
            [$v['first_name'], $v['last_name'], $v['email'], $this->nullIfEmpty($v['phone']),
             $v['membership_status'], $this->nullIfEmpty($v['address']), $this->nullIfEmpty($v['staff_notes']), $borrower_id]
        );
    }

    // ---------- Sign-up ----------
    // A new borrower creates their own account. Two rows are saved together:
    //   1. the sign-in account (users table, role = borrower)
    //   2. the borrower record (borrowers table), linked to that account
    // Returns ['user_id' => ..., 'borrower_id' => ...] or false if something went wrong.
    public function register($first_name, $last_name, $email, $password_hash)
    {
        try {
            // A transaction = "both steps succeed together, or neither happens"
            $this->pdo->beginTransaction();

            $this->run(
                "INSERT INTO users (full_name, email, password_hash, role, status, must_change_password)
                 VALUES (?, ?, ?, 'borrower', 'active', 0)",
                [$first_name . ' ' . $last_name, $email, $password_hash]
            );
            $user_id = (int)$this->pdo->lastInsertId();

            $code = $this->nextCode('borrowers', 'borrower_code', 'B', 3);
            $this->run(
                "INSERT INTO borrowers (borrower_code, user_id, first_name, last_name, email, membership_status)
                 VALUES (?, ?, ?, ?, ?, 'active')",
                [$code, $user_id, $first_name, $last_name, $email]
            );
            $borrower_id = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();
            return ['user_id' => $user_id, 'borrower_id' => $borrower_id];
        } catch (PDOException $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    private function nullIfEmpty($text)
    {
        return ($text === '') ? null : $text;
    }
}
