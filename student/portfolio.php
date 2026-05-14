<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';
require_once '../includes/profile_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    require_csrf();
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $githubLink = sanitize($_POST['github_link'] ?? '');
    $liveDemoLink = sanitize($_POST['live_demo_link'] ?? '');
    $imagePath = null;

    if ($title === '' || $description === '') {
        $error = 'Project title and description are required.';
    }

    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);

        if (!isset($allowedTypes[$mime])) {
            $error = 'Upload a JPG, PNG, or WEBP screenshot.';
        } else {
            $uploadDir = __DIR__ . '/../images/portfolio';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $fileName = 'project_' . $userId . '_' . time() . '.' . $allowedTypes[$mime];
            $target = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = '../images/portfolio/' . $fileName;
            } else {
                $error = 'Screenshot upload failed.';
            }
        }
    }

    if (!$error) {
        createPortfolioProject($conn, $userId, $title, $description, $githubLink, $liveDemoLink, $imagePath);
        redirect('portfolio.php');
    }
}

$projects = getPortfolioProjects($conn, $userId);
$progress = getStudentProgress($conn, $userId);
$tasks = getRoadmapTasks($conn, $userId);
$completedTasks = array_values(array_filter($tasks, fn($task) => $task['status'] === 'completed'));
$certificates = array_values(array_filter($completedTasks, fn($task) => $task['task_type'] === 'certification'));
$favoriteSubjects = decodeProfileList($profile['favorite_subjects'] ?? '[]');
$activityPreferences = decodeProfileList($profile['activity_preferences'] ?? '[]');
$skills = array_values(array_unique(array_filter(array_merge(
    parseSkillTags($profile['skills'] ?? ''),
    $favoriteSubjects,
    $activityPreferences
))));
$avatarUrl = profilePhotoUrl($profile['profile_photo'] ?? '');
$initials = profileInitials($profile['full_name'] ?? 'Student');
$githubLinks = array_values(array_unique(array_filter(array_map(fn($project) => $project['github_link'] ?? '', $projects))));
$demoLinks = array_values(array_unique(array_filter(array_map(fn($project) => $project['live_demo_link'] ?? '', $projects))));
$location = $profile['target_industry'] ?: 'Philippine IT Industry';

$pageTitle = 'Portfolio';
$activePage = 'portfolio';
include '../header.php';
?>

<section class="portfolioHero mb-6">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
        <div class="flex flex-col sm:flex-row gap-5">
            <div class="portfolioAvatar">
                <?php if ($avatarUrl): ?>
                    <img src="<?= e($avatarUrl) ?>" alt="<?= e($profile['full_name']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <p class="text-blue-300 font-semibold mb-2">Professional portfolio</p>
                <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($profile['full_name']) ?></h1>
                <p class="text-xl text-slate-200 mb-3"><?= e($profile['career_path'] ?: 'Career path in progress') ?></p>
                <div class="flex flex-wrap gap-2 text-sm">
                    <span class="badge"><i class="fa-solid fa-location-dot text-blue-300"></i><?= e($location) ?></span>
                    <span class="badge"><i class="fa-solid fa-graduation-cap text-green-300"></i><?= e($profile['student_type'] ?: 'Student') ?></span>
                    <span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10"><?= (int)$progress['readiness'] ?>% readiness</span>
                </div>
            </div>
        </div>
        <a href="profile.php" class="secondaryBtn self-start lg:self-auto">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Profile
        </a>
    </div>
</section>

