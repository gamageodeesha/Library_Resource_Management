<?php
// The "loans" table and the "vw_loans" view.
// vw_loans is the loans table joined with the resource and borrower details,
// plus the ready-made columns display_status and days_overdue.
class Loan extends Model
{
    private $notifications;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->notifications = new Notification($pdo);
    }

    // Today's date according to the database (keeps PHP and MySQL in agreement)
    public function today()
    {
        return $this->getValue('SELECT CURDATE()');
    }

    // =====================================================
    // Numbers
    // =====================================================
    public function countActive()
    {
        return (int)$this->getValue("SELECT COUNT(*) FROM loans WHERE status = 'active'");
    }

    public function countOverdue()
    {
        return (int)$this->getValue("SELECT COUNT(*) FROM loans WHERE status = 'active' AND due_date < CURDATE()");
    }

    // How many different borrowers have an active loan
    public function countActiveBorrowers()
    {
        return (int)$this->getValue("SELECT COUNT(DISTINCT borrower_id) FROM loans WHERE status = 'active'");
    }

    // The nearest due date among the active loans that are not overdue yet
    public function nextDueDate()
    {
        return $this->getValue("SELECT MIN(due_date) FROM loans WHERE status = 'active' AND due_date >= CURDATE()");
    }

    public function oldestOverdueDays()
    {
        return (int)$this->getValue("SELECT COALESCE(MAX(DATEDIFF(CURDATE(), due_date)), 0) FROM loans WHERE status = 'active' AND due_date < CURDATE()");
    }

    public function countReturnedByBorrower($borrower_id)
    {
        return (int)$this->getValue("SELECT COUNT(*) FROM loans WHERE borrower_id = ? AND status = 'returned'", [$borrower_id]);
    }

    // Numbers for the tabs: total, active, returned, overdue (for one borrower, or for everybody)
    public function counts($borrower_id = null)
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(status = 'active'), 0)   AS active,
                       COALESCE(SUM(status = 'returned'), 0) AS returned,
                       COALESCE(SUM(status = 'active' AND due_date < CURDATE()), 0) AS overdue
                FROM loans";
        $params = [];
        if ($borrower_id !== null) {
            $sql .= ' WHERE borrower_id = ?';
            $params[] = $borrower_id;
        }
        return $this->getRow($sql, $params);
    }

    // =====================================================
    // Finding loans
    // =====================================================
    public function find($loan_id)
    {
        return $this->getRow('SELECT * FROM vw_loans WHERE loan_id = ?', [$loan_id]);
    }

    // Only if the loan belongs to this borrower
    public function findForBorrower($loan_id, $borrower_id)
    {
        return $this->getRow('SELECT * FROM vw_loans WHERE loan_id = ? AND borrower_id = ?', [$loan_id, $borrower_id]);
    }

    // The latest loans (admin dashboard)
    public function recent($limit)
    {
        return $this->getRows('SELECT * FROM vw_loans ORDER BY loan_id DESC LIMIT ' . (int)$limit);
    }

    // The current (active) loans of one borrower, with a flag if a renewal request is waiting
    public function activeForBorrower($borrower_id)
    {
        return $this->getRows(
            "SELECT v.*,
                    DATEDIFF(v.due_date, CURDATE()) AS days_left,
                    (SELECT COUNT(*) FROM renewal_requests rr
                      WHERE rr.loan_id = v.loan_id AND rr.status = 'pending') AS pending_renewal
             FROM vw_loans v
             WHERE v.borrower_id = ? AND v.status = 'active'
             ORDER BY v.due_date ASC",
            [$borrower_id]
        );
    }

    // Loans that are overdue or due very soon (staff dashboard)
    public function needingAttention($due_soon_days, $limit)
    {
        return $this->getRows(
            "SELECT *, DATEDIFF(due_date, CURDATE()) AS days_left
             FROM vw_loans
             WHERE status = 'active' AND due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY due_date ASC
             LIMIT " . (int)$limit,
            [$due_soon_days]
        );
    }

    // Names of borrowers with overdue loans
    public function overdueBorrowerNames($limit)
    {
        return $this->getColumn(
            "SELECT DISTINCT borrower_name FROM vw_loans WHERE status = 'active' AND due_date < CURDATE() LIMIT " . (int)$limit
        );
    }

    // Every overdue loan, the longest overdue first
    public function allOverdue()
    {
        return $this->getRows(
            "SELECT v.*, DATEDIFF(CURDATE(), v.due_date) AS days_late
             FROM vw_loans v
             WHERE v.status = 'active' AND v.due_date < CURDATE()
             ORDER BY v.due_date ASC, v.loan_id ASC"
        );
    }

    // An overdue loan with what we need to send a reminder (false if it is no longer overdue)
    public function overdueForReminder($loan_id)
    {
        return $this->getRow(
            "SELECT l.loan_id, l.due_date, r.title,
                    b.first_name, b.last_name, b.email, b.user_id
             FROM loans l
             JOIN resources r ON r.resource_id = l.resource_id
             JOIN borrowers b ON b.borrower_id = l.borrower_id
             WHERE l.loan_id = ? AND l.status = 'active' AND l.due_date < CURDATE()",
            [$loan_id]
        );
    }

    // ---------- For the resource pages ----------
    // The active loan of a resource (false if nobody has it)
    public function activeForResource($resource_id)
    {
        return $this->getRow("SELECT loan_code, borrower_name FROM vw_loans WHERE resource_id = ? AND status = 'active'", [$resource_id]);
    }

    public function lastReturnDate($resource_id)
    {
        return $this->getValue("SELECT MAX(return_date) FROM loans WHERE resource_id = ? AND status = 'returned'", [$resource_id]);
    }

    // ---------- What one borrower did with one resource (borrower "Browse" page) ----------
    public function currentOfBorrowerForResource($borrower_id, $resource_id)
    {
        return $this->getRow("SELECT * FROM loans WHERE borrower_id = ? AND resource_id = ? AND status = 'active'", [$borrower_id, $resource_id]);
    }

    public function previousOfBorrowerForResource($borrower_id, $resource_id)
    {
        return $this->getRow(
            "SELECT * FROM loans WHERE borrower_id = ? AND resource_id = ? AND status = 'returned' ORDER BY return_date DESC, loan_id DESC LIMIT 1",
            [$borrower_id, $resource_id]
        );
    }

    // =====================================================
    // Searching (the tables with tabs, search box and filters)
    // =====================================================

    // Builds the "search" part of a WHERE. Typing "LN1031" or "1031" also finds that loan number.
    private function searchPart($search, $columns, &$params)
    {
        $like  = '%' . $search . '%';
        $parts = [];
        foreach ($columns as $column) {
            $parts[]  = $column . ' LIKE ?';
            $params[] = $like;
        }
        $digits = preg_replace('/\D/', '', $search);
        if ($digits !== '') {
            $parts[]  = 'v.loan_id = ?';
            $params[] = (int)$digits;
        }
        return '(' . implode(' OR ', $parts) . ')';
    }

    // Staff "Loans & Returns" list. $tab: all, active, returned or overdue. $sort: due_asc or due_desc
    public function listForStaff($tab, $search, $sort)
    {
        $where  = [];
        $params = [];

        if ($tab === 'active') {
            $where[] = "v.status = 'active'";
        } elseif ($tab === 'returned') {
            $where[] = "v.status = 'returned'";
        } elseif ($tab === 'overdue') {
            $where[] = "v.status = 'active' AND v.due_date < CURDATE()";
        }
        if ($search !== '') {
            $where[] = $this->searchPart($search, ['v.title', 'v.borrower_name', 'v.resource_code'], $params);
        }

        $sql = 'SELECT v.* FROM vw_loans v';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ($sort === 'due_desc') ? ' ORDER BY v.due_date DESC, v.loan_id DESC' : ' ORDER BY v.due_date ASC, v.loan_id ASC';

        return $this->getRows($sql, $params);
    }

    // Staff "Borrowing history". $tab: all, current or returned. $dates: all, 30 or 90
    // The LEFT JOIN adds the approved renewal (if the loan has one) so we can mention it.
    public function history($tab, $search, $borrower_id, $dates)
    {
        $where  = [];
        $params = [];

        if ($tab === 'current') {
            $where[] = "v.status = 'active'";
        } elseif ($tab === 'returned') {
            $where[] = "v.status = 'returned'";
        }
        if ($search !== '') {
            $where[] = $this->searchPart($search, ['v.title', 'v.borrower_name', 'v.resource_code'], $params);
        }
        if ($borrower_id > 0) {
            $where[]  = 'v.borrower_id = ?';
            $params[] = $borrower_id;
        }
        if ($dates !== 'all') {
            $where[]  = 'v.loan_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)';
            $params[] = (int)$dates;
        }

        $sql = "SELECT v.*, ren.renewal_id AS ren_id, ren.decided_at AS ren_date
                FROM vw_loans v
                LEFT JOIN renewal_requests ren ON ren.loan_id = v.loan_id AND ren.status = 'approved'";
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY v.loan_id DESC';

        return $this->getRows($sql, $params);
    }

    // Borrower "My Borrowing History" (only mine: WHERE borrower_id = ...)
    public function historyForBorrower($borrower_id, $tab, $search, $dates)
    {
        $where  = ['v.borrower_id = ?'];
        $params = [$borrower_id];

        if ($tab === 'current') {
            $where[] = "v.status = 'active'";
        } elseif ($tab === 'returned') {
            $where[] = "v.status = 'returned'";
        }
        if ($search !== '') {
            $where[] = $this->searchPart($search, ['v.title', 'v.resource_code'], $params);
        }
        if ($dates !== 'all') {
            $where[]  = 'v.loan_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)';
            $params[] = (int)$dates;
        }

        return $this->getRows('SELECT v.* FROM vw_loans v WHERE ' . implode(' AND ', $where) . ' ORDER BY v.loan_id DESC', $params);
    }

    // =====================================================
    // Register a loan (staff)
    // Returns ['errors' => [...], 'message' => '...']. No errors means it worked.
    // =====================================================
    public function register($borrower_id, $resource_id, $loan_date, $due_date, $staff_user_id)
    {
        $errors  = [];
        $message = '';

        try {
            // A transaction = "all steps succeed together, or none of them happen"
            $this->pdo->beginTransaction();

            $borrower = $this->getRow('SELECT * FROM borrowers WHERE borrower_id = ?', [$borrower_id]);

            // FOR UPDATE locks this resource row so nobody else can loan it at the same moment
            $resource = $this->getRow('SELECT * FROM resources WHERE resource_id = ? FOR UPDATE', [$resource_id]);

            if (!$borrower || $borrower['membership_status'] !== 'active') {
                $errors[] = 'This borrower is not an active member.';
            } elseif (!$resource) {
                $errors[] = 'This resource could not be found.';
            } elseif ($resource['status'] !== 'available') {
                $errors[] = 'This resource is no longer available.';
            }

            if (count($errors) === 0) {
                // Step 1: create the loan
                $this->run(
                    "INSERT INTO loans (borrower_id, resource_id, loan_date, original_due_date, due_date, status, registered_by)
                     VALUES (?, ?, ?, ?, ?, 'active', ?)",
                    [$borrower_id, $resource_id, $loan_date, $due_date, $due_date, $staff_user_id]
                );
                $loan_id = (int)$this->pdo->lastInsertId();

                // Step 2: mark the resource as on loan
                $this->run("UPDATE resources SET status = 'on_loan' WHERE resource_id = ?", [$resource_id]);

                // Step 3: tell the borrower (if they have a sign-in account)
                $this->notifications->add(
                    $borrower['user_id'], 'general',
                    $resource['title'] . ' was loaned to you',
                    'Due back on ' . format_date($due_date) . '. Loan LN' . str_pad($loan_id, 4, '0', STR_PAD_LEFT) . '.',
                    $loan_id
                );

                $this->pdo->commit();
                $message = 'Loan LN' . str_pad($loan_id, 4, '0', STR_PAD_LEFT) . ' registered: '
                         . $resource['title'] . ' to ' . $borrower['first_name'] . ' ' . $borrower['last_name'] . '.';
            } else {
                $this->pdo->rollBack();
            }
        } catch (PDOException $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $errors[] = 'The loan could not be saved. Please try again.';
        }

        return ['errors' => $errors, 'message' => $message];
    }

    // =====================================================
    // Process a return (staff)
    // Returns ['errors' => [...], 'message' => '...']. No errors means it worked.
    // =====================================================
    public function processReturn($loan_id, $return_date, $staff_user_id)
    {
        $errors  = [];
        $message = '';

        try {
            $this->pdo->beginTransaction();

            // Lock this loan so it cannot be returned twice at the same moment
            $loan = $this->getRow(
                'SELECT l.*, r.title, b.user_id AS borrower_user
                 FROM loans l
                 JOIN resources r ON r.resource_id = l.resource_id
                 JOIN borrowers b ON b.borrower_id = l.borrower_id
                 WHERE l.loan_id = ? FOR UPDATE',
                [$loan_id]
            );

            if (!$loan || $loan['status'] !== 'active') {
                $errors[] = 'No active loan was found. It may already have been returned.';
            } elseif ($return_date < $loan['loan_date']) {
                $errors[] = 'The return date cannot be before the loan date.';
            }

            if (count($errors) === 0) {
                // Step 1: the loan becomes Returned
                $this->run("UPDATE loans SET status = 'returned', return_date = ?, returned_to = ? WHERE loan_id = ?",
                           [$return_date, $staff_user_id, $loan_id]);

                // Step 2: the resource becomes Available again
                $this->run("UPDATE resources SET status = 'available' WHERE resource_id = ?", [$loan['resource_id']]);

                // Step 3: a waiting renewal request is no longer needed, so close it
                $this->run(
                    "UPDATE renewal_requests
                     SET status = 'rejected', decided_by = ?, decided_at = NOW(), decision_note = 'The resource was returned.'
                     WHERE loan_id = ? AND status = 'pending'",
                    [$staff_user_id, $loan_id]
                );

                // Step 4: tell the borrower
                $this->notifications->add(
                    $loan['borrower_user'], 'general',
                    'Return recorded: ' . $loan['title'],
                    'Thank you. We recorded your return on ' . format_date($return_date) . '.',
                    $loan_id
                );

                $this->pdo->commit();
                $message = 'Return recorded for ' . $loan['title'] . '. The resource is available again.';
            } else {
                $this->pdo->rollBack();
            }
        } catch (PDOException $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $errors[] = 'The return could not be saved. Please try again.';
        }

        return ['errors' => $errors, 'message' => $message];
    }
}
