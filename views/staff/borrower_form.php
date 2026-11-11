<div class="page-head">
    <div>
        <h1 class="page-title"><?= $editing ? 'Edit borrower' : 'Register borrower' ?></h1>
        <p class="page-sub">Keep contact details current. Required fields are marked with an asterisk.</p>
    </div>
    <a href="<?= e(route('staff/borrowers')) ?>" class="btn btn-soft"><i class="bi bi-arrow-left"></i> Back to borrowers</a>
</div>

<div class="row g-3">

    <!-- The form -->
    <div class="col-12 col-xl-8">
        <div class="card-box">
            <h2 class="card-title-text">Personal information</h2>
            <p class="card-sub"><?= $editing ? e($borrower['borrower_code']) . ' · ' . e($borrower['first_name'] . ' ' . $borrower['last_name']) : 'New borrower' ?></p>

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

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" maxlength="60"
                               value="<?= e($values['first_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" maxlength="60"
                               value="<?= e($values['last_name']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address *</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="190"
                           value="<?= e($values['email']) ?>" required>
                    <div class="form-text">Used for due-date reminders and renewal updates.</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone number</label>
                        <input type="text" class="form-control" id="phone" name="phone" maxlength="30"
                               value="<?= e($values['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="membership_status" class="form-label">Membership status *</label>
                        <select class="form-select" id="membership_status" name="membership_status">
                            <option value="active"   <?= $values['membership_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $values['membership_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" maxlength="255"
                           value="<?= e($values['address']) ?>">
                </div>

                <div class="mb-4">
                    <label for="staff_notes" class="form-label">Staff notes</label>
                    <textarea class="form-control" id="staff_notes" name="staff_notes" rows="3"><?= e($values['staff_notes']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg"></i> <?= $editing ? 'Save changes' : 'Save borrower' ?></button>
                <a href="<?= e(route('staff/borrowers')) ?>" class="btn btn-soft">Cancel</a>
            </form>
        </div>
    </div>

    <!-- Right-hand panel -->
    <div class="col-12 col-xl-4">
        <?php if ($editing): ?>
            <div class="card-box mb-3" style="height:auto">
                <h2 class="card-title-text">Membership summary</h2>
                <div class="my-2"><?= $borrower['membership_status'] === 'active' ? badge('Active member', 'green') : badge('Inactive member', 'gray') ?></div>
                <div class="kv"><span>Borrower ID</span><span><?= e($borrower['borrower_code']) ?></span></div>
                <div class="kv"><span>Current loans</span><span><?= $current ?></span></div>
                <div class="kv"><span>Overdue loans</span><span><?= $overdue ?></span></div>
                <div class="kv"><span>Returned loans</span><span><?= $returned ?></span></div>
                <div class="kv"><span>Renewal requests</span><span><?= $pending ?> pending</span></div>
            </div>

            <div class="info-box info-blue" style="margin-top:0">
                <strong><i class="bi bi-info-circle"></i> Existing loans stay unchanged</strong>
                Editing contact details does not update loan due dates, return records or renewal decisions.
            </div>
        <?php else: ?>
            <div class="card-box" style="height:auto">
                <h2 class="card-title-text">Registering a borrower?</h2>
                <p class="card-sub">A borrower ID (like B007) is assigned when you save.</p>
                <p style="font-size:0.8rem" class="mb-0">An administrator manages sign-in accounts and role permissions, so a new borrower cannot log in until an account is created for them.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

