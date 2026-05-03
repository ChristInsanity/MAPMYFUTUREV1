<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('../auth.php');
}

$userId = $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET PROFILE
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT *
    FROM student_profiles
    WHERE user_id=?
");

$stmt->bind_param("i",$userId);
$stmt->execute();

$profile = $stmt->get_result()->fetch_assoc();

if(!$profile){
    redirect('profile_setup.php');
}


/*
|--------------------------------------------------------------------------
| SAFE VALUES
|--------------------------------------------------------------------------
*/
$interest = $profile['interests'] ?? 'technology';
$aiSummary = $profile['ai_summary'] ?? "
Based on your {$profile['course']} background,
your interest in {$interest},
and your dream career of {$profile['dream_job']},
we found your strongest match.
";

$readiness = $profile['readiness_score'] ?? 72;
$completed = $profile['completed_skills'] ?? 8;
$missing = $profile['missing_skills'] ?? 3;
$projects = $profile['portfolio_projects'] ?? 2;


/*
|--------------------------------------------------------------------------
| CAREERS
|--------------------------------------------------------------------------
*/
$careers = [

    [
        "title"=>"UI/UX Designer",
        "salary"=>"₱35,000 - ₱65,000",
        "match"=>92,
        "color"=>"#3B82F6",
        "companies"=>[
            "Accenture",
            "Thinking Machines",
            "Globe"
        ]
    ],

    [
        "title"=>"Software Developer",
        "salary"=>"₱40,000 - ₱80,000",
        "match"=>88,
        "color"=>"#10B981",
        "companies"=>[
            "PLDT",
            "Smart",
            "Ayala"
        ]
    ],

    [
        "title"=>"Data Analyst",
        "salary"=>"₱35,000 - ₱60,000",
        "match"=>75,
        "color"=>"#F59E0B",
        "companies"=>[
            "Lazada",
            "Shopee",
            "GCash"
        ]
    ]

];


/*
|--------------------------------------------------------------------------
| ROADMAP
|--------------------------------------------------------------------------
*/
$roadmap = [

    [
        "title"=>"1st Year - Sem 1",
        "status"=>"completed",
        "tasks"=>[
            "Complete Introduction to Programming",
            "Learn HTML/CSS Basics"
        ]
    ],

    [
        "title"=>"1st Year - Sem 2",
        "status"=>"completed",
        "tasks"=>[
            "Master JavaScript Fundamentals",
            "Build First Portfolio Project"
        ]
    ],

    [
        "title"=>"2nd Year - Sem 1",
        "status"=>"progress",
        "tasks"=>[
            "Learn UI/UX Design Principles",
            "Get Figma Certification"
        ]
    ],

    [
        "title"=>"4th Year",
        "status"=>"goal",
        "tasks"=>[
            "Apply for ".$profile['dream_job']
        ]
    ]

];

?>


<!DOCTYPE html>
<html>
<head>

<title>Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>

</head>


<body class="bg-[#020B24] text-white">


<!-- NAV -->
<nav class="sticky top-0 z-50 bg-[#162338] border-b border-slate-700">

    <div class="max-w-7xl mx-auto px-8 py-4 flex justify-between items-center">

        <div class="flex gap-10 items-center">

            <div class="flex gap-3 items-center">

                <i class="fa-regular fa-map text-blue-400 text-2xl"></i>

                <h2 class="font-bold text-2xl">
                    Map My Future
                </h2>

            </div>


            <div class="hidden md:flex gap-3">

                <a href="dashboard.php" class="navBtn activeNav">Dashboard</a>

                <a href="skill_gap.php" class="navBtn">Skill Gap</a>

                <a href="courses.php" class="navBtn">Courses</a>

                <a href="mentors.php" class="navBtn">Mentors</a>

                <a href="jobs.php" class="navBtn">Jobs</a>

            </div>

        </div>



        <div class="flex gap-3">

            <a href="readiness.php" class="pillBtn">
                Readiness: <?= $readiness ?>%
            </a>

            <a
            href="portfolio.php"
            class="bg-blue-600 px-5 py-3 rounded-xl"
            >
                Portfolio
            </a>

        </div>

         <a
            href="../logout.php"
            class="redBtn"
            >
                Logout
            </a>


    </div>

</nav>





