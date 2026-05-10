<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_question'])) {
    $message = sanitize($_POST['message'] ?? '');
    sendMentorQuestion($conn, $userId, $message);
    redirect('mentors.php');
}

$mentorData = getMentorModuleData($conn, $userId);
$mentor = $mentorData['mentor'];
$messages = $mentorData['messages'];
$feedback = $mentorData['feedback'];

$pageTitle = 'Mentors';
$activePage = 'mentors';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Mentor module</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Mentor Support</h1>
    <p class="text-slate-400">View your assigned mentor, send questions, and track feedback attached to roadmap tasks.</p>
</div>

<?php if (!$mentor): ?>
    <div class="card">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-14 h-14 rounded-2xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                <i class="fa-solid fa-users text-blue-300 text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold">No mentor assigned yet</h2>
                <p class="text-slate-400">Your account is ready for mentor assignment when the mentor module goes live.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="grid lg:grid-cols-3 gap-8">
        <section class="space-y-6">
            <div class="card">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center text-2xl font-bold">
                        <?= e(strtoupper(substr($mentor['full_name'], 0, 1))) ?>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= e($mentor['full_name']) ?></h2>
                        <p class="text-slate-400"><?= e($mentor['email']) ?></p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Status</span>
                        <span class="text-green-300"><?= e(ucfirst($mentor['status'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Assigned</span>
                        <span><?= e(date('M d, Y', strtotime($mentor['assigned_at']))) ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="sectionTitle mb-5">Ask a Question</h2>
                <form method="POST" class="space-y-4">
                    <textarea class="inputStyle min-h-[150px]" name="message" required placeholder="Ask about a task, portfolio project, interview prep, or skill gap."></textarea>
                    <button class="primaryBtn w-full" name="send_question" type="submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send Question
                    </button>
                </form>
            </div>
        </section>

        <section class="lg:col-span-2 space-y-6">
            <div class="card">
                <h2 class="sectionTitle mb-5">Recent Feedback</h2>
                <div class="space-y-4">
                    <?php foreach ($feedback as $item): ?>
                        <div class="bg-[#020B24] border border-slate-700 rounded-2xl p-4">
                            <div class="flex justify-between gap-4 mb-2">
                                <h3 class="font-bold"><?= e($item['task_title'] ?? 'General Feedback') ?></h3>
                                <?php if ($item['rating']): ?>
                                    <span class="text-yellow-300"><i class="fa-solid fa-star"></i> <?= e($item['rating']) ?>/5</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-slate-300 leading-7"><?= e($item['comments']) ?></p>
                            <p class="text-slate-500 text-sm mt-3"><?= e(date('M d, Y h:i A', strtotime($item['created_at']))) ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($feedback) === 0): ?>
                        <p class="text-slate-400">No mentor feedback has been posted yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2 class="sectionTitle mb-5">Question History</h2>
                <div class="space-y-4">
                    <?php foreach ($messages as $message): ?>
                        <div class="bg-[#020B24] border border-slate-700 rounded-2xl p-4">
                            <div class="flex justify-between gap-4 mb-2">
                                <h3 class="font-bold"><?= e($message['full_name']) ?></h3>
                                <span class="text-slate-500 text-sm"><?= e(date('M d, h:i A', strtotime($message['created_at']))) ?></span>
                            </div>
                            <p class="text-slate-300 leading-7"><?= e($message['message']) ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($messages) === 0): ?>
                        <p class="text-slate-400">Your sent questions will appear here.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
<?php endif; ?>

<?php include '../footer.php'; ?>
