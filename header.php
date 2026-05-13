<?php
$pageTitle = $pageTitle ?? 'Map My Future';
$activePage = $activePage ?? 'dashboard';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$inStudentDir = strpos($scriptName, '/student/') !== false;
$inAdminDir = strpos($scriptName, '/admin/') !== false;
$inMentorDir = strpos($scriptName, '/mentor/') !== false;
$inEmployerDir = strpos($scriptName, '/employer/') !== false;
$breadcrumbs = $breadcrumbs ?? [];
$rootPrefix = ($inStudentDir || $inAdminDir || $inMentorDir || $inEmployerDir) ? '../' : '';
$studentPrefix = $inStudentDir ? '' : 'student/';
$role = $_SESSION['role'] ?? 'student';
$userName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'User';
$avatarInitial = strtoupper(substr($userName, 0, 1));

if ($inAdminDir) {
    $brand = ['Admin Center', 'dashboard.php', 'fa-shield-halved'];
    $roleLabel = 'Admin';
    $navItems = [
        'dashboard' => ['Dashboard', 'dashboard.php', 'fa-chart-line'],
        'students' => ['Users', 'students.php', 'fa-users'],
        'sales' => ['Reports', 'sales_reports.php', 'fa-file-lines'],
        'analytics' => ['Analytics', 'analytics.php', 'fa-chart-pie'],
        'subscriptions' => ['Subscription Reports', 'sales_reports.php', 'fa-crown'],
        'verification' => ['Verification', 'verification_center.php', 'fa-user-check'],
    ];
} elseif ($inMentorDir) {
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
} elseif ($inEmployerDir) {
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
        'mentors' => ['Mentors', $studentPrefix . 'find_mentors.php', 'fa-users'],
        'subscription' => ['Premium', $studentPrefix . 'subscription.php', 'fa-crown'],
        'portfolio' => ['Portfolio', $studentPrefix . 'portfolio.php', 'fa-folder-open'],
        'jobs' => ['Job Market', $studentPrefix . 'job_market.php', 'fa-briefcase'],
        'mentor_tasks' => ['Tasks', $studentPrefix . 'mentor_tasks.php', 'fa-list-check'],
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
        .navBtn,.navActive{display:inline-flex;min-height:44px;gap:9px;align-items:center;justify-content:center;padding:10px 13px;border-radius:14px;white-space:nowrap;transition:.2s;font-size:14px;}
        .navActive{color:#bfdbfe;background:#1e3a8a;border:1px solid rgba(59,130,246,.45);}
        .navBtn{color:#cbd5e1;border:1px solid transparent;}
        .navBtn:hover{color:#93c5fd;background:#162338;border-color:#334155;}
        .topShell{position:sticky;top:0;z-index:50;background:rgba(2,11,36,.96);backdrop-filter:blur(14px);border-bottom:1px solid #334155;}
        .mobileMenu{position:absolute;left:1rem;right:1rem;top:calc(100% + .5rem);background:#162338;border:1px solid #334155;border-radius:18px;padding:12px;box-shadow:0 24px 50px rgba(0,0,0,.35);}
        .navScroller{display:flex;gap:6px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;-ms-overflow-style:none;max-width:100%;}
        .navScroller::-webkit-scrollbar{display:none;}
        .navArrow{width:38px;min-width:38px;height:44px;border-radius:14px;background:#162338;border:1px solid #334155;color:#bfdbfe;display:inline-flex;align-items:center;justify-content:center;transition:.2s;}
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
        .avatar{width:40px;height:40px;border-radius:999px;background:#3B82F6;display:flex;align-items:center;justify-content:center;font-weight:800;}
        .floatingBack{position:sticky;top:82px;z-index:30;width:max-content;max-width:100%;border-radius:999px;background:#162338;border:1px solid #334155;display:inline-flex;align-items:center;gap:9px;color:#bfdbfe;box-shadow:0 10px 28px rgba(0,0,0,.25);transition:.2s;padding:10px 14px;margin-bottom:18px;}
        .floatingBack:hover{transform:translateX(-3px);border-color:#3B82F6;box-shadow:0 0 22px rgba(59,130,246,.28);}
        .breadcrumbs{display:flex;align-items:center;gap:10px;flex-wrap:wrap;color:#94a3b8;font-size:14px;margin-bottom:18px;}
        .breadcrumbs a{color:#bfdbfe;}
        .breadcrumbs i{font-size:11px;color:#475569;}
        .masonryCards{columns:1;column-gap:1.25rem;}
        .masonryCards>*{break-inside:avoid;margin-bottom:1.25rem;}
        @media (min-width:768px){.masonryCards{columns:2;}}
        @media (min-width:1280px){.masonryCards{columns:3;}}
        @media (max-width: 1023px){.floatingBack{top:76px;}.mobileMenu .navBtn,.mobileMenu .navActive{min-width:max-content;}}
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
                <span class="roleBadge"><?= e($roleLabel) ?></span>
                <div class="avatar"><?= e($avatarInitial) ?></div>
                <a href="<?= e($rootPrefix) ?>logout.php" class="dangerBtn min-h-[44px] px-4 py-2">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </div>

            <button id="mobileMenuBtn" type="button" class="lg:hidden secondaryBtn min-h-[44px] px-4 py-2" aria-expanded="false" aria-controls="mobileMenu">
                <i class="fa-solid fa-bars"></i>
                Menu
            </button>
        </div>

        <div id="mobileMenu" class="mobileMenu hidden lg:hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-700 pb-3 mb-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="avatar shrink-0"><?= e($avatarInitial) ?></div>
                    <div class="min-w-0">
                        <p class="font-bold truncate"><?= e($userName) ?></p>
                        <span class="roleBadge mt-1"><?= e($roleLabel) ?></span>
                    </div>
                </div>
                <a href="<?= e($rootPrefix) ?>logout.php" class="dangerBtn min-h-[44px] px-4 py-2">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
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
