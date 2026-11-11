<div class="page-head">
    <div>
        <h1 class="page-title">Manage borrowers</h1>
        <p class="page-sub">Find borrower records, current loans and previous borrowing activity.</p>
    </div>
    <a href="<?= e(route('staff/borrower_form')) ?>" class="btn btn-navy"><i class="bi bi-person-plus"></i> Register borrower</a>
</div>

<div class="row g-3">

    <!-- Directory -->
    <div class="col-12 col-xl-7">
        <div class="card-box">
            <h2 class="card-title-text">Borrower directory</h2>
            <p class="card-sub"><?= $total_borrowers ?> registered borrowers</p>

            <form method="get" class="row g-2 mb-3">
                <?= route_field() ?>
                <div class="col-8">
                    <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search name, email or borrower ID...">
                </div>
                <div class="col-4">
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="all"        <?= $filter === 'all' ? 'selected' : '' ?>>All borrowers</option>
                        <option value="active"     <?= $filter === 'active' ? 'selected' : '' ?>>Active members</option>
                        <option value="inactive"   <?= $filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="with_loans" <?= $filter === 'with_loans' ? 'selected' : '' ?>>With active loans</option>
                    </select>
                </div>
            </form>

            <?php if (count($borrowers) === 0): ?>
                <p class="text-muted mb-0">No borrowers match your search. <a href="<?= e(route('staff/borrowers')) ?>">Clear filters</a></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Borrower</th><th>Status</th><th>Active loans</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($borrowers as $b): ?>
                            <tr <?= $b['borrower_id'] == $selected_id ? 'style="background:#F8FAFC"' : '' ?>>
                                <td>
                                    <span class="row-title"><?= e($b['first_name'] . ' ' . $b['last_name']) ?> · <?= e($b['borrower_code']) ?></span>
                                    <span class="row-sub"><?= e($b['email']) ?></span>
                                </td>
                                <td><?= membership_badge($b['membership_status']) ?></td>
                                <td><?= badge((string)$b['active_loans'], 'blue') ?></td>
                                <td>
                                    <a href="<?= e(self_url(['id' => (int)$b['borrower_id'], 'q' => $search, 'filter' => $filter])) ?>">View</a>
                                    &nbsp;<a href="<?= e(route('staff/borrower_form', ['id' => (int)$b['borrower_id']])) ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">Showing <?= count($borrowers) ?> of <?= $total_borrowers ?> borrowers</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Selected borrower -->
    <div class="col-12 col-xl-5">
        <?php if ($selected): ?>
            <div class="card-box mb-3" style="height:auto">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="card-title-text"><?= e($selected['first_name'] . ' ' . $selected['last_name']) ?></h2>
                        <p class="card-sub"><?= e($selected['borrower_code']) ?> · Borrower details</p>
                    </div>
                    <a href="<?= e(route('staff/borrower_form', ['id' => (int)$selected['borrower_id']])) ?>" style="font-size:0.8rem">Edit</a>
                </div>
                <div class="kv"><span>Email</span><span><?= e($selected['email']) ?></span></div>
                <div class="kv"><span>Phone</span><span><?= e($selected['phone'] ?: '—') ?></span></div>
                <div class="kv"><span>Membership</span><span><?= e(ucfirst($selected['membership_status'])) ?></span></div>
                <div class="kv"><span>Current loans</span><span><?= count($current_loans) ?></span></div>
                <div class="kv"><span>Overdue</span><span><?= $overdue ?></span></div>
                <div class="kv"><span>Returned</span><span><?= $returned ?></span></div>
                <div class="kv"><span>Pending renewal</span><span><?= $pending ?></span></div>
                <a href="<?= e(route('staff/loan_register', ['borrower_id' => (int)$selected['borrower_id']])) ?>" class="btn btn-navy mt-3">
                    <i class="bi bi-plus-lg"></i> Register a loan
                </a>
            </div>

            <div class="card-box" style="height:auto">
                <h2 class="card-title-text">Current loans</h2>
                <?php if (count($current_loans) === 0): ?>
                    <p class="text-muted mt-2 mb-0">No active loans.</p>
                <?php else: ?>
                    <?php foreach ($current_loans as $loan): ?>
                        <div class="mt-3">
                            <span class="row-title"><?= e($loan['title']) ?></span>
                            <span class="row-sub"><?= e($loan['loan_code']) ?> · Due <?= format_date($loan['due_date']) ?></span>
                            <div class="mt-1">
                                <?= loan_badge($loan) ?>
                                <?php if ($loan['pending_renewal'] > 0): ?>
                                    <?= badge('Renewal pending', 'amber') ?>
                                <?php elseif ($loan['renewal_count'] >= 1): ?>
                                    <?= badge('Renewed once', 'gray') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= e(route('staff/history', ['borrower_id' => (int)$selected['borrower_id']])) ?>" class="d-inline-block mt-3" style="font-size:0.8rem">View full borrowing history →</a>
            </div>
        <?php else: ?>
            <div class="card-box"><p class="text-muted mb-0">Select a borrower to see their details.</p></div>
        <?php endif; ?>
    </div>
</div>

<p class="text-muted mt-3" style="font-size:0.75rem">Borrower records and sign-in permissions are separate. Ask an administrator to change account roles.</p>

