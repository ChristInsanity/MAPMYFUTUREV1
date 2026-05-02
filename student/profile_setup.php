<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('../auth.php');
}

$userId = $_SESSION['user_id'];


// already setup?
$check = $conn->prepare("
    SELECT profile_completed
    FROM users
    WHERE user_id=?
");

$check->bind_param("i",$userId);
$check->execute();

$user = $check->get_result()->fetch_assoc();

if($user['profile_completed']==1){
    redirect('dashboard.php');
}



// SAVE
if(isset($_POST['complete_setup'])){

    $course = sanitize($_POST['course']);
    $year = sanitize($_POST['year_level']);
    $skills = sanitize($_POST['skills']);
    $interest = sanitize($_POST['interest']);
    $dream = sanitize($_POST['dream_job']);


    // Career path logic
    if($interest=="creative"){
        $careerPath="uiux";
    }
    elseif($interest=="systems"){
        $careerPath="software";
    }
    else{
        $careerPath="data";
    }



    // save profile
    $stmt = $conn->prepare("
        INSERT INTO student_profiles
        (
            user_id,
            course,
            year_level,
            skills,
            interests,
            dream_job,
            career_path
        )
        VALUES
        (?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "issssss",
        $userId,
        $course,
        $year,
        $skills,
        $interest,
        $dream,
        $careerPath
    );

    $stmt->execute();



    // mark complete
    $update = $conn->prepare("
        UPDATE users
        SET profile_completed=1
        WHERE user_id=?
    ");

    $update->bind_param("i",$userId);
    $update->execute();



    $_SESSION['career_path']=$careerPath;


    redirect('ai_processing.php');
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Profile Setup</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>

</head>

<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center px-4">


<div class="w-full max-w-3xl">


    <!-- HEADER -->
    <div class="text-center mb-8">

        <div class="w-16 h-16 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center mb-4">
            <i class="fa-solid fa-route"></i>
        </div>

        <h1 class="text-3xl font-bold">
            Build Your Profile
        </h1>

        <p class="text-slate-400 mt-2">
            Help us build your career roadmap
        </p>

    </div>



    <!-- PROGRESS -->
    <div class="mb-6">

        <div class="flex justify-between mb-2 text-sm">

            <span id="stepText">
                Step 1 of 5
            </span>

            <span id="progressText">
                20%
            </span>

        </div>


        <div class="bg-slate-800 h-2 rounded-full">

            <div
                id="progressBar"
                class="bg-blue-600 h-2 rounded-full"
                style="width:20%"
            ></div>

        </div>

    </div>





<form method="POST">

<div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">


    <!-- STEP 1 -->
    <div class="step">

        <h2 class="text-2xl font-bold mb-6">
            What are you studying?
        </h2>

        <select
            name="course"
            required
            class="w-full p-4 bg-slate-800 rounded-xl"
        >

            <option value="">Choose</option>

            <option>BS Information Technology</option>
            <option>BS Computer Science</option>
            <option>BS Information Systems</option>

        </select>

    </div>



    <!-- STEP 2 -->
    <div class="step hidden">

        <h2 class="text-2xl font-bold mb-6">
            What year are you in?
        </h2>


        <div class="grid grid-cols-2 gap-4">

        <?php
        foreach(
            ["1st Year","2nd Year","3rd Year","4th Year"]
            as $year
        ):
        ?>

        <label>

            <input
                type="radio"
                name="year_level"
                value="<?= $year ?>"
                hidden
            >

            <div class="optionCard">
                <?= $year ?>
            </div>

        </label>

        <?php endforeach; ?>

        </div>

    </div>




    <!-- STEP 3 -->
    <div class="step hidden">

        <h2 class="text-2xl font-bold mb-6">
            What skills describe you?
        </h2>

        <input
            name="skills"
            required
            class="w-full p-4 bg-slate-800 rounded-xl"
            placeholder="Programming, Leadership..."
        >

    </div>





    <!-- STEP 4 -->
    <div class="step hidden">

        <h2 class="text-2xl font-bold mb-6">
            What excites you most?
        </h2>


        <div class="grid gap-4">


            <label>

                <input
                    type="radio"
                    name="interest"
                    value="creative"
                    hidden
                >

                <div class="optionCard">
                    🎨 UI/UX Design
                </div>

            </label>



            <label>

                <input
                    type="radio"
                    name="interest"
                    value="systems"
                    hidden
                >

                <div class="optionCard">
                    💻 Software Development
                </div>

            </label>



            <label>

                <input
                    type="radio"
                    name="interest"
                    value="data"
                    hidden
                >

                <div class="optionCard">
                    📊 Data Analytics
                </div>

            </label>

        </div>

    </div>






    <!-- STEP 5 -->
    <div class="step hidden">

        <h2 class="text-2xl font-bold mb-6">
            What's your dream career?
        </h2>


        <select
            name="dream_job"
            required
            class="w-full p-4 bg-slate-800 rounded-xl"
        >

            <option value="">Choose</option>

            <option>UI/UX Designer</option>
            <option>Software Engineer</option>
            <option>Data Analyst</option>

        </select>

    </div>





    <!-- BUTTONS -->
    <div class="flex justify-between mt-8">


        <button
            type="button"
            onclick="prevStep()"
            id="backBtn"
            class="px-6 py-3 bg-slate-800 rounded-xl"
        >
            Back
        </button>



        <button
            type="button"
            onclick="nextStep()"
            id="nextBtn"
            class="px-6 py-3 bg-blue-600 rounded-xl"
        >
            Next
        </button>



        <button
            type="submit"
            name="complete_setup"
            id="submitBtn"
            class="hidden px-6 py-3 bg-green-600 rounded-xl"
        >
            Complete Setup
        </button>


    </div>

</div>

</form>

</div>




<style>

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

</style>





<script>

let currentStep=0;

const steps=document.querySelectorAll(".step");



document.querySelectorAll("input[type=radio]")
.forEach(radio=>{

    radio.addEventListener("change",()=>{

        const group=radio.name;

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



    let percent=((currentStep+1)/5)*100;

    progressBar.style.width=percent+"%";

    progressText.innerText=Math.round(percent)+"%";

    stepText.innerText=
    `Step ${currentStep+1} of 5`;



    backBtn.disabled=currentStep==0;



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

    if(currentStep<4){

        currentStep++;

        showStep();

    }

}



function prevStep(){

    if(currentStep>0){

        currentStep--;

        showStep();

    }

}


showStep();

</script>


</body>
</html>