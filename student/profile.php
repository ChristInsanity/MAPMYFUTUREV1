<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';
require_once '../includes/profile_functions.php';

requireStudent();
ensureProfileEnhancementColumns($conn);

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

    $studentType = sanitize($_POST['student_type'] ?? '');
    $careerPath = sanitize($_POST['career_path'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $goals = sanitize($_POST['goals'] ?? '');
    $dreamJob = sanitize($_POST['dream_job'] ?? '');
    $interests = sanitize($_POST['interests'] ?? '');

    $profileOk = dbExecute(
        $conn,
        "INSERT INTO student_profiles (user_id, student_type, career_path, bio, goals, dream_job, interests)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            student_type = VALUES(student_type),
            career_path = VALUES(career_path),
            bio = VALUES(bio),
            goals = VALUES(goals),
            dream_job = VALUES(dream_job),
            interests = VALUES(interests)",
        "issssss",
        [$userId, $studentType, $careerPath, $bio, $goals, $dreamJob, $interests]
    );
    if ($profileOk && $careerPath !== '') {
        dbExecute($conn, "UPDATE users SET career_path = ? WHERE user_id = ?", "si", [$careerPath, $userId]);
    }

    $noticeSuccess = $identityOk && $photoOk && $profileOk;
    $notice = $noticeSuccess ? 'Profile updated.' : ($photoMessage ?: $identityMessage ?: 'Unable to update profile.');
}

$account = getUserAccount($conn, $userId, 'student');
$profile = dbFetchOne(
    $conn,
    "SELECT * FROM student_profiles WHERE user_id = ?",
    "i",
    [$userId]
) ?? [];
$subscription = getActiveSubscription($conn, $userId);
$avatarUrl = profilePhotoUrl($account['profile_photo'] ?? '');
$initials = profileInitials($account['full_name'] ?? 'Student');

$pageTitle = 'Student Profile';
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
                    <span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10">Student</span>
                    <span class="badge <?= ($account['status'] ?? '') === 'approved' ? 'text-green-200 border-green-500/30 bg-green-500/10' : 'text-yellow-200 border-yellow-500/30 bg-yellow-500/10' ?>">
                        <?= e(ucfirst($account['status'] ?? 'pending')) ?>
                    </span>
                    <?php if ($subscription): ?><span class="badge text-yellow-200 border-yellow-500/30 bg-yellow-500/10">Premium</span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-3 text-sm">
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                <p class="text-slate-400">Career</p>
                <p class="font-bold mt-1"><?= e($profile['career_path'] ?? $account['career_path'] ?? 'Exploring') ?></p>
            </div>
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                <p class="text-slate-400">Readiness</p>
                <p class="font-bold mt-1"><?= (int)($profile['readiness_score'] ?? 0) ?>%</p>
            </div>
        </div>
    </aside>

    <section class="card">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
            <div>
                <p class="text-blue-300 font-semibold mb-1">Account profile</p>
                <h2 class="sectionTitle">Identity and goals</h2>
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
                    <input id="profilePhotoInput" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" class="inputStyle text-sm">
                    <span class="block text-xs text-slate-500 mt-2">JPG, PNG, or WebP.</span>
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
                    <span class="block text-sm text-slate-400 mb-2">Student type</span>
                    <input name="student_type" class="inputStyle" value="<?= e($profile['student_type'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Career path</span>
                    <input name="career_path" class="inputStyle" value="<?= e($profile['career_path'] ?? $account['career_path'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Current interests</span>
                    <input name="interests" class="inputStyle" value="<?= e($profile['interests'] ?? '') ?>">
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Goal or dream role</span>
                    <input name="dream_job" class="inputStyle" value="<?= e($profile['dream_job'] ?? '') ?>">
                </label>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Bio</span>
                    <textarea name="bio" class="inputStyle min-h-[120px]" placeholder="Short profile summary"><?= e($profile['bio'] ?? '') ?></textarea>
                </label>
                <label>
                    <span class="block text-sm text-slate-400 mb-2">Goals</span>
                    <textarea name="goals" class="inputStyle min-h-[120px]" placeholder="What you are working toward"><?= e($profile['goals'] ?? '') ?></textarea>
                </label>
            </div>

            <div class="flex justify-end">
                <button class="primaryBtn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
            </div>
        </form>
    </section>
</div>

<?php include '../footer.php'; ?>