<div class="grid xl:grid-cols-[minmax(0,1fr)_360px] gap-6">
    <div class="space-y-6">
        <section class="card">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="sectionTitle">About</h2>
                <span class="text-sm text-slate-500">From profile</span>
            </div>
            <p class="text-slate-300 leading-7">
                <?= e($profile['bio'] ?: ($profile['ai_summary'] ?: 'Add a short profile summary to explain your direction, strengths, and career interests.')) ?>
            </p>
            <?php if (!empty($profile['goals'])): ?>
                <div class="mt-4 bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="text-slate-400 text-sm mb-1">Current goal</p>
                    <p class="font-semibold"><?= e($profile['goals']) ?></p>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="sectionTitle">Skills</h2>
                <span class="badge"><?= count($skills) ?> listed</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach (array_slice($skills, 0, 18) as $skill): ?>
                    <span class="skillChip"><?= e($skill) ?></span>
                <?php endforeach; ?>
                <?php if (count($skills) === 0): ?>
                    <p class="text-slate-400">Complete profile discovery and roadmap tasks to build this skills list.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <div>
                    <h2 class="sectionTitle mb-1">Projects</h2>
                    <p class="text-slate-400">Evidence of skills, code, prototypes, and deployed work.</p>
                </div>
                <span class="badge"><?= count($projects) ?> projects</span>
            </div>

            <div class="grid lg:grid-cols-2 gap-4">
                <?php foreach ($projects as $project): ?>
                    <article class="projectCard">
                        <?php if ($project['image']): ?>
                            <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>" class="w-full aspect-video object-cover rounded-xl border border-slate-700 mb-4">
                        <?php else: ?>
                            <div class="w-full aspect-video rounded-xl border border-slate-700 bg-[#020B24] flex items-center justify-center mb-4">
                                <i class="fa-solid fa-folder-open text-3xl text-blue-300"></i>
                            </div>
                        <?php endif; ?>

                        <div class="flex justify-between gap-3 mb-3">
                            <h3 class="text-lg font-bold leading-6"><?= e($project['title']) ?></h3>
                            <span class="badge <?= (int)$project['mentor_verified'] === 1 ? 'text-green-300 border-green-500/30 bg-green-500/10' : 'text-yellow-300 border-yellow-500/30 bg-yellow-500/10' ?>">
                                <?= (int)$project['mentor_verified'] === 1 ? 'Verified' : 'Pending' ?>
                            </span>
                        </div>
                        <p class="text-slate-300 leading-7 mb-4"><?= e($project['description']) ?></p>

                        <div class="flex flex-wrap gap-2">
                            <?php if ($project['github_link']): ?>
                                <a class="secondaryBtn px-3 py-2 text-sm" href="<?= e($project['github_link']) ?>" target="_blank" rel="noopener">
                                    <i class="fa-brands fa-github"></i>
                                    GitHub
                                </a>
                            <?php endif; ?>
                            <?php if ($project['live_demo_link']): ?>
                                <a class="secondaryBtn px-3 py-2 text-sm" href="<?= e($project['live_demo_link']) ?>" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    Live Demo
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (count($projects) === 0): ?>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 text-slate-400">No projects yet. Add your first project in the portfolio editor.</div>
            <?php endif; ?>
        </section>

        <div class="grid lg:grid-cols-2 gap-6">
            <section class="card">
                <h2 class="sectionTitle mb-4">Certificates</h2>
                <div class="space-y-3">
                    <?php foreach ($certificates as $certificate): ?>
                        <div class="portfolioRow">
                            <i class="fa-solid fa-certificate text-yellow-300"></i>
                            <div>
                                <p class="font-semibold"><?= e($certificate['task_title']) ?></p>
                                <p class="text-sm text-slate-500"><?= e($certificate['phase_title']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($certificates) === 0): ?>
                        <p class="text-slate-400">No certificate evidence yet.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <h2 class="sectionTitle mb-4">Experience</h2>
                <div class="space-y-3">
                    <?php foreach (array_slice($projects, 0, 3) as $project): ?>
                        <div class="portfolioRow">
                            <i class="fa-solid fa-briefcase text-blue-300"></i>
                            <div>
                                <p class="font-semibold"><?= e($project['title']) ?></p>
                                <p class="text-sm text-slate-500">Project-based experience</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($projects) === 0): ?>
                        <p class="text-slate-400">Add projects to turn learning work into portfolio experience.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <h2 class="sectionTitle mb-4">Education</h2>
                <div class="portfolioRow">
                    <i class="fa-solid fa-graduation-cap text-green-300"></i>
                    <div>
                        <p class="font-semibold"><?= e($profile['course'] ?: $profile['student_type'] ?: 'Student profile') ?></p>
                        <p class="text-sm text-slate-500"><?= e($profile['year_level'] ?: ($profile['career_path'] ?: 'Career track in progress')) ?></p>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2 class="sectionTitle mb-4">Achievements</h2>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="metricTile">
                        <p class="text-2xl font-bold"><?= (int)$progress['readiness'] ?>%</p>
                        <p class="text-xs text-slate-500">Readiness</p>
                    </div>
                    <div class="metricTile">
                        <p class="text-2xl font-bold"><?= count($completedTasks) ?></p>
                        <p class="text-xs text-slate-500">Tasks</p>
                    </div>
                    <div class="metricTile">
                        <p class="text-2xl font-bold"><?= (int)$progress['verified_projects'] ?></p>
                        <p class="text-xs text-slate-500">Verified</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <aside class="space-y-6">
        <section class="card">
            <h2 class="sectionTitle mb-5">Portfolio Editor</h2>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 mb-4 text-red-200"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_input() ?>
                <label class="block">
                    <span class="block text-sm text-slate-400 mb-2">Project title</span>
                    <input class="inputStyle" name="title" required placeholder="Student services prototype">
                </label>
                <label class="block">
                    <span class="block text-sm text-slate-400 mb-2">Project summary</span>
                    <textarea class="inputStyle min-h-[110px]" name="description" required placeholder="What did you build and what skills does it prove?"></textarea>
                </label>
                <label class="block">
                    <span class="block text-sm text-slate-400 mb-2">GitHub</span>
                    <input class="inputStyle" name="github_link" type="url" placeholder="https://github.com/...">
                </label>
                <label class="block">
                    <span class="block text-sm text-slate-400 mb-2">Live demo</span>
                    <input class="inputStyle" name="live_demo_link" type="url" placeholder="https://...">
                </label>
                <label class="block">
                    <span class="block text-sm text-slate-400 mb-2">Screenshot</span>
                    <input class="inputStyle text-sm" name="image" type="file" accept="image/png,image/jpeg,image/webp">
                </label>
                <button class="primaryBtn w-full" name="add_project" type="submit">
                    <i class="fa-solid fa-folder-plus"></i>
                    Save Project
                </button>
            </form>
        </section>

        <section class="card">
            <h2 class="sectionTitle mb-4">Social Links</h2>
            <div class="space-y-3">
                <?php if (count($githubLinks) > 0): ?>
                    <a href="<?= e($githubLinks[0]) ?>" target="_blank" rel="noopener" class="portfolioRow hover:border-blue-500/60">
                        <i class="fa-brands fa-github text-slate-200"></i>
                        <div>
                            <p class="font-semibold">GitHub</p>
                            <p class="text-sm text-slate-500 break-all"><?= e($githubLinks[0]) ?></p>
                        </div>
                    </a>
                <?php endif; ?>
                <?php if (count($demoLinks) > 0): ?>
                    <a href="<?= e($demoLinks[0]) ?>" target="_blank" rel="noopener" class="portfolioRow hover:border-blue-500/60">
                        <i class="fa-solid fa-arrow-up-right-from-square text-blue-300"></i>
                        <div>
                            <p class="font-semibold">Portfolio Link</p>
                            <p class="text-sm text-slate-500 break-all"><?= e($demoLinks[0]) ?></p>
                        </div>
                    </a>
                <?php endif; ?>
                <?php if (count($githubLinks) === 0 && count($demoLinks) === 0): ?>
                    <p class="text-slate-400">Add GitHub or live demo links to projects to show them here. LinkedIn and Behance can be added when profile fields exist.</p>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>

<style>
    .portfolioHero{background:linear-gradient(135deg,rgba(37,99,235,.18),rgba(22,35,56,.92));border:1px solid #334155;border-radius:16px;padding:24px;box-shadow:0 20px 46px rgba(0,0,0,.28);}
    .portfolioAvatar{width:104px;height:104px;border-radius:16px;background:#2563eb;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;overflow:hidden;box-shadow:inset 0 0 0 1px rgba(255,255,255,.16);}
    .skillChip{display:inline-flex;align-items:center;border:1px solid #334155;background:#020B24;border-radius:999px;padding:8px 11px;color:#cbd5e1;font-size:14px;font-weight:700;}
    .projectCard{background:#020B24;border:1px solid #334155;border-radius:14px;padding:16px;}
    .portfolioRow{display:flex;align-items:flex-start;gap:12px;background:#020B24;border:1px solid #334155;border-radius:12px;padding:12px;transition:border-color .18s ease,background .18s ease;}
    .portfolioRow:hover{background:#0f172a;}
    .portfolioRow i{width:20px;text-align:center;margin-top:3px;}
    .metricTile{background:#020B24;border:1px solid #334155;border-radius:12px;padding:12px 8px;}
</style>

<?php include '../footer.php'; ?>
