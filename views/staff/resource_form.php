<div class="page-head">
    <div>
        <h1 class="page-title"><?= $editing ? 'Edit resource' : 'Add resource' ?></h1>
        <p class="page-sub">
            <?= $editing ? 'Update catalogue details for ' . e($resource['resource_code']) . '.' : 'Add a new item to the catalogue.' ?>
            Required fields are marked with an asterisk.
        </p>
    </div>
    <a href="<?= e(route('staff/resources')) ?>" class="btn btn-soft"><i class="bi bi-arrow-left"></i> Back to resources</a>
</div>

<div class="row g-3">

    <!-- The form -->
    <div class="col-12 col-xl-8">
        <div class="card-box">
            <h2 class="card-title-text">Resource information</h2>
            <p class="card-sub"><?= $editing ? e($resource['resource_code']) . ' · ' . e($resource['title']) : 'New resource' ?></p>

            <?php if (count($errors) > 0): ?>
                <div class="alert alert-danger">
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="title" class="form-label">Title *</label>
                    <input type="text" class="form-control" id="title" name="title" maxlength="255"
                           value="<?= e($values['title']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="author" class="form-label">Author *</label>
                        <input type="text" class="form-control" id="author" name="author" maxlength="255"
                               value="<?= e($values['author']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn" maxlength="20"
                               value="<?= e($values['isbn']) ?>" placeholder="Optional">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category *</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Choose a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['category_id'] ?>" <?= $values['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="location" class="form-label">Shelf / location *</label>
                        <input type="text" class="form-control" id="location" name="location" maxlength="30"
                               value="<?= e($values['location']) ?>" placeholder="For example A2" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= e($values['description']) ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="internal_notes" class="form-label">Internal notes</label>
                    <textarea class="form-control" id="internal_notes" name="internal_notes" rows="3"><?= e($values['internal_notes']) ?></textarea>
                    <div class="form-text">Only staff can see these notes.</div>
                </div>

                <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg"></i> <?= $editing ? 'Save changes' : 'Save resource' ?></button>
                <a href="<?= e(route('staff/resources')) ?>" class="btn btn-soft">Cancel</a>
            </form>
        </div>
    </div>

    <!-- Right-hand panel -->
    <div class="col-12 col-xl-4">
        <?php if ($editing): ?>
            <div class="card-box mb-3" style="height:auto">
                <h2 class="card-title-text">Record overview</h2>
                <div class="my-2"><?= resource_badge($resource['status']) ?></div>
                <div class="kv"><span>Resource ID</span><span><?= e($resource['resource_code']) ?></span></div>
                <div class="kv"><span>Current loan</span>
                    <span><?= $current_loan ? e($current_loan['loan_code']) . ' · ' . e($current_loan['borrower_name']) : 'None' ?></span></div>
                <div class="kv"><span>Location</span><span>Shelf <?= e($resource['location']) ?></span></div>
                <div class="kv"><span>Last returned</span><span><?= format_date($last_returned) ?></span></div>
            </div>

            <div class="info-box info-blue" style="margin-top:0">
                <strong><i class="bi bi-info-circle"></i> Availability is not editable</strong>
                Status is worked out from active loans. Registering a loan sets this resource to On loan; processing a return makes it Available.
            </div>
        <?php else: ?>
            <div class="card-box" style="height:auto">
                <h2 class="card-title-text">Adding a new resource?</h2>
                <p class="card-sub">Fill in the title, author, category and shelf location.</p>
                <p style="font-size:0.8rem" class="mb-0">A unique resource ID (like R011) is assigned automatically when you save. New resources start as Available.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

