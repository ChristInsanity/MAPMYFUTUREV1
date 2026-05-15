<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';
require_once '../includes/profile_functions.php';

requireEmployer();
ensureMentorTables($conn);

$userId = (int)$_SESSION['user_id'];
$notice = null;
$noticeSuccess = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    [$identityOk, $identityMessage] = updateUserIdentity(
        $conn,
        $userId,
        $_POST['full_name'] ?? '',
        $_POST['email'] ?? ''
    );
    [$photoOk, $photoPath, $photoMessage] = saveProfilePhotoUpload($conn, $userId);

    $companyName = sanitize($_POST['company_name'] ?? '');
    $businessEmail = sanitize($_POST['business_email'] ?? '');
    $industry = sanitize($_POST['industry'] ?? '');
    $companySize = sanitize($_POST['company_size'] ?? '');
    $website = sanitize($_POST['website'] ?? '');
    $contactPerson = sanitize($_POST['contact_person'] ?? '');
    $contactPosition = sanitize($_POST['contact_position'] ?? '');
    $contactNumber = sanitize($_POST['contact_number'] ?? '');
    $officeAddress = sanitize($_POST['office_address'] ?? '');

    $profileOk = $companyName !== '' && dbExecute(
        $conn,
        "INSERT INTO employer_profiles
         (user_id, company_name, business_email, industry, company_size, website, contact_person, contact_position, contact_number, office_address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            company_name = VALUES(company_name),
            business_email = VALUES(business_email),
            industry = VALUES(industry),
            company_size = VALUES(company_size),
            website = VALUES(website),
            contact_person = VALUES(contact_person),
            contact_position = VALUES(contact_position),
            contact_number = VALUES(contact_number),
            office_address = VALUES(office_address)",
        "isssssssss",
        [$userId, $companyName, $businessEmail, $industry, $companySize, $website, $contactPerson, $contactPosition, $contactNumber, $officeAddress]
    );

    $noticeSuccess = $identityOk && $photoOk && $profileOk;
    $notice = $noticeSuccess ? 'Profile updated.' : ($companyName === '' ? 'Company name is required.' : ($photoMessage ?: $identityMessage ?: 'Unable to update profile.'));
}

$account = getUserAccount($conn, $userId, 'employer');
$profile = dbFetchOne($conn, "SELECT * FROM employer_profiles WHERE user_id = ?", "i", [$userId]) ?? [];
$avatarUrl = profilePhotoUrl($account['profile_photo'] ?? '');
$initials = profileInitials($account['full_name'] ?? 'Employer');

$pageTitle = 'Employer Profile';
$activePage = 'profile';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Profile']
];
include '../header.php';
?>

<div class="grid xl:grid-cols-[360px_minmax(0,1fr)] gap-6">
    <aside class="card h-max">
        <div class="flex items-start gap-4">
            <div class="w-20 h-20 rounded-2xl bg-blue-600 flex items-center justify-center text-2xl font-bold overflow-hidden shrink-0">
                <?php if ($avatarUrl): ?>
                    <img src="<?= e($avatarUrl) ?>" alt="<?= e($account['full_name']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <h1 class="text-2xl font-bold truncate"><?= e($profile['company_name'] ?? $account['full_name']) ?></h1>
                <p class="text-slate-400 truncate"><?= e($profile['industry'] ?? 'Employer') ?></p>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10">Employer</span>
                    <span class="badge <?= ($profile['verification_status'] ?? $account['status'] ?? '') === 'approved' ? 'text-green-200 border-green-500/30 bg-green-500/10' : 'text-yellow-200 border-yellow-500/30 bg-yellow-500/10' ?>">
                        <?= e(ucfirst($profile['verification_status'] ?? $account['status'] ?? 'pending')) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="mt-6 space-y-3 text-sm">
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                <p class="text-slate-400">Business email</p>
                <p class="font-bold mt-1 break-all"><?= e($profile['business_email'] ?? $account['email']) ?></p>
            </div>
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                <p class="text-slate-400">Contact</p>
                <p class="font-bold mt-1"><?= e($profile['contact_person'] ?? 'Not set') ?></p>
            </div>
        </div>
    </aside>

    <section class="card">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
            <div>
                <h2 class="sectionTitle">Company and contact details</h2>
            </div>
            <?php if ($notice): ?>
                <div class="rounded-xl border px-4 py-3 text-sm <?= profileNoticeClass($noticeSuccess) ?>"><?= e($notice) ?></div>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_input() ?>
            <div class="grid lg:grid-cols-[220px_minmax(0,1fr)] gap-6">
                <label class="block">
                    <span class="block text-sm text-slate-400 mb-2">Profile photo</span>
                    <input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" class="inputStyle text-sm">
                </label>
                <div class="grid md:grid-cols-2 gap-4">
                    <label>
                        <span class="block text-sm text-slate-400 mb-2">Full name</span>
                        <input name="full_name" class="inputStyle" value="<?= e($account['full_name'] ?? '') ?>" required>
                    </label>
                    <label>
                        <span class="block text-sm text-slate-400 mb-2">Email</span>
                        <input type="email" name="email" class="inputStyle" value="<?= e($account['email'] ?? '') ?>" required>
                    </label>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Company name</span>
                    <input name="company_name" class="inputStyle" value="<?= e($profile['company_name'] ?? '') ?>" required>
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Business email</span>
                    <input type="email" name="business_email" class="inputStyle" value="<?= e($profile['business_email'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Industry</span>
                    <input name="industry" class="inputStyle" value="<?= e($profile['industry'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Company size</span>
                    <input name="company_size" class="inputStyle" value="<?= e($profile['company_size'] ?? '') ?>">
                </label>
                <label class="md:col-span-2">
                    <span class="block text-sm text-slate-400 mb-2">Website</span>
                    <input type="url" name="website" class="inputStyle" value="<?= e($profile['website'] ?? '') ?>">
                </label>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Contact person</span>
                    <input name="contact_person" class="inputStyle" value="<?= e($profile['contact_person'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Contact position</span>
                    <input name="contact_position" class="inputStyle" value="<?= e($profile['contact_position'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Contact number</span>
                    <input name="contact_number" class="inputStyle" value="<?= e($profile['contact_number'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Office address</span>
                    <input name="office_address" class="inputStyle" value="<?= e($profile['office_address'] ?? '') ?>">
                </label>
            </div>

            <div class="flex justify-end">
                <button class="primaryBtn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
            </div>
        </form>
    </section>
</div>

<?php include '../footer.php'; ?>
