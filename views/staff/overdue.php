<div class="page-head">
    <div>
        <h1 class="page-title">Overdue loans</h1>
        <p class="page-sub">Follow up with borrowers and recover resources that are past their due date.</p>
    </div>
    <a href="<?= e(self_url(['export' => '1'])) ?>" class="btn btn-soft"><i class="bi bi-download"></i> Export list</a>
</div>

<!-- Three summary cards -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card-box">
            <div class="stat-label"><span>Overdue resources</span><i class="bi bi-clock text-danger"></i></div>
            <div class="stat-number"><?= $overdue_total ?></div>
            <div class="stat-note red">Of <?= $active_total ?> active loans</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-box">
            <div class="stat-label"><span>Borrowers to contact</span><i class="bi bi-people"></i></div>
            <div class="stat-number"><?= count($contacts) ?></div>
            <div class="stat-note"><?= e(implode(' · ', array_column($contacts, 'name'))) ?: 'Nobody to contact' ?></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-box">
            <div class="stat-label"><span>Longest overdue</span><i class="bi bi-calendar-x"></i></div>
            <div class="stat-number"><?= $longest ? (int)$longest['days_late'] . ' days' : '0 days' ?></div>
            <div class="stat-note"><?= $longest ? e($longest['title']) . ' · Due ' . format_date($longest['due_date']) : 'Nothing overdue' ?></div>
        </div>
    </div>
</div>

<!-- Overdue register -->
<div class="card-box mb-3" style="height:auto">
    <h2 class="card-title-text">Overdue register</h2>
    <p class="card-sub">Days overdue are calculated as of <?= format_date($today) ?></p>

    <form method="get" class="row g-2 mb-3">
        <?= route_field() ?>
        <div class="col-8">
            <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search borrower, title or loan ID...">
        </div>
        <div class="col-4">
            <select name="sort" class="form-select" onchange="this.form.submit()">
                <option value="most"  <?= $sort === 'most' ? 'selected' : '' ?>>Most overdue first</option>
                <option value="least" <?= $sort === 'least' ? 'selected' : '' ?>>Least overdue first</option>
            </select>
        </div>
    </form>

    <?php if ($overdue_total === 0): ?>
        <p class="text-muted mb-0">There are currently no overdue loans.</p>
    <?php elseif (count($loans) === 0): ?>
        <p class="text-muted mb-0">No overdue loans match your search. <a href="<?= e(route('staff/overdue')) ?>">Clear search</a></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="simple-table">
                <thead>
                    <tr><th>Loan / resource</th><th>Borrower</th><th>Borrowed</th><th>Due date</th><th>Overdue</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($loans as $row): ?>
                    <tr>
                        <td>
                            <span class="row-title"><?= e($row['title']) ?></span>
                            <span class="row-sub"><?= e($row['loan_code']) ?> · <?= e($row['resource_code']) ?></span>
                        </td>
                        <td><?= e($row['borrower_name']) ?></td>
                        <td><?= format_date($row['loan_date']) ?></td>
                        <td><?= format_date($row['due_date']) ?></td>
                        <td><?= badge((int)$row['days_late'] . ((int)$row['days_late'] === 1 ? ' day' : ' days'), 'red') ?></td>
                        <td>
                            <form method="post" action="" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reminder">
                                <input type="hidden" name="loan_id" value="<?= (int)$row['loan_id'] ?>">
                                <button type="submit" class="btn btn-link p-0" style="font-size:0.82rem">Send reminder</button>
                            </form>
                            &nbsp;<a href="<?= e(route('staff/loans', ['tab' => 'overdue', 'loan' => (int)$row['loan_id']])) ?>">Return</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">Showing <?= count($loans) ?> of <?= $overdue_total ?> overdue loans</p>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card-box" style="height:auto">
            <h2 class="card-title-text">Borrower contact details</h2>
            <?php if (count($contacts) === 0): ?>
                <p class="text-muted mt-2 mb-0">No contacts needed right now.</p>
            <?php else: ?>
                <div class="mt-2">
                    <?php foreach ($contacts as $c): ?>
                        <div class="kv"><span><?= e($c['name']) ?></span><span><?= e($c['email']) ?></span></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="text-muted mt-2 mb-0" style="font-size:0.75rem">Use borrower email addresses for library reminders. A reminder does not change the loan due date.</p>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card-box" style="height:auto">
            <h2 class="card-title-text">When a resource comes back</h2>
            <p class="card-sub">Choose Return to review the loan and record the actual return date. The resource becomes available after confirmation.</p>
            <a href="<?= e(route('staff/loans', ['tab' => 'overdue'])) ?>" class="btn btn-soft"><i class="bi bi-arrow-right"></i> Go to loans &amp; returns</a>
        </div>
    </div>
</div>

