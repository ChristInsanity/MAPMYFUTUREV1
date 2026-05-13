<?php
$pageTitle = $pageTitle ?? 'Map My Future';
$activePage = $activePage ?? 'dashboard';
$inStudentDir = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/student/') !== false;
$inAdminDir = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;
$inMentorDir = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/mentor/') !== false;
$rootPrefix = $inStudentDir ? '../' : '';
$studentPrefix = $inStudentDir ? '' : 'student/';
$breadcrumbs = $breadcrumbs ?? [];

if ($inAdminDir) {
    $rootPrefix = '../';
    $brand = ['Admin Center', 'dashboard.php', 'fa-shield-halved'];
    $navItems = [
        'dashboard' => ['Dashboard', 'dashboard.php', 'fa-chart-line'],
        'students' => ['Students', 'students.php', 'fa-user-graduate'],
        'mentors' => ['Mentors', 'mentors.php', 'fa-chalkboard-user'],
        'employers' => ['Employers', 'employers.php', 'fa-building'],
        'verification' => ['Verification', 'verification_center.php', 'fa-user-check'],
        'lessons' => ['Lessons', 'lesson_manager.php', 'fa-book-open'],
    ];
} elseif ($inMentorDir) {
    $rootPrefix = '../';
    $brand = ['Mentor Center', 'dashboard.php', 'fa-users'];
    $navItems = [
        'dashboard' => ['Dashboard', 'dashboard.php', 'fa-house'],
        'requests' => ['Requests', 'enrollment_requests.php', 'fa-user-plus'],
        'tasks' => ['Create Task', 'create_task.php', 'fa-list-check'],
        'submissions' => ['Submissions', 'review_submission.php', 'fa-file-circle-check'],
    ];
} else {
    $brand = ['Map My Future', $studentPrefix . 'dashboard.php', 'fa-route'];
    $navItems = [
        'dashboard' => ['Dashboard', $studentPrefix . 'dashboard.php', 'fa-house'],
        'skill_gap' => ['Skill Gap', $studentPrefix . 'skill_gap.php', 'fa-bolt'],
        'roadmap' => ['Roadmap', $studentPrefix . 'roadmap.php', 'fa-route'],
        'assessments' => ['Assessments', $studentPrefix . 'assessments.php', 'fa-brain'],
        'mentors' => ['Mentors', $studentPrefix . 'mentors.php', 'fa-users'],
        'mentor_tasks' => ['Tasks', $studentPrefix . 'mentor_tasks.php', 'fa-list-check'],
        'subscription' => ['Premium', $studentPrefix . 'subscription.php', 'fa-crown'],
        'portfolio' => ['Portfolio', $studentPrefix . 'portfolio.php', 'fa-folder-open'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body{background:#020B24;color:#fff;}
        .card,.statCard{background:#162338;border:1px solid #334155;padding:24px;border-radius:20px;}
        .sectionTitle{font-size:24px;font-weight:700;}
        .navBtn,.navActive{display:flex;gap:8px;align-items:center;white-space:nowrap;}
        .navActive{color:#60a5fa;}
        .navBtn{color:#cbd5e1;}
        .navBtn:hover{color:#93c5fd;}
        .quickBtn,.actionRow{display:flex;align-items:center;gap:12px;padding:16px;background:#162338;border:1px solid #334155;border-radius:16px;transition:.2s;}
        .quickBtn:hover,.actionRow:hover{background:#1e293b;border-color:#475569;}
        .inputStyle{width:100%;padding:14px 16px;background:#020B24;border:1px solid #334155;border-radius:14px;outline:none;}
        .inputStyle:focus{border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.15);}
        .primaryBtn{display:inline-flex;align-items:center;justify-content:center;gap:10px;background:#2563eb;padding:12px 18px;border-radius:14px;font-weight:700;transition:.2s;}
        .primaryBtn:hover{background:#3B82F6;}
        .secondaryBtn{display:inline-flex;align-items:center;justify-content:center;gap:10px;background:#1e293b;border:1px solid #334155;padding:12px 18px;border-radius:14px;font-weight:700;transition:.2s;}
        .secondaryBtn:hover{background:#263449;}
        .dangerBtn{display:inline-flex;align-items:center;justify-content:center;gap:10px;background:#dc2626;padding:12px 18px;border-radius:14px;font-weight:700;}
        .badge{display:inline-flex;align-items:center;gap:8px;border:1px solid #334155;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:700;}
        .floatingBack{position:fixed;top:86px;left:18px;z-index:40;width:46px;height:46px;border-radius:999px;background:#162338;border:1px solid #334155;display:flex;align-items:center;justify-content:center;color:#bfdbfe;box-shadow:0 10px 28px rgba(0,0,0,.25);transition:.2s;}
        .floatingBack:hover{transform:translateX(-3px);border-color:#3B82F6;box-shadow:0 0 22px rgba(59,130,246,.28);}
        .breadcrumbs{display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:#94a3b8;font-size:14px;margin-bottom:18px;}
        .breadcrumbs a{color:#bfdbfe;}
        .breadcrumbs i{font-size:11px;color:#475569;}
        @media (max-width: 900px){.floatingBack{top:76px;left:12px;width:42px;height:42px;}}
    </style>
</head>
<body class="min-h-screen">
<nav class="border-b border-slate-800 sticky top-0 bg-[#020B24]/95 backdrop-blur z-50">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-4 flex justify-between items-center gap-5">
        <div class="flex items-center gap-8 min-w-0">
            <a href="<?= e($brand[1]) ?>" class="text-xl font-bold text-blue-400 flex items-center gap-3 shrink-0">
                <i class="fa-solid <?= e($brand[2]) ?>"></i>
                <?= e($brand[0]) ?>
            </a>

            <div class="hidden xl:flex gap-6 text-sm overflow-x-auto">
                <?php foreach ($navItems as $key => $item): ?>
                    <a href="<?= e($item[1]) ?>" class="<?= $activePage === $key ? 'navActive' : 'navBtn' ?>">
                        <i class="fa-solid <?= e($item[2]) ?>"></i>
                        <?= e($item[0]) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex gap-2 sm:gap-3 shrink-0">
            <?php if (!$inAdminDir && !$inMentorDir): ?>
            <a href="<?= e($studentPrefix) ?>portfolio.php" class="hidden sm:inline-flex bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-xl items-center gap-2">
                <i class="fa-solid fa-folder-open"></i>
                Portfolio
            </a>
            <?php endif; ?>
            <a href="<?= e($rootPrefix) ?>logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-xl flex items-center gap-2">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
<?php if (!empty($backUrl)): ?>
    <a href="<?= e($backUrl) ?>" class="floatingBack" title="<?= e($backLabel ?? 'Back') ?>">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
<?php endif; ?>

<?php if (!empty($breadcrumbs)): ?>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index > 0): ?><i class="fa-solid fa-chevron-right"></i><?php endif; ?>
            <?php if (!empty($crumb['url'])): ?>
                <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
            <?php else: ?>
                <span><?= e($crumb['label']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<script>
window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
window.mmfPost = async function(url, data = {}, isFormData = false) {
    const options = {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': window.csrfToken,
            'Accept': 'application/json'
        }
    };

    if (isFormData) {
        data.append('csrf_token', window.csrfToken);
        options.body = data;
    } else {
        options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        const params = new URLSearchParams();
        Object.entries(data).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(item => params.append(key, item));
            } else {
                params.append(key, value);
            }
        });
        params.append('csrf_token', window.csrfToken);
        options.body = params;
    }

    const response = await fetch(url, options);
    return await response.json();
};
</script>
