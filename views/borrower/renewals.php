<div class="page-head">
    <div>
        <h1 class="page-title">My renewal requests</h1>
        <p class="page-sub">Follow the status of your requests and see which due dates have changed.</p>
    </div>
    <a href="<?= e(route('borrower/loans')) ?>" class="btn btn-soft"><i class="bi bi-arrow-left"></i> Back to current loans</a>
</div>

<div class="row g-3">

    <!-- My requests -->
    <div class="col-12 col-xl-7">
        <div class="card-box" style="height:auto">
            <h2 class="card-title-text">Your renewal requests</h2>
            <p class="card-sub"><?= e($_SESSION['full_name']) ?></p>

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all'])) ?>">All (<?= (int)$counts['total'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'pending'])) ?>">Pending (<?= (int)$counts['pending'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'approved' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'approved'])) ?>">Approved (<?= (int)$counts['approved'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'rejected' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'rejected'])) ?>">Rejected (<?= (int)$counts['rejected'] ?>)</a></li>
            </ul>

            <?php if (count($requests) === 0): ?>
                <p class="text-muted mb-0">You have no renewal requests here yet. You can ask for a renewal from
                    <a href="<?= e(route('borrower/loans')) ?>">My current loans</a>.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Request / resource</th><th>Requested due</th><th>Status</th><th>Decision</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $row): ?>
                            <?php $colour = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'red'][$row['status']]; ?>
                            <tr <?= $row['renewal_id'] == $selected_id ? 'style="background:#F8FAFC"' : '' ?>>
                                <td>
                                    <span class="row-title"><?= e($row['title']) ?></span>
                                    <span class="row-sub">RN<?= (int)$row['renewal_id'] ?> · LN<?= str_pad($row['loan_id'], 4, '0', STR_PAD_LEFT) ?> · <?= format_date($row['submitted_at']) ?></span>
                                    <a href="<?= e(self_url(['tab' => $tab, 'id' => (int)$row['renewal_id']])) ?>" style="font-size:0.78rem">View request</a>
                                </td>
                                <td><?= format_date($row['requested_due_date']) ?></td>
                                <td><?= badge(ucfirst($row['status']), $colour) ?></td>
                                <td><?= $row['status'] === 'pending' ? 'Awaiting review' : format_date($row['decided_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">Only requests for your own loans are shown.</p>
        </div>

        <div class="card-box mt-3" style="height:auto">
            <h2 class="card-title-text">What each status means</h2>
            <div class="row g-3 mt-1">
                <div class="col-md-4"><?= badge('Pending', 'amber') ?><div class="text-muted mt-1" style="font-size:0.78rem">Due date unchanged.</div></div>
                <div class="col-md-4"><?= badge('Approved', 'green') ?><div class="text-muted mt-1" style="font-size:0.78rem">Due date extended.</div></div>
                <div class="col-md-4"><?= badge('Rejected', 'red') ?><div class="text-muted mt-1" style="font-size:0.78rem">Due date unchanged.</div></div>
            </div>
        </div>
    </div>

    <!-- Selected request (read-only) -->
    <div class="col-12 col-xl-5">
        <?php if ($selected): ?>
            <?php $rn = 'RN' . $selected['renewal_id']; ?>
            <div class="card-box" style="height:auto">
                <h2 class="card-title-text">Submitted renewal request</h2>
                <p class="card-sub"><?= e($rn) ?> · <?= ucfirst($selected['status']) ?> · Read-only</p>

                <div class="kv"><span>Loan</span><span>LN<?= str_pad($selected['loan_id'], 4, '0', STR_PAD_LEFT) ?></span></div>
                <div class="kv"><span>Resource</span><span><?= e($selected['title']) ?></span></div>
                <div class="kv"><span>Borrowed</span><span><?= format_date($selected['loan_date']) ?></span></div>
                <div class="kv"><span>Due date when requested</span><span><?= format_date($selected['previous_due_date']) ?></span></div>
                <div class="kv"><span>Requested due date</span><span><?= format_date($selected['requested_due_date']) ?></span></div>
                <div class="kv"><span>Renewals used</span><span><?= (int)$selected['renewal_count'] ?> of <?= $max_renewals ?></span></div>

                <div class="fw-semibold mt-3" style="font-size:0.78rem;color:var(--navy)">Reason for renewal</div>
                <p class="mb-0" style="font-size:0.82rem"><?= e($selected['reason'] ?: 'No reason was given.') ?></p>

                <?php if ($selected['status'] === 'pending'): ?>
                    <div class="info-box info-warning">
                        <strong><i class="bi bi-hourglass-split"></i> Request submitted on <?= format_date($selected['submitted_at']) ?></strong>
                        Staff will review <?= e($rn) ?>. Until approved, your resource is still due on <?= format_date($selected['loan_due']) ?>.
                    </div>
                    <button class="btn btn-soft mt-3" disabled><i class="bi bi-check-lg"></i> Already submitted</button>
                <?php elseif ($selected['status'] === 'approved'): ?>
                    <div class="info-box" style="background:#F0FDF4;color:#166534;border:1px solid #BBF7D0">
                        <strong><i class="bi bi-check-circle"></i> Approved on <?= format_date($selected['decided_at']) ?></strong>
                        Your new due date is <?= format_date($selected['requested_due_date']) ?>.
                        <?= $selected['decision_note'] ? '<br>Staff note: ' . e($selected['decision_note']) : '' ?>
                    </div>
                <?php else: ?>
                    <div class="info-box info-danger">
                        <strong><i class="bi bi-x-circle"></i> Rejected on <?= format_date($selected['decided_at']) ?></strong>
                        Your due date did not change (<?= format_date($selected['previous_due_date']) ?>).
                        <?= $selected['decision_note'] ? '<br>Reason: ' . e($selected['decision_note']) : '' ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card-box" style="height:auto">
                <h2 class="card-title-text">Submitted renewal request</h2>
                <p class="text-muted mt-2 mb-0">Choose <strong>View request</strong> to see the details of one of your requests.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

