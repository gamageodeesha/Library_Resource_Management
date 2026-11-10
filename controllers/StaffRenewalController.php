<?php
// Staff: review renewal requests (approve or reject)
class StaffRenewalController extends Controller
{
    public function index()
    {
        $this->requireRole('staff');

        $renewals = new Renewal($this->pdo);
        $settings = new Setting($this->pdo);

        $max_renewals = (int)$settings->get('max_renewals_per_loan', 1);

        // ---------- What did the user ask for? ----------
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, ['all', 'pending', 'approved', 'rejected'])) {
            $tab = 'all';
        }
        $search = trim($_GET['q'] ?? '');
        $dates  = $_GET['dates'] ?? 'all';
        if (!in_array($dates, ['all', '7', '30'])) {
            $dates = 'all';
        }
        $selected_id     = (int)($_GET['id'] ?? 0);
        $decision_errors = [];
        $posted_note     = '';

        // =====================================================
        // APPROVE OR REJECT (the form on the right was submitted)
        // =====================================================
        if ($this->isPost()) {

            $selected_id  = (int)($_POST['request_id'] ?? 0);
            $decision     = $_POST['decision'] ?? '';
            $posted_note  = trim($_POST['decision_note'] ?? '');
            $done_message = '';

            if (!csrf_ok())                                  $decision_errors[] = 'Your session expired. Please try again.';
            if (!in_array($decision, ['approve', 'reject'])) $decision_errors[] = 'Please choose Approve or Reject.';
            if ($decision === 'reject' && $posted_note === '') {
                $decision_errors[] = 'Please add a decision note explaining why the request is rejected.';
            }
            if (strlen($posted_note) > 500) {
                $decision_errors[] = 'The decision note must be 500 characters or fewer.';
            }

            if (count($decision_errors) === 0) {
                // The model checks the rules and saves everything together
                $result          = $renewals->decide($selected_id, $decision, $posted_note, $_SESSION['user_id'], $max_renewals);
                $decision_errors = $result['errors'];
                $done_message    = $result['message'];
            }

            if (count($decision_errors) === 0 && $done_message !== '') {
                set_flash('success', $done_message);
                redirect('staff/renewals', ['tab' => $tab]);
            }
        }

        // ---------- The request shown in the right-hand panel ----------
        $selected = null;
        if ($selected_id > 0) {
            $selected = $renewals->findForStaff($selected_id);
        }

        // Can the selected request still be approved? ('' means yes)
        $problem = '';
        if ($selected && $selected['status'] === 'pending') {
            if ($selected['loan_status'] !== 'active') {
                $problem = 'This loan is no longer active.';
            } elseif ($selected['renewal_count'] >= $max_renewals) {
                $problem = 'This loan has already used its renewal.';
            } elseif ($selected['requested_due_date'] <= $selected['loan_due']) {
                $problem = 'The requested date is not after the current due date.';
            }
        }

        $this->render('staff/renewals', [
            'page_title'      => 'Renewal Requests',
            'active_page'     => 'renewals',
            'max_renewals'    => $max_renewals,
            'tab'             => $tab,
            'search'          => $search,
            'dates'           => $dates,
            'selected_id'     => $selected_id,
            'decision_errors' => $decision_errors,
            'posted_note'     => $posted_note,
            'requests'        => $renewals->listForStaff($tab, $search, $dates),
            'counts'          => $renewals->counts(),
            'selected'        => $selected,
            'problem'         => $problem,
        ]);
    }
}
