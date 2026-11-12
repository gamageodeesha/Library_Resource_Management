<?php
// The "users" table (the sign-in accounts of admins, staff and borrowers)
class User extends Model
{
    // ---------- Finding one user ----------
    public function find($user_id)
    {
        return $this->getRow('SELECT * FROM users WHERE user_id = ?', [$user_id]);
    }

    public function findByEmail($email)
    {
        return $this->getRow('SELECT * FROM users WHERE email = ?', [$email]);
    }

    // Is this email used by another account?
    public function emailTaken($email, $except_user_id)
    {
        return $this->getValue('SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?', [$email, $except_user_id]) > 0;
    }

    // ---------- Small updates ----------
    public function touchLogin($user_id)
    {
        $this->run('UPDATE users SET last_login_at = NOW() WHERE user_id = ?', [$user_id]);
    }

    public function updateName($user_id, $full_name)
    {
        $this->run('UPDATE users SET full_name = ? WHERE user_id = ?', [$full_name, $user_id]);
    }

    public function updatePassword($user_id, $password_hash)
    {
        $this->run('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?', [$password_hash, $user_id]);
    }

    // Used only by setup.php. Returns how many users were changed.
    public function setAllPasswords($password_hash)
    {
        return $this->run('UPDATE users SET password_hash = ?', [$password_hash])->rowCount();
    }

    // ---------- Lists and numbers ----------
    public function activeStaffIds()
    {
        return $this->getColumn("SELECT user_id FROM users WHERE role = 'staff' AND status = 'active'");
    }

    public function countAll()
    {
        return (int)$this->getValue('SELECT COUNT(*) FROM users');
    }

    public function countActive()
    {
        return (int)$this->getValue("SELECT COUNT(*) FROM users WHERE status = 'active'");
    }

    // How many users each role has: ['admin' => 1, 'staff' => 2, 'borrower' => 5]
    public function countByRole()
    {
        $counts = ['admin' => 0, 'staff' => 0, 'borrower' => 0];
        foreach ($this->getRows('SELECT role, COUNT(*) AS total FROM users GROUP BY role') as $row) {
            $counts[$row['role']] = (int)$row['total'];
        }
        return $counts;
    }

    public function newest($limit)
    {
        return $this->getRows('SELECT * FROM users ORDER BY user_id DESC LIMIT ' . (int)$limit);
    }

    // Other active admins (so the last admin can never lock themselves out)
    public function countOtherActiveAdmins($user_id)
    {
        return (int)$this->getValue("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND user_id <> ?", [$user_id]);
    }

    // ---------- Search + pages for the admin "Users" table ----------
    private function filter($search, $role)
    {
        $where  = [];
        $params = [];
        if ($search !== '') {
            $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like);
        }
        if ($role !== '') {
            $where[]  = 'u.role = ?';
            $params[] = $role;
        }
        $where_sql = count($where) > 0 ? ' WHERE ' . implode(' AND ', $where) : '';
        return [$where_sql, $params];
    }

    public function countMatching($search, $role)
    {
        list($where_sql, $params) = $this->filter($search, $role);
        return (int)$this->getValue('SELECT COUNT(*) FROM users u' . $where_sql, $params);
    }

    public function search($search, $role, $limit, $offset)
    {
        list($where_sql, $params) = $this->filter($search, $role);
        return $this->getRows(
            'SELECT u.*, b.borrower_code
             FROM users u
             LEFT JOIN borrowers b ON b.user_id = u.user_id'
            . $where_sql
            . ' ORDER BY u.role, u.full_name'
            . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }

    // ---------- Save (add a new user, or save changes to one) ----------
    // $user_id = 0 means "add a new user".
    // $temp_password = '' means "do not change the password".
    // $link_borrower_id > 0 means "link this account to that borrower record".
    // Returns true if everything was saved, false if something went wrong.
    public function saveAccount($user_id, $form, $temp_password, $link_borrower_id)
    {
        try {
            // A transaction = "all steps succeed together, or none of them happen"
            $this->pdo->beginTransaction();

            if ($user_id > 0) {
                $this->run(
                    'UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE user_id = ?',
                    [$form['full_name'], $form['email'], $form['role'], $form['status'], $user_id]
                );
                if ($temp_password !== '') {
                    $this->run(
                        'UPDATE users SET password_hash = ?, must_change_password = 1 WHERE user_id = ?',
                        [password_hash($temp_password, PASSWORD_DEFAULT), $user_id]
                    );
                }
                $target_id = $user_id;
            } else {
                $this->run(
                    'INSERT INTO users (full_name, email, password_hash, role, status, must_change_password)
                     VALUES (?, ?, ?, ?, ?, 1)',
                    [$form['full_name'], $form['email'], password_hash($temp_password, PASSWORD_DEFAULT), $form['role'], 'active']
                );
                $target_id = (int)$this->pdo->lastInsertId();
            }

            // Link the sign-in account to the borrower record
            if ($link_borrower_id > 0) {
                $borrowers = new Borrower($this->pdo);
                $borrowers->linkUser($link_borrower_id, $target_id);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }
}
