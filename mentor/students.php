<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$students = getMentorStudentsOverview($conn, $mentorId);
$pendingRequests = array_values(array_filter(getMentorIncomingRequests($conn, $mentorId), fn($request) => $request['status'] === 'pending'));

$activeStudents = array_values(array_filter($students, fn($student) => $student['status'] === 'active'));
$completedStudents = array_values(array_filter($students, fn($student) => $student['status'] === 'completed'));

$pageTitle = 'Mentor Students';
$activePage = 'students';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Students']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Mentor Students</h1>
</div>

<div class="flex flex-wrap gap-3 mb-6">
    <button class="tabBtn primaryBtn" data-tab="active">Active Students</button>
    <button class="tabBtn secondaryBtn" data-tab="pending">Pending Requests</button>
    <button class="tabBtn secondaryBtn" data-tab="completed">Completed Students</button>
</div>

<section id="activeTab" class="tabPanel grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($activeStudents as $student): ?>
        <article class="card">
            <div class="flex justify-between gap-3 mb-4">
                <div class="flex items-start gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-xl bg-blue-600 overflow-hidden flex items-center justify-center text-xl font-bold shrink-0">
                        <?php if (!empty($student['profile_photo'])): ?>
                            <img src="../<?= e($student['profile_photo']) ?>" alt="<?= e($student['full_name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= e(strtoupper(substr($student['full_name'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                    <h2 class="text-xl font-bold"><?= e($student['full_name']) ?></h2>
                    <p class="text-slate-400"><?= e($student['career_path'] ?: 'Career not set') ?></p>
                    <p class="text-sm text-blue-200 mt-1">
                        <?= $student['year_number'] && $student['semester_number'] ? 'Year ' . (int)$student['year_number'] . ' - Semester ' . (int)$student['semester_number'] : 'Year/semester not set' ?>
                    </p>
                    </div>
                </div>
                <span class="badge text-blue-300 border-blue-500/30 bg-blue-500/10"><?= (int)$student['readiness_score'] ?>%</span>
            </div>
            <div class="bg-slate-950 h-2 rounded-full overflow-hidden mb-3">
                <div class="h-full bg-blue-500" style="width:<?= (int)$student['readiness_score'] ?>%"></div>
            </div>
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3 mb-4">
                <div class="flex justify-between gap-3 text-sm mb-2">
                    <span class="text-slate-400"><?= e($student['subject_code'] ?: 'Current subject') ?></span>
                    <span class="font-bold text-blue-200"><?= (int)$student['roadmap_progress'] ?>%</span>
                </div>
                <div class="bg-slate-900 h-2 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500" style="width:<?= (int)$student['roadmap_progress'] ?>%"></div>
                </div>
                <p class="text-xs text-slate-500 mt-2"><?= e($student['subject_title'] ?: 'Roadmap progress') ?></p>
            </div>
            <p class="text-slate-500 text-sm mb-4">Latest activity: <?= $student['latest_activity'] ? e(date('M d, Y', strtotime($student['latest_activity']))) : 'No activity yet' ?></p>
            <div class="flex flex-wrap gap-2 mb-4">
                <?php if ((int)$student['unread_messages'] > 0): ?><span class="badge text-yellow-200 border-yellow-500/30 bg-yellow-500/10"><?= (int)$student['unread_messages'] ?> unread</span><?php endif; ?>
                <?php if ((int)$student['pending_submissions'] > 0): ?><span class="badge text-purple-200 border-purple-500/30 bg-purple-500/10"><?= (int)$student['pending_submissions'] ?> pending submissions</span><?php endif; ?>
            </div>
            <a href="student_workspace.php?student_id=<?= (int)$student['student_id'] ?>" class="primaryBtn w-full px-3 py-2 text-sm">Open Workspace</a>
        </article>
    <?php endforeach; ?>
    <?php if (count($activeStudents) === 0): ?><div class="card md:col-span-2 xl:col-span-3 text-slate-400">No active students yet.</div><?php endif; ?>
</section>

<section id="pendingTab" class="tabPanel hidden grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($pendingRequests as $request): ?>
        <article class="card">
            <h2 class="text-xl font-bold mb-1"><?= e($request['full_name']) ?></h2>
            <p class="text-slate-400 mb-4"><?= e($request['career_path'] ?: 'Career not set') ?></p>
            <div class="flex gap-2">
                <button class="primaryBtn requestAction" data-id="<?= (int)$request['request_id'] ?>" data-action="accepted">Accept</button>
                <button class="dangerBtn requestAction" data-id="<?= (int)$request['request_id'] ?>" data-action="rejected">Reject</button>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (count($pendingRequests) === 0): ?><div class="card md:col-span-2 xl:col-span-3 text-slate-400">No pending requests.</div><?php endif; ?>
</section>

<section id="completedTab" class="tabPanel hidden grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($completedStudents as $student): ?>
        <article class="card">
            <h2 class="text-xl font-bold mb-1"><?= e($student['full_name']) ?></h2>
            <p class="text-slate-400 mb-4"><?= e($student['career_path'] ?: 'Career not set') ?></p>
            <span class="badge text-green-300 border-green-500/30 bg-green-500/10">Completed</span>
        </article>
    <?php endforeach; ?>
    <?php if (count($completedStudents) === 0): ?><div class="card md:col-span-2 xl:col-span-3 text-slate-400">No completed students yet.</div><?php endif; ?>
</section>

<script>
document.querySelectorAll('.tabBtn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tabBtn').forEach(item => item.className = 'tabBtn secondaryBtn');
        button.className = 'tabBtn primaryBtn';
        document.querySelectorAll('.tabPanel').forEach(panel => panel.classList.add('hidden'));
        document.getElementById(`${button.dataset.tab}Tab`).classList.remove('hidden');
    });
});

document.querySelectorAll('.requestAction').forEach(button => {
    button.addEventListener('click', async () => {
        const result = await window.mmfPost('ajax_enrollment.php', {request_id: button.dataset.id, status: button.dataset.action});
        if (result.success) {
            button.closest('article').remove();
        } else {
            alert(result.message || 'Unable to update request.');
        }
    });
});
</script>

<?php include '../footer.php'; ?>
