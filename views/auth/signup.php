<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-wrap">

    <!-- Left side: brand message -->
    <div class="login-left">
        <div class="brand">
            <span class="brand-icon" style="background:rgba(255,255,255,0.12)"><i class="bi bi-book"></i></span>
            <span class="brand-name"><?= APP_NAME ?></span>
        </div>

        <div class="login-tag">YOUR LIBRARY, CONNECTED</div>
        <h1 class="login-heading">A world of knowledge.<br>A simpler way to share.</h1>
        <p class="login-text">Discover resources, keep track of your loans, and make room for your next great idea.</p>

        <div class="books">
            <span style="height:90px;background:#7BA3D9"></span>
            <span style="height:70px;background:#F2D9A8"></span>
            <span style="height:110px;background:#E8EEF7"></span>
            <span style="height:80px;background:#3B82F6"></span>
            <span style="height:100px;background:#94B3D0"></span>
            <span style="height:65px;background:#CBD9EE"></span>
        </div>

        <div class="login-foot">Library / Resource Lending System</div>
    </div>

    <!-- Right side: the form -->
    <div class="login-right">
        <div class="login-form-box">
            <span class="portal-pill">LIBRARY PORTAL</span>
            <h2 class="login-title">Create your account</h2>
            <p class="page-sub mb-4">Register as a borrower to browse resources and manage your loans.</p>

            <?php if (count($errors) > 0): ?>
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?= csrf_field() ?>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="first_name" class="form-label">First name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?= e($form['first_name']) ?>" maxlength="60" required>
                    </div>
                    <div class="col-6">
                        <label for="last_name" class="form-label">Last name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?= e($form['last_name']) ?>" maxlength="60" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address *</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($form['email']) ?>" placeholder="name@example.org" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <div class="password-wrap">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" id="togglePassword" aria-label="Show or hide password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="text-muted" style="font-size:0.72rem">At least 8 characters, with letters and numbers.</div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm password *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-navy w-100 py-2">Create account</button>
            </form>

            <hr class="mt-4">
            <p class="text-center mb-1" style="font-size:0.8rem">Already have an account? <a href="<?= e(route('login')) ?>">Sign in</a></p>
            <p class="text-center text-muted mt-4" style="font-size:0.7rem">© <?= date('Y') ?> <?= APP_NAME ?> · Library services</p>
        </div>
    </div>
</div>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
