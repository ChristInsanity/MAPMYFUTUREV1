<?php
require_once '../auth_guard.php';

requireMentor();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-[#020B24] text-white min-h-screen">
<nav class="border-b border-slate-800 bg-[#020B24]/95">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-4 flex justify-between items-center">
        <div class="text-xl font-bold text-blue-400 flex items-center gap-3">
            <i class="fa-solid fa-users"></i>
            Mentor Center
        </div>
        <a href="../logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-xl flex items-center gap-2">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
    <div class="bg-[#162338] border border-[#334155] rounded-2xl p-8">
        <h1 class="text-3xl font-bold mb-3">Mentor Dashboard</h1>
        <p class="text-slate-400">Your mentor workspace is ready for student assignments, feedback, and messages.</p>
    </div>
</main>
</body>
</html>
