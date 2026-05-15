<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();
ensureMentorTables($conn);

$userId = (int)$_SESSION['user_id'];
$assignments = dbFetchAll(
    $conn,
    "SELECT ma.assignment_id, ma.mentor_id, ma.status, ma.assigned_at,
            u.full_name, u.email, u.profile_photo,
            mp.specialization, mp.industry, mp.years_experience,
            COUNT(DISTINCT mt.mentor_task_id) AS assigned_tasks,
            COUNT(DISTINCT CASE WHEN mts.status = 'submitted' THEN mts.submission_id END) AS submitted_tasks,
            COUNT(DISTINCT CASE WHEN mts.status = 'approved' THEN mts.submission_id END) AS reviewed_tasks,
            MAX(COALESCE(mts.reviewed_at, mts.submitted_at, mt.created_at, ma.assigned_at)) AS latest_activity
     FROM mentor_assignments ma
     JOIN users u ON u.user_id = ma.mentor_id
     LEFT JOIN mentor_profiles mp ON mp.user_id = ma.mentor_id
     LEFT JOIN mentor_students ms ON ms.mentor_id = ma.mentor_id AND ms.student_id = ma.student_id AND ms.status = 'active'
     LEFT JOIN mentor_tasks mt ON mt.mentor_id = ma.mentor_id
        AND mt.subject_id = ms.subject_id
        AND (mt.assigned_student_id IS NULL OR mt.assigned_student_id = ma.student_id)
     LEFT JOIN mentor_task_submissions mts ON mts.mentor_task_id = mt.mentor_task_id AND mts.student_id = ma.student_id
     WHERE ma.student_id = ? AND ma.status = 'active'
     GROUP BY ma.assignment_id, ma.mentor_id, ma.status, ma.assigned_at,
              u.full_name, u.email, u.profile_photo, mp.specialization, mp.industry, mp.years_experience
     ORDER BY latest_activity DESC",
    "i",
    [$userId]
);
$requests = array_values(array_filter(getStudentMentorRequests($conn, $userId), fn($request) => $request['status'] === 'pending'));
$tasks = getAvailableMentorTasksForStudent($conn, $userId);

$pageTitle = 'My Mentor';
$activePage = 'my_mentor';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'My Mentor']
];
include '../header.php';
?>

<div class="mb-8">
    <div>
        <h1 class="text-3xl lg:text-4xl font-bold mb-2">My Mentor</h1>
    </div>
</div>

<section class="grid lg:grid-cols-3 gap-5 mb-8">
    <?php foreach ($assignments as $mentor): ?>
        <article class="card flex flex-col">
            <div class="flex items-start gap-4 mb-5">
                <div class="w-14 h-14 rounded-full bg-blue-600 overflow-hidden flex items-center justify-center text-xl font-bold shrink-0">
                    <?php if (!empty($mentor['profile_photo'])): ?>
                        <img src="../<?= e($mentor['profile_photo']) ?>" alt="<?= e($mentor['full_name']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= e(strtoupper(substr($mentor['full_name'], 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <h2 class="text-xl font-bold truncate"><?= e($mentor['full_name']) ?></h2>
                    <p class="text-slate-400 text-sm truncate"><?= e($mentor['specialization'] ?: 'Assigned mentor') ?></p>
                    <p class="text-slate-500 text-sm"><?= e($mentor['industry'] ?: 'Industry not set') ?></p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-5 text-center">
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-2xl font-bold"><?= (int)$mentor['assigned_tasks'] ?></p>
                    <p class="text-xs text-slate-400">Tasks</p>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-2xl font-bold"><?= (int)$mentor['submitted_tasks'] ?></p>
                    <p class="text-xs text-slate-400">Submitted</p>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-2xl font-bold"><?= (int)$mentor['reviewed_tasks'] ?></p>
                    <p class="text-xs text-slate-400">Reviewed</p>
                </div>
            </div>

            <p class="text-sm text-slate-500 mb-5 grow">
                Latest activity:
                <?= $mentor['latest_activity'] ? e(date('M d, Y', strtotime($mentor['latest_activity']))) : 'No activity yet' ?>
            </p>

            <div class="grid grid-cols-3 gap-2">
                <a href="mentor_room.php?id=<?= (int)$mentor['mentor_id'] ?>" class="primaryBtn px-3 py-2 text-sm">Room</a>
                <a href="mentor_tasks.php" class="secondaryBtn px-3 py-2 text-sm">Tasks</a>
                <a href="../mentor/profile.php?id=<?= (int)$mentor['mentor_id'] ?>" class="secondaryBtn px-3 py-2 text-sm">Profile</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (count($assignments) === 0): ?>
        <div class="card lg:col-span-3">
            <h2 class="sectionTitle mb-2">No assigned mentor yet</h2>
        </div>
    <?php endif; ?>
</section>

<?php if (count($requests) > 0): ?>
<section class="card mb-8">
    <h2 class="sectionTitle mb-4">Pending Requests</h2>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($requests as $request): ?>
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-bold"><?= e($request['mentor_name']) ?></p>
                        <p class="text-sm text-slate-400"><?= e($request['subject_title']) ?></p>
                    </div>
                    <span class="badge text-purple-300 border-purple-500/30 bg-purple-500/10">Pending</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="card">
    <div class="flex items-center justify-between gap-4 mb-5">
        <h2 class="sectionTitle">Submitted Tasks & Feedback</h2>
        <a href="mentor_tasks.php" class="secondaryBtn px-3 py-2 text-sm">View All</a>
    </div>
    <div class="grid lg:grid-cols-2 gap-4">
        <?php foreach (array_slice($tasks, 0, 4) as $task): ?>
            <article class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <h3 class="font-bold"><?= e($task['title']) ?></h3>
                        <p class="text-sm text-slate-400"><?= e($task['mentor_name']) ?> - <?= e($task['subject_title']) ?></p>
                    </div>
                    <span class="badge <?= e(statusClass($task['submission_status'] ? ($task['submission_status'] === 'approved' ? 'completed' : 'submitted') : 'available')) ?>">
                        <?= e($task['submission_status'] ? readableStatus($task['submission_status']) : 'Pending') ?>
                    </span>
                </div>
                <?php if ($task['comment']): ?>
                    <p class="text-sm text-blue-200 bg-blue-500/10 border border-blue-500/20 rounded-xl p-3"><?= e($task['comment']) ?></p>
                <?php else: ?>
                    <p class="text-sm text-slate-500"><?= $task['deadline'] ? 'Due ' . e(date('M d, Y', strtotime($task['deadline']))) : 'No deadline' ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (count($tasks) === 0): ?>
            <p class="text-slate-400 lg:col-span-2">No mentor tasks yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php include '../footer.php'; ?>
