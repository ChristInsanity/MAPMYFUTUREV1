<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
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

$submissions = dbFetchAll(
    $conn,
    "SELECT mts.*, mt.title AS task_title, mt.points, u.full_name AS student_name, cs.subject_title
     FROM mentor_task_submissions mts
     JOIN mentor_tasks mt ON mt.mentor_task_id = mts.mentor_task_id
     JOIN users u ON u.user_id = mts.student_id
     JOIN career_subjects cs ON cs.subject_id = mt.subject_id
     WHERE mt.mentor_id = ?
     ORDER BY mts.submitted_at DESC",
    "i",
    [$mentorId]
);

$pageTitle = 'Review Submissions';
$activePage = 'submissions';
$backUrl = 'dashboard.php';
$backLabel = 'Back to Dashboard';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Assignment review</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Review Submissions</h1>
    <p class="text-slate-400">View files or links, then comment, score, approve, or request revision.</p>
</div>

<?php if ($message): ?><div class="mb-6 rounded-2xl border border-green-500 bg-green-500/10 p-4 text-green-200"><?= e($message) ?></div><?php endif; ?>

<div class="space-y-4">
    <?php foreach ($submissions as $submission): ?>
        <article class="card">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-xl font-bold"><?= e($submission['task_title']) ?></h2>
                    <p class="text-slate-400"><?= e($submission['student_name']) ?> - <?= e($submission['subject_title']) ?></p>
                    <p class="text-slate-500 text-sm">Submitted <?= e(date('M d, Y h:i A', strtotime($submission['submitted_at']))) ?></p>
                </div>
                <span class="badge <?= e(statusClass($submission['status'] === 'approved' ? 'completed' : ($submission['status'] === 'revision_requested' ? 'submitted' : 'in_progress'))) ?>"><?= e(readableStatus($submission['status'])) ?></span>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-5">
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="text-slate-400 mb-2">Resources</p>
                    <?php if ($submission['submission_file']): ?><a class="text-blue-300" href="../<?= e($submission['submission_file']) ?>" target="_blank">View uploaded file</a><?php endif; ?>
                    <?php if ($submission['submission_link']): ?><a class="text-blue-300 block" href="<?= e($submission['submission_link']) ?>" target="_blank">Open submitted link</a><?php endif; ?>
                    <p class="text-slate-300 mt-3"><?= e($submission['notes']) ?></p>
                </div>
                <form method="POST" class="bg-[#020B24] border border-[#334155] rounded-xl p-4 space-y-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="submission_id" value="<?= (int)$submission['submission_id'] ?>">
                    <textarea name="comment" class="inputStyle min-h-[110px]" placeholder="Comment"><?= e($submission['comment']) ?></textarea>
                    <input type="number" name="score" min="0" max="<?= (int)$submission['points'] ?>" value="<?= (int)($submission['score'] ?? 0) ?>" class="inputStyle" placeholder="Score">
                    <select name="status" class="inputStyle">
                        <option value="approved">Approve</option>
                        <option value="revision_requested">Request revision</option>
                    </select>
                    <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-check"></i> Save Review</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (count($submissions) === 0): ?>
        <div class="card"><p class="text-slate-400">No submissions yet.</p></div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
