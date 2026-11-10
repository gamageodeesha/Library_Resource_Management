<div class="page-head">
    <div>
        <h1 class="page-title">Admin dashboard</h1>
        <p class="page-sub">An at-a-glance view of users, resources and lending activity.</p>
    </div>
    <a href="<?= e(route('admin/users')) ?>" class="btn btn-navy"><i class="bi bi-plus-lg"></i> Add user</a>
</div>

<!-- Four summary cards -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <a class="stat-link" href="<?= e(route('admin/users')) ?>">
            <div class="card-box">
                <div class="stat-label"><span>Users</span><i class="bi bi-people"></i></div>
                <div class="stat-number"><?= $total_users ?></div>
                <div class="stat-note"><?= $active_users ?> active accounts</div>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-box">
            <div class="stat-label"><span>Resources</span><i class="bi bi-collection"></i></div>
            <div class="stat-number"><?= $total_resources ?></div>
            <div class="stat-note"><?= $available ?> available</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-box">
            <div class="stat-label"><span>Active loans</span><i class="bi bi-book"></i></div>
            <div class="stat-number"><?= $active_loans ?></div>
            <div class="stat-note">Currently borrowed</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-box">
            <div class="stat-label"><span>Overdue loans</span><i class="bi bi-clock text-danger"></i></div>
            <div class="stat-number"><?= $overdue_count ?></div>
            <div class="stat-note <?= $overdue_count > 0 ? 'red' : '' ?>">
                <?= $overdue_count > 0 ? 'Need staff follow-up' : 'Nothing overdue' ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">

    <!-- Newest accounts -->
    <div class="col-12 col-xl-8">
        <div class="card-box">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="card-title-text">Newest accounts</h2>
                    <p class="card-sub">The five most recently created users</p>
                </div>
                <a href="<?= e(route('admin/users')) ?>" style="font-size:0.8rem">Manage users →</a>
            </div>
            <div class="table-responsive">
                <table class="simple-table">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td>
                                <span class="row-title"><?= e($u['full_name']) ?></span>
                                <span class="row-sub"><?= e($u['email']) ?></span>
                            </td>
                            <td><?= badge(ucfirst($u['role']), $u['role'] === 'borrower' ? 'gray' : 'blue') ?></td>
                            <td><?= $u['status'] === 'active' ? badge('Active', 'green') : badge('Inactive', 'gray') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Users by role + permissions -->
    <div class="col-12 col-xl-4">
        <div class="card-box">
            <h2 class="card-title-text">Users by role</h2>
            <p class="card-sub">How accounts are spread</p>
            <div class="kv"><span>Admins</span><span><?= $role_counts['admin'] ?></span></div>
            <div class="kv"><span>Library staff</span><span><?= $role_counts['staff'] ?></span></div>
            <div class="kv"><span>Borrowers</span><span><?= $role_counts['borrower'] ?></span></div>

            <div class="info-box info-blue">
                <strong><i class="bi bi-info-circle"></i> Role permissions</strong>
                Admin: manage access. Staff: manage lending. Borrower: own records and renewal requests. Only admins can assign roles.
            </div>
        </div>
    </div>
</div>

<!-- Recent loan activity -->
<div class="card-box">
    <h2 class="card-title-text">Recent loan activity</h2>
    <p class="card-sub">The five most recently registered loans</p>
    <div class="table-responsive">
        <table class="simple-table">
            <thead>
                <tr><th>Resource / loan</th><th>Borrower</th><th>Due date</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recent_loans as $loan): ?>
                <tr>
                    <td>
                        <span class="row-title"><?= e($loan['title']) ?></span>
                        <span class="row-sub"><?= e($loan['loan_code']) ?></span>
                    </td>
                    <td><?= e($loan['borrower_name']) ?></td>
                    <td><?= format_date($loan['due_date']) ?></td>
                    <td><?= loan_badge($loan) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

