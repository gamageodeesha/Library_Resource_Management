</main>

    <footer class="page-footer">
        <span><?= APP_NAME ?> · Library / Resource Lending System</span>
        <span>Today · <?= date('d M Y') ?></span>
    </footer>
</div>

<!-- Logout confirmation popup -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:12px;border:1px solid var(--border)">
            <div class="modal-body text-center p-4">
                <div class="mb-2" style="font-size:2rem;color:var(--navy)"><i class="bi bi-box-arrow-right"></i></div>
                <h5 class="card-title-text" id="logoutModalLabel">Sign out?</h5>
                <p class="text-muted mb-4" style="font-size:0.85rem">You will need to sign in again to continue.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Stay signed in</button>
                    <a href="<?= url('logout.php') ?>" class="btn btn-navy">Sign out</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>