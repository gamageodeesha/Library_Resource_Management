<div class="page-head">
    <div>
        <h1 class="page-title">Manage renewal requests</h1>
        <p class="page-sub">Review borrower requests. Each loan can be renewed a maximum of <?= $max_renewals === 1 ? 'once' : $max_renewals . ' times' ?>.</p>
    </div>
    <?php if ((int)$counts['pending'] > 0): ?>
        <div><?= badge((int)$counts['pending'] . ' pending review', 'amber') ?></div>
    <?php endif; ?>
</div>

<div class="row g-3">

    <!-- Request list -->
    <div class="col-12 col-xl-7">
        <div class="card-box">
            <h2 class="card-title-text">Renewal requests</h2>
            <p class="card-sub"><?= (int)$counts['total'] ?> request<?= (int)$counts['total'] === 1 ? '' : 's' ?> in total</p>

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all'])) ?>">All (<?= (int)$counts['total'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'pending'])) ?>">Pending (<?= (int)$counts['pending'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'approved' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'approved'])) ?>">Approved (<?= (int)$counts['approved'] ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'rejected' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'rejected'])) ?>">Rejected (<?= (int)$counts['rejected'] ?>)</a></li>
            </ul>

            <form method="get" class="row g-2 mb-3">
                <?= route_field() ?>
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <div class="col-8">
                    <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search request, borrower or title...">
                </div>
                <div class="col-4">
                    <select name="dates" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $dates === 'all' ? 'selected' : '' ?>>All dates</option>
                        <option value="7"   <?= $dates === '7' ? 'selected' : '' ?>>Last 7 days</option>
                        <option value="30"  <?= $dates === '30' ? 'selected' : '' ?>>Last 30 days</option>
                    </select>
                </div>
            </form>

            <?php if (count($requests) === 0): ?>
                <p class="text-muted mb-0">No renewal requests found.
                    <?php if ($search !== '' || $dates !== 'all'): ?><a href="<?= e(self_url(['tab' => $tab])) ?>">Clear filters</a><?php endif; ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Request / resource</th><th>Submitted</th><th>Requested due</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $row): ?>
                            <?php
                            $colour = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'red'][$row['status']];
                            $link   = self_url(['tab' => $tab, 'id' => (int)$row['renewal_id']]);
                            ?>
                            <tr <?= $row['renewal_id'] == $selected_id ? 'style="background:#F8FAFC"' : '' ?>>
                                <td>
                                    <span class="row-title"><?= e($row['title']) ?></span>
                                    <span class="row-sub">RN<?= (int)$row['renewal_id'] ?> · LN<?= str_pad($row['loan_id'], 4, '0', STR_PAD_LEFT) ?> · <?= e($row['borrower_name']) ?></span>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <a href="<?= e($link) ?>" style="font-size:0.78rem">Review request</a>
                                    <?php else: ?>
                                        <span class="row-sub"><?= ucfirst($row['status']) ?> <?= format_date($row['decided_at']) ?> · <a href="<?= e($link) ?>">View</a></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_date($row['submitted_at']) ?></td>
                                <td><?= format_date($row['requested_due_date']) ?></td>
                                <td><?= badge(ucfirst($row['status']), $colour) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">Showing <?= count($requests) ?> of <?= (int)$counts['total'] ?> renewal requests</p>
            <?php endif; ?>
        </div>

        <div class="info-box info-blue">
            <strong><i class="bi bi-info-circle"></i> Renewal rules</strong>
            Approval updates the due date and uses the loan's single renewal. Rejection leaves the existing due date unchanged. Pending requests do not extend a loan.
        </div>
    </div>

    <!-- Review panel -->
    <div class="col-12 col-xl-5">

        <?php if (count($decision_errors) > 0): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($decision_errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($selected): ?>
            <?php
            $rn          = 'RN' . $selected['renewal_id'];
            $is_pending  = ($selected['status'] === 'pending');
            $colour      = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'red'][$selected['status']];
            ?>
            <div class="card-box" style="height:auto">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="card-title-text">Review <?= e($rn) ?></h2>
                        <p class="card-sub"><?= ucfirst($selected['status']) ?> · Submitted <?= format_date($selected['submitted_at']) ?></p>
                    </div>
                    <?= badge(ucfirst($selected['status']), $colour) ?>
                </div>

                <h3 class="card-title-text mb-2"><?= e($selected['title']) ?></h3>
                <div class="kv"><span>Borrower</span><span><?= e($selected['borrower_name']) ?> · <?= e($selected['borrower_code']) ?></span></div>
                <div class="kv"><span>Loan</span><span>LN<?= str_pad($selected['loan_id'], 4, '0', STR_PAD_LEFT) ?> · <?= e($selected['resource_code']) ?></span></div>
                <div class="kv"><span>Borrowed</span><span><?= format_date($selected['loan_date']) ?></span></div>
                <div class="kv"><span>Current due</span><span><?= format_date($selected['loan_due']) ?></span></div>
                <div class="kv"><span>Requested due</span><span><?= format_date($selected['requested_due_date']) ?></span></div>
                <div class="kv"><span>Renewals used</span><span><?= (int)$selected['renewal_count'] ?> of <?= $max_renewals ?></span></div>

                <div class="fw-semibold mt-3" style="font-size:0.78rem;color:var(--navy)">Borrower's reason</div>
                <p class="mb-0" style="font-size:0.82rem"><?= e($selected['reason'] ?: 'No reason was given.') ?></p>

                <?php if ($is_pending): ?>
                    <form method="post" action="<?= e(self_url(['tab' => $tab])) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int)$selected['renewal_id'] ?>">

                        <label for="decision_note" class="form-label mt-3">Decision note</label>
                        <textarea class="form-control" id="decision_note" name="decision_note" rows="3" maxlength="500"
                                  placeholder="Add a note for the borrower (required if you reject)..."><?= e($posted_note) ?></textarea>

                        <?php if ($problem === ''): ?>
                            <div class="info-box" style="background:#F0FDF4;color:#166534;border:1px solid #BBF7D0">
                                <strong><i class="bi bi-check-circle"></i> Eligible for one renewal</strong>
                                Approve: due date becomes <?= format_date($selected['requested_due_date']) ?>. Reject: due date stays <?= format_date($selected['loan_due']) ?>.
                            </div>
                        <?php else: ?>
                            <div class="info-box info-warning">
                                <strong><i class="bi bi-exclamation-circle"></i> Cannot be approved</strong>
                                <?= e($problem) ?> You can still reject it.
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" name="decision" value="approve" class="btn btn-navy"
                                    <?= $problem !== '' ? 'disabled' : '' ?>
                                    onclick="return confirm('Approve this renewal and change the due date?');">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                            <button type="submit" name="decision" value="reject" class="btn btn-soft text-danger"
                                    onclick="return confirm('Reject this renewal request?');">
                                Reject
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="info-box <?= $selected['status'] === 'approved' ? 'info-blue' : 'info-danger' ?>">
                        <strong><?= ucfirst($selected['status']) ?> on <?= format_date($selected['decided_at']) ?><?= $selected['decided_by_name'] ? ' by ' . e($selected['decided_by_name']) : '' ?></strong>
                        <?= $selected['decision_note'] ? 'Note: ' . e($selected['decision_note']) : 'No note was added.' ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="card-box" style="height:auto">
                <h2 class="card-title-text">Review request</h2>
                <p class="text-muted mt-2 mb-0">Choose <strong>Review request</strong> on a pending request to approve or reject it.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

