<div class="page-head">
    <div>
        <h1 class="page-title">Borrowing history</h1>
        <p class="page-sub">A complete lending record: <?= (int)$counts['active'] ?> current loan<?= (int)$counts['active'] === 1 ? '' : 's' ?> and <?= (int)$counts['returned'] ?> returned loan<?= (int)$counts['returned'] === 1 ? '' : 's' ?>.</p>
    </div>
    <a href="<?= e($export_url) ?>" class="btn btn-soft"><i class="bi bi-download"></i> Export history</a>
</div>

<div class="card-box">

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all', 'borrower_id' => $borrower_id])) ?>">All records (<?= (int)$counts['total'] ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'current' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'current', 'borrower_id' => $borrower_id])) ?>">Current (<?= (int)$counts['active'] ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'returned' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'returned', 'borrower_id' => $borrower_id])) ?>">Returned (<?= (int)$counts['returned'] ?>)</a></li>
    </ul>

    <form method="get" class="row g-2 mb-3">
        <?= route_field() ?>
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div class="col-12 col-md-6">
            <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search borrower, resource or loan ID...">
        </div>
        <div class="col-6 col-md-3">
            <select name="borrower_id" class="form-select" onchange="this.form.submit()">
                <option value="0">All borrowers</option>
                <?php foreach ($borrower_list as $b): ?>
                    <option value="<?= (int)$b['borrower_id'] ?>" <?= $borrower_id == $b['borrower_id'] ? 'selected' : '' ?>>
                        <?= e($b['first_name'] . ' ' . $b['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="dates" class="form-select" onchange="this.form.submit()">
                <option value="all" <?= $dates === 'all' ? 'selected' : '' ?>>All dates</option>
                <option value="30"  <?= $dates === '30' ? 'selected' : '' ?>>Borrowed in last 30 days</option>
                <option value="90"  <?= $dates === '90' ? 'selected' : '' ?>>Borrowed in last 90 days</option>
            </select>
        </div>
    </form>

    <?php if (count($records) === 0): ?>
        <p class="text-muted mb-0">No history records found.
            <?php if ($has_filter): ?><a href="<?= e(self_url(['tab' => $tab])) ?>">Clear filters</a><?php endif; ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="simple-table">
                <thead>
                    <tr><th>Loan ID</th><th>Resource</th><th>Borrower</th><th>Borrowed</th><th>Due date</th><th>Returned</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= e($row['loan_code']) ?></td>
                        <td>
                            <span class="row-title"><?= e($row['title']) ?></span>
                            <?php if ($row['renewal_count'] > 0): ?>
                                <span class="row-sub">Renewed once<?= $row['ren_id'] ? ' · RN' . (int)$row['ren_id'] : '' ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($row['borrower_name']) ?></td>
                        <td><?= format_date($row['loan_date']) ?></td>
                        <td><?= format_date($row['due_date']) ?></td>
                        <td><?= format_date($row['return_date']) ?></td>
                        <td><?= loan_badge($row) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">
            Showing <?= count($records) ?> of <?= (int)$counts['total'] ?> loan records
            <?php if ($has_filter): ?> · <a href="<?= e(self_url(['tab' => $tab])) ?>">Clear filters</a><?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<!-- Notes about renewals shown in the table -->
<?php
$notes = [];
foreach ($records as $row) {
    if ($row['ren_id']) {
        $notes[] = $row['loan_code'] . '’s due date was extended from ' . format_date($row['original_due_date'])
                 . ' to ' . format_date($row['due_date']) . ' after RN' . (int)$row['ren_id']
                 . ' was approved on ' . format_date($row['ren_date']) . '.';
    }
}
?>
<div class="info-box info-blue">
    <strong><i class="bi bi-info-circle"></i> History keeps the original lending activity</strong>
    <?php if (count($notes) === 0): ?>
        Original loan dates and any approved renewals stay on record.
    <?php else: ?>
        <?php foreach ($notes as $note): ?><?= e($note) ?> <?php endforeach; ?>
    <?php endif; ?>
</div>

