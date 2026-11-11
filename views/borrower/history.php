<div class="page-head">
    <div>
        <h1 class="page-title">My borrowing history &amp; notifications</h1>
        <p class="page-sub">Your lending record and library updates, together in one place.</p>
    </div>
    <a href="<?= e(route('borrower/browse')) ?>" class="btn btn-soft"><i class="bi bi-search"></i> Browse resources</a>
</div>

<div class="row g-3">

    <!-- History -->
    <div class="col-12 col-xl-8">
        <div class="card-box" style="height:auto">

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all'])) ?>">All loans (<?= (int)$counts['total'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'current' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'current'])) ?>">Current (<?= (int)$counts['active'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'returned' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'returned'])) ?>">Returned (<?= (int)$counts['returned'] ?>)</a></li>
            </ul>

            <form method="get" class="row g-2 mb-3">
                <?= route_field() ?>
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <div class="col-8">
                    <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search your resources or loan ID...">
                </div>
                <div class="col-4">
                    <select name="dates" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $dates === 'all' ? 'selected' : '' ?>>All dates</option>
                        <option value="30"  <?= $dates === '30' ? 'selected' : '' ?>>Last 30 days</option>
                        <option value="90"  <?= $dates === '90' ? 'selected' : '' ?>>Last 90 days</option>
                    </select>
                </div>
            </form>

            <?php if (count($loans) === 0): ?>
                <p class="text-muted mb-0">No loans found.
                    <?php if ($search !== '' || $dates !== 'all'): ?><a href="<?= e(self_url(['tab' => $tab])) ?>">Clear filters</a><?php endif; ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Resource / loan</th><th>Borrowed</th><th>Due date</th><th>Returned</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($loans as $row): ?>
                            <tr <?= $row['loan_id'] == $selected_id ? 'style="background:#F8FAFC"' : '' ?>>
                                <td>
                                    <span class="row-title"><?= e($row['title']) ?></span>
                                    <span class="row-sub"><?= e($row['loan_code']) ?></span>
                                </td>
                                <td><?= format_date($row['loan_date']) ?></td>
                                <td><?= format_date($row['due_date']) ?></td>
                                <td><?= format_date($row['return_date']) ?></td>
                                <td><?= loan_badge($row) ?></td>
                                <td><a href="<?= e(self_url(['tab' => $tab, 'q' => $search, 'dates' => $dates, 'loan' => (int)$row['loan_id']])) ?>">Details</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">
                <?= (int)$counts['total'] ?> loan<?= (int)$counts['total'] === 1 ? '' : 's' ?> ·
                <?= (int)$counts['active'] ?> current · <?= (int)$counts['overdue'] ?> overdue · <?= (int)$counts['returned'] ?> returned
            </p>
        </div>

        <!-- The story of one loan -->
        <div class="card-box mt-3" style="height:auto">
            <?php if ($selected): ?>
                <h2 class="card-title-text">Selected history · <?= e($selected['title']) ?></h2>
                <p class="card-sub"><?= e($selected['loan_code']) ?> · <?= $selected['status'] === 'returned' ? 'Returned ' . format_date($selected['return_date']) : 'Currently on loan' ?></p>

                <p style="font-size:0.85rem" class="mb-2">
                    Borrowed <?= format_date($selected['loan_date']) ?> · due <?= format_date($selected['original_due_date']) ?>.
                    <?php if ($selected['status'] === 'returned'): ?>
                        You returned the resource <?= e(return_timing($selected['due_date'], $selected['return_date'])) ?>.
                    <?php elseif ($selected['display_status'] === 'overdue'): ?>
                        It is now <?= (int)$selected['days_overdue'] ?> day(s) overdue. Please return it to the library desk.
                    <?php else: ?>
                        It is due back on <?= format_date($selected['due_date']) ?>.
                    <?php endif; ?>
                </p>

                <?php if (count($requests) === 0): ?>
                    <p class="text-muted mb-0" style="font-size:0.82rem">No renewal was requested for this loan.</p>
                <?php endif; ?>
                <?php foreach ($requests as $rq): ?>
                    <p style="font-size:0.85rem" class="mb-1">
                        <?php if ($rq['status'] === 'pending'): ?>
                            Renewal RN<?= (int)$rq['renewal_id'] ?> (requested <?= format_date($rq['requested_due_date']) ?>) is awaiting staff review.
                        <?php elseif ($rq['status'] === 'approved'): ?>
                            Renewal RN<?= (int)$rq['renewal_id'] ?> was approved on <?= format_date($rq['decided_at']) ?>. The due date moved from <?= format_date($rq['previous_due_date']) ?> to <?= format_date($rq['requested_due_date']) ?>.
                        <?php else: ?>
                            Renewal RN<?= (int)$rq['renewal_id'] ?> was rejected on <?= format_date($rq['decided_at']) ?>. The requested date was <?= format_date($rq['requested_due_date']) ?>; the original due date stayed <?= format_date($rq['previous_due_date']) ?>.
                            <?= $rq['decision_note'] ? 'Reason: ' . e($rq['decision_note']) : '' ?>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            <?php else: ?>
                <h2 class="card-title-text">Selected history</h2>
                <p class="text-muted mt-2 mb-0">Choose <strong>Details</strong> on a loan to see its full story, including any renewals.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications -->
    <div class="col-12 col-xl-4">
        <div class="card-box" style="height:auto">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title-text">Notifications</h2>
                    <p class="card-sub">Updates for <?= e($_SESSION['full_name']) ?> only</p>
                </div>
                <?php if ($unread > 0): ?>
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-soft btn-sm">Mark all as read</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (count($notes) === 0): ?>
                <p class="text-muted mb-0">You have no notifications yet.</p>
            <?php else: ?>
                <?php foreach ($notes as $note) { partial('notification', ['note' => $note, 'role' => 'borrower']); } ?>
            <?php endif; ?>

            <a href="<?= e(route('notifications')) ?>" class="d-inline-block mt-2" style="font-size:0.8rem">View all notifications →</a>
            <p class="text-muted mt-2 mb-0" style="font-size:0.7rem">Account · <?= e($account_email) ?></p>
        </div>
    </div>
</div>

