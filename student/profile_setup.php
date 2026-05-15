<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('../auth.php');
}

$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT profile_completed FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['profile_completed'] == 1) {
    redirect('dashboard.php');
}

$subjectOptions = [
    'Mathematics',
    'Technology',
    'Design',
    'Business',
    'Problem Solving'
];

$activityOptions = [
    'Building Apps',
    'Designing Interfaces',
    'Analyzing Data',
    'Securing Systems',
    'Managing Teams'
];

$workStyleOptions = [
    'Creative',
    'Analytical',
    'Technical',
    'Collaborative'
];

$careerOptions = [
    'Software Engineer',
    'UI/UX Designer',
    'Data Analyst',
    'Cybersecurity Analyst'
];

$studentTypes = [
    'Senior High Graduate',
    'College Student',
    'Fresh Graduate',
    'Career Shifter'
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    require_csrf();
    $studentType = sanitize($_POST['student_type'] ?? '');
    $favoriteSubjects = $_POST['favorite_subjects'] ?? [];
    $activityPreferences = $_POST['activity_preferences'] ?? [];
    $workStyle = sanitize($_POST['work_style'] ?? '');
    $dreamKnown = sanitize($_POST['dream_known'] ?? '');
    $dreamJob = sanitize($_POST['dream_job'] ?? 'Exploring');

    $favoriteSubjects = array_values(array_filter(array_map('sanitize', (array)$favoriteSubjects)));
    $activityPreferences = array_values(array_filter(array_map('sanitize', (array)$activityPreferences)));

    if (!in_array($studentType, $studentTypes, true)) {
        $errors[] = 'Please choose the student profile that fits you best.';
    }

    if (count($favoriteSubjects) === 0) {
        $errors[] = 'Select at least one subject you enjoy.';
    }

    if (count($activityPreferences) === 0) {
        $errors[] = 'Select at least one activity that excites you.';
    }

    if (!in_array($workStyle, $workStyleOptions, true)) {
        $errors[] = 'Choose the work environment that suits you.';
    }

    if ($dreamKnown === 'yes' && !in_array($dreamJob, $careerOptions, true)) {
        $errors[] = 'Choose your dream career from the list.';
    }

    if ($dreamKnown !== 'yes') {
        $dreamJob = 'Exploring';
    }

    if (empty($errors)) {
        require_once '../includes/student_functions.php';

        saveDiscoveryProfile($conn, $userId, [
            'student_type' => $studentType,
            'favorite_subjects' => $favoriteSubjects,
            'activity_preferences' => $activityPreferences,
            'work_style' => $workStyle,
            'dream_job' => $dreamJob
        ]);

        redirect('career_recommendation.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Discovery | Map My Future</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-[#020B24] text-white min-h-screen px-4 py-6 lg:py-8">

<div class="w-full max-w-5xl mx-auto">
    <div class="text-center mb-6">
        <div class="w-14 h-14 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center mb-3 shadow-lg shadow-blue-600/30">
            <i class="fa-solid fa-route text-2xl"></i>
        </div>
        <h1 class="text-3xl lg:text-4xl font-bold mb-2">Discover the IT path made for you</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 rounded-2xl border border-red-500 bg-red-500/10 p-4 text-sm text-red-200">
            <ul class="space-y-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-[#162338] border border-[#334155] rounded-2xl p-5 lg:p-6 shadow-2xl shadow-black/20">
        <?= csrf_input() ?>
        <div class="flex items-center justify-between mb-5 gap-4">
            <div>
                <h2 class="text-2xl font-bold">Career discovery wizard</h2>
            </div>
        </div>

        <div class="mb-5">
            <div class="flex justify-between mb-2 text-sm text-slate-400">
                <span id="stepLabel">Step 1 of 5</span>
                <span id="progressText">20%</span>
            </div>
            <div class="bg-[#020B24] border border-[#334155] h-3 rounded-full overflow-hidden">
                <div id="progressBar" class="bg-blue-600 h-full transition-all duration-300" style="width:20%"></div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="step">
                <p class="text-slate-400 uppercase tracking-[0.18em] text-xs mb-2">Identity</p>
                <h3 class="text-2xl font-bold mb-3">Who are you?</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <?php foreach ($studentTypes as $type): ?>
                        <label class="optionCard group">
                            <input type="radio" name="student_type" value="<?= e($type) ?>" class="hidden" required>
                            <div class="flex items-center gap-3 min-h-[56px] p-4 rounded-2xl border border-[#334155] transition-all duration-300 group-hover:border-blue-500">
                                <span class="text-blue-300 text-lg">•</span>
                                <span><?= e($type) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step hidden">
                <p class="text-slate-400 uppercase tracking-[0.18em] text-xs mb-2">Skills</p>
                <h3 class="text-2xl font-bold mb-3">What subjects do you enjoy most?</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($subjectOptions as $subject): ?>
                        <label class="optionCard group">
                            <input type="checkbox" name="favorite_subjects[]" value="<?= e($subject) ?>" class="hidden">
                            <div class="min-h-[56px] p-4 rounded-2xl border border-[#334155] transition-all duration-300 group-hover:border-blue-500">
                                <span class="text-blue-300 text-lg">✓</span>
                                <span><?= e($subject) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step hidden">
                <p class="text-slate-400 uppercase tracking-[0.18em] text-xs mb-2">Professional Details</p>
                <h3 class="text-2xl font-bold mb-3">What activities excite you?</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($activityOptions as $activity): ?>
                        <label class="optionCard group">
                            <input type="checkbox" name="activity_preferences[]" value="<?= e($activity) ?>" class="hidden">
                            <div class="min-h-[56px] p-4 rounded-2xl border border-[#334155] transition-all duration-300 group-hover:border-blue-500">
                                <span class="text-blue-300 text-lg">✓</span>
                                <span><?= e($activity) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step hidden">
                <p class="text-slate-400 uppercase tracking-[0.18em] text-xs mb-2">Goals</p>
                <h3 class="text-2xl font-bold mb-3">What work environment fits you?</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <?php foreach ($workStyleOptions as $style): ?>
                        <label class="optionCard group">
                            <input type="radio" name="work_style" value="<?= e($style) ?>" class="hidden" required>
                            <div class="min-h-[56px] p-4 rounded-2xl border border-[#334155] transition-all duration-300 group-hover:border-blue-500">
                                <span class="text-blue-300 text-lg">•</span>
                                <span><?= e($style) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step hidden">
                <p class="text-slate-400 uppercase tracking-[0.18em] text-xs mb-2">Goals</p>
                <h3 class="text-2xl font-bold mb-3">Do you already have a dream career?</h3>
                <div class="grid sm:grid-cols-2 gap-3 mb-4">
                    <label class="optionCard group">
                        <input type="radio" name="dream_known" value="yes" class="hidden" required>
                        <div class="min-h-[56px] p-4 rounded-2xl border border-[#334155] transition-all duration-300 group-hover:border-blue-500">
                            <span class="text-blue-300 text-lg">✔</span>
                            <span>I already know</span>
                        </div>
                    </label>
                    <label class="optionCard group">
                        <input type="radio" name="dream_known" value="no" class="hidden" required>
                        <div class="min-h-[56px] p-4 rounded-2xl border border-[#334155] transition-all duration-300 group-hover:border-blue-500">
                            <span class="text-blue-300 text-lg">?</span>
                            <span>I’m still exploring</span>
                        </div>
                    </label>
                </div>
                <div class="dreamChoices hidden">
                    <label class="block text-sm text-slate-400 mb-2">Pick the IT career that feels most exciting.</label>
                    <select name="dream_job" class="inputStyle" aria-label="Dream career choice">
                        <option value="">Select career path</option>
                        <?php foreach ($careerOptions as $career): ?>
                            <option value="<?= e($career) ?>"><?= e($career) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="save_profile" value="1">
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between gap-3 mt-6">
            <button type="button" id="backBtn" class="btnSecondary min-h-[44px] w-full sm:w-auto py-3 px-5 rounded-xl">Back</button>
            <div class="flex gap-4 w-full sm:w-auto">
                <button type="button" id="nextBtn" class="btnPrimary min-h-[44px] w-full py-3 px-5 rounded-xl">Next</button>
                <button type="submit" id="submitBtn" class="hidden btnSuccess min-h-[44px] w-full sm:w-auto py-3 px-5 rounded-xl">Review Career Matches</button>
            </div>
        </div>
    </form>
</div>

<style>
    .optionCard{cursor:pointer;}
    .optionCard input:checked + div, .optionCard:hover div{border-color:#3B82F6;box-shadow:0 0 20px rgba(59,130,246,.2);}
    .optionCard input:checked + div span:first-child{color:#38bdf8;}
    .btnPrimary{background:#2563eb;color:#fff;font-weight:700;}
    .btnPrimary:hover{background:#3b82f6;}
    .btnSecondary{background:#1e293b;color:#cbd5e1;border:1px solid #334155;}
    .btnSuccess{background:#16a34a;color:#fff;font-weight:700;}
    select.inputStyle{color:#f8fafc;background-color:#020B24;}
    select.inputStyle option{color:#0f172a;background-color:#f8fafc;}
    select.inputStyle option:checked{color:#fff;background-color:#2563eb;}
    select.inputStyle option:hover{color:#fff;background-color:#1d4ed8;}
</style>

<script>
    const steps = Array.from(document.querySelectorAll('.step'));
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const stepLabel = document.getElementById('stepLabel');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const submitBtn = document.getElementById('submitBtn');
    const dreamChoices = document.querySelector('.dreamChoices');
    const dreamJobSelect = document.querySelector('select[name="dream_job"]');

    let currentStep = 0;

    function showStep() {
        steps.forEach((step, index) => {
            step.classList.toggle('hidden', index !== currentStep);
        });

        const percent = ((currentStep + 1) / steps.length) * 100;
        progressBar.style.width = `${Math.round(percent)}%`;
        progressText.textContent = `${Math.round(percent)}%`;
        stepLabel.textContent = `Step ${currentStep + 1} of ${steps.length}`;
        backBtn.disabled = currentStep === 0;

        if (currentStep === steps.length - 1) {
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
        }

        updateDreamVisibility();
    }

    function updateDreamVisibility() {
        const known = document.querySelector('input[name="dream_known"]:checked')?.value === 'yes';
        if (known) {
            dreamChoices.classList.remove('hidden');
            dreamJobSelect.required = true;
            if (dreamJobSelect.value === 'Exploring') {
                dreamJobSelect.value = '';
            }
        } else {
            dreamChoices.classList.add('hidden');
            dreamJobSelect.required = false;
            dreamJobSelect.value = 'Exploring';
        }
    }

    nextBtn.addEventListener('click', () => {
        if (!validateStep()) {
            return;
        }

        if (currentStep < steps.length - 1) {
            currentStep += 1;
            showStep();
        }
    });

    backBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep -= 1;
            showStep();
        }
    });

    function validateStep() {
        const step = steps[currentStep];
        const radioGroups = new Set();

        step.querySelectorAll('input[type="radio"][required]').forEach((radio) => {
            radioGroups.add(radio.name);
        });

        for (const name of radioGroups) {
            if (!step.querySelector(`input[name="${name}"]:checked`)) {
                return false;
            }
        }

        const requiredSelects = step.querySelectorAll('select[required]');
        for (const select of requiredSelects) {
            if (!select.value) {
                return false;
            }
        }

        if (step.querySelector('input[name="favorite_subjects[]"]') && !step.querySelector('input[name="favorite_subjects[]"]:checked')) {
            return false;
        }

        if (step.querySelector('input[name="activity_preferences[]"]') && !step.querySelector('input[name="activity_preferences[]"]:checked')) {
            return false;
        }

        return true;
    }

    document.querySelectorAll('input[name="dream_known"]').forEach(input => {
        input.addEventListener('change', updateDreamVisibility);
    });

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            checkbox.closest('label').querySelector('div').classList.toggle('border-blue-500', checkbox.checked);
        });
    });

    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const group = radio.name;
            document.querySelectorAll(`input[name="${group}"]`).forEach(input => {
                input.closest('label').querySelector('div').classList.remove('border-blue-500');
            });
            radio.closest('label').querySelector('div').classList.add('border-blue-500');
        });
    });

    showStep();
</script>

</body>
</html>
