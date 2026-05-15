<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireAdmin();
ensureMentorTables($conn);

$applicants = dbFetchAll(
    $conn,
    "SELECT user_id, full_name, email, role, status
     FROM users
     WHERE role IN ('mentor','employer')
     ORDER BY created_at DESC"
);
$pendingTotal = count(array_filter($applicants, fn($row) => $row['status'] === 'pending'));

$pageTitle = 'Verification Center';
$activePage = 'verification';
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Verification Center</h1>
</div>

<div id="adminMessage" class="hidden mb-6 rounded-2xl border p-4"></div>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="sectionTitle">Applicants</h2>
        <span class="text-slate-400 text-sm"><?= (int)$pendingTotal ?> pending</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-slate-400 text-sm border-b border-[#334155]">
                <tr>
                    <th class="py-3 pr-4">Full Name</th>
                    <th class="py-3 pr-4">Email</th>
                    <th class="py-3 pr-4">Role</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applicants as $applicant): ?>
                    <tr class="applicantRow border-b border-[#334155]/60" data-user-id="<?= (int)$applicant['user_id'] ?>" data-role="<?= e($applicant['role']) ?>">
                        <td class="py-4 pr-4 font-semibold"><?= e($applicant['full_name']) ?></td>
                        <td class="py-4 pr-4 text-slate-300"><?= e($applicant['email']) ?></td>
                        <td class="py-4 pr-4"><?= e(ucfirst($applicant['role'])) ?></td>
                        <td class="py-4 pr-4"><span class="statusBadge badge <?= e(statusClass($applicant['status'] === 'approved' ? 'completed' : ($applicant['status'] === 'rejected' ? 'locked' : 'submitted'))) ?>"><?= e(readableStatus($applicant['status'])) ?></span></td>
                        <td class="py-4 pr-4">
                            <div class="flex justify-end gap-2">
                                <button type="button" class="secondaryBtn reviewBtn">Review</button>
                                <?php if ($applicant['status'] === 'pending'): ?>
                                    <button type="button" class="primaryBtn quickApproveBtn">Approve</button>
                                    <button type="button" class="dangerBtn quickRejectBtn">Reject</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($applicants) === 0): ?>
                    <tr><td colspan="5" class="py-8 text-slate-400">No applications yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="reviewModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8 overflow-y-auto">
    <div class="max-w-3xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex justify-between gap-4 mb-5">
            <div>
                <h2 id="reviewName" class="text-2xl font-bold"></h2>
                <p id="reviewEmail" class="text-slate-400"></p>
            </div>
            <button type="button" onclick="closeReviewModal()" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="reviewBody" class="space-y-4 text-slate-300"></div>
        <div id="careerAssignmentPanel" class="hidden mt-5 bg-[#020B24] border border-[#334155] rounded-xl p-4">
            <h3 class="font-bold mb-3">Assign Mentorship Tracks</h3>
            <div id="careerCheckboxes" class="grid sm:grid-cols-2 gap-3"></div>
            <p class="text-slate-500 text-sm mt-3">Mentors can only appear in search, accept enrollments, and create tasks for assigned careers.</p>
        </div>
        <div class="flex flex-wrap gap-3 mt-6">
            <button type="button" id="modalApproveBtn" class="primaryBtn">Approve</button>
            <button type="button" id="modalRejectBtn" class="dangerBtn">Reject</button>
            <a id="modalEmailBtn" class="secondaryBtn" href="mailto:">Write Email</a>
        </div>
    </div>
</div>

<script>
let currentApplicantId = null;
let currentApplicantRole = null;

function showAdminMessage(result) {
    const message = document.getElementById('adminMessage');
    message.className = `mb-6 rounded-2xl border p-4 ${result.success ? 'border-green-500 bg-green-500/10 text-green-200' : 'border-red-500 bg-red-500/10 text-red-200'}`;
    message.textContent = result.message;
    message.classList.remove('hidden');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}

