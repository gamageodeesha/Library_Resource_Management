<?php
// The "renewal_requests" table (borrowers ask for more time, staff approve or reject)
class Renewal extends Model
{
    private $notifications;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->notifications = new Notification($pdo);
    }

    // =====================================================
    // Numbers
    // =====================================================
    public function countPending()
    {
        return (int)$this->getValue("SELECT COUNT(*) FROM renewal_requests WHERE status = 'pending'");
    }

    // Numbers for the tabs: total, pending, approved, rejected (for one borrower, or for everybody)
    public function counts($borrower_id = null)
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(status = 'pending'), 0)  AS pending,
                       COALESCE(SUM(status = 'approved'), 0) AS approved,
                       COALESCE(SUM(status = 'rejected'), 0) AS rejected
                FROM renewal_requests";
        $params = [];
        if ($borrower_id !== null) {
            $sql .= ' WHERE borrower_id = ?';
            $params[] = $borrower_id;
        }
        return $this->getRow($sql, $params);
    }

    // =====================================================
    // Finding requests
    // =====================================================

    // All the requests of one loan
    public function forLoan($loan_id, $newest_first)
    {
        $order = $newest_first ? 'renewal_id DESC' : 'renewal_id';
        return $this->getRows('SELECT * FROM renewal_requests WHERE loan_id = ? ORDER BY ' . $order, [$loan_id]);
    }

    // The oldest request that still waits for a decision (staff dashboard)
    public function oldestPending()
    {
        return $this->getRow(
            "SELECT rr.renewal_id, rr.requested_due_date, rr.submitted_at,
                    l.due_date AS current_due, r.title,
                    CONCAT(b.first_name, ' ', b.last_name) AS borrower_name
             FROM renewal_requests rr
             JOIN loans l     ON l.loan_id = rr.loan_id
             JOIN resources r ON r.resource_id = l.resource_id
             JOIN borrowers b ON b.borrower_id = rr.borrower_id
             WHERE rr.status = 'pending'
             ORDER BY rr.submitted_at ASC
             LIMIT 1"
        );
    }

    // ---------- Borrower side (only mine: WHERE borrower_id = ...) ----------
    // $tab: all, pending, approved or rejected
    public function listForBorrower($borrower_id, $tab)
    {
        $sql = "SELECT rr.*, r.title
                FROM renewal_requests rr
                JOIN loans l     ON l.loan_id = rr.loan_id
                JOIN resources r ON r.resource_id = l.resource_id
                WHERE rr.borrower_id = ?";
        $params = [$borrower_id];
        if ($tab !== 'all') {
            $sql     .= ' AND rr.status = ?';
            $params[] = $tab;
        }
        $sql .= ' ORDER BY rr.submitted_at DESC, rr.renewal_id DESC';

        return $this->getRows($sql, $params);
    }

    public function findForBorrower($renewal_id, $borrower_id)
    {
        return $this->getRow(
            "SELECT rr.*, r.title, l.loan_date, l.due_date AS loan_due, l.renewal_count, l.status AS loan_status
             FROM renewal_requests rr
             JOIN loans l     ON l.loan_id = rr.loan_id
             JOIN resources r ON r.resource_id = l.resource_id
             WHERE rr.renewal_id = ? AND rr.borrower_id = ?",
            [$renewal_id, $borrower_id]
        );
    }

    // ---------- Staff side ----------
    // $tab: all, pending, approved or rejected. $dates: all, 7 or 30
    public function listForStaff($tab, $search, $dates)
    {
        $where  = [];
        $params = [];

        if ($tab !== 'all') {
            $where[]  = 'rr.status = ?';
            $params[] = $tab;
        }
        if ($search !== '') {
            $like  = '%' . $search . '%';
            $parts = ['r.title LIKE ?', "CONCAT(b.first_name, ' ', b.last_name) LIKE ?"];
            array_push($params, $like, $like);
            $digits = preg_replace('/\D/', '', $search);          // "RN201" or "201" finds request 201
            if ($digits !== '') {
                $parts[]  = 'rr.renewal_id = ?';
                $params[] = (int)$digits;
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        if ($dates !== 'all') {
            $where[]  = 'rr.submitted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)';
            $params[] = (int)$dates;
        }

        $sql = "SELECT rr.*, r.title, CONCAT(b.first_name, ' ', b.last_name) AS borrower_name
                FROM renewal_requests rr
                JOIN loans l     ON l.loan_id = rr.loan_id
                JOIN resources r ON r.resource_id = l.resource_id
                JOIN borrowers b ON b.borrower_id = rr.borrower_id";
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY (rr.status = 'pending') DESC, rr.submitted_at DESC";

        return $this->getRows($sql, $params);
    }

    public function findForStaff($renewal_id)
    {
        return $this->getRow(
            "SELECT rr.*, r.title, r.resource_code,
                    l.loan_date, l.due_date AS loan_due, l.renewal_count, l.status AS loan_status,
                    b.borrower_code, CONCAT(b.first_name, ' ', b.last_name) AS borrower_name,
                    u.full_name AS decided_by_name
             FROM renewal_requests rr
             JOIN loans l     ON l.loan_id = rr.loan_id
             JOIN resources r ON r.resource_id = l.resource_id
             JOIN borrowers b ON b.borrower_id = rr.borrower_id
             LEFT JOIN users u ON u.user_id = rr.decided_by
             WHERE rr.renewal_id = ?",
            [$renewal_id]
        );
    }

    // =====================================================
    // A borrower asks for a renewal
    // Returns ['id' => new request number, 'errors' => [...]]. No errors means it worked.
    // =====================================================
    public function submit($loan_id, $borrower_id, $user_id, $requested_date, $reason, $max_renewals, $max_extension)
    {
        $errors = [];
        $new_id = 0;

        try {
            $this->pdo->beginTransaction();

            // The loan must belong to THIS borrower and still be active
            $loan = $this->getRow(
                "SELECT l.*, r.title, b.first_name, b.last_name
                 FROM loans l
                 JOIN resources r ON r.resource_id = l.resource_id
                 JOIN borrowers b ON b.borrower_id = l.borrower_id
                 WHERE l.loan_id = ? AND l.borrower_id = ? AND l.status = 'active' FOR UPDATE",
                [$loan_id, $borrower_id]
            );

            if (!$loan) {
                $errors[] = 'That loan could not be found.';
            } else {
                $has_pending = $this->getValue("SELECT COUNT(*) FROM renewal_requests WHERE loan_id = ? AND status = 'pending'", [$loan_id]) > 0;

                $latest_allowed = date('Y-m-d', strtotime($loan['due_date'] . ' +' . $max_extension . ' days'));

                if ($loan['renewal_count'] >= $max_renewals) {
                    $errors[] = 'This loan has already used its renewal.';
                } elseif ($has_pending) {
                    $errors[] = 'You already have a pending renewal request for this loan.';
                } elseif ($requested_date <= $loan['due_date']) {
                    $errors[] = 'The new due date must be after the current due date (' . format_date($loan['due_date']) . ').';
                } elseif ($requested_date > $latest_allowed) {
                    $errors[] = 'You can ask for at most ' . $max_extension . ' extra days (up to ' . format_date($latest_allowed) . ').';
                }
            }

            if (count($errors) === 0) {
                $this->run(
                    'INSERT INTO renewal_requests (loan_id, borrower_id, previous_due_date, requested_due_date, reason)
                     VALUES (?, ?, ?, ?, ?)',
                    [$loan_id, $borrower_id, $loan['due_date'], $requested_date, ($reason === '' ? null : $reason)]
                );
                $new_id = (int)$this->pdo->lastInsertId();
                $rn     = 'RN' . $new_id;

                // Tell the borrower
                $this->notifications->add(
                    $user_id, 'request_received',
                    $rn . ' is awaiting review',
                    'You requested ' . format_date($requested_date) . ' for ' . $loan['title'] . '. Your current due date remains ' . format_date($loan['due_date']) . '.',
                    $loan_id, $new_id
                );

                // Tell every active staff member
                $users = new User($this->pdo);
                foreach ($users->activeStaffIds() as $staff_id) {
                    $this->notifications->add(
                        $staff_id, 'request_received',
                        'New renewal request ' . $rn,
                        $loan['first_name'] . ' ' . $loan['last_name'] . ' requested a renewal for ' . $loan['title'] . ' (requested due ' . format_date($requested_date) . ').',
                        $loan_id, $new_id
                    );
                }

                $this->pdo->commit();
            } else {
                $this->pdo->rollBack();
            }
        } catch (PDOException $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $new_id   = 0;
            $errors[] = 'The request could not be saved. Please try again.';
        }

        return ['id' => $new_id, 'errors' => $errors];
    }

    // =====================================================
    // Staff approve or reject a request
    // $decision is 'approve' or 'reject'.
    // Returns ['errors' => [...], 'message' => '...']. No errors means it worked.
    // =====================================================
    public function decide($renewal_id, $decision, $note_text, $staff_user_id, $max_renewals)
    {
        $errors  = [];
        $message = '';

        try {
            $this->pdo->beginTransaction();

            // Lock the request and its loan so nobody else changes them at the same time
            $req = $this->getRow(
                'SELECT rr.*, l.due_date AS loan_due, l.status AS loan_status, l.renewal_count, r.title,
                        b.user_id AS borrower_user
                 FROM renewal_requests rr
                 JOIN loans l     ON l.loan_id = rr.loan_id
                 JOIN resources r ON r.resource_id = l.resource_id
                 JOIN borrowers b ON b.borrower_id = rr.borrower_id
                 WHERE rr.renewal_id = ? FOR UPDATE',
                [$renewal_id]
            );

            if (!$req || $req['status'] !== 'pending') {
                $errors[] = 'This request has already been decided.';
            } elseif ($decision === 'approve') {
                if ($req['loan_status'] !== 'active') {
                    $errors[] = 'This loan is no longer active, so it cannot be renewed.';
                } elseif ($req['renewal_count'] >= $max_renewals) {
                    $errors[] = 'This loan has already used its renewal.';
                } elseif ($req['requested_due_date'] <= $req['loan_due']) {
                    $errors[] = 'The requested date is not after the current due date.';
                }
            }

            if (count($errors) === 0) {
                $note = ($note_text === '') ? null : $note_text;
                $rn   = 'RN' . $req['renewal_id'];

                if ($decision === 'approve') {
                    // Step 1: move the due date and count the renewal
                    $this->run('UPDATE loans SET due_date = ?, renewal_count = renewal_count + 1 WHERE loan_id = ?',
                               [$req['requested_due_date'], $req['loan_id']]);

                    // Step 2: mark the request approved
                    $this->run("UPDATE renewal_requests SET status = 'approved', decided_by = ?, decided_at = NOW(), decision_note = ? WHERE renewal_id = ?",
                               [$staff_user_id, $note, $renewal_id]);

                    // Step 3: tell the borrower
                    $this->notifications->add(
                        $req['borrower_user'], 'renewal_approved',
                        'Your ' . $req['title'] . ' renewal was approved',
                        $rn . ' changed your due date from ' . format_date($req['loan_due']) . ' to ' . format_date($req['requested_due_date'])
                            . '. Renewals used: ' . ($req['renewal_count'] + 1) . ' of ' . $max_renewals . '.',
                        $req['loan_id'], $renewal_id
                    );
                    $message = $rn . ' approved. The new due date is ' . format_date($req['requested_due_date']) . '.';
                } else {
                    $this->run("UPDATE renewal_requests SET status = 'rejected', decided_by = ?, decided_at = NOW(), decision_note = ? WHERE renewal_id = ?",
                               [$staff_user_id, $note, $renewal_id]);

                    $this->notifications->add(
                        $req['borrower_user'], 'renewal_rejected',
                        'Your ' . $req['title'] . ' renewal was rejected',
                        $rn . ' did not change your ' . format_date($req['loan_due']) . ' due date. Reason: ' . $note_text,
                        $req['loan_id'], $renewal_id
                    );
                    $message = $rn . ' rejected. The due date stays ' . format_date($req['loan_due']) . '.';
                }

                $this->pdo->commit();
            } else {
                $this->pdo->rollBack();
            }
        } catch (PDOException $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $message  = '';
            $errors[] = 'The decision could not be saved. Please try again.';
        }

        return ['errors' => $errors, 'message' => $message];
    }
}
