<div class="page-head">
    <div>
        <h1 class="page-title">Manage loans &amp; returns</h1>
        <p class="page-sub">Track active loans and process returned resources at the library desk.</p>
    </div>
    <a href="<?= e(route('staff/loan_register')) ?>" class="btn btn-navy"><i class="bi bi-plus-lg"></i> Register a loan</a>
</div>

<div class="row g-3">

    <!-- Loan list -->
    <div class="col-12 col-xl-7">
        <div class="card-box">

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all'])) ?>">All (<?= (int)$counts['total'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'active' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'active'])) ?>">Active (<?= (int)$counts['active'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'returned' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'returned'])) ?>">Returned (<?= (int)$counts['returned'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'overdue' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'overdue'])) ?>">Overdue (<?= (int)$counts['overdue'] ?>)</a></li>
            </ul>

            <form method="get" class="row g-2 mb-3">
                <?= route_field() ?>
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <div class="col-8">
                    <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search resource, borrower or loan ID...">
                </div>
                <div class="col-4">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="due_asc"  <?= $sort === 'due_asc' ? 'selected' : '' ?>>Due date ↑</option>
                        <option value="due_desc" <?= $sort === 'due_desc' ? 'selected' : '' ?>>Due date ↓</option>
                    </select>
                </div>
            </form>

            <?php if (count($loans) === 0): ?>
                <p class="text-muted mb-0">No loans found.
                    <?php if ($search !== ''): ?><a href="<?= e(self_url(['tab' => $tab])) ?>">Clear search</a><?php endif; ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Resource / borrower</th><th>Due date</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($loans as $row): ?>
                            <tr <?= $row['loan_id'] == $selected_id ? 'style="background:#F8FAFC"' : '' ?>>
                                <td>
                                    <span class="row-title"><?= e($row['title']) ?></span>
                                    <span class="row-sub"><?= e($row['loan_code']) ?> · <?= e($row['borrower_name']) ?></span>
                                </td>
                                <td><?= format_date($row['due_date']) ?></td>
                                <td><?= loan_badge($row) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'active'): ?>
                                        <a href="<?= e(self_url(['tab' => $tab, 'loan' => (int)$row['loan_id'], 'q' => $search, 'sort' => $sort])) ?>">Return</a>
                                    <?php else: ?>
                                        <a href="<?= e(self_url(['tab' => $tab, 'loan' => (int)$row['loan_id'], 'q' => $search, 'sort' => $sort])) ?>">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">
                    Showing <?= count($loans) ?> loan<?= count($loans) === 1 ? '' : 's' ?> · <?= (int)$counts['active'] ?> active · <?= (int)$counts['overdue'] ?> overdue
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Process return panel -->
    <div class="col-12 col-xl-5">
        <?php if (count($return_errors) > 0): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($return_errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($selected && $selected['status'] === 'active'): ?>
            <div class="card-box" style="height:auto">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="card-title-text">Process return</h2>
                        <p class="card-sub">Selected loan · <?= e($selected['loan_code']) ?></p>
                    </div>
                    <?= loan_badge($selected) ?>
                </div>

                <h3 class="card-title-text mb-2"><?= e($selected['title']) ?></h3>
                <div class="kv"><span>Resource</span><span><?= e($selected['resource_code']) ?> · Shelf <?= e($selected['location']) ?></span></div>
                <div class="kv"><span>Borrower</span><span><?= e($selected['borrower_name']) ?> · <?= e($selected['borrower_code']) ?></span></div>
                <div class="kv"><span>Loan date</span><span><?= format_date($selected['loan_date']) ?></span></div>
                <div class="kv"><span>Due date</span><span><?= format_date($selected['due_date']) ?></span></div>

                <form method="post" action="<?= e(self_url(['tab' => $tab])) ?>"
                      onsubmit="return confirm('Confirm that this resource has been returned?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="return">
                    <input type="hidden" name="loan_id" value="<?= (int)$selected['loan_id'] ?>">

                    <label for="return_date" class="form-label mt-3">Return date *</label>
                    <input type="date" class="form-control" id="return_date" name="return_date"
                           value="<?= e($return_date_value) ?>"
                           min="<?= e($selected['loan_date']) ?>" max="<?= e($today) ?>" required>

                    <?php if ($selected['display_status'] === 'overdue'): ?>
                        <div class="info-box info-warning">
                            <strong><i class="bi bi-exclamation-circle"></i> This loan is <?= (int)$selected['days_overdue'] ?> day(s) overdue today</strong>
                            The overdue period is recorded in the loan history when this return is confirmed.
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <div class="fw-semibold" style="font-size:0.8rem;color:var(--navy)">Outcome on confirmation</div>
                        <div class="kv"><span>Loan status</span><span>Active → Returned</span></div>
                        <div class="kv"><span>Resource status</span><span>On loan → Available</span></div>
                    </div>

                    <button type="submit" class="btn btn-navy mt-2"><i class="bi bi-check-lg"></i> Confirm return</button>
                    <a href="<?= e(self_url(['tab' => $tab])) ?>" class="btn btn-soft mt-2">Cancel</a>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.72rem">Preview only. <?= e($selected['loan_code']) ?> remains active until the return is confirmed.</p>
                </form>
            </div>

        <?php elseif ($selected): ?>
            <!-- A returned loan: read-only details -->
            <div class="card-box" style="height:auto">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="card-title-text">Loan details</h2>
                        <p class="card-sub"><?= e($selected['loan_code']) ?></p>
                    </div>
                    <?= loan_badge($selected) ?>
                </div>
                <h3 class="card-title-text mb-2"><?= e($selected['title']) ?></h3>
                <div class="kv"><span>Borrower</span><span><?= e($selected['borrower_name']) ?></span></div>
                <div class="kv"><span>Loan date</span><span><?= format_date($selected['loan_date']) ?></span></div>
                <div class="kv"><span>Due date</span><span><?= format_date($selected['due_date']) ?></span></div>
                <div class="kv"><span>Returned</span><span><?= format_date($selected['return_date']) ?></span></div>
                <div class="kv"><span>Returned late</span><span><?= (int)$selected['days_overdue'] > 0 ? (int)$selected['days_overdue'] . ' day(s)' : 'No' ?></span></div>
                <div class="kv"><span>Renewals used</span><span><?= (int)$selected['renewal_count'] ?> of 1</span></div>
            </div>

        <?php else: ?>
            <div class="card-box" style="height:auto">
                <h2 class="card-title-text">Process return</h2>
                <p class="text-muted mt-2 mb-0">Choose <strong>Return</strong> next to an active loan to review it and record the return.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

