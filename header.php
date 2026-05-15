<?php
$pageTitle = $pageTitle ?? 'Map My Future';
$activePage = $activePage ?? 'dashboard';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$inStudentDir = strpos($scriptName, '/student/') !== false;
$inAdminDir = strpos($scriptName, '/admin/') !== false;
$inMentorDir = strpos($scriptName, '/mentor/') !== false;
$inEmployerDir = strpos($scriptName, '/employer/') !== false;
$navContext = $navContext ?? null;
$breadcrumbs = $breadcrumbs ?? [];
$rootPrefix = ($inStudentDir || $inAdminDir || $inMentorDir || $inEmployerDir) ? '../' : '';
$studentPrefix = $inStudentDir ? '' : $rootPrefix . 'student/';
$role = $_SESSION['role'] ?? 'student';
$userName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';
$firstName = strtok($userName, ' ') ?: $userName;
$avatarInitial = strtoupper(substr($userName, 0, 1));
$accountRoleLabel = ucfirst($role);
$profilePhoto = $_SESSION['profile_photo'] ?? null;

if (isset($conn, $_SESSION['user_id'])) {
    $profileStmt = $conn->prepare("SELECT full_name, email, profile_photo FROM users WHERE user_id = ? LIMIT 1");
    if ($profileStmt) {
        $headerUserId = (int)$_SESSION['user_id'];
        $profileStmt->bind_param("i", $headerUserId);
        $profileStmt->execute();
        $profileRow = $profileStmt->get_result()->fetch_assoc();
        $userName = $profileRow['full_name'] ?? $userName;
        $userEmail = $profileRow['email'] ?? $userEmail;
        $firstName = strtok($userName, ' ') ?: $userName;
        $avatarInitial = strtoupper(substr($userName, 0, 1));
        $profilePhoto = $profilePhoto ?: ($profileRow['profile_photo'] ?? null);
    }
}

$profileImageSrc = '';
if (!empty($profilePhoto)) {
    $isAbsoluteProfilePhoto = preg_match('/^(https?:)?\/\//', $profilePhoto) || str_starts_with($profilePhoto, '/') || str_starts_with($profilePhoto, '../');
    $profileImageSrc = $isAbsoluteProfilePhoto ? $profilePhoto : $rootPrefix . $profilePhoto;
}

if ($navContext === null) {
    $navContext = $inAdminDir ? 'admin' : ($inMentorDir ? 'mentor' : ($inEmployerDir ? 'employer' : 'student'));
}

