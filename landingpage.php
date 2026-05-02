<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Map My Future</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>

</head>

<body class="bg-slate-950 text-white scroll-smooth">


<!-- NAVBAR -->
<nav class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur border-b border-slate-800">

    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

        <div class="flex items-center gap-3">

            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                <i class="fa-solid fa-route"></i>
            </div>

            <h1 class="text-xl font-bold">
                Map My Future
            </h1>

        </div>


        <div class="flex items-center gap-4">

            <a href="auth.php"
               class="text-slate-300 hover:text-white transition">
                Login
            </a>

            <a href="auth.php"
               class="bg-blue-600 hover:bg-blue-500 px-5 py-2 rounded-xl font-medium transition">
                Get Started
            </a>

        </div>

    </div>

</nav>



<!-- HERO -->
<section
class="relative bg-cover bg-center bg-no-repeat"
style="background-image:url('images/hero-bg.jpg');"
>

    <div class="absolute inset-0 bg-black/70"></div>


    <div class="relative max-w-7xl mx-auto px-6 py-36 grid md:grid-cols-2 gap-12 items-center">


        <div>

            <span class="text-blue-400 font-semibold">
                AI Career Guidance for Filipino Students
            </span>

            <h1 class="text-5xl md:text-6xl font-bold mt-4 mb-6 leading-tight">

                Discover Your Path.
                <br>
                Build Your Future.

            </h1>


            <p class="text-slate-300 text-lg mb-8">

                Map My Future helps students discover their strengths,
                align their skills, and build personalized career roadmaps
                powered by artificial intelligence.

            </p>


            <div class="flex gap-4">

                <a href="auth.php"
                   class="bg-blue-600 hover:bg-blue-500 px-8 py-4 rounded-xl font-semibold transition">

                    Get Started

                </a>


                <button class="border border-slate-500 px-8 py-4 rounded-xl hover:bg-white/10 transition">

                    Watch Demo

                </button>

            </div>

        </div>



        <!-- Floating card -->
        <div
        class="bg-slate-900/80 backdrop-blur-xl
               border border-slate-700
               rounded-2xl p-8
               hover:scale-105 transition duration-500">

            <div class="flex items-center gap-4 mb-5">

                <div class="w-14 h-14 bg-green-500/20 rounded-xl flex items-center justify-center">

                    <i class="fa-solid fa-chart-line text-green-400 text-xl"></i>

                </div>

                <div>

                    <p class="text-green-400 font-bold text-2xl">
                        Career Match 94%
                    </p>

                    <p class="text-slate-400">
                        UI/UX Designer
                    </p>

                </div>

            </div>


            <div class="bg-slate-700 h-3 rounded-full">

                <div class="bg-blue-500 h-3 w-[94%] rounded-full"></div>

            </div>


            <div class="mt-8 space-y-3 text-slate-300">

                <p>✓ Creative Thinking</p>
                <p>✓ Visual Design</p>
                <p>✓ User Research</p>

            </div>

        </div>


    </div>

</section>