<div class="max-w-7xl mx-auto p-8">


    <!-- HEADER -->
    <h1 class="text-5xl font-bold mb-4">

        Your Career Roadmap

    </h1>


    <div class="flex gap-3 mb-8 flex-wrap">

        <div class="badge">
            <?= $profile['course'] ?>
        </div>

        <div class="badge text-green-400">
            PH Local
        </div>

        <div class="badge text-blue-400">
            Tech Industry
        </div>

    </div>





    <div class="grid grid-cols-3 gap-8">


        <!-- LEFT -->
        <div class="col-span-2 space-y-8">



            <!-- CAREERS -->
            <div class="card">

                <h2 class="sectionTitle mb-6">

                    Recommended Career Paths

                </h2>


                <?php foreach($careers as $career): ?>


                    <div class="careerCard">

                        <div>

                            <h3 class="text-3xl font-bold mb-2">

                                <?= $career['title'] ?>

                            </h3>


                            <p class="text-slate-400 mb-3">

                                <?= $career['salary'] ?>

                            </p>


                            <div class="flex gap-2 flex-wrap">

                                <?php foreach($career['companies'] as $company): ?>

                                    <span class="companyTag">

                                        <?= $company ?>

                                    </span>

                                <?php endforeach; ?>

                            </div>

                        </div>



                        <div
                        class="text-5xl font-bold"
                        style="color:<?= $career['color'] ?>"
                        >

                            <?= $career['match'] ?>%

                        </div>

                    </div>


                <?php endforeach; ?>


            </div>






            <!-- AI -->
            <div class="aiCard">

                <h2 class="sectionTitle mb-5">

                    Why <?= $profile['dream_job'] ?>?

                </h2>


                <p class="text-slate-300 leading-9 text-lg">

                    <?= $aiSummary ?>

                </p>

            </div>








            <!-- ROADMAP -->
            <div class="card">


                <h2 class="sectionTitle mb-8">

                    Your Journey

                </h2>


                <?php foreach($roadmap as $step): ?>

                    <div class="timelineItem">

                        <div class="timelineDot"></div>

                        <div class="flex-1">

                            <h3 class="text-2xl font-bold mb-5">

                                <?= $step['title'] ?>

                            </h3>


                            <?php foreach($step['tasks'] as $task): ?>

                                <div class="taskCard">

                                    <?= $task ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>


            </div>

        </div>







        <!-- RIGHT -->
        <div class="space-y-8">


            <!-- GOAL -->
            <div class="goalCard">

                <h2 class="text-xl font-bold mb-5">

                    Your Goal

                </h2>


                <h3 class="text-3xl font-bold mb-4">

                    <?= $profile['dream_job'] ?>

                </h3>


                <p>

                    Target: 4th Year

                </p>

            </div>






            <!-- PROGRESS -->
            <div class="card">

                <h2 class="sectionTitle mb-5">

                    Progress Overview

                </h2>


                <div class="progressBar">

                    <div
                    class="progressFill"
                    style="width:<?= $readiness ?>%"
                    ></div>

                </div>


                <div class="grid grid-cols-2 gap-6 mt-8">


                    <div>

                        <h3 class="statGreen">

                            <?= $completed ?>

                        </h3>

                        Completed

                    </div>


                    <div>

                        <h3 class="statYellow">

                            <?= $missing ?>

                        </h3>

                        Missing

                    </div>

                </div>

            </div>






            <!-- ACTIONS -->
            <div class="card space-y-3">

                <a href="skill_gap.php" class="actionBtn">
                    View Skill Gaps
                </a>

                <a href="courses.php" class="actionBtn">
                    Find Courses
                </a>

                <a href="mentors.php" class="actionBtn">
                    Connect with Mentors
                </a>

                <a href="jobs.php" class="actionBtn">
                    Browse Jobs
                </a>

            </div>

        </div>


    </div>


</div>





<style>

.navBtn{
    padding:12px 18px;
    border-radius:12px;
}

.navBtn:hover{
    background:#1e293b;
}

.activeNav{
    background:#2563eb;
}

.card{
    background:#162338;
    border:1px solid #334155;
    border-radius:24px;
    padding:32px;
}

.aiCard{
    background:linear-gradient(
        90deg,
        rgba(59,130,246,.2),
        rgba(22,35,56,1)
    );

    border:1px solid #3B82F6;
    border-radius:24px;
    padding:32px;
}

.goalCard{
    background:linear-gradient(
        135deg,
        #3B82F6,
        #2563EB
    );

    border-radius:24px;
    padding:32px;
}

.sectionTitle{
    font-size:30px;
    font-weight:bold;
}

.badge{
    border:1px solid #334155;
    border-radius:999px;
    padding:8px 18px;
}

.pillBtn{
    background:#1e293b;
    padding:12px 20px;
    border-radius:14px;
}

.careerCard{
    border:1px solid #475569;
    border-radius:20px;
    padding:28px;
    margin-bottom:20px;
    display:flex;
    justify-content:space-between;
}

.companyTag{
    border:1px solid #475569;
    border-radius:999px;
    padding:6px 14px;
}

.timelineItem{
    display:flex;
    gap:20px;
    margin-bottom:40px;
}

.timelineDot{
    width:20px;
    height:20px;
    background:#3B82F6;
    border-radius:999px;
    margin-top:8px;
}

.taskCard{
    background:#020B24;
    border:1px solid #334155;
    border-radius:14px;
    padding:18px;
    margin-bottom:12px;
}

.progressBar{
    background:#1e293b;
    height:12px;
    border-radius:999px;
}

.progressFill{
    background:#3B82F6;
    height:100%;
    border-radius:999px;
}

.statGreen{
    color:#10B981;
    font-size:38px;
    font-weight:bold;
}

.statYellow{
    color:#F59E0B;
    font-size:38px;
    font-weight:bold;
}

.actionBtn{
    display:block;
    padding:16px;
    border:1px solid #334155;
    border-radius:14px;
}

.actionBtn:hover{
    background:#1e293b;
}

.redBtn{
    background:#dc2626;
    padding:12px 18px;
    border-radius:14px;
}

</style>


</body>
</html>