<div class="page-head">
    <div>
        <h1 class="page-title">Manage users &amp; roles</h1>
        <p class="page-sub">Control account access and keep responsibilities clearly defined.</p>
    </div>
    <a href="<?= e(self_url($base)) ?>" class="btn btn-navy"><i class="bi bi-plus-lg"></i> Add user</a>
</div>

<div class="row g-3">

    <!-- User list -->
    <div class="col-12 col-xl-7">
        <div class="card-box">
            <h2 class="card-title-text">User accounts</h2>
            <p class="card-sub"><?= $active_users ?> active account<?= $active_users === 1 ? '' : 's' ?> in your library (<?= $total_users ?> in total)</p>

            <form method="get" class="row g-2 mb-3">
                <?= route_field() ?>
                <div class="col-8">
                    <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Search name or email...">
                </div>
                <div class="col-4">
                    <select name="role" class="form-select" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        <option value="admin"    <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="staff"    <?= $role_filter === 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="borrower" <?= $role_filter === 'borrower' ? 'selected' : '' ?>>Borrower</option>
                    </select>
                </div>
            </form>

            <?php if (count($users) === 0): ?>
                <p class="text-muted mb-0">No users match your search. <a href="<?= e(route('admin/users')) ?>">Clear filters</a></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="simple-table">
                        <thead>
                            <tr><th>User</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr <?= ($editing_now && $u['user_id'] == $edit_user['user_id']) ? 'style="background:#F8FAFC"' : '' ?>>
                                <td>
                                    <span class="row-title"><?= e($u['full_name']) ?><?= $u['user_id'] == $_SESSION['user_id'] ? ' (you)' : '' ?></span>
                                    <span class="row-sub"><?= e($u['email']) ?><?= $u['borrower_code'] ? ' · ' . e($u['borrower_code']) : '' ?></span>
                                </td>
                                <td><?= badge(ucfirst($u['role']), $u['role'] === 'borrower' ? 'gray' : 'blue') ?></td>
                                <td><?= $u['status'] === 'active' ? badge('Active', 'green') : badge('Inactive', 'gray') ?></td>
                                <td><a href="<?= e(self_url(array_merge($base, ['edit' => (int)$u['user_id']]))) ?>">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="text-muted" style="font-size:0.75rem">
                        Showing <?= $offset + 1 ?>–<?= $offset + count($users) ?> of <?= $matching ?> user<?= $matching === 1 ? '' : 's' ?>
                    </span>
                    <?php if ($pages > 1): ?>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= e(self_url(array_merge($base, ['page' => $page - 1]))) ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $pages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= e(self_url(array_merge($base, ['page' => $i]))) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= e(self_url(array_merge($base, ['page' => $page + 1]))) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add / edit form -->
    <div class="col-12 col-xl-5">
        <div class="card-box" style="height:auto">
            <h2 class="card-title-text"><?= $editing_now ? 'Edit user' : 'Add user' ?></h2>
            <p class="card-sub"><?= $editing_now ? e($edit_user['email']) : 'Create a sign-in account' ?></p>

            <?php if (count($errors) > 0): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(self_url($base)) ?>" id="userForm"
                  data-original-status="<?= $editing_now ? e($edit_user['status']) : 'active' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_user">
                <input type="hidden" name="user_id" value="<?= $editing_now ? (int)$edit_user['user_id'] : 0 ?>">

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full name *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" maxlength="120"
                           value="<?= e($form['full_name']) ?>" placeholder="Enter full name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address *</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="190"
                           value="<?= e($form['email']) ?>" placeholder="name@example.org" required>
                </div>

                <?php $is_self = $editing_now && $edit_user['user_id'] == $_SESSION['user_id']; ?>
                <?php $role_locked = $is_self || $linked_borrower; ?>

                <div class="mb-3">
                    <label for="role" class="form-label">Role *</label>
                    <select class="form-select" id="role" name="role" <?= $role_locked ? 'style="pointer-events:none;background:#F1F5F9"' : '' ?>>
                        <option value="admin"    <?= $form['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="staff"    <?= $form['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="borrower" <?= $form['role'] === 'borrower' ? 'selected' : '' ?>>Borrower</option>
                    </select>
                    <?php if ($is_self): ?>
                        <div class="form-text">You cannot change your own role or status.</div>
                    <?php elseif ($linked_borrower): ?>
                        <div class="form-text">Linked to borrower record, so the role is fixed.</div>
                    <?php endif; ?>
                </div>

                <!-- Only shown for the Borrower role -->
                <div class="mb-3" id="borrowerLink" style="display:none">
                    <?php if ($linked_borrower): ?>
                        <label class="form-label">Borrower record</label>
                        <div class="info-box info-blue" style="margin-top:0">
                            <?= e($linked_borrower['borrower_code'] . ' · ' . $linked_borrower['first_name'] . ' ' . $linked_borrower['last_name']) ?>
                        </div>
                    <?php elseif (count($free_borrowers) === 0): ?>
                        <label class="form-label">Borrower record *</label>
                        <div class="info-box info-warning" style="margin-top:0">
                            Every borrower already has an account. Ask staff to register the borrower first (Staff → Borrowers), then come back here.
                        </div>
                    <?php else: ?>
                        <label for="borrower_id" class="form-label">Borrower record *</label>
                        <select class="form-select" id="borrower_id" name="borrower_id">
                            <option value="0">Choose the borrower...</option>
                            <?php foreach ($free_borrowers as $fb): ?>
                                <option value="<?= (int)$fb['borrower_id'] ?>" <?= $form['borrower_id'] == $fb['borrower_id'] ? 'selected' : '' ?>>
                                    <?= e($fb['borrower_code'] . ' — ' . $fb['first_name'] . ' ' . $fb['last_name'] . ' (' . $fb['email'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only borrowers without an account are listed.</div>
                    <?php endif; ?>
                </div>

                <?php if ($editing_now): ?>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" <?= $is_self ? 'style="pointer-events:none;background:#F1F5F9"' : '' ?>>
                            <option value="active"   <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (cannot sign in)</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="temp_password" class="form-label"><?= $editing_now ? 'Reset password (optional)' : 'Temporary password *' ?></label>
                    <input type="text" class="form-control" id="temp_password" name="temp_password" autocomplete="off"
                           placeholder="<?= $editing_now ? 'Leave empty to keep the current password' : 'Set temporary password' ?>">
                    <div class="form-text">At least 8 characters with letters and numbers. The user must choose their own password at the next sign-in.</div>
                </div>

                <button type="submit" class="btn btn-navy"><?= $editing_now ? 'Save changes' : 'Save user' ?></button>
                <a href="<?= e(self_url($base)) ?>" class="btn btn-soft">Cancel</a>
            </form>
        </div>

        <div class="info-box info-blue">
            <strong><i class="bi bi-info-circle"></i> Role permissions</strong>
            Admin: manage access. Staff: manage lending. Borrower: own records and renewal requests. Only admins can assign roles.
        </div>
    </div>
</div>

<!-- Small script: show the borrower picker only for the Borrower role, and confirm deactivation -->
<script>
var roleSelect = document.getElementById('role');
var borrowerBox = document.getElementById('borrowerLink');

function updateBorrowerBox() {
    borrowerBox.style.display = (roleSelect.value === 'borrower') ? 'block' : 'none';
}
roleSelect.addEventListener('change', updateBorrowerBox);
updateBorrowerBox();

document.getElementById('userForm').addEventListener('submit', function (event) {
    var statusSelect = document.getElementById('status');
    if (statusSelect && this.dataset.originalStatus === 'active' && statusSelect.value === 'inactive') {
        if (!confirm('Deactivate this account? The user will no longer be able to sign in.')) {
            event.preventDefault();
        }
    }
});
</script>

