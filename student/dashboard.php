<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('../auth.php');
}

$userId=$_SESSION['user_id'];

$stmt=$conn->prepare("
SELECT *
FROM student_profiles
WHERE user_id=?
");

$stmt->bind_param("i",$userId);
$stmt->execute();

$profile=$stmt->get_result()->fetch_assoc();

if(!$profile){
    redirect('profile_setup.php');
}

$interest=$profile['interests'] ?? 'technology';

$aiSummary=$profile['ai_summary'] ?? "
Based on your {$profile['course']} background,
your interest in {$interest},
and your dream career of {$profile['dream_job']},
we found your strongest match.
";

$readiness=$profile['readiness_score'] ?? 72;
$completed=$profile['completed_skills'] ?? 12;
$missing=$profile['missing_skills'] ?? 3;
$projects=$profile['portfolio_projects'] ?? 8;


$careers=[

[
"title"=>"UI/UX Designer",
"salary"=>"₱25,000 - ₱60,000",
"match"=>92,
"progress"=>65,
"color"=>"from-blue-500 to-cyan-500"
],

[
"title"=>"Software Developer",
"salary"=>"₱30,000 - ₱80,000",
"match"=>88,
"progress"=>58,
"color"=>"from-purple-500 to-pink-500"
],

[
"title"=>"Data Analyst",
"salary"=>"₱28,000 - ₱70,000",
"match"=>85,
"progress"=>52,
"color"=>"from-green-500 to-emerald-500"
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
<nav class="border-b border-slate-800 sticky top-0 bg-[#020B24]/95 backdrop-blur z-50">

<div class="max-w-7xl mx-auto px-4 lg:px-8 py-4 flex justify-between items-center">

<div class="flex items-center gap-8">

<div class="text-xl font-bold text-blue-400 flex items-center gap-3">
    <i class="fa-regular fa-map text-blue-400"></i>
    Map My Future
</div>

<div class="hidden lg:flex gap-6 text-slate-300">

<a href="#" class="text-blue-400 flex items-center gap-2">
<i class="fa-solid fa-house"></i>
Dashboard
</a>

<a href="skill_gap.php" class="flex items-center gap-2">
<i class="fa-solid fa-bolt"></i>
Skill Gap
</a>

<a href="courses.php" class="flex items-center gap-2">
<i class="fa-solid fa-book-open"></i>
Courses
</a>

<a href="mentors.php" class="flex items-center gap-2">
<i class="fa-solid fa-users"></i>
Mentors
</a>

<a href="jobs.php" class="flex items-center gap-2">
<i class="fa-solid fa-briefcase"></i>
Jobs
</a>

</div>

</div>


<div class="flex gap-2 sm:gap-4">

<a
href="portfolio.php"
class="bg-blue-600 px-4 py-2 rounded-xl flex items-center gap-2"
>
<i class="fa-solid fa-folder-open"></i>
Portfolio
</a>


<a
href="../logout.php"
class="bg-red-600 px-4 py-2 rounded-xl flex items-center gap-2"
>
<i class="fa-solid fa-right-from-bracket"></i>
Logout
</a>

</div>

</div>

</nav>





<div class="max-w-7xl mx-auto px-4 lg:px-8 py-8">



<!-- WELCOME -->
<div class="mb-8">

<div class="flex flex-col lg:flex-row justify-between gap-4 mb-6">

<div>

<h1 class="text-3xl lg:text-5xl font-bold mb-2">
Welcome back!
</h1>

<p class="text-slate-400">
<?= $profile['course'] ?>
</p>

</div>


<div>

<a
href="#"
class="bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-3 rounded-xl font-bold inline-flex items-center gap-3"
>
<i class="fa-solid fa-sparkles"></i>
Upgrade Premium
</a>

</div>

</div>



<div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-6">

<div class="flex gap-3 items-center mb-2">

<i class="fa-solid fa-bullseye text-blue-400"></i>

<p class="text-blue-400">
Career Goal
</p>

</div>

<h2 class="text-2xl font-bold">
<?= $profile['dream_job'] ?>
</h2>

</div>

</div>






<!-- STATS -->

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">


<div class="statCard">

<div class="flex justify-between mb-3">

<p>Career Readiness</p>

<i class="fa-solid fa-chart-line text-green-400"></i>

</div>

<h2><?= $readiness ?>%</h2>

</div>



<div class="statCard">

<div class="flex justify-between mb-3">

<p>Skills Mastered</p>

<i class="fa-solid fa-award text-blue-400"></i>

</div>

<h2><?= $completed ?></h2>

</div>



<div class="statCard">

<div class="flex justify-between mb-3">

<p>Projects Built</p>

<i class="fa-solid fa-layer-group text-purple-400"></i>

</div>

<h2><?= $projects ?></h2>

</div>



<div class="statCard">

<div class="flex justify-between mb-3">

<p>Missing Skills</p>

<i class="fa-solid fa-calendar text-yellow-400"></i>

</div>

<h2><?= $missing ?></h2>

</div>


</div>







<div class="grid lg:grid-cols-3 gap-8">



<!-- LEFT -->
<div class="lg:col-span-2 space-y-8">



<!-- CAREERS -->

<div>

<h2 class="sectionTitle mb-5">
Your Career Paths
</h2>


<div class="space-y-4">

<?php foreach($careers as $career): ?>

<div class="card">

<div class="flex justify-between mb-4">

<div>

<h3 class="text-2xl font-bold mb-1">
<?= $career['title'] ?>
</h3>

<p class="text-slate-400">
<?= $career['salary'] ?>
</p>

</div>


<div class="text-green-400 font-bold">
<?= $career['match'] ?>%
</div>

</div>



<div>

<div class="flex justify-between text-sm mb-2">

<p class="text-slate-400">
Progress
</p>

<p>
<?= $career['progress'] ?>%
</p>

</div>


<div class="bg-slate-900 rounded-full h-3">

<div
class="h-full rounded-full bg-gradient-to-r <?= $career['color'] ?>"
style="width:<?= $career['progress'] ?>%"
></div>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>






<!-- AI -->
<div class="bg-gradient-to-br from-purple-900/40 to-blue-900/40 border border-purple-500/20 rounded-2xl p-6">

<div class="flex items-center gap-3 mb-4">

<i class="fa-solid fa-sparkles text-purple-400"></i>

<h2 class="sectionTitle">
Why <?= $profile['dream_job'] ?>?
</h2>

</div>


<p class="text-slate-300 leading-8">
<?= $aiSummary ?>
</p>

</div>

</div>







<!-- RIGHT -->

<div class="space-y-6">



<!-- QUICK -->
<div>

<h2 class="sectionTitle mb-4">
Quick Actions
</h2>


<div class="space-y-3">

<a href="skill_gap.php" class="quickBtn">
<i class="fa-solid fa-bolt text-purple-400"></i>
View Skill Gaps
</a>

<a href="courses.php" class="quickBtn">
<i class="fa-solid fa-book text-green-400"></i>
Find Courses
</a>

<a href="mentors.php" class="quickBtn">
<i class="fa-solid fa-users text-blue-400"></i>
Connect with Mentors
</a>

<a href="jobs.php" class="quickBtn">
<i class="fa-solid fa-briefcase text-yellow-400"></i>
Browse Jobs
</a>

</div>

</div>






<!-- MILESTONES -->
<div class="card">

<h2 class="sectionTitle mb-4">
Upcoming Milestones
</h2>


<div class="space-y-4">

<div class="flex gap-3">
<div class="w-2 h-2 rounded-full bg-yellow-400 mt-2"></div>
<div>
Complete Certification
</div>
</div>


<div class="flex gap-3">
<div class="w-2 h-2 rounded-full bg-blue-400 mt-2"></div>
<div>
Build Portfolio Project
</div>
</div>


<div class="flex gap-3">
<div class="w-2 h-2 rounded-full bg-purple-400 mt-2"></div>
<div>
Apply for Internship
</div>
</div>


</div>

</div>







<!-- MENTOR -->
<div class="card">

<h2 class="sectionTitle mb-4">
Featured Mentor
</h2>


<div class="flex gap-4 items-center">

<div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center">

<i class="fa-solid fa-user"></i>

</div>


<div>

<h3 class="font-bold">
Maria Santos
</h3>

<p class="text-slate-400 text-sm">
Senior Mentor
</p>

</div>

</div>

</div>



</div>


</div>



</div>


</div>

</div>





<style>

.card{
background:#162338;
border:1px solid #334155;
padding:24px;
border-radius:20px;
}

.sectionTitle{
font-size:24px;
font-weight:bold;
}

.quickBtn{
display:block;
padding:18px;
background:#162338;
border:1px solid #334155;
border-radius:16px;
}

.quickBtn:hover{
background:#1e293b;
}

.statCard{
background:#162338;
border:1px solid #334155;
padding:24px;
border-radius:20px;
}

.statCard p{
color:#94a3b8;
margin-bottom:8px;
}

.statCard h2{
font-size:32px;
font-weight:bold;
}

</style>

</body>
</html>