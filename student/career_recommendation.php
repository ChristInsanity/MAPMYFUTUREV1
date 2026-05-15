<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$careerMatches = getCareerMatches($conn, $userId);

if (empty($careerMatches)) {
    $answers = [
        'student_type' => $profile['student_type'] ?? '',
        'favorite_subjects' => decodeProfileList($profile['favorite_subjects'] ?? '[]'),
        'activity_preferences' => decodeProfileList($profile['activity_preferences'] ?? '[]'),
        'work_style' => $profile['work_style'] ?? '',
        'dream_job' => $profile['dream_job'] ?? 'Exploring'
    ];

    saveDiscoveryProfile($conn, $userId, $answers);
    $careerMatches = getCareerMatches($conn, $userId);
}

$topMatch = $careerMatches[0] ?? null;
$message = $profile['ai_summary'] ?? 'Map My Future has prepared your personalized career recommendation list.';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $selectedPathId = (int)($_POST['path_id'] ?? 0);

    if ($selectedPathId <= 0) {
        $error = 'Please select a career path before continuing.';
    } elseif (finalizeStudentCareer($conn, $userId, $selectedPathId)) {
        $primaryCareer = getPrimaryCareerMatch($conn, $userId);
        $_SESSION['career_path'] = $primaryCareer['title'] ?? '';
        redirect('ai_processing.php');
    } else {
        $error = 'Unable to save your selected career. Please try again.';
    }
}

$pageTitle = 'Career Recommendation';
$activePage = 'roadmap';
include '../header.php';
?>

<div class="mb-10">
    <div class="mb-10 text-center">
        <div class="w-20 h-20 bg-blue-600 rounded-3xl mx-auto flex items-center justify-center mb-5 shadow-lg shadow-blue-600/25">
            <i class="fa-solid fa-star text-2xl"></i>
        </div>
        <h1 class="text-4xl font-bold mb-3">Your AI-powered career recommendation</h1>
        <p class="text-slate-400 max-w-2xl mx-auto">Review the top matches from your discovery profile and select the career path that feels right. It will shape your roadmap and learning path.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 rounded-2xl border border-red-500 bg-red-500/10 p-4 text-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6 mb-10">
        <div class="bg-[#162338] border border-[#334155] rounded-3xl p-6">
            <p class="text-slate-400 uppercase tracking-[0.24em] text-xs mb-4">Recommended career</p>
            <?php if ($topMatch): ?>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 rounded-3xl bg-blue-600/10 flex items-center justify-center text-blue-300 text-2xl">
                        <i class="fa-solid <?= e($topMatch['icon']) ?>"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= e($topMatch['title']) ?></h2>
                        <p class="text-slate-400 mt-1"><?= (int)$topMatch['match_percentage'] ?>% match</p>
                    </div>
                </div>
                <p class="text-slate-300 leading-7"><?= e($topMatch['description']) ?></p>
            <?php else: ?>
                <p class="text-slate-300">No career matches are available yet. Complete your profile to continue.</p>
            <?php endif; ?>
        </div>
        <div class="lg:col-span-2 bg-[#162338] border border-[#334155] rounded-3xl p-6">
            <p class="text-slate-400 uppercase tracking-[0.24em] text-xs mb-4">Your discovery summary</p>
            <p class="text-slate-300 leading-7"><?= e($message) ?></p>
            <div class="mt-8 grid sm:grid-cols-2 gap-4">
                <?php if ($topMatch): ?>
                    <div class="bg-[#020B24] border border-[#334155] rounded-3xl p-5">
                        <p class="text-slate-400 uppercase text-xs tracking-[0.24em] mb-2">Top match</p>
                        <h3 class="text-xl font-bold mb-1"><?= e($topMatch['title']) ?></h3>
                    </div>
                <?php endif; ?>
                <div class="bg-[#020B24] border border-[#334155] rounded-3xl p-5">
                    <p class="text-slate-400 uppercase text-xs tracking-[0.24em] mb-2">Next step</p>
                    <h3 class="text-xl font-bold mb-1">Finalize your chosen career</h3>
                    <p class="text-slate-400">Your selected career becomes the foundation of your Year 1 Semester 1 roadmap.</p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" class="space-y-4">
        <?= csrf_input() ?>
        <?php foreach ($careerMatches as $index => $match): ?>
            <label class="careerCardItem block bg-[#162338] border border-[#334155] rounded-3xl p-5 cursor-pointer hover:border-blue-500 transition-all">
                <input type="radio" name="path_id" value="<?= (int)$match['path_id'] ?>" class="hidden" <?= $index === 0 ? 'checked' : '' ?> required>
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-3xl bg-blue-600/10 flex items-center justify-center text-blue-300 text-2xl mt-1">
                        <i class="fa-solid <?= e($match['icon']) ?>"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between gap-4 mb-2">
                            <h2 class="text-lg font-semibold"><?= e($match['title']) ?></h2>
                            <span class="badge bg-[#020B24] border border-[#334155] text-blue-300"><?= (int)$match['match_percentage'] ?>%</span>
                        </div>
                        <p class="text-slate-400 leading-6 mb-3"><?= e($match['description']) ?></p>
                        <div class="h-2 rounded-full overflow-hidden bg-[#020B24] border border-[#334155]">
                            <div class="h-full bg-blue-500" style="width:<?= (int)$match['match_percentage'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </label>
        <?php endforeach; ?>
        <div class="flex flex-col sm:flex-row gap-4 mt-4">
            <button type="submit" class="primaryBtn w-full sm:w-auto justify-center">Select this career and continue</button>
            <a href="profile_setup.php" class="secondaryBtn w-full sm:w-auto justify-center">Edit discovery answers</a>
        </div>
    </form>
</div>

<script>
    const careerInputs = document.querySelectorAll('input[name="path_id"]');

    function refreshCareerSelection() {
        careerInputs.forEach((input) => {
            const card = input.closest('.careerCardItem');
            card.classList.toggle('border-blue-500', input.checked);
        });
    }

    careerInputs.forEach((input) => {
        input.addEventListener('change', refreshCareerSelection);
    });

    refreshCareerSelection();
</script>

<?php include '../footer.php'; ?>
