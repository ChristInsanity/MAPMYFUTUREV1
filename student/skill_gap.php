<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$tasks = getRoadmapTasks($conn, $userId);
$grouped = groupTasksByPhase($tasks);
$progress = getStudentProgress($conn, $userId);

$completedSkills = array_filter($tasks, fn($task) => $task['status'] === 'completed');
$missingSkills = array_filter($tasks, fn($task) => in_array($task['status'], ['available', 'in_progress', 'submitted'], true));
$lockedSkills = array_filter($tasks, fn($task) => $task['status'] === 'locked');

$pageTitle = 'Skill Gap';
$activePage = 'skill_gap';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Skill gap analysis</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($profile['career_path']) ?> Readiness</h1>
    <p class="text-slate-400">Live skill status based on your roadmap tasks, quiz attempts, projects, and mentor reviews.</p>
</div>

<div class="grid md:grid-cols-3 gap-4 mb-8">
    <div class="statCard">
        <div class="flex justify-between text-slate-400 mb-3">
            <p>Completed Skills</p>
            <i class="fa-solid fa-circle-check text-green-400"></i>
        </div>
        <h2 class="text-3xl font-bold"><?= count($completedSkills) ?></h2>
    </div>
    <div class="statCard">
        <div class="flex justify-between text-slate-400 mb-3">
            <p>Missing Skills</p>
            <i class="fa-solid fa-bolt text-yellow-400"></i>
        </div>
        <h2 class="text-3xl font-bold"><?= count($missingSkills) ?></h2>
    </div>
    <div class="statCard">
        <div class="flex justify-between text-slate-400 mb-3">
            <p>Locked Skills</p>
            <i class="fa-solid fa-lock text-slate-400"></i>
        </div>
        <h2 class="text-3xl font-bold"><?= count($lockedSkills) ?></h2>
    </div>
</div>

<div class="card mb-8">
    <div class="flex justify-between mb-3">
        <h2 class="sectionTitle">Overall Progress</h2>
        <span class="font-bold text-blue-300"><?= e($progress['readiness']) ?>%</span>
    </div>
    <div class="bg-slate-950 h-4 rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-blue-600 to-cyan-400" style="width:<?= (int)$progress['readiness'] ?>%"></div>
    </div>
</div>

<div class="masonryCards mb-8">
    <section class="card">
        <h2 class="sectionTitle mb-5">Completed</h2>
        <div class="space-y-3">
            <?php foreach ($completedSkills as $task): ?>
                <div class="actionRow">
                    <i class="fa-solid <?= e(taskIcon($task['task_type'])) ?> text-green-400"></i>
                    <div>
                        <p class="font-semibold"><?= e($task['task_title']) ?></p>
                        <p class="text-slate-400 text-sm"><?= e($task['points']) ?> XP earned</p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($completedSkills) === 0): ?>
                <p class="text-slate-400">Complete your first roadmap task to build this list.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <h2 class="sectionTitle mb-5">Missing</h2>
        <div class="space-y-3">
            <?php foreach ($missingSkills as $task): ?>
                <a href="roadmap.php" class="actionRow">
                    <i class="fa-solid <?= e(taskIcon($task['task_type'])) ?> text-yellow-400"></i>
                    <div class="flex-1">
                        <p class="font-semibold"><?= e($task['task_title']) ?></p>
                        <div class="mt-2 bg-slate-950 h-2 rounded-full overflow-hidden">
                            <div class="h-full bg-yellow-400" style="width:<?= (int)$task['progress_percent'] ?>%"></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (count($missingSkills) === 0): ?>
                <p class="text-slate-400">No active skill gaps right now.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <h2 class="sectionTitle mb-5">Locked</h2>
        <div class="space-y-3">
            <?php foreach ($lockedSkills as $task): ?>
                <div class="actionRow opacity-70">
                    <i class="fa-solid fa-lock text-slate-500"></i>
                    <div>
                        <p class="font-semibold"><?= e($task['task_title']) ?></p>
                        <p class="text-slate-500 text-sm"><?= e($task['phase_title']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($lockedSkills) === 0): ?>
                <p class="text-slate-400">No locked skills remain.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="space-y-4">
    <h2 class="sectionTitle">Phase Progress</h2>
    <?php foreach ($grouped as $phase): ?>
        <?php
        $total = count($phase['tasks']);
        $done = count(array_filter($phase['tasks'], fn($task) => $task['status'] === 'completed'));
        $percent = $total > 0 ? (int)round(($done / $total) * 100) : 0;
        ?>
        <div class="card">
            <div class="flex justify-between gap-4 mb-3">
                <div>
                    <h3 class="text-xl font-bold"><?= e($phase['title']) ?></h3>
                    <p class="text-slate-400"><?= e($phase['description']) ?></p>
                </div>
                <span class="font-bold text-blue-300"><?= $percent ?>%</span>
            </div>
            <div class="bg-slate-950 h-3 rounded-full overflow-hidden">
                <div class="h-full bg-blue-500" style="width:<?= $percent ?>%"></div>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<?php include '../footer.php'; ?>
