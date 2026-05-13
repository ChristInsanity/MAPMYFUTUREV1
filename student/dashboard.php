<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

ensureStudentRoadmap($conn, $userId);
$progress = getStudentProgress($conn, $userId);
$careerMatches = getCareerMatches($conn, $userId);
$subjects = getStudentSubjectRows($conn, $userId);
$roadmap = getRoadmapByYear($conn, $userId);
$mentor = getFeaturedMentor($conn, $userId);
$currentSubjectUrl = $progress['current_subject_id'] > 0 ? 'subject.php?id=' . (int)$progress['current_subject_id'] : 'roadmap.php';
$subscription = getActiveSubscription($conn, $userId);

$pageTitle = 'Student Dashboard';
$activePage = 'dashboard';
include '../header.php';
?>

<div class="mb-8">
    <div class="flex flex-col lg:flex-row justify-between gap-5 mb-6">
        <div>
            <p class="text-blue-300 font-semibold mb-2">Career command center</p>
            <h1 class="text-3xl lg:text-5xl font-bold mb-2">Welcome back, <?= e(explode(' ', $profile['full_name'])[0]) ?></h1>
            <p class="text-slate-400"><?= e($profile['student_type'] ?? 'Student') ?> pursuing <?= e($profile['career_path']) ?></p>
        </div>
        <a href="<?= e($currentSubjectUrl) ?>" class="primaryBtn self-start">
            <i class="fa-solid fa-play"></i>
            Continue Learning
        </a>
    </div>

    <?php if ($subscription): ?>
        <div class="mb-6 inline-flex items-center gap-3 bg-green-500/10 border border-green-500/30 rounded-2xl px-5 py-3 text-green-200 font-bold">
            <i class="fa-solid fa-crown"></i>
            PREMIUM ACTIVE
        </div>
    <?php endif; ?>

    <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-6">
        <div class="flex gap-3 items-center mb-2 text-blue-300">
            <i class="fa-solid fa-bullseye"></i>
            <p>Career Path</p>
        </div>
        <h2 class="text-2xl font-bold mb-2"><?= e($profile['career_path']) ?></h2>
        <p class="text-slate-300 leading-7"><?= e($profile['ai_summary']) ?></p>
    </div>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="statCard">
        <div class="flex justify-between mb-3 text-slate-400">
            <p>Current Year</p>
            <i class="fa-solid fa-graduation-cap text-blue-400"></i>
        </div>
        <h2 class="text-3xl font-bold">Year <?= (int)$progress['current_year'] ?></h2>
    </div>

    <div class="statCard">
        <div class="flex justify-between mb-3 text-slate-400">
            <p>Current Semester</p>
            <i class="fa-solid fa-calendar-days text-green-400"></i>
        </div>
        <h2 class="text-3xl font-bold">Semester <?= (int)$progress['current_semester'] ?></h2>
    </div>

    <div class="statCard">
        <div class="flex justify-between mb-3 text-slate-400">
            <p>Overall Progress</p>
            <i class="fa-solid fa-chart-line text-yellow-400"></i>
        </div>
        <h2 class="text-3xl font-bold"><?= (int)$progress['overall_progress'] ?>%</h2>
    </div>

    <div class="statCard">
        <div class="flex justify-between mb-3 text-slate-400">
            <p>Plan</p>
            <i class="fa-solid fa-crown text-yellow-400"></i>
        </div>
        <h2 class="text-2xl font-bold"><?= e($progress['premium_status']) ?></h2>
    </div>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="statCard">
        <p class="text-slate-400 mb-2">Current Subject</p>
        <h2 class="text-xl font-bold leading-7"><?= e($progress['current_subject']) ?></h2>
    </div>
    <div class="statCard">
        <p class="text-slate-400 mb-2">Completed Subjects</p>
        <h2 class="text-3xl font-bold"><?= (int)$progress['completed_subjects'] ?>/<?= (int)$progress['total_subjects'] ?></h2>
    </div>
    <div class="statCard">
        <p class="text-slate-400 mb-2">Pending Tasks</p>
        <h2 class="text-3xl font-bold"><?= (int)$progress['pending_tasks'] ?></h2>
    </div>
    <div class="statCard">
        <p class="text-slate-400 mb-2">Career Readiness</p>
        <h2 class="text-3xl font-bold"><?= (int)$progress['readiness'] ?>%</h2>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <section>
            <div class="flex items-center justify-between gap-4 mb-5">
                <h2 class="sectionTitle">Roadmap Progress</h2>
                <a href="roadmap.php" class="text-blue-300 inline-flex items-center gap-2">
                    View full roadmap
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="space-y-5">
                <?php foreach ($roadmap as $year => $semesters): ?>
                    <div class="card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold">Year <?= (int)$year ?></h3>
                            <?php
                            $yearSubjects = array_merge(...array_values($semesters));
                            $yearDone = count(array_filter($yearSubjects, fn($subject) => $subject['status'] === 'completed'));
                            $yearPercent = count($yearSubjects) > 0 ? (int)round(($yearDone / count($yearSubjects)) * 100) : 0;
                            ?>
                            <span class="text-blue-300 font-bold"><?= $yearPercent ?>%</span>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <?php foreach ($semesters as $semester => $semesterSubjects): ?>
                                <div class="bg-[#020B24] border border-[#334155] rounded-2xl p-4">
                                    <p class="text-slate-400 mb-3">Semester <?= (int)$semester ?></p>
                                    <div class="space-y-2">
                                        <?php foreach ($semesterSubjects as $subject): ?>
                                            <a href="subject.php?id=<?= (int)$subject['subject_id'] ?>" class="flex items-center justify-between gap-3 text-sm">
                                                <span class="<?= $subject['status'] === 'locked' ? 'text-slate-500' : 'text-slate-100' ?>"><?= e($subject['subject_title']) ?></span>
                                                <span class="badge <?= e(statusClass($subject['status'])) ?>"><?= e(readableStatus($subject['status'])) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="card">
            <h2 class="sectionTitle mb-4">Top Career Matches</h2>
            <div class="space-y-4">
                <?php foreach (array_slice($careerMatches, 0, 4) as $match): ?>
                    <div>
                        <div class="flex justify-between gap-3 mb-2">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid <?= e($match['icon']) ?> text-blue-300"></i>
                                <p class="font-semibold"><?= e($match['title']) ?></p>
                            </div>
                            <p class="font-bold"><?= (int)$match['match_percentage'] ?>%</p>
                        </div>
                        <div class="bg-[#020B24] h-2 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500" style="width:<?= (int)$match['match_percentage'] ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <h2 class="sectionTitle mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="<?= e($currentSubjectUrl) ?>" class="quickBtn"><i class="fa-solid fa-play text-blue-400"></i> Continue Learning</a>
                <a href="roadmap.php" class="quickBtn"><i class="fa-solid fa-route text-green-400"></i> View Roadmap</a>
                <a href="mentors.php" class="quickBtn"><i class="fa-solid fa-users text-purple-400"></i> Mentor Support</a>
                <a href="subscription.php" class="quickBtn"><i class="fa-solid fa-crown text-yellow-400"></i> Premium Plan</a>
                <a href="portfolio.php" class="quickBtn"><i class="fa-solid fa-folder-open text-yellow-400"></i> Portfolio</a>
            </div>
        </section>

        <section class="card">
            <h2 class="sectionTitle mb-4">Mentor</h2>
            <?php if ($mentor): ?>
                <div class="flex gap-4 items-center">
                    <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center font-bold">
                        <?= e(strtoupper(substr($mentor['full_name'], 0, 1))) ?>
                    </div>
                    <div>
                        <h3 class="font-bold"><?= e($mentor['full_name']) ?></h3>
                        <p class="text-slate-400 text-sm"><?= e(ucfirst($mentor['status'])) ?> mentor</p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-slate-400 mb-4">No mentor is assigned yet. Premium learners can request guided feedback.</p>
                <a href="mentors.php" class="secondaryBtn">Request Mentor</a>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php include '../footer.php'; ?>
