<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
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

$pageTitle = 'Portfolio';
$activePage = 'portfolio';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Portfolio module</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Career Portfolio</h1>
    <p class="text-slate-400">Add project proof, screenshots, GitHub links, and live demos. Mentor verification is stored for future mentor workflows.</p>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-1">
        <div class="card sticky top-24">
            <h2 class="sectionTitle mb-5">Add Project</h2>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 mb-4 text-red-200"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input class="inputStyle" name="title" required placeholder="Project title">
                <textarea class="inputStyle min-h-[130px]" name="description" required placeholder="What did you build and what skills does it prove?"></textarea>
                <input class="inputStyle" name="github_link" type="url" placeholder="GitHub link">
                <input class="inputStyle" name="live_demo_link" type="url" placeholder="Live demo link">
                <input class="inputStyle" name="image" type="file" accept="image/png,image/jpeg,image/webp">
                <button class="primaryBtn w-full" name="add_project" type="submit">
                    <i class="fa-solid fa-folder-plus"></i>
                    Save Project
                </button>
            </form>
        </div>
    </section>

    <section class="lg:col-span-2 space-y-6">
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="statCard">
                <p class="text-slate-400 mb-2">Projects</p>
                <h2 class="text-3xl font-bold"><?= e($progress['portfolio_projects']) ?></h2>
            </div>
            <div class="statCard">
                <p class="text-slate-400 mb-2">Verified</p>
                <h2 class="text-3xl font-bold"><?= e($progress['verified_projects']) ?></h2>
            </div>
            <div class="statCard">
                <p class="text-slate-400 mb-2">Readiness</p>
                <h2 class="text-3xl font-bold"><?= e($progress['readiness']) ?>%</h2>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <?php foreach ($projects as $project): ?>
                <article class="card overflow-hidden">
                    <?php if ($project['image']): ?>
                        <img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>" class="w-full aspect-video object-cover rounded-2xl border border-slate-700 mb-5">
                    <?php else: ?>
                        <div class="w-full aspect-video rounded-2xl border border-slate-700 bg-[#020B24] flex items-center justify-center mb-5">
                            <i class="fa-solid fa-folder-open text-4xl text-blue-300"></i>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between gap-4 mb-3">
                        <h2 class="text-xl font-bold"><?= e($project['title']) ?></h2>
                        <span class="badge <?= (int)$project['mentor_verified'] === 1 ? 'text-green-300 border-green-500/30 bg-green-500/10' : 'text-yellow-300 border-yellow-500/30 bg-yellow-500/10' ?>">
                            <?= (int)$project['mentor_verified'] === 1 ? 'Verified' : 'Pending' ?>
                        </span>
                    </div>
                    <p class="text-slate-300 leading-7 mb-5"><?= e($project['description']) ?></p>

                    <div class="flex flex-wrap gap-2">
                        <?php if ($project['github_link']): ?>
                            <a class="secondaryBtn" href="<?= e($project['github_link']) ?>" target="_blank" rel="noopener">
                                <i class="fa-brands fa-github"></i>
                                GitHub
                            </a>
                        <?php endif; ?>
                        <?php if ($project['live_demo_link']): ?>
                            <a class="secondaryBtn" href="<?= e($project['live_demo_link']) ?>" target="_blank" rel="noopener">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                Live Demo
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (count($projects) === 0): ?>
            <div class="card">
                <p class="text-slate-400">No projects yet. Add your first project to start building proof of skill.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include '../footer.php'; ?>
