<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$studentFilterId = (int)($_GET['student_id'] ?? 0);
$reviewSubmissionId = (int)($_GET['id'] ?? 0);
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

$submissionDetailSql = "";
if ($reviewSubmissionId > 0) {
    $submissionDetailSql = " AND mts.submission_id = ?";
    $reviewTypes .= "i";
    $reviewParams[] = $reviewSubmissionId;
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
     JOIN mentor_task_submissions mts ON mts.mentor_task_id = mt.mentor_task_id AND mts.student_id = ms.student_id
     WHERE mt.mentor_id = ? {$studentFilterSql} {$submissionDetailSql}
     ORDER BY mts.submitted_at DESC",
    $reviewTypes,
    $reviewParams
);
$detail = $reviewSubmissionId > 0 ? ($reviews[0] ?? null) : null;

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

<?php if ($reviewSubmissionId > 0): ?>
    <?php if (!$detail): ?>
        <div class="card"><p class="text-slate-400">Submission not found.</p></div>
    <?php else: ?>
        <?php
        $statusLabel = [
            'submitted' => 'Submitted',
            'approved' => 'Reviewed',
            'revision_requested' => 'Needs Revision',
        ][$detail['review_status']] ?? readableStatus($detail['review_status']);
        $statusStyle = $detail['review_status'] === 'approved'
            ? 'completed'
            : ($detail['review_status'] === 'revision_requested' ? 'submitted' : 'in_progress');
        ?>
        <div class="grid lg:grid-cols-2 gap-6">
            <section class="card">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="sectionTitle mb-2"><?= e($detail['task_title']) ?></h2>
                        <p class="text-slate-400"><?= e($detail['student_name']) ?> - <?= e($detail['subject_code']) ?> <?= e($detail['subject_title']) ?></p>
                        <p class="text-slate-500 text-sm">Year <?= (int)$detail['year_number'] ?> - Semester <?= (int)$detail['semester_number'] ?></p>
                        <p class="text-slate-500 text-sm">Submitted <?= e(date('M d, Y h:i A', strtotime($detail['submitted_at']))) ?></p>
                    </div>
                    <span class="badge <?= e(statusClass($statusStyle)) ?>"><?= e($statusLabel) ?></span>
                </div>
                <div class="space-y-3">
                    <?php if ($detail['submission_file']): ?><a class="secondaryBtn w-full" href="../<?= e($detail['submission_file']) ?>" target="_blank"><i class="fa-solid fa-file"></i> View uploaded file</a><?php endif; ?>
                    <?php if ($detail['submission_link']): ?><a class="secondaryBtn w-full" href="<?= e($detail['submission_link']) ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open submitted link</a><?php endif; ?>
                    <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                        <p class="text-slate-400 mb-2">Student comments</p>
                        <p class="text-slate-300"><?= $detail['notes'] ? nl2br(e($detail['notes'])) : 'No comments included.' ?></p>
                    </div>
                </div>
            </section>

            <form method="POST" class="card space-y-4">
                <?= csrf_input() ?>
                <input type="hidden" name="submission_id" value="<?= (int)$detail['submission_id'] ?>">
                <h2 class="sectionTitle">Grading & Feedback</h2>
                <textarea name="comment" class="inputStyle min-h-[140px]" placeholder="Feedback"><?= e($detail['comment']) ?></textarea>
                <input type="number" name="score" min="0" max="<?= (int)$detail['points'] ?>" value="<?= (int)($detail['score'] ?? 0) ?>" class="inputStyle" placeholder="Score">
                <select name="status" class="inputStyle">
                    <option value="approved" <?= $detail['review_status'] === 'approved' ? 'selected' : '' ?>>Mark reviewed</option>
                    <option value="revision_requested" <?= $detail['review_status'] === 'revision_requested' ? 'selected' : '' ?>>Needs revision</option>
                </select>
                <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-check"></i> Save Review</button>
            </form>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($reviews as $submission): ?>
            <?php
            $statusLabel = [
                'submitted' => 'Submitted',
                'approved' => 'Reviewed',
                'revision_requested' => 'Needs Revision',
            ][$submission['review_status']] ?? readableStatus($submission['review_status']);
            $statusStyle = $submission['review_status'] === 'approved'
                ? 'completed'
                : ($submission['review_status'] === 'revision_requested' ? 'submitted' : 'in_progress');
            ?>
            <article class="card flex flex-col">
                <div class="grow">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-xl font-bold"><?= e($submission['student_name']) ?></h2>
                            <p class="text-slate-400"><?= e($submission['task_title']) ?></p>
                        </div>
                        <span class="badge <?= e(statusClass($statusStyle)) ?>"><?= e($statusLabel) ?></span>
                    </div>
                    <div class="space-y-2 text-sm text-slate-400 mb-5">
                        <p><span class="text-slate-500">Subject:</span> <?= e($submission['subject_code']) ?> <?= e($submission['subject_title']) ?></p>
                        <p><span class="text-slate-500">Term:</span> Year <?= (int)$submission['year_number'] ?> - Semester <?= (int)$submission['semester_number'] ?></p>
                        <p><span class="text-slate-500">Submitted:</span> <?= e(date('M d, Y h:i A', strtotime($submission['submitted_at']))) ?></p>
                    </div>
                </div>
                <a href="review_submission.php?id=<?= (int)$submission['submission_id'] ?>" class="primaryBtn w-full">Review</a>
            </article>
        <?php endforeach; ?>
        <?php if (count($reviews) === 0): ?>
            <div class="card md:col-span-2 xl:col-span-3"><p class="text-slate-400">No submissions yet.</p></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include '../footer.php'; ?>
