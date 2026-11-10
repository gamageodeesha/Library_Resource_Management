<?php
// Staff: the catalogue of resources (list, add, edit)
class StaffResourceController extends Controller
{
    // =====================================================
    // The list of resources
    // =====================================================
    public function index()
    {
        $this->requireRole('staff');

        $resources  = new Resource($this->pdo);
        $categories = new Category($this->pdo);

        // ---------- What did the user ask for? (tab, search, filters) ----------
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, ['all', 'available', 'on_loan'])) {
            $tab = 'all';
        }
        $search      = trim($_GET['q'] ?? '');
        $category_id = (int)($_GET['category'] ?? 0);
        $location    = trim($_GET['location'] ?? '');

        // ---------- Numbers for the tabs ----------
        $counts    = $resources->counts();
        $total     = $counts['total'];
        $available = $counts['available'];

        $this->render('staff/resources', [
            'page_title'  => 'Resources',
            'active_page' => 'resources',
            'tab'         => $tab,
            'search'      => $search,
            'category_id' => $category_id,
            'location'    => $location,
            'resources'   => $resources->search($tab, $search, $category_id, $location),
            'total'       => $total,
            'available'   => $available,
            'on_loan'     => $total - $available,
            'categories'  => $categories->all(),
            'locations'   => $resources->locations(),
            'has_filter'  => ($search !== '' || $category_id > 0 || $location !== ''),
        ]);
    }

    // =====================================================
    // Add or edit one resource
    //   route staff/resource_form          -> add a new resource
    //   route staff/resource_form&id=7     -> edit resource number 7
    // =====================================================
    public function form()
    {
        $this->requireRole('staff');

        $resources  = new Resource($this->pdo);
        $categories = new Category($this->pdo);
        $loans      = new Loan($this->pdo);

        // ---------- Add or edit? ----------
        $id       = (int)($_GET['id'] ?? 0);
        $editing  = ($id > 0);
        $resource = null;

        if ($editing) {
            $resource = $resources->find($id);
            if (!$resource) {
                set_flash('danger', 'That resource could not be found.');
                redirect('staff/resources');
            }
        }

        $category_list = $categories->all();

        // ---------- Starting values for the form ----------
        $fields = ['title', 'author', 'isbn', 'category_id', 'location', 'description', 'internal_notes'];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $editing ? (string)($resource[$field] ?? '') : '';
        }
        $errors = [];

        // ---------- The form was submitted ----------
        if ($this->isPost()) {

            if (!csrf_ok()) {
                $errors[] = 'Your session expired. Please try again.';
            }

            foreach ($fields as $field) {
                $values[$field] = trim($_POST[$field] ?? '');
            }

            // 1. Required fields
            if ($values['title'] === '')    $errors[] = 'Title is required.';
            if ($values['author'] === '')   $errors[] = 'Author is required.';
            if ($values['location'] === '') $errors[] = 'Shelf / location is required.';

            // 2. Category must be one from the list
            $category_ok = false;
            foreach ($category_list as $cat) {
                if ($cat['category_id'] == $values['category_id']) {
                    $category_ok = true;
                }
            }
            if (!$category_ok) $errors[] = 'Please choose a category.';

            // 3. ISBN (optional): remove spaces and dashes, then check the format and duplicates
            $isbn = str_replace(['-', ' '], '', $values['isbn']);
            if ($isbn !== '') {
                if (!preg_match('/^(\d{13}|\d{9}[\dXx])$/', $isbn)) {
                    $errors[] = 'ISBN must have 10 or 13 digits.';
                } elseif ($resources->isbnTaken($isbn, $id)) {
                    $errors[] = 'Another resource already uses this ISBN.';
                }
            }

            // 4. Everything is fine: save
            if (count($errors) === 0) {
                $data = $values;
                $data['isbn'] = $isbn;     // the cleaned-up ISBN

                if ($editing) {
                    $resources->update($id, $data);
                    set_flash('success', 'Resource ' . $resource['resource_code'] . ' was updated.');
                } else {
                    $code = $resources->create($data);
                    set_flash('success', 'Resource ' . $code . ' was added to the catalogue.');
                }
                redirect('staff/resources');
            }
        }

        // ---------- Extra information for the right-hand panel (edit mode only) ----------
        $current_loan  = null;
        $last_returned = null;
        if ($editing) {
            $current_loan  = $loans->activeForResource($id);
            $last_returned = $loans->lastReturnDate($id);
        }

        $this->render('staff/resource_form', [
            'page_title'    => 'Resources',
            'active_page'   => 'resources',
            'id'            => $id,
            'editing'       => $editing,
            'resource'      => $resource,
            'categories'    => $category_list,
            'values'        => $values,
            'errors'        => $errors,
            'current_loan'  => $current_loan,
            'last_returned' => $last_returned,
        ]);
    }
}
