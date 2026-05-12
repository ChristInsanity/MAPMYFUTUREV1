<?php
require_once '../auth_guard.php';

requireAdmin();


/*
|--------------------------------------------------------------------------
| LIVE COUNTS
|--------------------------------------------------------------------------
*/

$pendingTotal = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE role IN('mentor','employer')
AND status='pending'
")->fetch_assoc()['total'] ?? 0;


$approvedTotal = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE role IN('mentor','employer')
AND status='approved'
")->fetch_assoc()['total'] ?? 0;


$rejectedTotal = $conn->query("
SELECT COUNT(*) total
FROM users
WHERE role IN('mentor','employer')
AND status='rejected'
")->fetch_assoc()['total'] ?? 0;



/*
|--------------------------------------------------------------------------
| APPROVE / REJECT
|--------------------------------------------------------------------------
*/

if(isset($_GET['approve'])){

    $id=(int)$_GET['approve'];

    $stmt=$conn->prepare("
    UPDATE users
    SET status='approved'
    WHERE user_id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();

    redirect("verification_center.php");
}


if(isset($_GET['reject'])){

    $id=(int)$_GET['reject'];

    $stmt=$conn->prepare("
    UPDATE users
    SET status='rejected'
    WHERE user_id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();

    redirect("verification_center.php");
}




/*
|--------------------------------------------------------------------------
| FETCH PENDING
|--------------------------------------------------------------------------
*/

$applicants = $conn->query("
SELECT *
FROM users
WHERE role IN('mentor','employer')
ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Verification Center</title>

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


<div class="flex gap-10 items-center">


<div class="text-xl font-bold text-blue-400 flex gap-3 items-center">

<i class="fa-solid fa-shield-halved"></i>

Admin Center

</div>


<div class="hidden xl:flex gap-6 text-sm">


<a href="dashboard.php" class="navBtn">
<i class="fa-solid fa-chart-line"></i>
Dashboard
</a>


<a href="#" class="navActive">
<i class="fa-solid fa-user-check"></i>
Verification
</a>


<a href="analytics.php" class="navBtn">
<i class="fa-solid fa-chart-pie"></i>
Analytics
</a>


</div>


</div>



<a
href="../logout.php"
class="bg-red-600 px-5 py-2 rounded-xl flex gap-2 items-center"
>
<i class="fa-solid fa-right-from-bracket"></i>
Logout
</a>


</div>

</nav>






<div class="max-w-7xl mx-auto px-4 lg:px-8 py-8">


<!-- HEADER -->

<div class="mb-8">

<h1 class="text-4xl font-bold mb-2">

Verification Center

</h1>

<p class="text-slate-400">

Approve mentor and employer accounts

</p>

</div>






<!-- STATS -->

<div class="grid sm:grid-cols-3 gap-4 mb-8">


<div class="statCard">

<div class="flex justify-between mb-3">

<p>Pending</p>

<i class="fa-solid fa-clock text-yellow-400"></i>

</div>

<h2><?= $pendingTotal ?></h2>

</div>




<div class="statCard">

<div class="flex justify-between mb-3">

<p>Approved</p>

<i class="fa-solid fa-circle-check text-green-400"></i>

</div>

<h2><?= $approvedTotal ?></h2>

</div>




<div class="statCard">

<div class="flex justify-between mb-3">

<p>Rejected</p>

<i class="fa-solid fa-circle-xmark text-red-400"></i>

</div>

<h2><?= $rejectedTotal ?></h2>

</div>


</div>







<!-- APPLICANTS -->

<div class="card">

    <div class="flex justify-between items-center mb-6">

        <h2 class="sectionTitle">
            Account Requests
        </h2>

        <span class="text-slate-400 text-sm">
            <?= $pendingTotal ?> Pending
        </span>

    </div>


    <div class="space-y-3">

        <?php while($row=$applicants->fetch_assoc()): ?>

        <div class="applicantRow">


            <!-- LEFT -->
            <div class="flex items-center gap-4 min-w-0 flex-1">


                <!-- Avatar -->
                <div class="avatarCircle">

                    <?= strtoupper(substr($row['full_name'],0,1)) ?>

                </div>



                <!-- Details -->
                <div class="min-w-0">

                    <h3 class="font-semibold truncate">

                        <?= $row['full_name'] ?>

                    </h3>


                    <p class="text-slate-400 text-sm truncate">

                        <?= $row['email'] ?>

                    </p>

                </div>


            </div>





            <!-- BADGES -->
            <div class="hidden md:flex gap-2">

                <span class="badge">

                    <?= ucfirst($row['role']) ?>

                </span>


                <span class="<?= badgeClass($row['status']) ?>">

                    <?= ucfirst($row['status']) ?>

                </span>

            </div>






            <!-- ACTIONS -->
            <?php if($row['status']=="pending"): ?>

            <div class="flex gap-2 ml-auto">


                <a
                href="?approve=<?= $row['user_id'] ?>"
                class="iconApprove"
                title="Approve"
                >
                    <i class="fa-solid fa-check"></i>
                </a>



                <a
                href="?reject=<?= $row['user_id'] ?>"
                class="iconReject"
                title="Reject"
                >
                    <i class="fa-solid fa-xmark"></i>
                </a>


            </div>

            <?php endif; ?>


        </div>

        <?php endwhile; ?>

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


.statCard h2{
font-size:32px;
font-weight:bold;
}


.statCard p{
color:#94a3b8;
}


.sectionTitle{
font-size:24px;
font-weight:bold;
}


.applicantRow{
    background:#020B24;
    border:1px solid #334155;
    border-radius:16px;
    padding:16px 18px;

    display:flex;
    align-items:center;
    gap:16px;

    transition:.2s;
}

.applicantRow:hover{
    border-color:#475569;
    transform:translateY(-1px);
}


.avatarCircle{
    width:44px;
    height:44px;

    border-radius:999px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-weight:bold;

    background:#2563eb;
}


.badge{
    padding:6px 12px;
    border-radius:999px;
    border:1px solid #334155;

    font-size:12px;
}


.iconApprove,
.iconReject{
    width:38px;
    height:38px;

    border-radius:12px;

    display:flex;
    align-items:center;
    justify-content:center;

    transition:.2s;
}


.iconApprove{
    background:#16a34a;
}

.iconApprove:hover{
    background:#15803d;
}


.iconReject{
    background:#dc2626;
}

.iconReject:hover{
    background:#b91c1c;
}

</style>


</body>
</html>


<?php

function badgeClass($status){

    switch($status){

        case "approved":
            return "badge text-green-400";

        case "rejected":
            return "badge text-red-400";

        default:
            return "badge text-yellow-400";
    }
}
?>
