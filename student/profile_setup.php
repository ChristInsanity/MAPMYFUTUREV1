<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('../auth.php');
}

$userId = $_SESSION['user_id'];


/*
=====================================
CHECK IF ALREADY COMPLETED
=====================================
*/

$check = $conn->prepare("
    SELECT profile_completed
    FROM users
    WHERE user_id = ?
");

$check->bind_param("i", $userId);
$check->execute();

$user = $check->get_result()->fetch_assoc();

if($user['profile_completed'] == 1){
    redirect('dashboard.php');
}



/*
=====================================
SAVE PROFILE
=====================================
*/

if(isset($_POST['complete_setup'])){

    $course = sanitize($_POST['course']);
    $year = sanitize($_POST['year_level']);
    $skills = sanitize($_POST['skills']);
    $interest = sanitize($_POST['interest']);
    $dream = sanitize($_POST['dream_job']);


    /*
    =====================================
    AI CAREER ENGINE
    =====================================
    */

    if($interest == "creative"){

        $careerPath = "UI/UX Designer";

        $readiness = 72;

        $completedSkills = 8;

        $missingSkills = 3;

        $industry = "PH Tech Industry";

        $summary =
        "You have strong creative and design thinking potential for product design and UI/UX.";

    }
    elseif($interest == "systems"){

        $careerPath = "Software Engineer";

        $readiness = 68;

        $completedSkills = 7;

        $missingSkills = 4;

        $industry = "Software Development";

        $summary =
        "Your logical and technical strengths align with backend systems and software engineering.";

    }
    else{

        $careerPath = "Data Analyst";

        $readiness = 65;

        $completedSkills = 6;

        $missingSkills = 5;

        $industry = "Data Analytics";

        $summary =
        "You demonstrate strong analytical potential suited for data-driven decision making.";

    }



    /*
    =====================================
    PORTFOLIO ESTIMATION
    =====================================
    */

    if($year == "1st Year"){
        $projects = 1;
    }
    elseif($year == "2nd Year"){
        $projects = 3;
    }
    elseif($year == "3rd Year"){
        $projects = 5;
    }
    else{
        $projects = 8;
    }



    /*
    =====================================
    SAVE PROFILE
    =====================================
    */

    $stmt = $conn->prepare("
        INSERT INTO student_profiles
        (
            user_id,
            course,
            year_level,
            skills,
            interests,
            dream_job,
            career_path,
            readiness_score,
            completed_skills,
            missing_skills,
            portfolio_projects,
            target_industry,
            ai_summary
        )
        VALUES
        (?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");


    $stmt->bind_param(
        "issssssiiiiss",
        $userId,
        $course,
        $year,
        $skills,
        $interest,
        $dream,
        $careerPath,
        $readiness,
        $completedSkills,
        $missingSkills,
        $projects,
        $industry,
        $summary
    );

    $stmt->execute();



    /*
    =====================================
    MARK COMPLETE
    =====================================
    */

    $update = $conn->prepare("
        UPDATE users
        SET profile_completed = 1
        WHERE user_id = ?
    ");

    $update->bind_param("i", $userId);
    $update->execute();



    $_SESSION['career_path'] = $careerPath;



    /*
    =====================================
    GO TO AI LOADING
    =====================================
    */

    redirect('ai_processing.php');
}
?>


<!DOCTYPE html>
<html>
<head>

<title>Map My Future</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>

</head>


<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center px-4">


<div class="w-full max-w-3xl">


    <!-- HEADER -->
    <div class="text-center mb-10">

        <div class="w-20 h-20 bg-blue-600 rounded-3xl mx-auto flex items-center justify-center mb-5 shadow-lg shadow-blue-600/30">
            <i class="fa-solid fa-route text-2xl"></i>
        </div>

        <h1 class="text-4xl font-bold mb-3">
            Build Your Career Profile
        </h1>

        <p class="text-slate-400 text-lg">
            Help AI build your personalized roadmap
        </p>

    </div>




    <!-- PROGRESS -->
    <div class="mb-8">

        <div class="flex justify-between mb-3 text-sm">

            <span id="stepText">
                Step 1 of 5
            </span>

            <span id="progressText">
                20%
            </span>

        </div>


        <div class="bg-slate-800 h-3 rounded-full">

            <div
                id="progressBar"
                class="bg-blue-600 h-3 rounded-full transition-all duration-500"
                style="width:20%"
            ></div>

        </div>

    </div>




    <form method="POST">

        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-10">


            <!-- STEP 1 -->
            <div class="step">

                <h2 class="stepTitle">
                    What are you studying?
                </h2>

                <select name="course" required class="inputStyle">

                    <option value="">Choose course</option>

                    <option>BS Information Technology</option>
                    <option>BS Computer Science</option>
                    <option>BS Information Systems</option>

                </select>

            </div>




            <!-- STEP 2 -->
            <div class="step hidden">

                <h2 class="stepTitle">
                    What year are you in?
                </h2>


                <div class="grid grid-cols-2 gap-4">

                    <?php foreach(["1st Year","2nd Year","3rd Year","4th Year"] as $year): ?>

                        <label>

                            <input
                            type="radio"
                            name="year_level"
                            value="<?= $year ?>"
                            hidden>

                            <div class="optionCard">
                                <?= $year ?>
                            </div>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>




            <!-- STEP 3 -->
            <div class="step hidden">

                <h2 class="stepTitle">
                    What skills describe you?
                </h2>

                <input
                name="skills"
                required
                class="inputStyle"
                placeholder="Programming, Leadership, Design...">

            </div>




            <!-- STEP 4 -->
            <div class="step hidden">

                <h2 class="stepTitle">
                    What excites you most?
                </h2>


                <div class="space-y-4">

                    <label>
                        <input type="radio" name="interest" value="creative" hidden>
                        <div class="optionCard">🎨 UI/UX Design</div>
                    </label>

                    <label>
                        <input type="radio" name="interest" value="systems" hidden>
                        <div class="optionCard">💻 Software Development</div>
                    </label>

                    <label>
                        <input type="radio" name="interest" value="data" hidden>
                        <div class="optionCard">📊 Data Analytics</div>
                    </label>

                </div>

            </div>




            <!-- STEP 5 -->
            <div class="step hidden">

                <h2 class="stepTitle">
                    What's your dream career?
                </h2>


                <select name="dream_job" required class="inputStyle">

                    <option value="">Choose dream career</option>

                    <option>UI/UX Designer</option>
                    <option>Software Engineer</option>
                    <option>Data Analyst</option>

                </select>

            </div>




            <!-- BUTTONS -->
            <div class="flex justify-between mt-10">

                <button
                type="button"
                onclick="prevStep()"
                id="backBtn"
                class="btnSecondary">
                    Back
                </button>


                <button
                type="button"
                onclick="nextStep()"
                id="nextBtn"
                class="btnPrimary">
                    Next
                </button>


                <button
                type="submit"
                name="complete_setup"
                id="submitBtn"
                class="hidden btnSuccess">
                    Generate My Roadmap
                </button>

            </div>

        </div>

    </form>

</div>




<style>

.stepTitle{
    font-size:28px;
    font-weight:bold;
    margin-bottom:25px;
}

.inputStyle{
    width:100%;
    padding:18px;
    background:#1e293b;
    border-radius:16px;
}

.optionCard{
    background:#1e293b;
    padding:20px;
    border-radius:16px;
    cursor:pointer;
    border:2px solid transparent;
    transition:.3s;
}

.optionCard:hover{
    border-color:#2563eb;
}

.optionCard.selected{
    border-color:#2563eb;
    box-shadow:0 0 20px rgba(37,99,235,.3);
}

.btnPrimary,
.btnSecondary,
.btnSuccess{
    padding:14px 28px;
    border-radius:16px;
}

.btnPrimary{
    background:#2563eb;
}

.btnSecondary{
    background:#1e293b;
}

.btnSuccess{
    background:#16a34a;
}

</style>




<script>

let currentStep = 0;

const steps = document.querySelectorAll(".step");


document.querySelectorAll("input[type=radio]")
.forEach(radio=>{

    radio.addEventListener("change",()=>{

        let group = radio.name;

        document
        .querySelectorAll(`input[name="${group}"]`)
        .forEach(r=>{

            r.nextElementSibling
            .classList.remove("selected");

        });


        radio.nextElementSibling
        .classList.add("selected");

    });

});



function showStep(){

    steps.forEach(
        step=>step.classList.add("hidden")
    );

    steps[currentStep]
    .classList.remove("hidden");


    let percent = ((currentStep+1)/5)*100;


    progressBar.style.width = percent+"%";

    progressText.innerText = Math.round(percent)+"%";

    stepText.innerText =
    `Step ${currentStep+1} of 5`;


    backBtn.disabled = currentStep==0;


    if(currentStep==4){

        nextBtn.classList.add("hidden");

        submitBtn.classList.remove("hidden");

    }
    else{

        nextBtn.classList.remove("hidden");

        submitBtn.classList.add("hidden");

    }

}


function nextStep(){

    if(currentStep < 4){

        currentStep++;

        showStep();

    }

}


function prevStep(){

    if(currentStep > 0){

        currentStep--;

        showStep();

    }

}


showStep();

</script>


</body>
</html>
