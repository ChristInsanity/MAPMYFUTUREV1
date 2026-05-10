<?php
$pageTitle = $pageTitle ?? 'Map My Future';
$activePage = $activePage ?? 'dashboard';
$inStudentDir = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/student/') !== false;
$rootPrefix = $inStudentDir ? '../' : '';
$studentPrefix = $inStudentDir ? '' : 'student/';

$navItems = [
    'dashboard' => ['Dashboard', $studentPrefix . 'dashboard.php', 'fa-house'],
    'skill_gap' => ['Skill Gap', $studentPrefix . 'skill_gap.php', 'fa-bolt'],
    'roadmap' => ['Roadmap', $studentPrefix . 'roadmap.php', 'fa-route'],
    'assessments' => ['Assessments', $studentPrefix . 'assessments.php', 'fa-brain'],
    'mentors' => ['Mentors', $studentPrefix . 'mentors.php', 'fa-users'],
    'portfolio' => ['Portfolio', $studentPrefix . 'portfolio.php', 'fa-folder-open'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>
<body class="min-h-screen">
<nav class="border-b border-slate-800 sticky top-0 bg-[#020B24]/95 backdrop-blur z-50">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-4 flex justify-between items-center gap-5">
        <div class="flex items-center gap-8 min-w-0">
            <a href="<?= e($studentPrefix) ?>dashboard.php" class="text-xl font-bold text-blue-400 flex items-center gap-3 shrink-0">
                <i class="fa-solid fa-route"></i>
                Map My Future
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
            <a href="<?= e($studentPrefix) ?>portfolio.php" class="hidden sm:inline-flex bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-xl items-center gap-2">
                <i class="fa-solid fa-folder-open"></i>
                Portfolio
            </a>
            <a href="<?= e($rootPrefix) ?>logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-xl flex items-center gap-2">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
