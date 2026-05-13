<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$premium = hasPremiumAccess($conn, $userId);
$mentors = getMentorsForStudentCareer($conn, $userId);
$requests = dbFetchAll(
    $conn,
    "SELECT msr.*, u.full_name AS mentor_name
     FROM mentor_student_requests msr
     JOIN users u ON u.user_id = msr.mentor_id
     WHERE msr.student_id = ?
     ORDER BY msr.created_at DESC",
    "i",
    [$userId]
);

$pageTitle = 'Find Mentor';
$activePage = 'mentors';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Mentors']
];
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Find a mentor</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($profile['career_path']) ?> Mentors</h1>
    <p class="text-slate-400">Only mentors assigned by admin to your career path appear here.</p>
</div>

<?php if (!$premium): ?>
    <div class="card mb-8 border-yellow-500/40">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="sectionTitle mb-2">Premium required</h2>
                <p class="text-slate-400">Mentor enrollment requires Premium. You can still view matching mentors.</p>
            </div>
            <button type="button" onclick="document.getElementById('upgradeModal').classList.remove('hidden')" class="primaryBtn">
                <i class="fa-solid fa-crown"></i>
                Upgrade
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($mentors as $mentor): ?>
        <article class="card mentorCard" data-mentor-id="<?= (int)$mentor['user_id'] ?>">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 rounded-full bg-blue-600 flex items-center justify-center font-bold text-xl">
                    <?= e(strtoupper(substr($mentor['full_name'], 0, 1))) ?>
                </div>
                <div>
                    <h2 class="text-xl font-bold"><?= e($mentor['full_name']) ?></h2>
                    <p class="text-slate-400 text-sm"><?= e($mentor['email']) ?></p>
                </div>
            </div>

            <div class="space-y-2 text-sm text-slate-300 mb-5">
                <p><span class="text-slate-500">Specialization:</span> <?= e($mentor['specialization'] ?? 'Not specified') ?></p>
                <p><span class="text-slate-500">Experience:</span> <?= (int)($mentor['years_experience'] ?? 0) ?> years</p>
                <p><span class="text-slate-500">Mentorship careers:</span> <?= e($mentor['assigned_careers']) ?></p>
            </div>

            <div class="flex gap-3">
                <a href="../mentor/profile.php?id=<?= (int)$mentor['user_id'] ?>" class="secondaryBtn flex-1">
                    View Profile
                </a>
                <?php if ($mentor['request_status']): ?>
                    <span class="badge <?= e(statusClass($mentor['request_status'] === 'accepted' ? 'completed' : 'submitted')) ?> flex-1 justify-center">
                        <?= e(readableStatus($mentor['request_status'])) ?>
                    </span>
                <?php else: ?>
                    <button type="button" class="primaryBtn flex-1 enrollBtn" data-mentor-id="<?= (int)$mentor['user_id'] ?>">
                        Enroll
                    </button>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (count($mentors) === 0): ?>
        <div class="card md:col-span-2 xl:col-span-3">
            <p class="text-slate-400">No approved mentors are assigned to your career path yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php if (count($requests) > 0): ?>
    <section class="card mt-8">
        <h2 class="sectionTitle mb-4">Enrollment Requests</h2>
        <div class="grid md:grid-cols-2 gap-3">
            <?php foreach ($requests as $request): ?>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 flex justify-between gap-3">
                    <span><?= e($request['mentor_name']) ?></span>
                    <span class="badge <?= e(statusClass($request['status'] === 'accepted' ? 'completed' : ($request['status'] === 'rejected' ? 'locked' : 'submitted'))) ?>">
                        <?= e(readableStatus($request['status'])) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<div id="upgradeModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8">
    <div class="max-w-md mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6 text-center">
        <i class="fa-solid fa-crown text-yellow-300 text-4xl mb-4"></i>
        <h2 class="text-2xl font-bold mb-2">Upgrade to enroll</h2>
        <p class="text-slate-400 mb-5">Premium unlocks mentor enrollment and feedback.</p>
        <div class="flex gap-3">
            <a href="subscription.php" class="primaryBtn flex-1">Upgrade</a>
            <button type="button" onclick="document.getElementById('upgradeModal').classList.add('hidden')" class="secondaryBtn flex-1">Cancel</button>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.enrollBtn').forEach(button => {
    button.addEventListener('click', async () => {
        <?php if (!$premium): ?>
        document.getElementById('upgradeModal').classList.remove('hidden');
        return;
        <?php endif; ?>

        button.disabled = true;
        button.textContent = 'Sending...';
        const result = await window.mmfPost('ajax_mentor_request.php', {mentor_id: button.dataset.mentorId});

        if (result.success) {
            button.outerHTML = '<span class="badge text-purple-300 border-purple-500/30 bg-purple-500/10 flex-1 justify-center">Pending</span>';
        } else if (result.requires_premium) {
            document.getElementById('upgradeModal').classList.remove('hidden');
        } else {
            alert(result.message || 'Unable to enroll.');
            button.disabled = false;
            button.textContent = 'Enroll';
        }
    });
});
</script>

<?php include '../footer.php'; ?>
