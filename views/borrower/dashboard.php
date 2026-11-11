<div class="page-head">
    <div>
        <h1 class="page-title">Welcome back, <?= e(first_name($_SESSION['full_name'])) ?></h1>
        <p class="page-sub">A little reading, a lot of possibility. Here's what's happening with your library account.</p>
    </div>
    <a href="<?= e(route('borrower/browse')) ?>" class="btn btn-navy"><i class="bi bi-search"></i> Browse resources</a>
</div>

<!-- Four summary cards -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-box">
            <div class="stat-label"><span>Current loans</span><i class="bi bi-book"></i></div>
            <div class="stat-number"><?= $current_count ?></div>
            <div class="stat-note">Next return: <?= format_date($next_return) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-box">
            <div class="stat-label"><span>Overdue loans</span><i class="bi bi-exclamation-circle"></i></div>
            <div class="stat-number"><?= $overdue_count ?></div>
            <?php if ($overdue_count > 0): ?>
                <div class="stat-note red">Please return these soon</div>
            <?php else: ?>
                <div class="stat-note">You're up to date</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-box">
            <div class="stat-label"><span>Returned resources</span><i class="bi bi-clock-history"></i></div>
            <div class="stat-number"><?= $returned_count ?></div>
            <div class="stat-note">Your reading journey so far</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-box">
            <div class="stat-label"><span>Pending renewals</span><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-number"><?= $pending_count ?></div>
            <div class="stat-note">Awaiting staff review</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">

    <!-- Current loans table -->
    <div class="col-12 col-xl-8">
        <div class="card-box">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title-text">Your current loans</h2>
                    <p class="card-sub">Keep an eye on your return dates</p>
                </div>
                <a href="<?= e(route('borrower/loans')) ?>" style="font-size:0.8rem">View loans →</a>
            </div>

            <?php if ($current_count === 0): ?>
                <p class="text-muted mb-0">You have no borrowed resources right now.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Resource</th><th>Due date</th><th>Renewal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>
                                    <span class="row-title"><?= e($loan['title']) ?></span>
                                    <span class="row-sub"><?= e($loan['loan_code']) ?> · <?= e($loan['author']) ?></span>
                                </td>
                                <td><?= format_date($loan['due_date']) ?></td>
                                <td>
                                    <?php
                                    if ($loan['pending_renewal'] > 0) {
                                        echo badge('Pending', 'amber');
                                    } elseif ($loan['renewal_count'] >= 1) {
                                        echo badge('Limit reached', 'gray');
                                    } else {
                                        echo badge('Eligible', 'green');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                // Warning boxes for overdue loans and loans due soon
                foreach ($loans as $loan):
                    if ($loan['display_status'] === 'overdue'): ?>
                        <div class="info-box info-danger">
                            <strong><i class="bi bi-exclamation-circle"></i> <?= e($loan['title']) ?> is overdue by <?= (int)$loan['days_overdue'] ?> day(s)</strong>
                            Please return it to the library desk as soon as you can.
                        </div>
                    <?php elseif ($loan['days_left'] <= 2): ?>
                        <div class="info-box info-warning">
                            <strong><i class="bi bi-exclamation-circle"></i>
                                <?= e($loan['title']) ?> is due
                                <?= $loan['days_left'] == 0 ? 'today' : 'in ' . (int)$loan['days_left'] . ' day(s)' ?>
                            </strong>
                            Please return it by <?= format_date($loan['due_date']) ?>.
                            <?= $loan['renewal_count'] >= 1 ? 'Its one permitted renewal has already been used.' : '' ?>
                        </div>
                    <?php endif;
                endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Latest updates -->
    <div class="col-12 col-xl-4">
        <div class="card-box">
            <div class="d-flex justify-content-between">
                <h2 class="card-title-text">Latest updates</h2>
                <i class="bi bi-bell text-muted"></i>
            </div>
            <p class="card-sub">Updates for your account</p>

            <?php if (count($updates) === 0): ?>
                <p class="text-muted mb-0">No notifications yet.</p>
            <?php else: ?>
                <?php foreach ($updates as $note): ?>
                    <div class="mb-3">
                        <?= notification_badge($note['type']) ?>
                        <div class="fw-semibold mt-1" style="color:var(--navy)"><?= e($note['title']) ?></div>
                        <div class="text-muted" style="font-size:0.78rem"><?= e($note['message']) ?></div>
                        <div class="text-muted" style="font-size:0.7rem"><?= format_date($note['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <a href="<?= e(route('borrower/history')) ?>" style="font-size:0.8rem">View history &amp; notifications →</a>
        </div>
    </div>
</div>

<!-- Find your next read -->
<div class="info-box info-blue d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <strong style="color:var(--navy)"><i class="bi bi-collection"></i> Find your next great read</strong>
        <?= $available ?> resources are available. Browse online, then visit the library desk to borrow.
    </div>
    <a href="<?= e(route('borrower/browse')) ?>" class="btn btn-soft">Explore catalogue →</a>
</div>