if ($navContext === 'admin') {
    $brand = ['Admin Center', 'dashboard.php', 'fa-shield-halved'];
    $roleLabel = 'Admin';
    $navItems = [
        'dashboard' => ['Dashboard', 'dashboard.php', 'fa-chart-line'],
        'students' => ['Users', 'students.php', 'fa-users'],
        'analytics' => ['Analytics', 'analytics.php', 'fa-chart-pie'],
        'subscriptions' => ['Subscription Reports', 'sales_reports.php', 'fa-crown'],
        'verification' => ['Verification', 'verification_center.php', 'fa-user-check'],
    ];
} elseif ($navContext === 'mentor') {
    $brand = ['Mentor Center', 'dashboard.php', 'fa-users'];
    $roleLabel = 'Mentor';
    $navItems = [
        'dashboard' => ['Dashboard', 'dashboard.php', 'fa-house'],
        'students' => ['Students', 'students.php', 'fa-user-graduate'],
        'tasks' => ['Tasks', 'create_task.php', 'fa-list-check'],
        'profile' => ['Profile', 'profile.php', 'fa-id-card'],
        'portfolio' => ['Portfolio', 'portfolio.php', 'fa-folder-open'],
        'requests' => ['Requests', 'enrollment_requests.php', 'fa-user-plus'],
        'submissions' => ['Submissions', 'review_submission.php', 'fa-file-circle-check'],
    ];
} elseif ($navContext === 'employer') {
    $brand = ['Employer Center', 'dashboard.php', 'fa-building'];
    $roleLabel = 'Employer';
    $navItems = [
        'dashboard' => ['Dashboard', 'dashboard.php', 'fa-chart-line'],
        'jobs' => ['Job Posts', 'jobs.php', 'fa-briefcase'],
        'applicants' => ['Applicants', 'applicants.php', 'fa-users'],
    ];
} else {
    $brand = ['Map My Future', $studentPrefix . 'dashboard.php', 'fa-route'];
    $roleLabel = 'Student';
    $navItems = [
        'dashboard' => ['Dashboard', $studentPrefix . 'dashboard.php', 'fa-house'],
        'roadmap' => ['Roadmap', $studentPrefix . 'roadmap.php', 'fa-route'],
        'assessments' => ['Assessment', $studentPrefix . 'assessments.php', 'fa-brain'],
        'skill_gap' => ['Skill Gap', $studentPrefix . 'skill_gap.php', 'fa-bolt'],
        'find_mentor' => ['Find Mentor', $studentPrefix . 'find_mentors.php', 'fa-user-plus'],
        'my_mentor' => ['My Mentor', $studentPrefix . 'mentors.php', 'fa-user-check'],
        'subscription' => ['Premium', $studentPrefix . 'subscription.php', 'fa-crown'],
        'jobs' => ['Job Market', $studentPrefix . 'job_market.php', 'fa-briefcase'],
    ];
}

$profileUrl = null;
$portfolioUrl = null;
$premiumUrl = null;

if ($role === 'student') {
    $studentAccountPrefix = $inStudentDir ? '' : $rootPrefix . 'student/';
    $profileUrl = $studentAccountPrefix . 'profile.php';
    $portfolioUrl = $studentAccountPrefix . 'portfolio.php';
    $premiumUrl = $studentAccountPrefix . 'subscription.php';
} elseif ($role === 'mentor') {
    $mentorAccountPrefix = $inMentorDir ? '' : $rootPrefix . 'mentor/';
    $profileUrl = $mentorAccountPrefix . 'profile.php';
    $portfolioUrl = $mentorAccountPrefix . 'portfolio.php';
} elseif ($role === 'employer') {
    $employerAccountPrefix = $inEmployerDir ? '' : $rootPrefix . 'employer/';
    $profileUrl = $employerAccountPrefix . 'profile.php';
} elseif ($role === 'admin') {
    $adminAccountPrefix = $inAdminDir ? '' : $rootPrefix . 'admin/';
    $profileUrl = $adminAccountPrefix . 'profile.php';
}

$profileMenuItems = [
    ['Profile', 'fa-user', $profileUrl, $activePage === 'profile'],
    ['Settings', 'fa-gear', $profileUrl, $activePage === 'profile'],
    ['Notifications', 'fa-bell', null, false],
    ['My Portfolio', 'fa-folder-open', $portfolioUrl, $activePage === 'portfolio'],
];

$roleProfile = [];
if (isset($conn, $_SESSION['user_id'])) {
    $headerUserId = (int)$_SESSION['user_id'];
    if ($role === 'student') {
        $roleStmt = $conn->prepare("SELECT course, career_path FROM student_profiles WHERE user_id = ? LIMIT 1");
    } elseif ($role === 'mentor') {
        $roleStmt = $conn->prepare("SELECT specialization, certifications FROM mentor_profiles WHERE user_id = ? LIMIT 1");
    } elseif ($role === 'employer') {
        $roleStmt = $conn->prepare("SELECT company_name, industry FROM employer_profiles WHERE user_id = ? LIMIT 1");
    } else {
        $roleStmt = null;
    }

    if ($roleStmt) {
        $roleStmt->bind_param("i", $headerUserId);
        $roleStmt->execute();
        $roleProfile = $roleStmt->get_result()->fetch_assoc() ?: [];
    }
}

