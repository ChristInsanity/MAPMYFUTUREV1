<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$profile = dbFetchOne(
    $conn,
    "SELECT u.full_name, u.email, mp.bio, mp.linkedin_url, mp.github_url, mp.portfolio_url
     FROM users u
     LEFT JOIN mentor_profiles mp ON mp.user_id = u.user_id
     WHERE u.user_id = ?",
    "i",
    [$mentorId]
);
$items = getMentorPortfolioItems($conn, $mentorId);
$certifications = dbFetchAll($conn, "SELECT * FROM mentor_certifications WHERE user_id = ? ORDER BY uploaded_at DESC", "i", [$mentorId]);

$pageTitle = 'Mentor Portfolio';
$activePage = 'portfolio';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Portfolio']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Portfolio</h1>
</div>

<div id="portfolioMessage" class="hidden mb-6 rounded-2xl border p-4"></div>

<form id="portfolioForm" class="space-y-6">
    <?= csrf_input() ?>
    <section class="card space-y-4">
        <h2 class="sectionTitle">About</h2>
        <textarea name="bio" class="inputStyle min-h-[130px]" placeholder="Tell students how you mentor and what you specialize in."><?= e($profile['bio'] ?? '') ?></textarea>
        <div class="grid md:grid-cols-3 gap-4">
            <input name="linkedin_url" class="inputStyle" placeholder="LinkedIn URL" value="<?= e($profile['linkedin_url'] ?? '') ?>">
            <input name="github_url" class="inputStyle" placeholder="GitHub URL" value="<?= e($profile['github_url'] ?? '') ?>">
            <input name="portfolio_url" class="inputStyle" placeholder="Portfolio URL" value="<?= e($profile['portfolio_url'] ?? '') ?>">
        </div>
    </section>

    <?php
    $blocks = [
        'education' => ['Education', 'fa-graduation-cap', 'Degree, school, or learning program'],
        'experience' => ['Experience', 'fa-briefcase', 'Role, company, or professional experience'],
        'skill' => ['Skills', 'fa-code', 'Skill or expertise tag'],
        'project' => ['Featured Projects', 'fa-folder-open', 'Project title']
    ];
    foreach ($blocks as $type => $meta):
    ?>
    <section class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="sectionTitle"><i class="fa-solid <?= e($meta[1]) ?> text-blue-300 mr-2"></i><?= e($meta[0]) ?></h2>
            <button type="button" class="secondaryBtn addItemBtn" data-type="<?= e($type) ?>"><i class="fa-solid fa-plus"></i> Add</button>
        </div>
        <div id="<?= e($type) ?>List" class="space-y-3 portfolioList" data-type="<?= e($type) ?>" data-placeholder="<?= e($meta[2]) ?>">
            <?php foreach ($items[$type] as $item): ?>
                <div class="portfolioRow grid gap-3 <?= $type === 'project' ? 'md:grid-cols-4' : 'md:grid-cols-[1fr_1fr_auto]' ?>">
                    <input name="<?= e($type) ?>_title[]" class="inputStyle" placeholder="<?= e($meta[2]) ?>" value="<?= e($item['title']) ?>">
                    <input name="<?= e($type) ?>_description[]" class="inputStyle" placeholder="Description" value="<?= e($item['description']) ?>">
                    <?php if ($type === 'project'): ?>
                        <input name="project_link[]" class="inputStyle" placeholder="Project link" value="<?= e($item['link_url']) ?>">
                        <input type="file" name="project_file[]" class="text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-3">
                    <?php endif; ?>
                    <button type="button" class="dangerBtn removeItemBtn"><i class="fa-solid fa-trash"></i></button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <section class="card">
        <h2 class="sectionTitle mb-4">Certifications</h2>
        <div class="grid md:grid-cols-2 gap-3">
            <?php foreach ($certifications as $cert): ?>
                <a href="../<?= e($cert['file_path']) ?>" target="_blank" class="actionRow">
                    <i class="fa-solid fa-certificate text-yellow-300"></i>
                    <span><?= e($cert['title']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (count($certifications) === 0): ?><p class="text-slate-400">No uploaded certifications yet.</p><?php endif; ?>
        </div>
    </section>

    <button type="submit" class="primaryBtn"><i class="fa-solid fa-floppy-disk"></i> Save Portfolio</button>
</form>

<template id="portfolioRowTemplate">
    <div class="portfolioRow grid gap-3 md:grid-cols-[1fr_1fr_auto]">
        <input class="inputStyle rowTitle">
        <input class="inputStyle rowDescription" placeholder="Description">
        <button type="button" class="dangerBtn removeItemBtn"><i class="fa-solid fa-trash"></i></button>
    </div>
</template>

<template id="projectRowTemplate">
    <div class="portfolioRow grid gap-3 md:grid-cols-4">
        <input name="project_title[]" class="inputStyle" placeholder="Project title">
        <input name="project_description[]" class="inputStyle" placeholder="Description">
        <input name="project_link[]" class="inputStyle" placeholder="Project link">
        <div class="flex gap-2">
            <input type="file" name="project_file[]" class="w-full text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-3">
            <button type="button" class="dangerBtn removeItemBtn"><i class="fa-solid fa-trash"></i></button>
        </div>
    </div>
</template>

<script>
function bindRemove(scope = document) {
    scope.querySelectorAll('.removeItemBtn').forEach(button => {
        button.onclick = () => button.closest('.portfolioRow').remove();
    });
}
bindRemove();

document.querySelectorAll('.addItemBtn').forEach(button => {
    button.addEventListener('click', () => {
        const type = button.dataset.type;
        const list = document.getElementById(`${type}List`);
        let row;

        if (type === 'project') {
            row = document.getElementById('projectRowTemplate').content.firstElementChild.cloneNode(true);
        } else {
            row = document.getElementById('portfolioRowTemplate').content.firstElementChild.cloneNode(true);
            row.querySelector('.rowTitle').name = `${type}_title[]`;
            row.querySelector('.rowTitle').placeholder = list.dataset.placeholder;
            row.querySelector('.rowDescription').name = `${type}_description[]`;
        }

        list.appendChild(row);
        bindRemove(row);
    });
});

document.getElementById('portfolioForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.mmfPost('ajax_portfolio.php', new FormData(event.currentTarget), true);
    const message = document.getElementById('portfolioMessage');
    message.className = `mb-6 rounded-2xl border p-4 ${result.success ? 'border-green-500 bg-green-500/10 text-green-200' : 'border-red-500 bg-red-500/10 text-red-200'}`;
    message.textContent = result.message || 'Saved.';
    message.classList.remove('hidden');
});
</script>

<?php include '../footer.php'; ?>
