<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Map My Future</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-white">

<!-- NAVBAR -->
<nav class="bg-slate-900 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">Map My Future</h1>

        <div class="space-x-4">
            <a href="auth.php" class="text-slate-300 hover:text-white">Login</a>
            <a href="auth.php" class="bg-blue-600 px-4 py-2 rounded-lg">Get Started</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-10 items-center">

    <div>
        <h1 class="text-5xl font-bold mb-6">Map Your Future Career</h1>
        <p class="text-slate-300 mb-6">
            AI-powered career guidance tailored for Filipino students.
        </p>

        <div class="flex gap-4">
            <a href="auth.php" class="bg-blue-600 px-6 py-3 rounded-lg">Get Started</a>
            <button class="border border-slate-700 px-6 py-3 rounded-lg">Watch Demo</button>
        </div>
    </div>

    <div class="bg-slate-800 p-6 rounded-xl">
        <p class="text-green-400 font-bold text-xl">Career Match: 94%</p>
        <p class="text-slate-400">UI/UX Designer</p>

        <div class="mt-4 bg-slate-700 h-2 rounded-full">
            <div class="bg-blue-500 h-2 w-[94%] rounded-full"></div>
        </div>
    </div>

</section>

<!-- FEATURES -->
<section class="py-16 bg-slate-900">
    <div class="max-w-7xl mx-auto px-6 text-center">

        <h2 class="text-3xl font-bold mb-10">Features</h2>

        <div class="grid md:grid-cols-4 gap-6">

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-semibold mb-2">AI Roadmap</h3>
                <p class="text-slate-400 text-sm">Personalized career path</p>
            </div>

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-semibold mb-2">Skill Gap</h3>
                <p class="text-slate-400 text-sm">Find missing skills</p>
            </div>

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-semibold mb-2">Mentorship</h3>
                <p class="text-slate-400 text-sm">Connect with experts</p>
            </div>

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-semibold mb-2">Job Matching</h3>
                <p class="text-slate-400 text-sm">Find the right job</p>
            </div>

        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-6 text-center">

        <h2 class="text-3xl font-bold mb-10">How It Works</h2>

        <div class="grid md:grid-cols-4 gap-6">

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-bold">1. Create Profile</h3>
            </div>

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-bold">2. AI Analysis</h3>
            </div>

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-bold">3. Follow Roadmap</h3>
            </div>

            <div class="bg-slate-800 p-6 rounded-xl">
                <h3 class="font-bold">4. Get Hired</h3>
            </div>

        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 text-center">
    <h2 class="text-3xl font-bold mb-4">Ready to Start?</h2>
    <a href="auth.php" class="bg-blue-600 px-8 py-3 rounded-lg">Get Started Free</a>
</section>

<!-- FOOTER -->
<footer class="bg-slate-900 border-t border-slate-800 py-6 text-center text-slate-400">
    © 2026 Map My Future
</footer>

</body>
</html>