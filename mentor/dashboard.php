<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$assignedCareers = getMentorCareerAssignments($conn, $mentorId);
$pendingRequests = dbFetchOne($conn, "SELECT COUNT(*) AS total FROM mentor_student_requests WHERE mentor_id = ? AND status = 'pending'", "i", [$mentorId]);
$activeStudents = dbFetchOne($conn, "SELECT COUNT(DISTINCT student_id) AS total FROM mentor_students WHERE mentor_id = ? AND status = 'active'", "i", [$mentorId]);
$tasksCreated = dbFetchOne($conn, "SELECT COUNT(*) AS total FROM mentor_tasks WHERE mentor_id = ?", "i", [$mentorId]);
$pendingSubmissions = dbFetchOne(
    $conn,
    "SELECT COUNT(*) AS total
     FROM mentor_task_submissions mts
     JOIN mentor_tasks mt ON mt.mentor_task_id = mts.mentor_task_id
     WHERE mt.mentor_id = ? AND mts.status = 'submitted'",
    "i",
    [$mentorId]
);
$assignedSubjects = dbFetchAll(
    $conn,
    "SELECT DISTINCT cs.subject_title, cs.subject_code
     FROM mentor_students ms
     JOIN career_subjects cs ON cs.subject_id = ms.subject_id
     WHERE ms.mentor_id = ? AND ms.status = 'active'
     ORDER BY cs.subject_title",
    "i",
    [$mentorId]
);

$pageTitle = 'Mentor Dashboard';
$activePage = 'dashboard';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Mentor workspace</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Welcome, <?= e($_SESSION['name'] ?? 'Mentor') ?></h1>
    <p class="text-slate-400">Manage requests, create lesson-linked tasks, and review submissions.</p>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <div class="statCard"><p class="text-slate-400 mb-2">Assigned Careers</p><h2 class="text-3xl font-bold"><?= count($assignedCareers) ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Pending Requests</p><h2 class="text-3xl font-bold"><?= (int)$pendingRequests['total'] ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Active Students</p><h2 class="text-3xl font-bold"><?= (int)$activeStudents['total'] ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Tasks Created</p><h2 class="text-3xl font-bold"><?= (int)$tasksCreated['total'] ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Pending Submissions</p><h2 class="text-3xl font-bold"><?= (int)$pendingSubmissions['total'] ?></h2></div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="card lg:col-span-2">
        <h2 class="sectionTitle mb-4">Assigned Careers</h2>
        <div class="grid md:grid-cols-2 gap-3">
            <?php foreach ($assignedCareers as $career): ?>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="font-bold"><i class="fa-solid <?= e($career['icon']) ?> text-blue-300 mr-2"></i><?= e($career['title']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (count($assignedCareers) === 0): ?>
                <p class="text-slate-400">No assigned careers yet. Admin must assign tracks before mentor work is available.</p>
            <?php endif; ?>
        </div>
    </section>

    <aside class="card">
        <h2 class="sectionTitle mb-4">Actions</h2>
        <div class="space-y-3">
            <a href="enrollment_requests.php" class="quickBtn"><i class="fa-solid fa-user-plus text-blue-300"></i> Review Requests</a>
            <a href="create_task.php" class="quickBtn"><i class="fa-solid fa-list-check text-green-300"></i> Create Task</a>
            <a href="review_submission.php" class="quickBtn"><i class="fa-solid fa-file-circle-check text-yellow-300"></i> Review Submissions</a>
        </div>
    </aside>
</div>

<?php include '../footer.php'; ?>
