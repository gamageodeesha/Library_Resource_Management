<div class="page-head">
    <div>
        <h1 class="page-title">My profile</h1>
        <p class="page-sub">Your account details and sign-in settings.</p>
    </div>
</div>

<?php if (!empty($_SESSION['must_change_password'])): ?>
    <div class="info-box info-warning" style="margin-top:0;margin-bottom:1rem">
        <strong><i class="bi bi-exclamation-circle"></i> Choose a new password</strong>
        Your account uses a temporary password. Please set your own below before you continue.
    </div>
<?php endif; ?>

<div class="row g-3">

    <!-- Account details -->
    <div class="col-12 col-xl-6">
        <div class="card-box" style="height:auto">
            <h2 class="card-title-text">Account details</h2>
            <p class="card-sub">Your sign-in account</p>

            <div class="kv"><span>Email</span><span><?= e($user['email']) ?></span></div>
            <div class="kv"><span>Role</span><span><?= e(ucfirst($user['role'])) ?></span></div>
            <div class="kv"><span>Status</span><span><?= e(ucfirst($user['status'])) ?></span></div>
            <div class="kv"><span>Member since</span><span><?= format_date($user['created_at']) ?></span></div>
            <div class="kv"><span>Last sign-in</span><span><?= $user['last_login_at'] ? format_date($user['last_login_at']) : '—' ?></span></div>

            <?php if ($borrower): ?>
                <hr>
                <div class="fw-semibold mb-1" style="font-size:0.78rem;color:var(--navy)">Library membership</div>
                <div class="kv"><span>Borrower ID</span><span><?= e($borrower['borrower_code']) ?></span></div>
                <div class="kv"><span>Membership</span><span><?= e(ucfirst($borrower['membership_status'])) ?></span></div>
                <div class="kv"><span>Phone</span><span><?= e($borrower['phone'] ?: '—') ?></span></div>
                <div class="kv"><span>Address</span><span><?= e($borrower['address'] ?: '—') ?></span></div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem">To change your email, phone or address, please ask library staff.</p>
            <?php else: ?>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem">To change your email or role, please ask an administrator.</p>
            <?php endif; ?>
        </div>

        <!-- Change name -->
        <div class="card-box mt-3" style="height:auto">
            <h2 class="card-title-text">Display name</h2>
            <p class="card-sub">Shown in the top bar and on records you create</p>

            <?php if (count($name_errors) > 0): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($name_errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>

            <form method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_name">
                <label for="full_name" class="form-label">Full name *</label>
                <input type="text" class="form-control" id="full_name" name="full_name" maxlength="120" value="<?= e($new_name) ?>" required>
                <button type="submit" class="btn btn-navy mt-3">Save name</button>
            </form>
        </div>
    </div>

    <!-- Change password -->
    <div class="col-12 col-xl-6">
        <div class="card-box" style="height:auto">
            <h2 class="card-title-text">Change password</h2>
            <p class="card-sub">Use at least 8 characters with letters and numbers</p>

            <?php if (count($password_errors) > 0): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($password_errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>

            <form method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label for="current_password" class="form-label">Current password *</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New password *</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm new password *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-navy">Change password</button>
            </form>
        </div>
    </div>
</div>