$profilePanelFields = [
    ['Name', $userName],
    ['Email', $userEmail ?: 'Not available'],
    ['Role', $accountRoleLabel],
];
if ($role === 'student') {
    $profilePanelFields[] = ['Course', $roleProfile['course'] ?? 'Not set'];
    $profilePanelFields[] = ['Roadmap', $roleProfile['career_path'] ?? 'Not set'];
} elseif ($role === 'mentor') {
    $profilePanelFields[] = ['Expertise', $roleProfile['specialization'] ?? 'Not set'];
    $profilePanelFields[] = ['Certifications', $roleProfile['certifications'] ?? 'Not set'];
} elseif ($role === 'employer') {
    $profilePanelFields[] = ['Company', $roleProfile['company_name'] ?? 'Not set'];
    $profilePanelFields[] = ['Industry', $roleProfile['industry'] ?? 'Not set'];
} elseif ($role === 'admin') {
    $profilePanelFields[] = ['Admin Profile', 'Administrative account information'];
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
        .card,.statCard{background:#162338;border:1px solid #334155;padding:24px;border-radius:16px;}
        .sectionTitle{font-size:24px;font-weight:700;}
        .navBtn,.navActive{display:inline-flex;min-height:38px;gap:7px;align-items:center;justify-content:center;padding:8px 11px;border-radius:12px;white-space:nowrap;transition:.2s;font-size:13px;font-weight:700;}
        .navActive{color:#dbeafe;background:rgba(37,99,235,.22);border:1px solid rgba(59,130,246,.42);}
        .navBtn{color:#cbd5e1;border:1px solid transparent;}
        .navBtn:hover{color:#93c5fd;background:#162338;border-color:#334155;}
        .topShell{position:sticky;top:0;z-index:50;background:rgba(2,11,36,.96);backdrop-filter:blur(14px);border-bottom:1px solid #334155;}
        .mobileMenu{position:absolute;left:1rem;right:1rem;top:calc(100% + .5rem);background:#162338;border:1px solid #334155;border-radius:14px;padding:12px;box-shadow:0 24px 50px rgba(0,0,0,.35);}
        .navScroller{display:flex;gap:6px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;-ms-overflow-style:none;max-width:100%;}
        .navScroller::-webkit-scrollbar{display:none;}
        .navArrow{width:36px;min-width:36px;height:38px;border-radius:12px;background:#162338;border:1px solid #334155;color:#bfdbfe;display:inline-flex;align-items:center;justify-content:center;transition:.2s;}
        .navArrow:hover{border-color:#3B82F6;background:#1e293b;}
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
        .roleBadge{display:inline-flex;align-items:center;min-height:34px;border:1px solid rgba(59,130,246,.35);background:rgba(59,130,246,.12);color:#bfdbfe;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:800;}
        .avatar{width:40px;height:40px;border-radius:999px;background:#3B82F6;display:flex;align-items:center;justify-content:center;font-weight:800;overflow:hidden;position:relative;box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);}
        .avatar img{width:100%;height:100%;object-fit:cover;}
        .profileDropdown{position:relative;}
        .profileTrigger{min-height:42px;display:inline-flex;align-items:center;gap:9px;border:1px solid rgba(148,163,184,.24);background:rgba(15,23,42,.78);border-radius:12px;padding:4px 10px 4px 4px;color:#e2e8f0;box-shadow:0 10px 26px rgba(0,0,0,.18);transition:transform .18s ease,background .18s ease,border-color .18s ease,box-shadow .18s ease;}
        .profileTrigger:hover,.profileTrigger:focus-visible{transform:translateY(-1px);border-color:rgba(59,130,246,.55);background:rgba(30,41,59,.88);box-shadow:0 14px 34px rgba(0,0,0,.26),0 0 0 3px rgba(59,130,246,.12);outline:none;}
        .profileTrigger:hover .avatar,.profileTrigger:focus-visible .avatar{transform:scale(1.04);}
        .profileTrigger .avatar{transition:transform .18s ease;}
        .profileName{max-width:110px;font-size:14px;font-weight:800;line-height:1;color:#f8fafc;}
        .profileChevron{font-size:12px;color:#93c5fd;transition:transform .2s ease;}
        .profileDropdown.isOpen .profileChevron{transform:rotate(180deg);}
        .statusDot{position:absolute;right:1px;bottom:1px;width:11px;height:11px;border-radius:999px;background:#22c55e;border:2px solid #0f172a;box-shadow:0 0 0 2px rgba(34,197,94,.15);}
        .profileMenu{position:absolute;right:0;top:calc(100% + 10px);width:min(320px,calc(100vw - 2rem));padding:8px;border-radius:12px;background:rgba(15,23,42,.98);border:1px solid rgba(148,163,184,.22);box-shadow:0 22px 54px rgba(0,0,0,.46),0 0 0 1px rgba(255,255,255,.04);backdrop-filter:blur(18px);opacity:0;transform:translateY(-8px);pointer-events:none;transition:opacity .18s ease,transform .18s ease;z-index:80;}
        .profileDropdown.isOpen .profileMenu{opacity:1;transform:translateY(0);pointer-events:auto;}
        .profileMenuHeader{display:flex;align-items:center;gap:13px;padding:12px 12px 14px;border-bottom:1px solid rgba(148,163,184,.16);margin-bottom:8px;}
        .profileMenuHeader .avatar{width:54px;height:54px;font-size:20px;flex:0 0 auto;}
        .profileMenuName{font-weight:800;color:#f8fafc;line-height:1.25;}
        .profileMenuRole{color:#93c5fd;font-size:13px;font-weight:700;margin-top:3px;}
        .profileMenuEmail{color:#94a3b8;font-size:12px;margin-top:2px;}
        .profileMenuList{display:grid;gap:4px;}
        .profileMenuItem{min-height:40px;display:flex;align-items:center;gap:10px;border-radius:12px;padding:9px 10px;color:#cbd5e1;font-weight:700;transition:background .16s ease,color .16s ease,transform .16s ease;}
        .profileMenuItem i{width:18px;text-align:center;color:#93c5fd;font-size:14px;}
        .profileMenuItem:hover,.profileMenuItem:focus-visible{background:rgba(59,130,246,.12);color:#f8fafc;outline:none;}
        .profileMenuItem.isActive{background:rgba(59,130,246,.18);color:#bfdbfe;}
        .profileMenuItem.isDisabled{color:#64748b;cursor:not-allowed;}
        .profileMenuItem.isDisabled i{color:#64748b;}
        .profileMenuDivider{height:1px;background:rgba(148,163,184,.16);margin:8px 4px;}
        .profileLogout{color:#fecaca;}
        .profileLogout i{color:#f87171;}
        .profileLogout:hover,.profileLogout:focus-visible{background:rgba(239,68,68,.12);color:#fff;}
        .profilePanelOverlay{position:fixed;inset:0;z-index:90;background:rgba(0,0,0,.62);display:flex;align-items:flex-start;justify-content:flex-end;padding:88px 24px 24px;}
        .profilePanelOverlay[hidden]{display:none;}
        .profilePanel{width:min(460px,100%);background:#162338;border:1px solid #334155;border-radius:14px;box-shadow:0 28px 70px rgba(0,0,0,.48);padding:20px;}
        .profilePanelField{background:#020B24;border:1px solid #334155;border-radius:12px;padding:12px;}
        .profilePanelField span{display:block;color:#94a3b8;font-size:12px;margin-bottom:4px;}
        .profilePanelField strong{display:block;color:#f8fafc;font-size:14px;line-height:1.45;}
        .floatingBack{position:sticky;top:82px;z-index:30;width:max-content;max-width:100%;border-radius:999px;background:#162338;border:1px solid #334155;display:inline-flex;align-items:center;gap:9px;color:#bfdbfe;box-shadow:0 10px 28px rgba(0,0,0,.25);transition:.2s;padding:10px 14px;margin-bottom:18px;}
        .floatingBack:hover{transform:translateX(-3px);border-color:#3B82F6;box-shadow:0 0 22px rgba(59,130,246,.28);}
        .breadcrumbs{display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:#94a3b8;font-size:14px;margin-bottom:18px;}
        .breadcrumbs a{color:#bfdbfe;}
        .breadcrumbs i{font-size:11px;color:#475569;}
        .masonryCards{columns:1;column-gap:1.25rem;}
        .masonryCards>*{break-inside:avoid;margin-bottom:1.25rem;}
        @media (min-width:768px){.masonryCards{columns:2;}}
        @media (min-width:1280px){.masonryCards{columns:3;}}
        @media (max-width: 1023px){.floatingBack{top:76px;}.mobileMenu .navBtn,.mobileMenu .navActive{min-width:max-content;}.mobileMenu .profileDropdown{width:100%;}.mobileMenu .profileTrigger{width:100%;justify-content:space-between;border-radius:12px;padding:6px 12px 6px 6px;}.mobileMenu .profileMenu{position:static;width:100%;margin-top:10px;transform:translateY(-4px);}.mobileMenu .profileDropdown.isOpen .profileMenu{transform:translateY(0);}.profilePanelOverlay{align-items:flex-end;padding:16px;}.profilePanel{width:100%;}}
        @media (max-width: 480px){.profileName{display:none;}.profileMenu{right:auto;left:50%;transform:translate(-50%,-8px);}.profileDropdown.isOpen .profileMenu{transform:translate(-50%,0);}.mobileMenu .profileMenu,.mobileMenu .profileDropdown.isOpen .profileMenu{transform:none;}}
    </style>
</head>
<body class="min-h-screen">
<nav class="topShell">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-3">
        <div class="flex items-center justify-between gap-4">
            <a href="<?= e($brand[1]) ?>" class="text-xl font-bold text-blue-400 flex items-center gap-3 shrink-0">
                <i class="fa-solid <?= e($brand[2]) ?>"></i>
                <?= e($brand[0]) ?>
            </a>

            <div class="hidden lg:grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2 flex-1 min-w-0 max-w-4xl">
                <button type="button" class="navArrow" data-nav-arrow="left" aria-label="Scroll navigation left">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="navScroller" data-nav-scroller>
                    <?php foreach ($navItems as $key => $item): ?>
                        <a href="<?= e($item[1]) ?>" class="<?= $activePage === $key ? 'navActive' : 'navBtn' ?>">
                            <i class="fa-solid <?= e($item[2]) ?>"></i>
                            <?= e($item[0]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="navArrow" data-nav-arrow="right" aria-label="Scroll navigation right">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <div class="hidden lg:flex items-center gap-3 shrink-0">
                <div class="profileDropdown" data-profile-menu>
                    <button type="button" class="profileTrigger" data-profile-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Open profile menu">
                        <span class="avatar" data-avatar-initial="<?= e($avatarInitial) ?>" aria-hidden="true">
                            <?php if ($profileImageSrc): ?>
                                <img src="<?= e($profileImageSrc) ?>" alt="" data-profile-avatar-img>
                            <?php else: ?>
                                <?= e($avatarInitial) ?>
                            <?php endif; ?>
                            <span class="statusDot"></span>
                        </span>
                        <span class="profileName truncate"><?= e($firstName) ?></span>
                        <i class="fa-solid fa-chevron-down profileChevron" aria-hidden="true"></i>
                    </button>
                    <div class="profileMenu" data-profile-panel role="menu" hidden>
                        <div class="profileMenuHeader">
                            <div class="avatar" data-avatar-initial="<?= e($avatarInitial) ?>">
                                <?php if ($profileImageSrc): ?>
                                    <img src="<?= e($profileImageSrc) ?>" alt="<?= e($userName) ?>" data-profile-avatar-img>
                                <?php else: ?>
                                    <?= e($avatarInitial) ?>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="profileMenuName truncate"><?= e($userName) ?></p>
                                <p class="profileMenuEmail truncate"><?= e($userEmail) ?></p>
                                <p class="profileMenuRole"><?= e($accountRoleLabel) ?></p>
                            </div>
                        </div>
                        <div class="profileMenuList">
                            <?php foreach ($profileMenuItems as $menuItem): ?>
                                <?php if ($menuItem[2]): ?>
                                    <a href="<?= e($menuItem[2]) ?>" class="profileMenuItem <?= $menuItem[3] ? 'isActive' : '' ?>" role="menuitem" <?= $menuItem[0] === 'Profile' ? 'data-profile-panel-open' : '' ?>>
                                        <i class="fa-solid <?= e($menuItem[1]) ?>"></i>
                                        <?= e($menuItem[0] === 'Profile' ? 'My Profile' : $menuItem[0]) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="profileMenuItem isDisabled" role="menuitem" aria-disabled="true">
                                        <i class="fa-solid <?= e($menuItem[1]) ?>"></i>
                                        <?= e($menuItem[0]) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div class="profileMenuDivider"></div>
                            <a href="<?= e($rootPrefix) ?>logout.php" class="profileMenuItem profileLogout" role="menuitem">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <button id="mobileMenuBtn" type="button" class="lg:hidden secondaryBtn min-h-[44px] px-4 py-2" aria-expanded="false" aria-controls="mobileMenu">
                <i class="fa-solid fa-bars"></i>
                Menu
            </button>
        </div>

        <div id="mobileMenu" class="mobileMenu hidden lg:hidden">
            <div class="border-b border-slate-700 pb-3 mb-3">
                <div class="profileDropdown" data-profile-menu>
                    <button type="button" class="profileTrigger" data-profile-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Open profile menu">
                        <span class="flex items-center gap-3 min-w-0">
                            <span class="avatar shrink-0" data-avatar-initial="<?= e($avatarInitial) ?>" aria-hidden="true">
                                <?php if ($profileImageSrc): ?>
                                    <img src="<?= e($profileImageSrc) ?>" alt="" data-profile-avatar-img>
                                <?php else: ?>
                                    <?= e($avatarInitial) ?>
                                <?php endif; ?>
                                <span class="statusDot"></span>
                            </span>
                            <span class="min-w-0 text-left">
                                <span class="block font-bold truncate"><?= e($userName) ?></span>
                                <span class="block text-sm text-blue-300 font-semibold"><?= e($accountRoleLabel) ?></span>
                            </span>
                        </span>
                        <i class="fa-solid fa-chevron-down profileChevron" aria-hidden="true"></i>
                    </button>
                    <div class="profileMenu" data-profile-panel role="menu" hidden>
                        <div class="profileMenuHeader">
                            <div class="avatar" data-avatar-initial="<?= e($avatarInitial) ?>">
                                <?php if ($profileImageSrc): ?>
                                    <img src="<?= e($profileImageSrc) ?>" alt="<?= e($userName) ?>" data-profile-avatar-img>
                                <?php else: ?>
                                    <?= e($avatarInitial) ?>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="profileMenuName truncate"><?= e($userName) ?></p>
                                <p class="profileMenuEmail truncate"><?= e($userEmail) ?></p>
                                <p class="profileMenuRole"><?= e($accountRoleLabel) ?></p>
                            </div>
                        </div>
                        <div class="profileMenuList">
                            <?php foreach ($profileMenuItems as $menuItem): ?>
                                <?php if ($menuItem[2]): ?>
                                    <a href="<?= e($menuItem[2]) ?>" class="profileMenuItem <?= $menuItem[3] ? 'isActive' : '' ?>" role="menuitem" <?= $menuItem[0] === 'Profile' ? 'data-profile-panel-open' : '' ?>>
                                        <i class="fa-solid <?= e($menuItem[1]) ?>"></i>
                                        <?= e($menuItem[0] === 'Profile' ? 'My Profile' : $menuItem[0]) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="profileMenuItem isDisabled" role="menuitem" aria-disabled="true">
                                        <i class="fa-solid <?= e($menuItem[1]) ?>"></i>
                                        <?= e($menuItem[0]) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div class="profileMenuDivider"></div>
                            <a href="<?= e($rootPrefix) ?>logout.php" class="profileMenuItem profileLogout" role="menuitem">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2">
                <button type="button" class="navArrow" data-nav-arrow="left" aria-label="Scroll mobile navigation left">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="navScroller" data-nav-scroller>
                    <?php foreach ($navItems as $key => $item): ?>
                        <a href="<?= e($item[1]) ?>" class="<?= $activePage === $key ? 'navActive' : 'navBtn' ?>">
                            <i class="fa-solid <?= e($item[2]) ?> w-5"></i>
                            <?= e($item[0]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="navArrow" data-nav-arrow="right" aria-label="Scroll mobile navigation right">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<div class="profilePanelOverlay" data-profile-account-panel hidden>
    <section class="profilePanel" role="dialog" aria-modal="true" aria-labelledby="profilePanelTitle">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div class="flex items-center gap-3 min-w-0">
                <div class="avatar shrink-0" data-avatar-initial="<?= e($avatarInitial) ?>">
                    <?php if ($profileImageSrc): ?>
                        <img src="<?= e($profileImageSrc) ?>" alt="<?= e($userName) ?>" data-profile-avatar-img>
                    <?php else: ?>
                        <?= e($avatarInitial) ?>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <h2 id="profilePanelTitle" class="text-xl font-bold truncate"><?= e($userName) ?></h2>
                    <p class="text-sm text-slate-400 truncate"><?= e($userEmail ?: $accountRoleLabel) ?></p>
                </div>
            </div>
            <button type="button" class="secondaryBtn px-3 py-2" data-profile-panel-close aria-label="Close profile panel">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="grid gap-3 mb-5">
            <?php foreach ($profilePanelFields as $field): ?>
                <div class="profilePanelField">
                    <span><?= e($field[0]) ?></span>
                    <strong><?= e($field[1]) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap gap-3">
            <?php if ($profileUrl): ?>
                <a href="<?= e($profileUrl) ?>" class="primaryBtn"><i class="fa-solid fa-pen"></i> Edit Profile</a>
            <?php endif; ?>
            <button type="button" class="secondaryBtn" data-profile-panel-close>Close</button>
        </div>
    </section>
</div>

<main class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
<?php if (!empty($backUrl)): ?>
    <a href="<?= e($backUrl) ?>" class="floatingBack" title="<?= e($backLabel ?? 'Back') ?>">
        <i class="fa-solid fa-arrow-left"></i>
        <span><?= e($backLabel ?? 'Back') ?></span>
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
    const result = await response.json();

    if (result.csrf_token) {
        window.csrfToken = result.csrf_token;
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            tokenMeta.content = result.csrf_token;
        }
    }

    return result;
};

const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileMenu = document.getElementById('mobileMenu');
mobileMenuBtn?.addEventListener('click', () => {
    const isOpen = !mobileMenu.classList.contains('hidden');
    mobileMenu.classList.toggle('hidden', isOpen);
    mobileMenuBtn.setAttribute('aria-expanded', String(!isOpen));
    if (isOpen) {
        closeAllProfileMenus();
    }
});

const profileMenus = Array.from(document.querySelectorAll('[data-profile-menu]'));
const accountPanel = document.querySelector('[data-profile-account-panel]');
const accountPanelOpeners = document.querySelectorAll('[data-profile-panel-open]');
const accountPanelClosers = document.querySelectorAll('[data-profile-panel-close]');

document.querySelectorAll('[data-profile-avatar-img]').forEach((image) => {
    image.addEventListener('error', () => {
        const avatar = image.closest('.avatar');
        if (!avatar) return;
        image.remove();
        if (!avatar.querySelector('[data-avatar-fallback]')) {
            const fallback = document.createElement('span');
            fallback.dataset.avatarFallback = 'true';
            fallback.textContent = avatar.dataset.avatarInitial || '?';
            avatar.prepend(fallback);
        }
    });
});

function getFocusableProfileItems(menu) {
    return Array.from(menu.querySelectorAll('a[role="menuitem"]'));
}

function closeProfileMenu(menu) {
    const toggle = menu.querySelector('[data-profile-toggle]');
    const panel = menu.querySelector('[data-profile-panel]');
    menu.classList.remove('isOpen');
    toggle?.setAttribute('aria-expanded', 'false');
    if (panel) {
        window.setTimeout(() => {
            if (!menu.classList.contains('isOpen')) {
                panel.hidden = true;
            }
        }, 180);
    }
}

function closeAllProfileMenus(exceptMenu = null) {
    profileMenus.forEach((menu) => {
        if (menu !== exceptMenu) {
            closeProfileMenu(menu);
        }
    });
}

function openProfileMenu(menu, focusFirst = false) {
    const toggle = menu.querySelector('[data-profile-toggle]');
    const panel = menu.querySelector('[data-profile-panel]');
    closeAllProfileMenus(menu);
    if (panel) {
        panel.hidden = false;
    }
    requestAnimationFrame(() => {
        menu.classList.add('isOpen');
        toggle?.setAttribute('aria-expanded', 'true');
        if (focusFirst) {
            getFocusableProfileItems(menu)[0]?.focus();
        }
    });
}

profileMenus.forEach((menu) => {
    const toggle = menu.querySelector('[data-profile-toggle]');
    const panel = menu.querySelector('[data-profile-panel]');

    toggle?.addEventListener('click', () => {
        if (menu.classList.contains('isOpen')) {
            closeProfileMenu(menu);
        } else {
            openProfileMenu(menu);
        }
    });

    toggle?.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openProfileMenu(menu, true);
        }
    });

    panel?.addEventListener('keydown', (event) => {
        const items = getFocusableProfileItems(menu);
        const currentIndex = items.indexOf(document.activeElement);

        if (event.key === 'Escape') {
            event.preventDefault();
            closeProfileMenu(menu);
            toggle?.focus();
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            const nextIndex = currentIndex === -1
                ? 0
                : (currentIndex + direction + items.length) % items.length;
            items[nextIndex]?.focus();
        }
    });
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-profile-menu]')) {
        closeAllProfileMenus();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAllProfileMenus();
        if (accountPanel && !accountPanel.hidden) {
            accountPanel.hidden = true;
        }
    }
});

accountPanelOpeners.forEach((link) => {
    link.addEventListener('click', (event) => {
        if (!accountPanel) return;
        event.preventDefault();
        closeAllProfileMenus();
        accountPanel.hidden = false;
        accountPanel.querySelector('[data-profile-panel-close]')?.focus();
    });
});

accountPanelClosers.forEach((button) => {
    button.addEventListener('click', () => {
        if (accountPanel) {
            accountPanel.hidden = true;
        }
    });
});

accountPanel?.addEventListener('click', (event) => {
    if (event.target === accountPanel) {
        accountPanel.hidden = true;
    }
});

document.querySelectorAll('[data-nav-arrow]').forEach((button) => {
    button.addEventListener('click', () => {
        const wrapper = button.parentElement;
        const scroller = wrapper?.querySelector('[data-nav-scroller]');
        if (!scroller) return;
        const direction = button.dataset.navArrow === 'left' ? -1 : 1;
        scroller.scrollBy({left: direction * Math.max(180, scroller.clientWidth * 0.75), behavior: 'smooth'});
    });
});
</script>
