<div class="page-head">
    <div>
        <h1 class="page-title">My current loans</h1>
        <p class="page-sub">
            You have <?= count($loans) ?> resource<?= count($loans) === 1 ? '' : 's' ?> on loan and
            <?= $overdue_count === 0 ? 'no overdue loans' : $overdue_count . ' overdue loan' . ($overdue_count === 1 ? '' : 's') ?>.
            Return resources at the library desk.
        </p>
    </div>
    <a href="<?= e(route('borrower/browse')) ?>" class="btn btn-soft"><i class="bi bi-search"></i> Browse resources</a>
</div>

<?php if (count($errors) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (count($loans) === 0): ?>
    <div class="card-box" style="height:auto"><p class="text-muted mb-0">You have no borrowed resources right now.</p></div>
<?php endif; ?>

<div class="row g-3 mb-3">
<?php foreach ($loans as $loan): ?>
    <?php
    $requests   = $loan['requests'];
    $pending    = null;
    $last_done  = null;       // the most recent approved/rejected request
    foreach ($requests as $rq) {
        if ($rq['status'] === 'pending' && $pending === null) $pending = $rq;
        if ($rq['status'] !== 'pending' && $last_done === null) $last_done = $rq;
    }
    $limit_reached = ($loan['renewal_count'] >= $max_renewals);
    $default_date  = date('Y-m-d', strtotime($loan['due_date'] . ' +' . $extension_days . ' days'));
    $min_date      = date('Y-m-d', strtotime($loan['due_date'] . ' +1 day'));
    $max_date      = date('Y-m-d', strtotime($loan['due_date'] . ' +' . $max_extension . ' days'));

    // Badge in the card header
    if ($loan['display_status'] === 'overdue') {
        $header_badge = badge('Overdue ' . (int)$loan['days_overdue'] . 'd', 'red');
    } elseif ($loan['days_left'] <= 2) {
        $header_badge = badge($loan['days_left'] == 0 ? 'Due today' : 'Due in ' . (int)$loan['days_left'] . ' days', 'amber');
    } else {
        $header_badge = badge('Active', 'blue');
    }
    ?>
    <div class="col-12 col-xl-6">
        <div class="card-box" style="height:auto">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title-text"><?= e($loan['title']) ?></h2>
                    <p class="card-sub"><?= e($loan['author']) ?> · <?= e($loan['resource_code']) ?></p>
                </div>
                <?= $header_badge ?>
            </div>

            <div class="kv"><span>Loan</span><span><?= e($loan['loan_code']) ?> · Shelf <?= e($loan['location']) ?></span></div>
            <div class="kv"><span>Borrowed</span><span><?= format_date($loan['loan_date']) ?></span></div>
            <div class="kv"><span>Original due</span><span><?= format_date($loan['original_due_date']) ?></span></div>
            <div class="kv"><span>Current due</span><span><?= format_date($loan['due_date']) ?></span></div>
            <div class="kv"><span>Renewals used</span><span><?= (int)$loan['renewal_count'] ?> of <?= $max_renewals ?></span></div>
            <?php if ($last_done): ?>
                <div class="kv"><span>Renewal decision</span><span>RN<?= (int)$last_done['renewal_id'] ?> <?= e($last_done['status']) ?> · <?= format_date($last_done['decided_at']) ?></span></div>
            <?php endif; ?>
            <?php if ($pending): ?>
                <div class="kv"><span>Requested due</span><span><?= format_date($pending['requested_due_date']) ?></span></div>
                <div class="kv"><span>Request</span><span>RN<?= (int)$pending['renewal_id'] ?> · Submitted <?= format_date($pending['submitted_at']) ?></span></div>
            <?php endif; ?>

            <?php if ($pending): ?>
                <div class="info-box info-blue">
                    <strong><i class="bi bi-hourglass-split"></i> Renewal awaiting staff review</strong>
                    Your due date remains <?= format_date($loan['due_date']) ?> until RN<?= (int)$pending['renewal_id'] ?> is approved. You cannot submit another request while this one is pending.
                </div>
                <div class="mt-3">
                    <button class="btn btn-soft" disabled>Renewal pending</button>
                    <a class="btn btn-soft" href="<?= e(route('borrower/renewals', ['id' => (int)$pending['renewal_id']])) ?>">View request</a>
                </div>

            <?php elseif ($limit_reached): ?>
                <div class="info-box info-warning">
                    <strong><i class="bi bi-exclamation-circle"></i> Renewal limit reached</strong>
                    This loan has already been renewed. A second renewal is not permitted. Please return <?= e($loan['title']) ?> by <?= format_date($loan['due_date']) ?>.
                </div>
                <div class="mt-3"><button class="btn btn-soft" disabled><i class="bi bi-lock"></i> Renewal limit reached</button></div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem">Visit library staff if you need help with your return.</p>

            <?php else: ?>
                <div class="mt-3">
                    <button class="btn btn-navy" type="button" data-bs-toggle="collapse" data-bs-target="#renew-<?= (int)$loan['loan_id'] ?>">
                        <i class="bi bi-arrow-repeat"></i> Request renewal
                    </button>
                </div>
                <div class="collapse mt-3" id="renew-<?= (int)$loan['loan_id'] ?>">
                    <form method="post" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="request_renewal">
                        <input type="hidden" name="loan_id" value="<?= (int)$loan['loan_id'] ?>">

                        <label class="form-label">New due date *</label>
                        <input type="date" class="form-control" name="requested_due_date"
                               value="<?= e($default_date) ?>" min="<?= e($min_date) ?>" max="<?= e($max_date) ?>" required>
                        <div class="form-text">Up to <?= $max_extension ?> days after your current due date.</div>

                        <label class="form-label mt-3">Reason for renewal</label>
                        <textarea class="form-control" name="reason" rows="2" maxlength="500" placeholder="Optional, but it helps staff decide."></textarea>

                        <button type="submit" class="btn btn-navy mt-3"
                                onclick="return confirm('Submit this renewal request?');">Submit request</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- <div class="card-box" style="height:auto">
    <h2 class="card-title-text">A simple renewal policy</h2>
    <div class="row g-3 mt-1">
        <div class="col-md-4">
            <div class="fw-semibold" style="color:var(--navy)">One renewal per loan</div>
            <div class="text-muted" style="font-size:0.8rem">The renewal is used only when a request is approved.</div>
        </div>
        <div class="col-md-4">
            <div class="fw-semibold" style="color:var(--navy)">Your due date stays in place</div>
            <div class="text-muted" style="font-size:0.8rem">Pending or rejected requests do not extend your loan.</div>
        </div>
        <div class="col-md-4">
            <div class="fw-semibold" style="color:var(--navy)">Staff complete all returns</div>
            <div class="text-muted" style="font-size:0.8rem">Bring the physical resource back to the library desk.</div>
        </div>
    </div>
</div> -->

