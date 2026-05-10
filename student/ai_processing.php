<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile || (int)$profile['profile_completed'] !== 1) {
    redirect('profile_setup.php');
}

ensureStudentRoadmap($conn, $userId);
$_SESSION['career_path'] = $profile['career_path'];

$messages = getDynamicProcessingMessages($profile);
$steps = [
    ['icon' => 'fa-brain', 'text' => $messages[0]],
    ['icon' => 'fa-chart-line', 'text' => $messages[1]],
    ['icon' => 'fa-route', 'text' => $messages[2]],
    ['icon' => 'fa-graduation-cap', 'text' => $messages[3]],
    ['icon' => 'fa-wand-magic-sparkles', 'text' => $messages[4]],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Processing | Map My Future</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-[#020B24] text-white min-h-screen flex items-center justify-center px-4 py-10">

<div class="w-full max-w-2xl">
    <div class="text-center mb-10">
        <div class="w-20 h-20 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center mb-5 animate-pulse shadow-lg shadow-blue-600/25">
            <i class="fa-solid fa-brain text-2xl"></i>
        </div>
        <h1 class="text-3xl font-bold mb-3">Analyzing Your Future Map</h1>
        <p class="text-slate-400">Map My Future is turning your discovery profile into a structured IT career roadmap.</p>
    </div>

    <div class="bg-[#162338] border border-[#334155] rounded-2xl p-6 lg:p-8">
        <div class="space-y-4 mb-8">
            <?php foreach ($steps as $index => $step): ?>
                <div class="processStep <?= $index === 0 ? 'activeStep' : '' ?>">
                    <div class="w-11 h-11 rounded-xl bg-[#020B24] border border-[#334155] flex items-center justify-center text-blue-300">
                        <i class="fa-solid <?= e($step['icon']) ?>"></i>
                    </div>
                    <span><?= e($step['text']) ?></span>
                    <div class="loaderDots <?= $index === 0 ? '' : 'hidden' ?>">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div>
            <div class="flex justify-between mb-2 text-sm text-slate-400">
                <span>Processing</span>
                <span id="progressText">0%</span>
            </div>
            <div class="bg-[#020B24] border border-[#334155] h-3 rounded-full overflow-hidden">
                <div id="progressBar" class="bg-blue-600 h-full transition-all duration-300" style="width:0%"></div>
            </div>
        </div>

        <div class="mt-7 bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 text-center">
            <p class="text-sm text-slate-300">
                Preparing <?= e($profile['career_path']) ?> subjects, modules, tasks, and unlock rules from your saved profile.
            </p>
        </div>
    </div>

    <div class="text-center mt-6 text-slate-500 text-sm">
        Your roadmap always starts at Year 1 Semester 1, then unlocks as you complete subjects.
    </div>
</div>

<style>
    .processStep{display:flex;align-items:center;gap:16px;padding:16px;border-radius:16px;background:#020B24;border:1px solid #334155;opacity:.45;transition:.35s;}
    .activeStep{opacity:1;border-color:#3B82F6;box-shadow:0 0 20px rgba(59,130,246,.16);}
    .processStep span{flex:1;line-height:1.55;}
    .loaderDots{margin-left:auto;display:flex;gap:5px;}
    .loaderDots span{width:6px;height:6px;border-radius:50%;background:#3B82F6;animation:bounce 1s infinite;}
    .loaderDots span:nth-child(2){animation-delay:.2s;}
    .loaderDots span:nth-child(3){animation-delay:.4s;}
    @keyframes bounce{0%,100%{transform:translateY(0);}50%{transform:translateY(-5px);}}
</style>

<script>
let progress = 0;
const steps = document.querySelectorAll('.processStep');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const totalMs = 5000;
const tickMs = 50;
const increment = 100 / (totalMs / tickMs);

const interval = setInterval(() => {
    progress = Math.min(100, progress + increment);
    const rounded = Math.round(progress);
    progressBar.style.width = rounded + '%';
    progressText.innerText = rounded + '%';

    const activeIndex = Math.min(steps.length - 1, Math.floor(rounded / 20));
    activateStep(activeIndex);

    if (rounded >= 100) {
        clearInterval(interval);
        setTimeout(() => {
            window.location = 'dashboard.php';
        }, 450);
    }
}, tickMs);

function activateStep(index){
    steps.forEach((step, stepIndex) => {
        const loader = step.querySelector('.loaderDots');
        step.classList.toggle('activeStep', stepIndex === index);
        if (loader) {
            loader.classList.toggle('hidden', stepIndex !== index);
        }
    });
}
</script>

</body>
</html>
