<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$roadmap = getRoadmapByYear($conn, $userId);
$progress = getStudentProgress($conn, $userId);

$pageTitle = 'Learning Roadmap';
$activePage = 'roadmap';
include '../header.php';
?>

<div class="mb-8">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-5">
        <div>
            <p class="text-blue-300 font-semibold mb-2">College-like roadmap</p>
            <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($profile['career_path']) ?> Curriculum</h1>
            <p class="text-slate-400">Every student starts at Year 1 Semester 1. Complete each semester to unlock the next stage.</p>
        </div>
        <div class="card min-w-[240px]">
            <p class="text-slate-400 mb-2">Overall Progress</p>
            <div class="text-3xl font-bold"><?= (int)$progress['overall_progress'] ?>%</div>
        </div>
    </div>
</div>

<div class="space-y-8">
    <?php foreach ($roadmap as $year => $semesters): ?>
        <section class="card">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center font-bold"><?= (int)$year ?></div>
                    <div>
                        <h2 class="sectionTitle">Year <?= (int)$year ?></h2>
                        <p class="text-slate-400">Two-semester progression for your chosen path.</p>
                    </div>
                </div>
                <?php
                $yearSubjects = array_merge(...array_values($semesters));
                $yearDone = count(array_filter($yearSubjects, fn($subject) => $subject['status'] === 'completed'));
                $yearPercent = count($yearSubjects) > 0 ? (int)round(($yearDone / count($yearSubjects)) * 100) : 0;
                ?>
                <div class="min-w-[220px]">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-slate-400">Year Progress</span>
                        <span><?= $yearPercent ?>%</span>
                    </div>
                    <div class="bg-[#020B24] h-3 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500" style="width:<?= $yearPercent ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-5">
                <?php foreach ($semesters as $semester => $subjects): ?>
                    <?php
                    $semesterDone = count(array_filter($subjects, fn($subject) => $subject['status'] === 'completed'));
                    $semesterPercent = count($subjects) > 0 ? (int)round(($semesterDone / count($subjects)) * 100) : 0;
                    $lockedSemester = count(array_filter($subjects, fn($subject) => $subject['status'] !== 'locked')) === 0;
                    ?>
                    <article class="bg-[#020B24] border border-[#334155] rounded-2xl p-5 <?= $lockedSemester ? 'opacity-70' : '' ?>">
                        <div class="flex justify-between gap-4 mb-5">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <i class="fa-solid <?= $lockedSemester ? 'fa-lock text-slate-500' : 'fa-unlock text-blue-300' ?>"></i>
                                    <h3 class="text-xl font-bold">Semester <?= (int)$semester ?></h3>
                                </div>
                                <p class="text-slate-400 text-sm"><?= $semesterDone ?> of <?= count($subjects) ?> subjects completed</p>
                            </div>
                            <span class="font-bold text-blue-300"><?= $semesterPercent ?>%</span>
                        </div>

                        <div class="bg-slate-900 h-2 rounded-full overflow-hidden mb-5">
                            <div class="h-full bg-blue-500" style="width:<?= $semesterPercent ?>%"></div>
                        </div>

                        <div class="space-y-3">
                            <?php foreach ($subjects as $subject): ?>
                                <a href="subject.php?id=<?= (int)$subject['subject_id'] ?>" class="block bg-[#162338] border border-[#334155] rounded-xl p-4 hover:border-blue-500 transition">
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                        <div>
                                            <p class="text-slate-500 text-xs font-bold mb-1"><?= e($subject['subject_code']) ?></p>
                                            <h4 class="font-bold mb-1"><?= e($subject['subject_title']) ?></h4>
                                            <p class="text-slate-400 text-sm leading-6"><?= e($subject['description']) ?></p>
                                        </div>
                                        <span class="badge <?= e(statusClass($subject['status'])) ?> shrink-0">
                                            <i class="fa-solid <?= $subject['status'] === 'locked' ? 'fa-lock' : ($subject['status'] === 'completed' ? 'fa-circle-check' : 'fa-play') ?>"></i>
                                            <?= e(readableStatus($subject['status'])) ?>
                                        </span>
                                    </div>

                                    <div class="mt-4">
                                        <div class="flex justify-between text-xs mb-2">
                                            <span class="text-slate-500">Subject Progress</span>
                                            <span><?= (int)$subject['progress'] ?>%</span>
                                        </div>
                                        <div class="bg-[#020B24] h-2 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500" style="width:<?= (int)$subject['progress'] ?>%"></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<?php if (count($roadmap) === 0): ?>
    <div class="card">
        <p class="text-slate-400">Your roadmap is not initialized yet. Return to discovery to choose a career path.</p>
        <a href="profile_setup.php" class="primaryBtn mt-4">Open Discovery</a>
    </div>
<?php endif; ?>

<?php include '../footer.php'; ?>
