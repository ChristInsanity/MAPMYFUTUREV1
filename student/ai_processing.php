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
<body class="bg-[#020B24] text-white min-h-screen flex items-center justify-center px-4 py-6 overflow-x-hidden">

<div class="w-full max-w-2xl">
    <section class="aiCard" aria-live="polite">
        <div class="text-center mb-7">
            <div class="aiOrb mx-auto">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
            </div>
            <h1 class="text-3xl lg:text-4xl font-bold mb-3">Building your future map</h1>
        </div>

        <div class="processingBlock">
            <div class="flex items-end justify-end gap-4 mb-3">
                <span id="progressText" class="text-2xl font-bold text-blue-200">0%</span>
            </div>

            <div class="bg-[#020B24] border border-[#334155] h-2 rounded-full overflow-hidden mb-5">
                <div id="progressBar" class="progressGlow h-full w-0 transition-all duration-300"></div>
            </div>

            <div class="space-y-2.5">
                <?php foreach ($steps as $index => $step): ?>
                    <div class="processStep <?= $index === 0 ? 'activeStep' : '' ?>">
                        <div class="stepIcon">
                            <i class="fa-solid <?= e($step['icon']) ?> stepSourceIcon"></i>
                            <i class="fa-solid fa-check stepDoneIcon"></i>
                        </div>
                        <span><?= e($step['text']) ?></span>
                        <div class="loaderDots <?= $index === 0 ? '' : 'hidden' ?>">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
</div>

<style>
    .aiCard{background:rgba(22,35,56,.9);border:1px solid rgba(51,65,85,.9);border-radius:16px;padding:24px;box-shadow:0 20px 46px rgba(0,0,0,.32),0 1px 0 rgba(255,255,255,.03) inset;backdrop-filter:blur(16px);}
    .aiOrb{width:58px;height:58px;border-radius:16px;background:#2563eb;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:18px;box-shadow:0 0 28px rgba(37,99,235,.34);animation:softPulse 2.4s ease-in-out infinite;}
    .processingBlock{background:rgba(2,11,36,.55);border:1px solid rgba(51,65,85,.78);border-radius:14px;padding:16px;}
    .progressGlow{background:#3B82F6;box-shadow:0 0 16px rgba(59,130,246,.42);}
    .processStep{display:flex;align-items:center;gap:12px;min-height:54px;padding:10px 12px;border-radius:12px;background:#020B24;border:1px solid #334155;opacity:.56;transition:opacity .28s ease,border-color .28s ease,background .28s ease,transform .28s ease;}
    .activeStep{opacity:1;border-color:#3B82F6;background:rgba(15,23,42,.9);box-shadow:0 8px 22px rgba(0,0,0,.18);}
    .completedStep{opacity:1;border-color:rgba(34,197,94,.34);background:rgba(34,197,94,.06);}
    .stepIcon{width:38px;height:38px;border-radius:12px;background:#0f172a;border:1px solid #334155;display:flex;align-items:center;justify-content:center;color:#93c5fd;flex:0 0 auto;}
    .processStep span{flex:1;line-height:1.55;}
    .stepDoneIcon{display:none;color:#86efac;}
    .completedStep .stepSourceIcon{display:none;}
    .completedStep .stepDoneIcon{display:block;}
    .loaderDots{margin-left:auto;display:flex;gap:5px;}
    .loaderDots span{width:6px;height:6px;border-radius:50%;background:#3B82F6;animation:bounce 1s infinite;}
    .loaderDots span:nth-child(2){animation-delay:.2s;}
    .loaderDots span:nth-child(3){animation-delay:.4s;}
    @media (min-width:768px){.aiCard{padding:28px;}.processingBlock{padding:18px;}}
    @media (max-width:480px){.aiCard{padding:18px;}.processStep{align-items:flex-start;}.loaderDots{padding-top:16px;}}
    @keyframes bounce{0%,100%{transform:translateY(0);}50%{transform:translateY(-5px);}}
    @keyframes softPulse{0%,100%{transform:scale(1);box-shadow:0 0 24px rgba(37,99,235,.28);}50%{transform:scale(1.035);box-shadow:0 0 34px rgba(37,99,235,.42);}}
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
            window.location = 'roadmap.php';
        }, 450);
    }
}, tickMs);

function activateStep(index){
    steps.forEach((step, stepIndex) => {
        const loader = step.querySelector('.loaderDots');
        step.classList.toggle('activeStep', stepIndex === index);
        step.classList.toggle('completedStep', stepIndex < index);
        if (loader) {
            loader.classList.toggle('hidden', stepIndex !== index);
        }
    });
}
</script>

</body>
</html>
