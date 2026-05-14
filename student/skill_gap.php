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

<section class="card mb-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 mb-6">
        <div>
            <h2 class="sectionTitle mb-1">Skill Cards</h2>
            <p class="text-slate-400">Compact view of completed, active, and upcoming readiness work.</p>
        </div>
        <a href="roadmap.php" class="secondaryBtn self-start md:self-auto px-4 py-2 text-sm">
            <i class="fa-solid fa-route"></i>
            Open Roadmap
        </a>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($completedSkills as $task): ?>
            <article class="skillCard">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="skillIcon bg-green-500/10 text-green-300 border-green-500/25">
                        <i class="fa-solid <?= e(taskIcon($task['task_type'])) ?>"></i>
                    </div>
                    <span class="skillPill text-green-200 border-green-500/30 bg-green-500/10">Complete</span>
                </div>
                <h3 class="font-bold leading-6 mb-3"><?= e($task['task_title']) ?></h3>
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-slate-400">Proficiency</span>
                    <strong class="text-green-200">100%</strong>
                </div>
                <div class="skillBar"><span class="bg-green-400" style="width:100%"></span></div>
                <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                    <span class="text-slate-400"><i class="fa-solid fa-arrow-trend-up mr-1 text-green-300"></i><?= e($task['points']) ?> XP earned</span>
                    <span class="text-green-200 font-bold">Done</span>
                </div>
            </article>
        <?php endforeach; ?>

        <?php foreach ($missingSkills as $task): ?>
            <?php $taskProgress = (int)$task['progress_percent']; ?>
            <a href="roadmap.php" class="skillCard hover:border-yellow-400/60">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="skillIcon bg-yellow-500/10 text-yellow-300 border-yellow-500/25">
                        <i class="fa-solid <?= e(taskIcon($task['task_type'])) ?>"></i>
                    </div>
                    <span class="skillPill text-yellow-200 border-yellow-500/30 bg-yellow-500/10"><?= e(readableStatus($task['status'])) ?></span>
                </div>
                <h3 class="font-bold leading-6 mb-3"><?= e($task['task_title']) ?></h3>
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-slate-400">Proficiency</span>
                    <strong class="text-yellow-200"><?= $taskProgress ?>%</strong>
                </div>
                <div class="skillBar"><span class="bg-yellow-400" style="width:<?= $taskProgress ?>%"></span></div>
                <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                    <span class="text-slate-400"><i class="fa-solid fa-arrow-trend-up mr-1 text-yellow-300"></i><?= 100 - $taskProgress ?>% to close</span>
                    <span class="text-yellow-200 font-bold">Continue</span>
                </div>
            </a>
        <?php endforeach; ?>

        <?php foreach ($lockedSkills as $task): ?>
            <article class="skillCard opacity-75">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="skillIcon bg-slate-500/10 text-slate-400 border-slate-500/25">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <span class="skillPill text-slate-300 border-slate-600 bg-slate-800/70">Locked</span>
                </div>
                <h3 class="font-bold leading-6 mb-3"><?= e($task['task_title']) ?></h3>
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-slate-400">Proficiency</span>
                    <strong class="text-slate-300">0%</strong>
                </div>
                <div class="skillBar"><span class="bg-slate-500" style="width:0%"></span></div>
                <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                    <span class="text-slate-500"><i class="fa-solid fa-layer-group mr-1"></i><?= e($task['phase_title']) ?></span>
                    <span class="text-slate-400 font-bold">Unlock later</span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (count($completedSkills) + count($missingSkills) + count($lockedSkills) === 0): ?>
        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 text-slate-400">No roadmap skills are available yet.</div>
    <?php endif; ?>
</section>

<style>
    .skillCard{display:block;background:#020B24;border:1px solid #334155;border-radius:14px;padding:16px;transition:border-color .18s ease,background .18s ease,transform .18s ease;}
    .skillCard:hover{background:#0f172a;transform:translateY(-1px);}
    .skillIcon{width:42px;height:42px;border-radius:12px;border:1px solid;display:flex;align-items:center;justify-content:center;font-size:17px;}
    .skillPill{display:inline-flex;align-items:center;border:1px solid;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;white-space:nowrap;}
    .skillBar{height:7px;background:#0f172a;border:1px solid #1e293b;border-radius:999px;overflow:hidden;}
    .skillBar span{display:block;height:100%;border-radius:999px;}
</style>

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
