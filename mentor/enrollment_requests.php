<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();

$mentorId = (int)$_SESSION['user_id'];
$requests = getMentorIncomingRequests($conn, $mentorId);

$pageTitle = 'Enrollment Requests';
$activePage = 'requests';
$backUrl = 'dashboard.php';
$backLabel = 'Back to Dashboard';
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Student Requests</h1>
</div>

<div class="card">
    <div class="space-y-3">
        <?php foreach ($requests as $request): ?>
            <div class="requestRow bg-[#020B24] border border-[#334155] rounded-xl p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4" data-request-id="<?= (int)$request['request_id'] ?>">
                <div>
                    <h2 class="font-bold"><?= e($request['full_name']) ?></h2>
                    <p class="text-slate-400 text-sm"><?= e($request['email']) ?></p>
                    <p class="text-slate-400 text-sm"><?= e($request['career_path'] ?? 'Career path pending') ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="statusBadge badge <?= e(statusClass($request['status'] === 'accepted' ? 'completed' : ($request['status'] === 'rejected' ? 'locked' : 'submitted'))) ?>"><?= e(readableStatus($request['status'])) ?></span>
                    <?php if ($request['status'] === 'pending'): ?>
                        <button type="button" class="primaryBtn requestAction" data-status="accepted"><i class="fa-solid fa-check"></i> Accept</button>
                        <button type="button" class="dangerBtn requestAction" data-status="rejected"><i class="fa-solid fa-xmark"></i> Reject</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (count($requests) === 0): ?>
            <p class="text-slate-400">No enrollment requests yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.requestAction').forEach(button => {
    button.addEventListener('click', async () => {
        const row = button.closest('.requestRow');
        const result = await window.mmfPost('ajax_enrollment.php', {
            request_id: row.dataset.requestId,
            status: button.dataset.status
        });

        if (result.success) {
            const badge = row.querySelector('.statusBadge');
            badge.textContent = button.dataset.status === 'accepted' ? 'Accepted' : 'Rejected';
            row.querySelectorAll('.requestAction').forEach(action => action.remove());
        } else {
            alert(result.message || 'Unable to update request.');
        }
    });
});
</script>

<?php include '../footer.php'; ?>
