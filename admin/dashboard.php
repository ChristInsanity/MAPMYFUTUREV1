<?php
require_once '../auth_guard.php';

requireAdmin();

$totalUsers = $conn->query("
SELECT COUNT(*) total
FROM users
")->fetch_assoc()['total'] ?? 0;

$totalStudents = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE role='student'
")->fetch_assoc()['total'] ?? 0;

$totalMentors = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE role='mentor'
AND status='approved'
")->fetch_assoc()['total'] ?? 0;

$totalEmployers = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE role='employer'
AND status='approved'
")->fetch_assoc()['total'] ?? 0;

$pendingVerifications = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE role IN('mentor','employer')
AND status='pending'
")->fetch_assoc()['total'] ?? 0;

$approvedAccounts = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE status='approved'
")->fetch_assoc()['total'] ?? 0;

$totalProfiles = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE profile_completed=1
")->fetch_assoc()['total'] ?? 0;

$totalRoadmaps = $conn->query("
SELECT COUNT(*) total
FROM student_tasks
")->fetch_assoc()['total'] ?? 0;

$recentUsers = $conn->query("
SELECT full_name, role, created_at
FROM users
ORDER BY created_at DESC
LIMIT 4
");

$careerTrends = $conn->query("
SELECT career_path, COUNT(*) total
FROM student_profiles
WHERE career_path IS NOT NULL AND career_path <> ''
GROUP BY career_path
ORDER BY total DESC, career_path
LIMIT 6
");

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';
$breadcrumbs = [
    ['label' => 'Dashboard']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">System Overview</h1>
    <p class="text-slate-400">Map My Future Administration Center</p>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
        ['Users', $totalUsers, 'users'],
        ['Students', $totalStudents, 'user-graduate'],
        ['Mentors', $totalMentors, 'chalkboard-user'],
        ['Employers', $totalEmployers, 'building'],
        ['Pending', $pendingVerifications, 'user-check'],
        ['Approved', $approvedAccounts, 'badge-check'],
        ['Profiles', $totalProfiles, 'id-card'],
        ['Roadmaps', $totalRoadmaps, 'route']
    ];

    foreach ($cards as $card):
    ?>
        <div class="statCard">
            <div class="flex justify-between mb-3 text-slate-400">
                <p><?= e($card[0]) ?></p>
                <i class="fa-solid fa-<?= e($card[2]) ?> text-blue-400"></i>
            </div>
            <h2 class="text-3xl font-bold"><?= (int)$card[1] ?></h2>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <section class="card">
            <h2 class="sectionTitle mb-5">Recent Platform Activity</h2>
            <div class="space-y-4">
                <?php while ($activity = $recentUsers->fetch_assoc()): ?>
                    <div class="activityItem">
                        <i class="fa-solid fa-user-plus text-blue-400"></i>
                        <span><?= e($activity['full_name']) ?> registered as <?= e($activity['role']) ?></span>
                        <span class="ml-auto text-slate-500 text-sm"><?= e(date('M d', strtotime($activity['created_at']))) ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="card">
            <h2 class="sectionTitle mb-5">Top Career Trends</h2>
            <div class="flex flex-wrap gap-3">
                <?php while ($trend = $careerTrends->fetch_assoc()): ?>
                    <span class="trendTag"><?= e($trend['career_path']) ?> (<?= (int)$trend['total'] ?>)</span>
                <?php endwhile; ?>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="card">
            <h2 class="sectionTitle mb-5">Verification Center</h2>
            <div class="space-y-3">
                <a href="verification_center.php" class="actionBtn">
                    <i class="fa-solid fa-user-check text-green-400"></i>
                    Approve Mentors
                </a>
                <a href="verification_center.php" class="actionBtn">
                    <i class="fa-solid fa-building-circle-check text-blue-400"></i>
                    Approve Employers
                </a>
            </div>
        </section>

        <section class="card">
            <h2 class="sectionTitle mb-5">Platform Health</h2>
            <p class="text-slate-300 mb-3"><?= (int)$totalProfiles ?> completed student profiles</p>
            <p class="text-slate-300"><?= (int)$totalRoadmaps ?> active student roadmap tasks</p>
        </section>
    </aside>
</div>

<style>
    .activityItem,.actionBtn{display:flex;gap:12px;align-items:center;padding:16px;background:#020B24;border:1px solid #334155;border-radius:14px;}
    .actionBtn:hover{background:#1e293b;}
    .trendTag{padding:10px 16px;border-radius:999px;border:1px solid #334155;}
</style>

<?php include '../footer.php'; ?>
