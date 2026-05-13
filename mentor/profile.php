<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireLogin();
ensureMentorTables($conn);

$viewerId = (int)$_SESSION['user_id'];
$mentorId = (int)($_GET['id'] ?? ($viewerId));
$isOwner = ($_SESSION['role'] ?? '') === 'mentor' && $mentorId === $viewerId;

$mentor = dbFetchOne(
    $conn,
    "SELECT u.user_id, u.full_name, u.email, u.profile_photo,
            mp.age, mp.degree, mp.specialization, mp.years_experience, mp.industry,
            mp.bio, mp.experience, mp.linkedin_url, mp.github_url, mp.behance_url, mp.portfolio_url
     FROM users u
     LEFT JOIN mentor_profiles mp ON mp.user_id = u.user_id
     WHERE u.user_id = ? AND u.role = 'mentor'",
    "i",
    [$mentorId]
);

if (!$mentor) {
    redirect('../auth.php');
}

$certifications = dbFetchAll($conn, "SELECT * FROM mentor_certifications WHERE user_id = ? ORDER BY uploaded_at DESC", "i", [$mentorId]);
$careers = getMentorCareerAssignments($conn, $mentorId);
$portfolioItems = getMentorPortfolioItems($conn, $mentorId);

$pageTitle = 'Mentor Profile';
$activePage = $isOwner ? 'profile' : 'mentors';
$backUrl = ($_SESSION['role'] ?? '') === 'student' ? '../student/find_mentors.php' : 'dashboard.php';
$backLabel = 'Back';
include '../header.php';
?>

<div class="mb-8">
    <div class="flex items-center gap-5">
        <div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-3xl font-bold">
            <?= e(strtoupper(substr($mentor['full_name'], 0, 1))) ?>
        </div>
        <div>
            <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($mentor['full_name']) ?></h1>
            <p class="text-slate-400"><?= e($mentor['specialization'] ?? 'Mentor') ?><?= $mentor['years_experience'] !== null ? ' - ' . (int)$mentor['years_experience'] . ' years experience' : '' ?></p>
        </div>
    </div>
</div>

<div id="profileMessage" class="hidden mb-6 rounded-2xl border p-4"></div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-2 space-y-6">
        <div class="card">
            <h2 class="sectionTitle mb-4">About</h2>
            <p class="text-slate-300 leading-7"><?= e($mentor['bio'] ?? 'No bio added yet.') ?></p>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Experience</h2>
            <?php if (count($portfolioItems['experience']) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($portfolioItems['experience'] as $item): ?>
                        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                            <p class="font-bold"><?= e($item['title']) ?></p>
                            <p class="text-slate-400"><?= e($item['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-slate-300 leading-7"><?= nl2br(e($mentor['experience'] ?? 'No experience details added yet.')) ?></p>
            <?php endif; ?>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Education</h2>
            <?php if (count($portfolioItems['education']) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($portfolioItems['education'] as $item): ?>
                        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                            <p class="font-bold"><?= e($item['title']) ?></p>
                            <p class="text-slate-400"><?= e($item['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-slate-300"><?= e($mentor['degree'] ?? 'No education details added yet.') ?></p>
            <?php endif; ?>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Featured Projects</h2>
            <div class="space-y-3">
                <?php foreach ($portfolioItems['project'] as $item): ?>
                    <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                        <p class="font-bold"><?= e($item['title']) ?></p>
                        <p class="text-slate-400 mb-2"><?= e($item['description']) ?></p>
                        <div class="flex flex-wrap gap-3 text-blue-200 text-sm">
                            <?php if ($item['link_url']): ?><a href="<?= e($item['link_url']) ?>" target="_blank">Open link</a><?php endif; ?>
                            <?php if ($item['file_path']): ?><a href="../<?= e($item['file_path']) ?>" target="_blank">Open file</a><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($portfolioItems['project']) === 0): ?><p class="text-slate-400">No featured projects yet.</p><?php endif; ?>
            </div>
        </div>
        <?php if ($isOwner): ?>
            <form id="profileForm" class="card space-y-4">
                <?= csrf_input() ?>
                <h2 class="sectionTitle">Edit Profile</h2>
                <textarea name="bio" class="inputStyle min-h-[110px]" placeholder="About"><?= e($mentor['bio']) ?></textarea>
                <textarea name="experience" class="inputStyle min-h-[130px]" placeholder="Experience"><?= e($mentor['experience']) ?></textarea>
                <input name="degree" class="inputStyle" placeholder="Degree" value="<?= e($mentor['degree']) ?>">
                <div class="grid sm:grid-cols-2 gap-4">
                    <input name="linkedin_url" class="inputStyle" placeholder="LinkedIn" value="<?= e($mentor['linkedin_url']) ?>">
                    <input name="github_url" class="inputStyle" placeholder="GitHub" value="<?= e($mentor['github_url']) ?>">
                    <input name="behance_url" class="inputStyle" placeholder="Behance" value="<?= e($mentor['behance_url']) ?>">
                    <input name="portfolio_url" class="inputStyle" placeholder="Portfolio" value="<?= e($mentor['portfolio_url']) ?>">
                </div>
                <button class="primaryBtn" type="submit">Save Profile</button>
            </form>
        <?php endif; ?>
    </section>

    <aside class="space-y-6">
        <div class="card">
            <h2 class="sectionTitle mb-4">Expertise</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($careers as $career): ?>
                    <span class="badge text-blue-300 border-blue-500/30 bg-blue-500/10"><?= e($career['title']) ?></span>
                <?php endforeach; ?>
                <?php foreach ($portfolioItems['skill'] as $skill): ?>
                    <span class="badge text-cyan-300 border-cyan-500/30 bg-cyan-500/10"><?= e($skill['title']) ?></span>
                <?php endforeach; ?>
                <?php if (count($careers) === 0 && count($portfolioItems['skill']) === 0): ?><p class="text-slate-400">No assigned careers yet.</p><?php endif; ?>
            </div>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Certifications</h2>
            <div class="space-y-3">
                <?php foreach ($certifications as $cert): ?>
                    <a class="block bg-[#020B24] border border-[#334155] rounded-xl p-3 text-blue-200" href="../<?= e($cert['file_path']) ?>" target="_blank">
                        <i class="fa-solid fa-certificate text-yellow-300 mr-2"></i><?= e($cert['title']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (count($certifications) === 0): ?><p class="text-slate-400">No certificates uploaded.</p><?php endif; ?>
            </div>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Portfolio Links</h2>
            <div class="space-y-2 text-blue-200">
                <?php foreach (['linkedin_url' => 'LinkedIn', 'github_url' => 'GitHub', 'behance_url' => 'Behance', 'portfolio_url' => 'Portfolio'] as $field => $label): ?>
                    <?php if (!empty($mentor[$field])): ?>
                        <a class="block" href="<?= e($mentor[$field]) ?>" target="_blank"><?= e($label) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</div>

<?php if ($isOwner): ?>
<script>
document.getElementById('profileForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.mmfPost('ajax_profile.php', new FormData(event.currentTarget), true);
    const message = document.getElementById('profileMessage');
    message.className = `mb-6 rounded-2xl border p-4 ${result.success ? 'border-green-500 bg-green-500/10 text-green-200' : 'border-red-500 bg-red-500/10 text-red-200'}`;
    message.textContent = result.message;
    message.classList.remove('hidden');
});
</script>
<?php endif; ?>

<?php include '../footer.php'; ?>
