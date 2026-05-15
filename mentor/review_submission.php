<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$studentFilterId = (int)($_GET['student_id'] ?? 0);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $submissionId = (int)($_POST['submission_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'approved');
    $score = (int)($_POST['score'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');

    if (!in_array($status, ['approved', 'revision_requested'], true)) {
        $status = 'approved';
    }

    dbExecute(
        $conn,
        "UPDATE mentor_task_submissions mts
         JOIN mentor_tasks mt ON mt.mentor_task_id = mts.mentor_task_id
         SET mts.status = ?, mts.score = ?, mts.comment = ?, mts.reviewed_at = NOW()
         WHERE mts.submission_id = ? AND mt.mentor_id = ?",
        "sisii",
        [$status, $score, $comment, $submissionId, $mentorId]
    );
    $message = 'Submission reviewed.';
}

$reviewTypes = "i";
$reviewParams = [$mentorId];
$studentFilterSql = "";
if ($studentFilterId > 0) {
    $studentFilterSql = " AND ms.student_id = ?";
    $reviewTypes .= "i";
    $reviewParams[] = $studentFilterId;
}

$reviews = dbFetchAll(
    $conn,
    "SELECT mt.mentor_task_id, mt.title AS task_title, mt.points, mt.deadline,
            ms.student_id, u.full_name AS student_name,
            cs.subject_title, cs.subject_code, csem.semester_number, cy.year_number,
            mts.submission_id, mts.submission_file, mts.submission_link, mts.notes,
            COALESCE(mts.status, 'pending') AS review_status,
            mts.score, mts.comment, mts.submitted_at, mts.reviewed_at
     FROM mentor_tasks mt
     JOIN mentor_students ms ON ms.mentor_id = mt.mentor_id
        AND ms.subject_id = mt.subject_id
        AND ms.status = 'active'
        AND (mt.assigned_student_id IS NULL OR mt.assigned_student_id = ms.student_id)
     JOIN users u ON u.user_id = ms.student_id
     JOIN career_subjects cs ON cs.subject_id = mt.subject_id
     JOIN career_semesters csem ON csem.semester_id = cs.semester_id
     JOIN career_years cy ON cy.year_id = csem.year_id
     LEFT JOIN mentor_task_submissions mts ON mts.mentor_task_id = mt.mentor_task_id AND mts.student_id = ms.student_id
     WHERE mt.mentor_id = ? {$studentFilterSql}
     ORDER BY COALESCE(mts.submitted_at, mt.created_at) DESC",
    $reviewTypes,
    $reviewParams
);

$pageTitle = 'Task Review';
$activePage = 'submissions';
$backUrl = 'dashboard.php';
$backLabel = 'Back to Dashboard';
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Task Review</h1>
</div>

<?php if ($message): ?><div class="mb-6 rounded-2xl border border-green-500 bg-green-500/10 p-4 text-green-200"><?= e($message) ?></div><?php endif; ?>

<div class="space-y-4">
    <?php foreach ($reviews as $submission): ?>
        <?php
        $statusLabel = [
            'pending' => 'Pending',
            'submitted' => 'Submitted',
            'approved' => 'Reviewed',
            'revision_requested' => 'Needs Revision',
        ][$submission['review_status']] ?? readableStatus($submission['review_status']);
        $statusStyle = $submission['review_status'] === 'approved'
            ? 'completed'
            : ($submission['review_status'] === 'revision_requested' ? 'submitted' : ($submission['review_status'] === 'submitted' ? 'in_progress' : 'available'));
        ?>
        <article class="card">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-xl font-bold"><?= e($submission['task_title']) ?></h2>
                    <p class="text-slate-400"><?= e($submission['student_name']) ?> - <?= e($submission['subject_code']) ?> <?= e($submission['subject_title']) ?></p>
                    <p class="text-slate-500 text-sm">Year <?= (int)$submission['year_number'] ?> - Semester <?= (int)$submission['semester_number'] ?></p>
                    <p class="text-slate-500 text-sm">
                        <?= $submission['submitted_at'] ? 'Submitted ' . e(date('M d, Y h:i A', strtotime($submission['submitted_at']))) : 'No submission yet' ?>
                    </p>
                </div>
                <span class="badge <?= e(statusClass($statusStyle)) ?>"><?= e($statusLabel) ?></span>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-5">
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="text-slate-400 mb-2">Resources</p>
                    <?php if ($submission['submission_file']): ?><a class="text-blue-300" href="../<?= e($submission['submission_file']) ?>" target="_blank">View uploaded file</a><?php endif; ?>
                    <?php if ($submission['submission_link']): ?><a class="text-blue-300 block" href="<?= e($submission['submission_link']) ?>" target="_blank">Open submitted link</a><?php endif; ?>
                    <?php if ($submission['notes']): ?>
                        <p class="text-slate-300 mt-3"><?= e($submission['notes']) ?></p>
                    <?php elseif (!$submission['submission_id']): ?>
                        <p class="text-slate-500">Waiting for student submission.</p>
                    <?php endif; ?>
                </div>
                <?php if ($submission['submission_id']): ?>
                    <form method="POST" class="bg-[#020B24] border border-[#334155] rounded-xl p-4 space-y-3">
                        <?= csrf_input() ?>
                        <input type="hidden" name="submission_id" value="<?= (int)$submission['submission_id'] ?>">
                        <textarea name="comment" class="inputStyle min-h-[110px]" placeholder="Comment"><?= e($submission['comment']) ?></textarea>
                        <input type="number" name="score" min="0" max="<?= (int)$submission['points'] ?>" value="<?= (int)($submission['score'] ?? 0) ?>" class="inputStyle" placeholder="Score">
                        <select name="status" class="inputStyle">
                            <option value="approved" <?= $submission['review_status'] === 'approved' ? 'selected' : '' ?>>Mark reviewed</option>
                            <option value="revision_requested" <?= $submission['review_status'] === 'revision_requested' ? 'selected' : '' ?>>Needs revision</option>
                        </select>
                        <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-check"></i> Save Review</button>
                    </form>
                <?php else: ?>
                    <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 flex items-center text-slate-400">
                        Pending submission
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (count($reviews) === 0): ?>
        <div class="card"><p class="text-slate-400">No assigned tasks yet.</p></div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
