<?php
require_once '../config.php';


if (!isset($_SESSION['user_id'])) {
    redirect('../auth.php');
}


$userId = $_SESSION['user_id'];


// Get student info
$stmt = $conn->prepare("
    SELECT career_path, profile_completed
    FROM users
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();


// Safety check
if ($user['profile_completed'] != 1) {
    redirect('profile_setup.php');
}


// Store career path in session
$_SESSION['career_path'] = $user['career_path'];

?>



<!DOCTYPE html>
<html>
<head>

<title>AI Processing</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>

</head>



<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center px-4">


<div class="w-full max-w-2xl">


    <!-- HEADER -->
    <div class="text-center mb-10">

        <div class="w-20 h-20 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center mb-5 animate-pulse">
            <i class="fa-solid fa-route text-2xl"></i>
        </div>


        <h1 class="text-3xl font-bold mb-3">
            Building Your Future...
        </h1>


        <p class="text-slate-400">
            Our AI is analyzing your profile and generating your personalized roadmap.
        </p>

    </div>





    <!-- MAIN CARD -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">


        <!-- STEPS -->
        <div class="space-y-5 mb-8">


            <div class="processStep activeStep">

                <i class="fa-solid fa-brain text-blue-500 text-xl"></i>

                <span>
                    Analyzing your profile...
                </span>

                <div class="loaderDots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

            </div>





            <div class="processStep">

                <i class="fa-solid fa-chart-line text-green-500 text-xl"></i>

                <span>
                    Matching Philippine job market data...
                </span>

            </div>





            <div class="processStep">

                <i class="fa-solid fa-map text-yellow-500 text-xl"></i>

                <span>
                    Generating your career roadmap...
                </span>

            </div>





            <div class="processStep">

                <i class="fa-solid fa-circle-check text-blue-500 text-xl"></i>

                <span>
                    Finalizing your future map...
                </span>

            </div>


        </div>





        <!-- PROGRESS -->
        <div>

            <div class="flex justify-between mb-2 text-sm text-slate-400">

                <span>
                    Processing
                </span>

                <span id="progressText">
                    0%
                </span>

            </div>



            <div class="bg-slate-800 h-3 rounded-full">

                <div
                    id="progressBar"
                    class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                    style="width:0%"
                ></div>

            </div>

        </div>





        <!-- INFO -->
        <div class="mt-8 bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 text-center">

            <p class="text-sm text-slate-300">

                🚀 We are comparing your profile against
                thousands of career opportunities.

            </p>

        </div>


    </div>





    <!-- FACT -->
    <div class="text-center mt-6 text-slate-500 text-sm">

        💡 Our platform maps skills, interests, and industry trends.

    </div>


</div>






<style>

.processStep{

    display:flex;

    align-items:center;

    gap:16px;

    padding:18px;

    border-radius:16px;

    background:#0f172a;

    border:2px solid transparent;

    opacity:.4;

    transition:.5s;
}



.activeStep{

    opacity:1;

    border-color:#2563eb;

    box-shadow:0 0 20px rgba(37,99,235,.15);
}





.loaderDots{

    margin-left:auto;

    display:flex;

    gap:5px;
}



.loaderDots span{

    width:6px;

    height:6px;

    border-radius:50%;

    background:#3b82f6;

    animation:bounce 1s infinite;
}


.loaderDots span:nth-child(2){

    animation-delay:.2s;
}


.loaderDots span:nth-child(3){

    animation-delay:.4s;
}



@keyframes bounce{

    0%,100%{
        transform:translateY(0);
    }

    50%{
        transform:translateY(-5px);
    }

}

</style>







<script>

let progress = 0;

let currentStep = 0;


const steps =
document.querySelectorAll(".processStep");


const progressBar =
document.getElementById("progressBar");


const progressText =
document.getElementById("progressText");





const interval = setInterval(()=>{


    progress += 1;


    progressBar.style.width =
    progress + "%";


    progressText.innerText =
    progress + "%";



    // every 25%
    if(progress === 25){

        activateStep(1);

    }


    if(progress === 50){

        activateStep(2);

    }


    if(progress === 75){

        activateStep(3);

    }



    if(progress >= 100){

        clearInterval(interval);


        setTimeout(()=>{

            window.location =
            "dashboard.php";

        },1000);

    }


},50);





function activateStep(index){

    steps.forEach(step=>{

        step.classList.remove(
            "activeStep"
        );

    });


    steps[index]
    .classList.add(
        "activeStep"
    );

}

</script>



</body>
</html>