<?php
require_once '../auth_guard.php';

requireAdmin();


/*
|--------------------------------------------------------------------------
| REAL DATABASE STATS
|--------------------------------------------------------------------------
*/

$totalUsers=$conn->query("
SELECT COUNT(*) total
FROM users
")->fetch_assoc()['total'] ?? 0;


$totalStudents=$conn->query("
SELECT COUNT(*) total
FROM users
WHERE role='student'
")->fetch_assoc()['total'] ?? 0;


$totalMentors=$conn->query("
SELECT COUNT(*) total
FROM users
WHERE role='mentor'
AND status='approved'
")->fetch_assoc()['total'] ?? 0;


$totalEmployers=$conn->query("
SELECT COUNT(*) total
FROM users
WHERE role='employer'
AND status='approved'
")->fetch_assoc()['total'] ?? 0;


$pendingVerifications=$conn->query("
SELECT COUNT(*) total
FROM users
WHERE role IN('mentor','employer')
AND status='pending'
")->fetch_assoc()['total'] ?? 0;


$approvedAccounts=$conn->query("
SELECT COUNT(*) total
FROM users
WHERE status='approved'
")->fetch_assoc()['total'] ?? 0;


$totalProfiles=$conn->query("
SELECT COUNT(*) total
FROM users
WHERE profile_completed=1
")->fetch_assoc()['total'] ?? 0;


$totalRoadmaps=$conn->query("
SELECT COUNT(*) total
FROM student_tasks
")->fetch_assoc()['total'] ?? 0;

$recentUsers=$conn->query("
SELECT full_name, role, created_at
FROM users
ORDER BY created_at DESC
LIMIT 4
");

$careerTrends=$conn->query("
SELECT career_path, COUNT(*) total
FROM student_profiles
WHERE career_path IS NOT NULL AND career_path <> ''
GROUP BY career_path
ORDER BY total DESC, career_path
LIMIT 6
");

?>

<!DOCTYPE html>
<html>
<head>

<title>Admin Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>

</head>


<body class="bg-[#020B24] text-white min-h-screen">



<!-- NAV -->

<nav class="sticky top-0 z-50 bg-[#020B24]/95 backdrop-blur border-b border-slate-800">

<div class="max-w-7xl mx-auto px-4 lg:px-8 py-4 flex justify-between items-center">



<div class="flex items-center gap-10">


<div class="flex items-center gap-3 text-blue-400 text-xl font-bold">

<i class="fa-solid fa-shield-halved text-2xl"></i>

Admin Center

</div>



<div class="hidden xl:flex gap-6 text-slate-300 text-sm">


<a href="dashboard.php" class="navActive">
<i class="fa-solid fa-chart-line"></i>
Dashboard
</a>


<a href="students.php" class="navBtn">
<i class="fa-solid fa-user-graduate"></i>
Students
</a>


<a href="mentors.php" class="navBtn">
<i class="fa-solid fa-chalkboard-user"></i>
Mentors
</a>


<a href="employers.php" class="navBtn">
<i class="fa-solid fa-building"></i>
Employers
</a>


<a href="verification_center.php" class="navBtn">
<i class="fa-solid fa-user-check"></i>
Verification
</a>


<a href="roadmaps.php" class="navBtn">
<i class="fa-solid fa-route"></i>
Roadmaps
</a>


<a href="analytics.php" class="navBtn">
<i class="fa-solid fa-chart-pie"></i>
Analytics
</a>


</div>


</div>




<a
href="../logout.php"
class="bg-red-600 hover:bg-red-700 px-4 lg:px-5 py-2 rounded-xl flex gap-2 items-center"
>

<i class="fa-solid fa-right-from-bracket"></i>

Logout

</a>


</div>

</nav>







<div class="max-w-7xl mx-auto px-4 lg:px-8 py-8">



<!-- HEADER -->

<div class="mb-8">

<h1 class="text-3xl lg:text-4xl font-bold mb-2">
System Overview
</h1>

<p class="text-slate-400">
Map My Future Administration Center
</p>

</div>






<!-- STATS -->

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">


<?php

$cards=[

["Users",$totalUsers,"users"],
["Students",$totalStudents,"user-graduate"],
["Mentors",$totalMentors,"chalkboard-user"],
["Employers",$totalEmployers,"building"],

["Pending",$pendingVerifications,"user-check"],
["Approved",$approvedAccounts,"badge-check"],
["Profiles",$totalProfiles,"id-card"],
["Roadmaps",$totalRoadmaps,"route"]

];

foreach($cards as $card):

?>


<div class="statCard">


<div class="flex justify-between mb-4">


<p>
<?= $card[0] ?>
</p>


<i class="fa-solid fa-<?= $card[2] ?> text-blue-400"></i>


</div>



<h2>

<?= $card[1] ?>

</h2>


</div>


<?php endforeach; ?>


</div>









<div class="grid lg:grid-cols-3 gap-8">



<!-- LEFT -->

<div class="lg:col-span-2 space-y-8">





<div class="card">


<h2 class="sectionTitle mb-5">

Recent Platform Activity

</h2>



<div class="space-y-4">

<?php while($activity=$recentUsers->fetch_assoc()): ?>

<div class="activityItem">
<i class="fa-solid fa-user-plus text-blue-400"></i>
<?= e($activity['full_name']) ?> registered as <?= e($activity['role']) ?>
<span class="ml-auto text-slate-500 text-sm"><?= e(date('M d', strtotime($activity['created_at']))) ?></span>
</div>

<?php endwhile; ?>

</div>


</div>







<div class="card">


<h2 class="sectionTitle mb-5">

Top Career Trends

</h2>



<div class="flex flex-wrap gap-3">

<?php while($trend=$careerTrends->fetch_assoc()): ?>
<span class="trendTag">
<?= e($trend['career_path']) ?> (<?= (int)$trend['total'] ?>)
</span>
<?php endwhile; ?>

</div>


</div>


</div>








<!-- RIGHT -->

<div class="space-y-6">





<div class="card">


<h2 class="sectionTitle mb-5">

Verification Center

</h2>



<div class="space-y-3">


<a
href="verification_center.php"
class="actionBtn"
>

<i class="fa-solid fa-user-check text-green-400"></i>

Approve Mentors

</a>




<a
href="verification_center.php"
class="actionBtn"
>

<i class="fa-solid fa-building-circle-check text-blue-400"></i>

Approve Employers

</a>


</div>


</div>







<div class="card">


<h2 class="sectionTitle mb-5">

Platform Health

</h2>



<p class="text-slate-300 mb-3">

<?= $totalProfiles ?>

 completed student profiles

</p>


<p class="text-slate-300">

<?= $totalRoadmaps ?>

 active student roadmap tasks

</p>


</div>


</div>


</div>


</div>







<style>

.navBtn,
.navActive{
display:flex;
gap:8px;
align-items:center;
}

.navActive{
color:#60a5fa;
}



.card,
.statCard{
background:#162338;
border:1px solid #334155;
padding:24px;
border-radius:20px;
}



.sectionTitle{
font-size:22px;
font-weight:bold;
}



.statCard p{
color:#94a3b8;
}



.statCard h2{
font-size:30px;
font-weight:bold;
}



.activityItem,
.actionBtn{
display:flex;
gap:12px;
align-items:center;
padding:16px;
background:#020B24;
border:1px solid #334155;
border-radius:14px;
}



.actionBtn:hover{
background:#1e293b;
}



.trendTag{
padding:10px 16px;
border-radius:999px;
border:1px solid #334155;
}


</style>


</body>
</html>