<!-- FEATURES -->
<section class="py-24 bg-slate-900">

    <div class="max-w-7xl mx-auto px-6">


        <div class="text-center mb-16">

            <h2 class="text-4xl font-bold mb-4">
                Powerful Features
            </h2>

            <p class="text-slate-400">
                Everything you need to plan your career journey.
            </p>

        </div>



        <div class="grid md:grid-cols-4 gap-6">


            <!-- CARD -->
            <div class="group bg-slate-800 border border-slate-800 p-8 rounded-2xl
                        hover:-translate-y-3 hover:border-blue-500
                        hover:shadow-2xl transition duration-300">

                <i class="fa-solid fa-road text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-semibold mb-2 text-lg">
                    AI Roadmap
                </h3>

                <p class="text-slate-400 text-sm">
                    Personalized learning paths based on your goals.
                </p>

            </div>


            <div class="group bg-slate-800 border border-slate-800 p-8 rounded-2xl
                        hover:-translate-y-3 hover:border-blue-500
                        hover:shadow-2xl transition duration-300">

                <i class="fa-solid fa-chart-column text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-semibold mb-2 text-lg">
                    Skill Gap Analysis
                </h3>

                <p class="text-slate-400 text-sm">
                    Identify missing skills for your dream career.
                </p>

            </div>


            <div class="group bg-slate-800 border border-slate-800 p-8 rounded-2xl
                        hover:-translate-y-3 hover:border-blue-500
                        hover:shadow-2xl transition duration-300">

                <i class="fa-solid fa-users text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-semibold mb-2 text-lg">
                    Mentorship
                </h3>

                <p class="text-slate-400 text-sm">
                    Learn from industry professionals.
                </p>

            </div>


            <div class="group bg-slate-800 border border-slate-800 p-8 rounded-2xl
                        hover:-translate-y-3 hover:border-blue-500
                        hover:shadow-2xl transition duration-300">

                <i class="fa-solid fa-briefcase text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-semibold mb-2 text-lg">
                    Job Matching
                </h3>

                <p class="text-slate-400 text-sm">
                    Connect with opportunities that fit your profile.
                </p>

            </div>

        </div>

    </div>

</section>



<!-- HOW IT WORKS -->
<section class="py-24">

    <div class="max-w-7xl mx-auto px-6">


        <div class="text-center mb-16">

            <h2 class="text-4xl font-bold mb-4">
                Your Journey in 4 Steps
            </h2>

            <p class="text-slate-400 max-w-2xl mx-auto">
                From self-discovery to career success,
                Map My Future guides you every step of the way.
            </p>

        </div>



        <div class="grid md:grid-cols-4 gap-8">


            <!-- STEP -->
            <div class="group bg-slate-900 border border-slate-800
                        p-8 rounded-2xl text-center
                        hover:-translate-y-3 hover:border-blue-500
                        transition duration-300">

                <i class="fa-solid fa-user text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-bold mb-3">
                    Create Profile
                </h3>

                <p class="text-slate-400 text-sm">
                    Share your interests, strengths, and goals.
                </p>

            </div>



            <div class="group bg-slate-900 border border-slate-800
                        p-8 rounded-2xl text-center
                        hover:-translate-y-3 hover:border-blue-500
                        transition duration-300">

                <i class="fa-solid fa-brain text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-bold mb-3">
                    AI Analysis
                </h3>

                <p class="text-slate-400 text-sm">
                    AI evaluates your personality and potential.
                </p>

            </div>



            <div class="group bg-slate-900 border border-slate-800
                        p-8 rounded-2xl text-center
                        hover:-translate-y-3 hover:border-blue-500
                        transition duration-300">

                <i class="fa-solid fa-map text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-bold mb-3">
                    Follow Roadmap
                </h3>

                <p class="text-slate-400 text-sm">
                    Complete recommended milestones and skills.
                </p>

            </div>



            <div class="group bg-slate-900 border border-slate-800
                        p-8 rounded-2xl text-center
                        hover:-translate-y-3 hover:border-blue-500
                        transition duration-300">

                <i class="fa-solid fa-trophy text-blue-500 text-3xl mb-5
                          group-hover:scale-110 transition"></i>

                <h3 class="font-bold mb-3">
                    Achieve Success
                </h3>

                <p class="text-slate-400 text-sm">
                    Build confidence and land opportunities.
                </p>

            </div>


        </div>

    </div>

</section>



<!-- CTA -->
<section class="py-28 text-center bg-slate-900">

    <h2 class="text-5xl font-bold mb-6">
        Start Building Your Future Today
    </h2>

    <p class="text-slate-400 mb-10 max-w-xl mx-auto">
        Join thousands of Filipino students discovering
        the careers that fit them best.
    </p>


    <a href="auth.php"
       class="bg-blue-600 hover:bg-blue-500
              px-10 py-4 rounded-xl font-semibold
              transition">

        Get Started Free

    </a>

</section>



<!-- FOOTER -->
<footer class="bg-slate-950 border-t border-slate-800 py-8 text-center text-slate-400">

    © 2026 Map My Future • Built for Filipino Students

</footer>


</body>
</html>