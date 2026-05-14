<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';
require_once '../includes/profile_functions.php';

requireAdmin();

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

    $noticeSuccess = $identityOk && $photoOk;
    $notice = $noticeSuccess ? 'Profile updated.' : ($photoMessage ?: $identityMessage ?: 'Unable to update profile.');
}

$account = getUserAccount($conn, $userId, 'admin');
$avatarUrl = profilePhotoUrl($account['profile_photo'] ?? '');
$initials = profileInitials($account['full_name'] ?? 'Admin');

$pageTitle = 'Admin Profile';
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
                <h1 class="text-2xl font-bold truncate"><?= e($account['full_name']) ?></h1>
                <p class="text-slate-400 truncate"><?= e($account['email']) ?></p>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10">Admin</span>
                    <span class="badge text-green-200 border-green-500/30 bg-green-500/10"><?= e(ucfirst($account['status'] ?? 'approved')) ?></span>
                </div>
            </div>
        </div>
    </aside>

    <section class="card">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
            <div>
                <p class="text-blue-300 font-semibold mb-1">Admin account</p>
                <h2 class="sectionTitle">Identity</h2>
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
            <div class="flex justify-end">
                <button class="primaryBtn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
            </div>
        </form>
    </section>
</div>

<?php include '../footer.php'; ?>
