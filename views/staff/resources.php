<div class="page-head">
    <div>
        <h1 class="page-title">Manage resources</h1>
        <p class="page-sub">Maintain your catalogue and see what's available at a glance.</p>
    </div>
    <a href="<?= e(route('staff/resource_form')) ?>" class="btn btn-navy"><i class="bi bi-plus-lg"></i> Add resource</a>
</div>

<div class="card-box">

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all'])) ?>">All resources (<?= $total ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'available' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'available'])) ?>">Available (<?= $available ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'on_loan' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'on_loan'])) ?>">On loan (<?= $on_loan ?>)</a></li>
    </ul>

    <!-- Search and filters -->
    <form method="get" class="row g-2 mb-3">
        <?= route_field() ?>
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div class="col-12 col-md-6">
            <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search title, author, ISBN or resource ID...">
        </div>
        <div class="col-6 col-md-3">
            <select name="category" class="form-select" onchange="this.form.submit()">
                <option value="0">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="location" class="form-select" onchange="this.form.submit()">
                <option value="">All locations</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= e($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- Table -->
    <?php if (count($resources) === 0): ?>
        <p class="text-muted mb-0">No resources match your search.
            <?php if ($has_filter): ?><a href="<?= e(self_url(['tab' => $tab])) ?>">Clear filters</a><?php endif; ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="simple-table">
                <thead>
                    <tr><th>ID</th><th>Resource / author</th><th>Category</th><th>Location</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($resources as $r): ?>
                    <tr>
                        <td><?= e($r['resource_code']) ?></td>
                        <td>
                            <span class="row-title"><?= e($r['title']) ?></span>
                            <span class="row-sub"><?= e($r['author']) ?></span>
                        </td>
                        <td><?= e($r['category_name']) ?></td>
                        <td><?= e($r['location']) ?></td>
                        <td><?= resource_badge($r['status']) ?></td>
                        <td><a href="<?= e(route('staff/resource_form', ['id' => (int)$r['resource_id']])) ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">
            Showing <?= count($resources) ?> of <?= $total ?> resources
            <?php if ($has_filter): ?> · <a href="<?= e(self_url(['tab' => $tab])) ?>">Clear filters</a><?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<div class="info-box info-blue">
    <strong><i class="bi bi-info-circle"></i> Availability is always up to date</strong>
    Resource status is calculated from loans and returns. It cannot be changed manually on a resource record.
</div>

