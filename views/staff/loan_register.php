<div class="page-head">
    <div>
        <h1 class="page-title">Register a loan</h1>
        <p class="page-sub">Complete borrowing at the library desk. All required details must be verified before saving.</p>
    </div>
    <a href="<?= e(route('staff/loans')) ?>" class="btn btn-soft"><i class="bi bi-arrow-left"></i> Back to loans</a>
</div>

<div class="row g-3">

    <!-- The form -->
    <div class="col-12 col-xl-8">
        <div class="card-box">
            <h2 class="card-title-text">Loan details</h2>
            <p class="card-sub">New loan · Not yet registered</p>

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

                <!-- Borrower -->
                <div class="mb-3">
                    <label for="borrower_id" class="form-label">Borrower *</label>
                    <select class="form-select" id="borrower_id" name="borrower_id" required>
                        <option value="">Select a borrower</option>
                        <?php foreach ($borrowers as $b): ?>
                            <option value="<?= (int)$b['borrower_id'] ?>"
                                    data-name="<?= e($b['first_name'] . ' ' . $b['last_name']) ?>"
                                    data-code="<?= e($b['borrower_code']) ?>"
                                    data-email="<?= e($b['email']) ?>"
                                    data-loans="<?= (int)$b['active_loans'] ?>"
                                    <?= $values['borrower_id'] == $b['borrower_id'] ? 'selected' : '' ?>>
                                <?= e($b['borrower_code'] . ' — ' . $b['first_name'] . ' ' . $b['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Select an active borrower record.</div>
                    <div class="info-box info-blue" id="borrower-info" style="display:none"></div>
                </div>

                <!-- Resource -->
                <div class="mb-3">
                    <label for="resource_id" class="form-label">Resource *</label>
                    <select class="form-select" id="resource_id" name="resource_id" required>
                        <option value="">Select an available resource</option>
                        <?php foreach ($resources as $r): ?>
                            <option value="<?= (int)$r['resource_id'] ?>"
                                    data-title="<?= e($r['title']) ?>"
                                    data-code="<?= e($r['resource_code']) ?>"
                                    data-author="<?= e($r['author']) ?>"
                                    data-location="<?= e($r['location']) ?>"
                                    data-category="<?= e($r['category_name']) ?>"
                                    <?= $values['resource_id'] == $r['resource_id'] ? 'selected' : '' ?>>
                                <?= e($r['resource_code'] . ' — ' . $r['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Only resources without an active loan are listed.</div>
                    <div class="info-box" id="resource-info" style="display:none;background:#F0FDF4;color:#166534;border:1px solid #BBF7D0"></div>
                </div>

                <!-- Dates -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="loan_date" class="form-label">Loan date *</label>
                        <input type="date" class="form-control" id="loan_date" name="loan_date"
                               value="<?= e($values['loan_date']) ?>" max="<?= e($today) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="due_date" class="form-label">Due date *</label>
                        <input type="date" class="form-control" id="due_date" name="due_date"
                               value="<?= e($values['due_date']) ?>" required>
                        <div class="form-text">Due date must be after the loan date.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg"></i> Register loan</button>
                <a href="<?= e(route('staff/loans')) ?>" class="btn btn-soft">Cancel</a>
                <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">Saving creates an active loan and makes the resource unavailable for other borrowers.</p>
            </form>
        </div>
    </div>

    <!-- Right-hand panels -->
    <div class="col-12 col-xl-4">
        <div class="card-box mb-3" style="height:auto">
            <h2 class="card-title-text">Review before registering</h2>
            <div class="my-2"><?= badge('Draft loan', 'gray') ?></div>
            <div class="kv"><span>Borrower</span><span id="sum-borrower">—</span></div>
            <div class="kv"><span>Resource</span><span id="sum-resource">—</span></div>
            <div class="kv"><span>Author</span><span id="sum-author">—</span></div>
            <div class="kv"><span>Loan date</span><span id="sum-loan">—</span></div>
            <div class="kv"><span>Due date</span><span id="sum-due">—</span></div>
            <div class="kv"><span>Loan period</span><span id="sum-period">—</span></div>
        </div>

        <div class="card-box mb-3" style="height:auto">
            <h2 class="card-title-text">After you register</h2>
            <div class="kv mt-2"><span>Loan status</span><span>New → Active</span></div>
            <div class="kv"><span>Resource status</span><span>Available → On loan</span></div>
            <div class="kv"><span>Recorded by</span><span><?= e($_SESSION['full_name']) ?></span></div>
        </div>

        <div class="info-box info-blue" style="margin-top:0">
            <strong><i class="bi bi-info-circle"></i> Staff-only lending</strong>
            Borrowers cannot register their own loans. Confirm the physical resource and borrower details at the desk.
        </div>
    </div>
</div>

<!-- Small script: fills the review panel and the due date as you type -->
<script>
var loanPeriod = <?= (int)$period ?>;
var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

var borrowerSelect = document.getElementById('borrower_id');
var resourceSelect = document.getElementById('resource_id');
var loanDateInput  = document.getElementById('loan_date');
var dueDateInput   = document.getElementById('due_date');

// 2026-10-08  ->  08 Oct 2026
function showDate(text) {
    if (!text) { return '—'; }
    var parts = text.split('-');
    return parts[2] + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
}

// Date object -> 2026-10-08
function toInputDate(d) {
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + month + '-' + day;
}

function updateSummary() {
    var b = borrowerSelect.options[borrowerSelect.selectedIndex];
    var r = resourceSelect.options[resourceSelect.selectedIndex];
    var infoB = document.getElementById('borrower-info');
    var infoR = document.getElementById('resource-info');

    // Borrower
    if (borrowerSelect.value) {
        document.getElementById('sum-borrower').textContent = b.dataset.name + ' · ' + b.dataset.code;
        infoB.style.display = 'block';
        infoB.textContent = b.dataset.name + ' · Active member · ' + b.dataset.email + ' · ' + b.dataset.loans + ' active loans';
    } else {
        document.getElementById('sum-borrower').textContent = '—';
        infoB.style.display = 'none';
    }

    // Resource
    if (resourceSelect.value) {
        document.getElementById('sum-resource').textContent = r.dataset.title + ' · ' + r.dataset.code;
        document.getElementById('sum-author').textContent = r.dataset.author;
        infoR.style.display = 'block';
        infoR.textContent = 'Availability checked · Eligible. ' + r.dataset.code + ' is Available. Shelf ' + r.dataset.location + ' · ' + r.dataset.category + '.';
    } else {
        document.getElementById('sum-resource').textContent = '—';
        document.getElementById('sum-author').textContent = '—';
        infoR.style.display = 'none';
    }

    // Dates
    document.getElementById('sum-loan').textContent = showDate(loanDateInput.value);
    document.getElementById('sum-due').textContent = showDate(dueDateInput.value);

    if (loanDateInput.value && dueDateInput.value) {
        var days = Math.round((new Date(dueDateInput.value) - new Date(loanDateInput.value)) / 86400000);
        document.getElementById('sum-period').textContent = days + ' days';
    } else {
        document.getElementById('sum-period').textContent = '—';
    }
}

// When the loan date changes, move the due date to loan date + loan period
loanDateInput.addEventListener('change', function () {
    if (loanDateInput.value) {
        var d = new Date(loanDateInput.value + 'T00:00:00');
        d.setDate(d.getDate() + loanPeriod);
        dueDateInput.value = toInputDate(d);
    }
    updateSummary();
});

borrowerSelect.addEventListener('change', updateSummary);
resourceSelect.addEventListener('change', updateSummary);
dueDateInput.addEventListener('change', updateSummary);

updateSummary();
</script>

