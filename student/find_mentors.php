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

$pageTitle = 'Find Mentors';
$activePage = 'find_mentor';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Find Mentors']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($profile['career_path'] ?? 'Career') ?> mentors</h1>
</div>

<div class="card mb-6">
    <label class="block text-sm text-slate-400 mb-2">Search mentors</label>
    <input id="mentorSearch" class="inputStyle" placeholder="Search by name, specialization, industry, or career expertise">
</div>

<?php if (!$premium): ?>
<div class="card mb-8 border-yellow-500/40">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="sectionTitle mb-2">Premium unlocks mentor enrollment</h2>
            <p class="text-slate-400">You can view mentor profiles now. Request mentor access after upgrading.</p>
        </div>
        <button type="button" class="primaryBtn" data-open-upgrade><i class="fa-solid fa-crown"></i> Upgrade</button>
    </div>
</div>
<?php endif; ?>

<div id="mentorGrid" class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($mentors as $mentor): ?>
        <?php
        $requestStatus = $mentor['request_status'] ?? '';
        $portfolioLink = $mentor['portfolio_url'] ?: ($mentor['github_url'] ?: ($mentor['linkedin_url'] ?: ''));
        $capacity = max(1, (int)($mentor['max_student_capacity'] ?? 10));
        $activeStudents = (int)$mentor['total_students'];
        $isAvailable = $activeStudents < $capacity;
        $searchText = strtolower(implode(' ', [
            $mentor['full_name'] ?? '',
            $mentor['specialization'] ?? '',
            $mentor['industry'] ?? '',
            $mentor['assigned_careers'] ?? ''
        ]));
        ?>
        <article class="card flex flex-col mentorCard" data-mentor-id="<?= (int)$mentor['user_id'] ?>" data-search="<?= e($searchText) ?>">
            <div class="flex items-start gap-4 mb-5">
                <div class="w-16 h-16 rounded-full bg-blue-600 overflow-hidden flex items-center justify-center text-2xl font-bold shrink-0">
                    <?php if (!empty($mentor['profile_photo'])): ?>
                        <img src="../<?= e($mentor['profile_photo']) ?>" alt="<?= e($mentor['full_name']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= e(strtoupper(substr($mentor['full_name'], 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <h2 class="text-xl font-bold truncate"><?= e($mentor['full_name']) ?></h2>
                    <p class="text-slate-400 text-sm truncate"><?= e($mentor['email']) ?></p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="badge text-green-200 border-green-500/30 bg-green-500/10"><i class="fa-solid fa-circle-check"></i> Career match</span>
                        <?php if ((int)($mentor['is_premium_mentor'] ?? 0) === 1): ?><span class="badge text-yellow-200 border-yellow-500/30 bg-yellow-500/10"><i class="fa-solid fa-crown"></i> Premium</span><?php endif; ?>
                        <span class="badge <?= $isAvailable ? 'text-blue-200 border-blue-500/30 bg-blue-500/10' : 'text-slate-300 border-slate-500/30 bg-slate-500/10' ?>">
                            <?= $activeStudents ?>/<?= $capacity ?> students
                        </span>
                    </div>
                </div>
            </div>

            <div class="space-y-3 text-sm text-slate-300 mb-5 grow">
                <p><span class="text-slate-500">Specialization:</span> <?= e($mentor['specialization'] ?: 'Not specified') ?></p>
                <p><span class="text-slate-500">Industry:</span> <?= e($mentor['industry'] ?: 'Not specified') ?></p>
                <p><span class="text-slate-500">Experience:</span> <?= (int)($mentor['years_experience'] ?? 0) ?> years</p>
                <p><span class="text-slate-500">Mentorship careers:</span> <?= e($mentor['assigned_careers']) ?></p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array_filter(array_map('trim', explode(',', (string)$mentor['assigned_careers']))) as $tag): ?>
                        <span class="badge text-cyan-200 border-cyan-500/30 bg-cyan-500/10"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Portfolio Preview</p>
                    <?php if ($portfolioLink): ?>
                        <a href="<?= e($portfolioLink) ?>" target="_blank" class="text-blue-200 break-all"><?= e($portfolioLink) ?></a>
                    <?php else: ?>
                        <p class="text-slate-500">No public link added yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <a href="../mentor/profile.php?id=<?= (int)$mentor['user_id'] ?>" class="secondaryBtn px-3 py-2 text-sm">View</a>
                <?php if ($requestStatus): ?>
                    <span class="badge justify-center <?= e(statusClass($requestStatus === 'accepted' ? 'completed' : ($requestStatus === 'rejected' ? 'locked' : 'submitted'))) ?>">
                        <?= e(readableStatus($requestStatus)) ?>
                    </span>
                <?php else: ?>
                    <?php if ($isAvailable): ?>
                        <button type="button" class="primaryBtn px-3 py-2 text-sm enrollBtn" data-mentor-id="<?= (int)$mentor['user_id'] ?>">Request</button>
                    <?php else: ?>
                        <span class="badge justify-center text-slate-300 border-slate-500/30 bg-slate-500/10">Unavailable</span>
                    <?php endif; ?>
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

<div id="upgradeModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8">
    <div class="max-w-md mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6 text-center">
        <i class="fa-solid fa-crown text-yellow-300 text-4xl mb-4"></i>
        <h2 class="text-2xl font-bold mb-2">Upgrade to enroll</h2>
        <p class="text-slate-400 mb-5">Premium unlocks mentor enrollment, mentor feedback, and portfolio reviews.</p>
        <div class="flex gap-3">
            <a href="subscription.php" class="primaryBtn flex-1">Upgrade</a>
            <button type="button" id="closeUpgrade" class="secondaryBtn flex-1">Cancel</button>
        </div>
    </div>
</div>

<script>
const upgradeModal = document.getElementById('upgradeModal');
const mentorSearch = document.getElementById('mentorSearch');
mentorSearch?.addEventListener('input', () => {
    const query = mentorSearch.value.trim().toLowerCase();
    document.querySelectorAll('.mentorCard').forEach(card => {
        card.classList.toggle('hidden', query && !card.dataset.search.includes(query));
    });
});

document.querySelectorAll('[data-open-upgrade]').forEach(button => {
    button.addEventListener('click', () => upgradeModal.classList.remove('hidden'));
});
document.getElementById('closeUpgrade')?.addEventListener('click', () => upgradeModal.classList.add('hidden'));

document.querySelectorAll('.enrollBtn').forEach(button => {
    button.addEventListener('click', async () => {
        <?php if (!$premium): ?>
        upgradeModal.classList.remove('hidden');
        return;
        <?php endif; ?>

        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        const result = await window.mmfPost('ajax_mentor_request.php', {mentor_id: button.dataset.mentorId});

        if (result.success) {
            button.outerHTML = '<span class="badge justify-center text-purple-300 border-purple-500/30 bg-purple-500/10">Pending</span>';
        } else if (result.requires_premium) {
            upgradeModal.classList.remove('hidden');
        } else {
            alert(result.message || 'Unable to enroll.');
            button.disabled = false;
            button.textContent = 'Request';
        }
    });
});
</script>

<?php include '../footer.php'; ?>
