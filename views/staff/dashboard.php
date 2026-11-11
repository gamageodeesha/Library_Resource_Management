<div class="page-head">
    <div>
        <h1 class="page-title"><?= greeting() ?>, <?= e(first_name($_SESSION['full_name'])) ?></h1>
        <p class="page-sub">Your lending desk overview. Let's keep knowledge moving.</p>
    </div>
    <a href="<?= e(route('staff/loan_register')) ?>" class="btn btn-navy"><i class="bi bi-plus-lg"></i> Register a loan</a>
</div>

<!-- Four summary cards -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <a class="stat-link" href="<?= e(route('staff/resources')) ?>">
            <div class="card-box">
                <div class="stat-label"><span>Resources</span><i class="bi bi-collection"></i></div>
                <div class="stat-number"><?= $total_resources ?></div>
                <div class="stat-note"><?= $available ?> available · <?= $on_loan ?> on loan</div>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <a class="stat-link" href="<?= e(route('staff/borrowers')) ?>">
            <div class="card-box">
                <div class="stat-label"><span>Borrowers</span><i class="bi bi-people"></i></div>
                <div class="stat-number"><?= $total_borrowers ?></div>
                <div class="stat-note"><?= $with_loans ?> with active loans</div>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <a class="stat-link" href="<?= e(route('staff/loans')) ?>">
            <div class="card-box">
                <div class="stat-label"><span>Active loans</span><i class="bi bi-book"></i></div>
                <div class="stat-number"><?= $active_loans ?></div>
                <div class="stat-note">Next due: <?= format_date($next_due) ?></div>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <a class="stat-link" href="<?= e(route('staff/overdue')) ?>">
            <div class="card-box">
                <div class="stat-label"><span>Overdue loans</span><i class="bi bi-clock text-danger"></i></div>
                <div class="stat-number"><?= $overdue_count ?></div>
                <?php if ($overdue_count > 0): ?>
                    <div class="stat-note red">Oldest: <?= $oldest_overdue ?> days overdue</div>
                <?php else: ?>
                    <div class="stat-note">Nothing overdue</div>
                <?php endif; ?>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-3">

    <!-- Loans needing attention -->
    <div class="col-12 col-xl-8">
        <div class="card-box">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title-text">Loans needing attention</h2>
                    <p class="card-sub">Overdue and upcoming returns</p>
                </div>
                <a href="<?= e(route('staff/loans')) ?>" style="font-size:0.8rem">View all loans →</a>
            </div>

            <?php if (count($attention) === 0): ?>
                <p class="text-muted mb-0">No loans need attention right now.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Resource / loan</th><th>Borrower</th><th>Due date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($attention as $loan): ?>
                            <tr>
                                <td>
                                    <span class="row-title"><?= e($loan['title']) ?></span>
                                    <span class="row-sub"><?= e($loan['loan_code']) ?></span>
                                </td>
                                <td><?= e($loan['borrower_name']) ?></td>
                                <td><?= format_date($loan['due_date']) ?></td>
                                <td>
                                    <?php
                                    if ($loan['display_status'] === 'overdue') {
                                        echo badge($loan['days_overdue'] . 'd overdue', 'red');
                                    } elseif ($loan['days_left'] == 0) {
                                        echo badge('Due today', 'amber');
                                    } else {
                                        echo badge('Due in ' . $loan['days_left'] . ' days', 'amber');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($overdue_count > 0): ?>
                <div class="info-box info-warning">
                    <strong><i class="bi bi-exclamation-circle"></i> <?= $overdue_count ?> overdue loan<?= $overdue_count > 1 ? 's' : '' ?> require follow-up</strong>
                    Contact <?= e(implode(', ', $overdue_names)) ?> or process a return when their resources arrive.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending renewal -->
    <div class="col-12 col-xl-4">
        <div class="card-box">
            <h2 class="card-title-text">Pending renewal</h2>
            <p class="card-sub">
                <?= $pending_total ?> request<?= $pending_total === 1 ? '' : 's' ?> awaiting your review
            </p>

            <?php if ($renewal): ?>
                <div class="mb-2"><?= badge('RN' . $renewal['renewal_id'] . ' · Pending', 'amber') ?></div>
                <h3 class="card-title-text mb-2"><?= e($renewal['title']) ?></h3>
                <div class="kv"><span>Borrower</span><span><?= e($renewal['borrower_name']) ?></span></div>
                <div class="kv"><span>Current due</span><span><?= format_date($renewal['current_due']) ?></span></div>
                <div class="kv"><span>Requested due</span><span><?= format_date($renewal['requested_due_date']) ?></span></div>
                <div class="kv"><span>Submitted</span><span><?= format_date($renewal['submitted_at']) ?></span></div>
                <a href="<?= e(route('staff/renewals', ['id' => (int)$renewal['renewal_id']])) ?>" class="btn btn-navy mt-3">
                    <i class="bi bi-arrow-right"></i> Review request
                </a>
            <?php else: ?>
                <p class="text-muted mb-0">No pending renewal requests.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick actions -->
<div class="card-box">
    <h2 class="card-title-text">Quick actions</h2>
    <p class="card-sub">Common tasks at the library desk</p>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-soft quick-btn" href="<?= e(route('staff/loans')) ?>"><i class="bi bi-arrow-return-left"></i> Process a return</a>
        <a class="btn btn-soft quick-btn" href="<?= e(route('staff/resource_form')) ?>"><i class="bi bi-plus-square"></i> Add resource</a>
        <a class="btn btn-soft quick-btn" href="<?= e(route('staff/borrower_form')) ?>"><i class="bi bi-person-plus"></i> Register borrower</a>
        <a class="btn btn-soft quick-btn" href="<?= e(route('staff/history')) ?>"><i class="bi bi-clock-history"></i> View borrowing history</a>
    </div>
</div>

