<?php
// The "resources" table (the books and other items in the catalogue)
class Resource extends Model
{
    public function find($resource_id)
    {
        return $this->getRow('SELECT * FROM resources WHERE resource_id = ?', [$resource_id]);
    }

    // ['total' => 10, 'available' => 7]
    public function counts()
    {
        $row = $this->getRow("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'available'), 0) AS available FROM resources");
        return ['total' => (int)$row['total'], 'available' => (int)$row['available']];
    }

    // All the shelf locations that are in use (for a drop-down)
    public function locations()
    {
        return $this->getColumn('SELECT DISTINCT location FROM resources ORDER BY location');
    }

    // ---------- Lists ----------
    // Staff catalogue. $tab: all, available or on_loan
    public function search($tab, $search, $category_id, $location)
    {
        $where  = [];
        $params = [];

        if ($tab !== 'all') {
            $where[]  = 'r.status = ?';
            $params[] = $tab;
        }
        if ($search !== '') {
            $where[] = '(r.title LIKE ? OR r.author LIKE ? OR r.resource_code LIKE ? OR r.isbn LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($category_id > 0) {
            $where[]  = 'r.category_id = ?';
            $params[] = $category_id;
        }
        if ($location !== '') {
            $where[]  = 'r.location = ?';
            $params[] = $location;
        }

        $sql = 'SELECT r.*, c.name AS category_name
                FROM resources r
                JOIN categories c ON c.category_id = r.category_id';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.resource_code';

        return $this->getRows($sql, $params);
    }

    // Borrower catalogue. Note: internal staff notes are NOT selected, so borrowers can never see them.
    public function browse($tab, $search, $category_id)
    {
        $where  = [];
        $params = [];

        if ($tab !== 'all') {
            $where[]  = 'r.status = ?';
            $params[] = $tab;
        }
        if ($search !== '') {
            $where[] = '(r.title LIKE ? OR r.author LIKE ? OR r.resource_code LIKE ? OR r.isbn LIKE ? OR r.description LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($category_id > 0) {
            $where[]  = 'r.category_id = ?';
            $params[] = $category_id;
        }

        $sql = 'SELECT r.resource_id, r.resource_code, r.title, r.author, r.isbn, r.location, r.description, r.status,
                       c.name AS category_name
                FROM resources r
                JOIN categories c ON c.category_id = r.category_id';
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.resource_code';

        return $this->getRows($sql, $params);
    }

    // Only AVAILABLE resources can be loaned (used on the "Register a loan" page)
    public function availableForLoan()
    {
        return $this->getRows(
            "SELECT r.resource_id, r.resource_code, r.title, r.author, r.location, c.name AS category_name
             FROM resources r
             JOIN categories c ON c.category_id = r.category_id
             WHERE r.status = 'available'
             ORDER BY r.resource_code"
        );
    }

    // ---------- ISBN ----------
    public function isbnTaken($isbn, $except_resource_id)
    {
        return $this->getValue('SELECT COUNT(*) FROM resources WHERE isbn = ? AND resource_id <> ?', [$isbn, $except_resource_id]) > 0;
    }

    // ---------- Add and edit ----------
    // $v holds: title, author, isbn, category_id, location, description, internal_notes
    // Empty isbn / description / notes are saved as NULL. Returns the new code, like 'R011'.
    public function create($v)
    {
        $code = $this->nextCode('resources', 'resource_code', 'R', 3);
        $this->run(
            'INSERT INTO resources (resource_code, title, author, isbn, category_id, location, description, internal_notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$code, $v['title'], $v['author'], $this->nullIfEmpty($v['isbn']), $v['category_id'],
             $v['location'], $this->nullIfEmpty($v['description']), $this->nullIfEmpty($v['internal_notes'])]
        );
        return $code;
    }

    public function update($resource_id, $v)
    {
        $this->run(
            'UPDATE resources
             SET title = ?, author = ?, isbn = ?, category_id = ?, location = ?, description = ?, internal_notes = ?
             WHERE resource_id = ?',
            [$v['title'], $v['author'], $this->nullIfEmpty($v['isbn']), $v['category_id'],
             $v['location'], $this->nullIfEmpty($v['description']), $this->nullIfEmpty($v['internal_notes']), $resource_id]
        );
    }

    private function nullIfEmpty($text)
    {
        return ($text === '') ? null : $text;
    }
}
