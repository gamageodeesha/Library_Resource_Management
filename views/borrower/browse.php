<div class="page-head">
    <div>
        <h1 class="page-title">Browse resources</h1>
        <p class="page-sub">Discover the collection. Visit the library desk to borrow an available resource.</p>
    </div>
    <div><?= badge($available . ' available now', 'green') ?></div>
</div>

<div class="row g-3">

    <!-- Catalogue -->
    <div class="col-12 col-xl-7">
        <div class="card-box">

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all'])) ?>">All resources (<?= $total ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'available' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'available'])) ?>">Available (<?= $available ?>)</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'on_loan' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'on_loan'])) ?>">On loan (<?= $on_loan ?>)</a></li>
            </ul>

            <form method="get" class="row g-2 mb-3">
                <?= route_field() ?>
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <div class="col-8">
                    <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search title, author">
                </div>
                <div class="col-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="0">All categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['category_id'] ?>" <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if (count($resources) === 0): ?>
                <p class="text-muted mb-0">No resources match your search.
                    <?php if ($search !== '' || $category_id > 0): ?><a href="<?= e(self_url(['tab' => $tab])) ?>">Clear filters</a><?php endif; ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>Resource</th><th>Category</th><th>Status</th><th>Details</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resources as $r): ?>
                            <tr <?= ($selected && $r['resource_id'] == $selected['resource_id']) ? 'style="background:#F8FAFC"' : '' ?>>
                                <td>
                                    <span class="row-title"><?= e($r['title']) ?></span>
                                    <span class="row-sub"><?= e($r['author']) ?> · <?= e($r['resource_code']) ?></span>
                                </td>
                                <td><?= e($r['category_name']) ?></td>
                                <td><?= resource_badge($r['status']) ?></td>
                                <td><a href="<?= e(self_url(['tab' => $tab, 'q' => $search, 'category' => $category_id, 'id' => (int)$r['resource_id']])) ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">Showing <?= count($resources) ?> of <?= $total ?> resources</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resource details -->
    <div class="col-12 col-xl-5">
        <?php if ($selected): ?>
            <div class="card-box mb-3" style="height:auto">
                <h2 class="card-title-text">Resource details</h2>
                <p class="card-sub">Selected resource · <?= e($selected['resource_code']) ?></p>

                <h3 class="card-title-text" style="font-size:1.1rem"><?= e($selected['title']) ?></h3>
                <div class="text-muted mb-2"><?= e($selected['author']) ?></div>
                <div class="mb-3"><?= resource_badge($selected['status']) ?></div>

                <div class="kv"><span>Resource ID</span><span><?= e($selected['resource_code']) ?></span></div>
                <div class="kv"><span>Category</span><span><?= e($selected['category_name']) ?></span></div>
                <div class="kv"><span>Location</span><span>Shelf <?= e($selected['location']) ?></span></div>
                <div class="kv"><span>ISBN</span><span><?= e($selected['isbn'] ?: '—') ?></span></div>

                <div class="fw-semibold mt-3" style="font-size:0.78rem;color:var(--navy)">About this resource</div>
                <p class="mb-0" style="font-size:0.82rem"><?= e($selected['description'] ?: 'No description has been added yet.') ?></p>

                <?php if ($my_current): ?>
                    <div class="info-box info-warning">
                        <strong><i class="bi bi-book"></i> You currently have this on loan</strong>
                        Due back on <?= format_date($my_current['due_date']) ?>. See My Current Loans for renewal options.
                    </div>
                <?php elseif ($selected['status'] === 'available'): ?>
                    <div class="info-box info-blue">
                        <strong><i class="bi bi-info-circle"></i> How to borrow</strong>
                        Ask library staff for <?= e($selected['resource_code']) ?> at shelf <?= e($selected['location']) ?>. Borrowing is completed in person at the library desk.
                    </div>
                <?php else: ?>
                    <div class="info-box info-warning">
                        <strong><i class="bi bi-clock"></i> Currently on loan</strong>
                        This resource is with another borrower. Check back soon or ask library staff.
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($my_previous): ?>
                <div class="card-box" style="height:auto">
                    <h2 class="card-title-text">Your previous loan</h2>
                    <div class="kv mt-2"><span>Loan</span><span><?= e('LN' . str_pad($my_previous['loan_id'], 4, '0', STR_PAD_LEFT)) ?></span></div>
                    <div class="kv"><span>Returned</span><span><?= format_date($my_previous['return_date']) ?></span></div>
                    <div class="kv"><span>Outcome</span><span>Returned <?= e(return_timing($my_previous['due_date'], $my_previous['return_date'])) ?></span></div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card-box" style="height:auto"><p class="text-muted mb-0">Select a resource to see its details.</p></div>
        <?php endif; ?>
    </div>
</div>