async function loadReview(row) {
    currentApplicantId = row.dataset.userId;
    currentApplicantRole = row.dataset.role;
    const result = await window.mmfPost('ajax_applicant.php', {action: 'review', user_id: currentApplicantId});
    if (!result.success) {
        showAdminMessage(result);
        return;
    }

    const applicant = result.applicant;
    document.getElementById('reviewName').textContent = applicant.full_name;
    document.getElementById('reviewEmail').textContent = `${applicant.email} · ${applicant.role}`;
    document.getElementById('modalEmailBtn').href = `mailto:${applicant.email}?subject=Map%20My%20Future%20Application`;

    const body = document.getElementById('reviewBody');
    if (applicant.role === 'mentor') {
        body.innerHTML = `
            <div class="grid sm:grid-cols-2 gap-3">
                <div><span class="text-slate-500">Degree</span><p>${applicant.degree || ''}</p></div>
                <div><span class="text-slate-500">Specialization</span><p>${applicant.specialization || ''}</p></div>
                <div><span class="text-slate-500">Experience</span><p>${applicant.years_experience || 0} years</p></div>
                <div><span class="text-slate-500">Industry</span><p>${applicant.industry || ''}</p></div>
            </div>
            <div><span class="text-slate-500">Bio</span><p>${applicant.bio || ''}</p></div>
            ${applicant.resume_upload ? `<a class="text-blue-300" href="../${applicant.resume_upload}" target="_blank">View resume</a>` : ''}
            <div><h3 class="font-bold mt-3 mb-2">Certifications</h3>${result.certifications.map(cert => `<a class="block text-blue-300" target="_blank" href="../${cert.file_path}">✓ ${cert.title}</a>`).join('') || '<p class="text-slate-500">No certifications uploaded.</p>'}</div>
        `;
        document.getElementById('careerAssignmentPanel').classList.remove('hidden');
        document.getElementById('careerCheckboxes').innerHTML = result.career_paths.map(path => {
            const checked = result.assigned_careers.some(career => Number(career.career_path_id) === Number(path.path_id));
            return `<label class="bg-[#162338] border border-[#334155] rounded-xl p-3 flex gap-2 items-center"><input type="checkbox" class="careerCheck" value="${path.path_id}" ${checked ? 'checked' : ''}> ${path.title}</label>`;
        }).join('');
    } else {
        body.innerHTML = `
            <div class="grid sm:grid-cols-2 gap-3">
                <div><span class="text-slate-500">Company</span><p>${applicant.company_name || ''}</p></div>
                <div><span class="text-slate-500">Business Email</span><p>${applicant.business_email || ''}</p></div>
                <div><span class="text-slate-500">Industry</span><p>${applicant.employer_industry || ''}</p></div>
                <div><span class="text-slate-500">Company Size</span><p>${applicant.company_size || ''}</p></div>
                <div><span class="text-slate-500">Website</span><p>${applicant.website || ''}</p></div>
                <div><span class="text-slate-500">Registration No.</span><p>${applicant.business_registration_number || ''}</p></div>
                <div><span class="text-slate-500">Contact Person</span><p>${applicant.contact_person || ''}</p></div>
                <div><span class="text-slate-500">Position</span><p>${applicant.contact_position || ''}</p></div>
            </div>
            <div><span class="text-slate-500">Office Address</span><p>${applicant.office_address || ''}</p></div>
            <div class="flex gap-3 flex-wrap">
                ${applicant.business_permit_upload ? `<a class="text-blue-300" href="../${applicant.business_permit_upload}" target="_blank">View business permit</a>` : ''}
                ${applicant.company_profile_pdf ? `<a class="text-blue-300" href="../${applicant.company_profile_pdf}" target="_blank">View company profile</a>` : ''}
            </div>
        `;
        document.getElementById('careerAssignmentPanel').classList.add('hidden');
    }

    document.getElementById('reviewModal').classList.remove('hidden');
}

async function updateApplicant(userId, role, action) {
    const careerIds = role === 'mentor'
        ? Array.from(document.querySelectorAll('.careerCheck:checked')).map(input => input.value)
        : [];
    const result = await window.mmfPost('ajax_applicant.php', {
        action,
        user_id: userId,
        'career_path_ids[]': careerIds
    });
    showAdminMessage(result);
    if (result.success) {
        const row = document.querySelector(`.applicantRow[data-user-id="${userId}"]`);
        row.querySelector('.statusBadge').textContent = action === 'approve' ? 'Approved' : 'Rejected';
        row.querySelectorAll('.quickApproveBtn,.quickRejectBtn').forEach(button => button.remove());
        closeReviewModal();
    }
}

document.querySelectorAll('.reviewBtn').forEach(button => button.addEventListener('click', () => loadReview(button.closest('.applicantRow'))));
document.querySelectorAll('.quickApproveBtn').forEach(button => button.addEventListener('click', () => {
    const row = button.closest('.applicantRow');
    if (row.dataset.role === 'mentor') {
        loadReview(row);
        return;
    }
    updateApplicant(row.dataset.userId, row.dataset.role, 'approve');
}));
document.querySelectorAll('.quickRejectBtn').forEach(button => button.addEventListener('click', () => {
    const row = button.closest('.applicantRow');
    updateApplicant(row.dataset.userId, row.dataset.role, 'reject');
}));
document.getElementById('modalApproveBtn').addEventListener('click', () => updateApplicant(currentApplicantId, currentApplicantRole, 'approve'));
document.getElementById('modalRejectBtn').addEventListener('click', () => updateApplicant(currentApplicantId, currentApplicantRole, 'reject'));
</script>

<?php include '../footer.php'; ?>
