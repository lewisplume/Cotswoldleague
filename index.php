<?php include_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Cotswold League | Official Site</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body {
            background-color: #0f172a;
        }

        .swim-gradient {
            background: linear-gradient(135deg, #075985 0%, #0ea5e9 100%);
        }

        .timer-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

    </style>
</head>

<body class="text-white font-sans min-h-screen flex flex-col">

    <div id="fb-root"></div>
    <script async defer crossorigin="anonymous"
        src="https://connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v24.0&appId=2628298884190796"></script>

    <?php include 'nav.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow flex items-center justify-center">
        <div class="w-full max-w-4xl px-6 text-center py-12">

            <div class="mb-6">
                <img src="images/league-logo.svg" alt="The Cotswold League Logo"
                    class="h-32 md:h-48 w-auto mx-auto drop-shadow-2xl">
            </div>

            <div class="mb-10">
                <h1 class="text-4xl md:text-6xl font-extrabold mb-2 tracking-tight">
                    THE <span class="text-sky-500">COTSWOLD</span> LEAGUE
                </h1>
                <p class="text-lg text-slate-400 uppercase tracking-widest mb-6">2027 Season</p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="clubs"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full text-white bg-sky-600 hover:bg-sky-700 transition-colors shadow-lg hover:shadow-sky-500/30">
                        View Participating Teams
                    </a>
                    <a href="table"
                        class="inline-flex items-center px-6 py-3 border border-slate-700 text-base font-medium rounded-full text-white bg-transparent hover:bg-slate-800 transition-colors">
                        View League Table
                    </a>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-300">AGM: Saturday 6th June at 9.45am. Club representatives can view full details below.</p>
                    <a href="teamportal.php#documents"
                        class="inline-flex items-center mt-3 px-6 py-3 border border-slate-700 text-base font-medium rounded-full text-white bg-transparent hover:bg-slate-800 transition-colors">
                        View AGM Details
                    </a>
                </div>
            </div>

            <!-- COUNTDOWN SECTION -->
            <div class="mb-16">
                <h2 class="text-xl font-bold mb-6 text-sky-400 uppercase tracking-tighter italic text-center">Round 1
                    Begins In:</h2>
                <div class="grid grid-cols-4 gap-2 md:gap-4 max-w-md mx-auto">
                    <div class="timer-box p-3 rounded-xl">
                        <div id="days" class="text-3xl md:text-4xl font-black text-white">00</div>
                        <div class="text-[10px] uppercase tracking-widest text-slate-500">Days</div>
                    </div>
                    <div class="timer-box p-3 rounded-xl">
                        <div id="hours" class="text-3xl md:text-4xl font-black text-white">00</div>
                        <div class="text-[10px] uppercase tracking-widest text-slate-500">Hrs</div>
                    </div>
                    <div class="timer-box p-3 rounded-xl">
                        <div id="minutes" class="text-3xl md:text-4xl font-black text-white">00</div>
                        <div class="text-[10px] uppercase tracking-widest text-slate-500">Min</div>
                    </div>
                    <div class="timer-box p-3 rounded-xl">
                        <div id="seconds" class="text-3xl md:text-4xl font-black text-white text-sky-500">00</div>
                        <div class="text-[10px] uppercase tracking-widest text-slate-500">Sec</div>
                    </div>
                </div>
                <p class="mt-4 text-slate-500 font-medium text-sm">13 February 2027</p>
            </div>

            <!-- ABOUT SECTION -->
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-xl font-bold mb-6 text-sky-400 uppercase tracking-tighter italic">About The League</h2>
                <p class="text-slate-300 leading-relaxed mb-8 text-lg">
                    The Cotswold League stands as a beacon of competitive swimming excellence, uniting clubs from across
                    the region in a display of skill, determination, and sportsmanship. Since its inception, the league
                    has provided a platform for swimmers to challenge themselves, break records, and forge lasting
                    friendships.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="https://www.facebook.com/profile.php?id=100094686571540" target="_blank"
                        class="inline-flex items-center gap-2 px-6 py-3 border border-[#1877F2] text-[#1877F2] hover:bg-[#1877F2] hover:text-white rounded-full transition-all duration-300 font-medium group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="group-hover:stroke-white">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                        Follow Us on Facebook
                    </a>
                    <a href="https://www.instagram.com/thecotswoldleague/" target="_blank"
                        class="inline-flex items-center gap-2 px-6 py-3 border border-[#E1306C] text-[#E1306C] hover:bg-[#E1306C] hover:text-white rounded-full transition-all duration-300 font-medium group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="group-hover:stroke-white">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                        Follow Us on Instagram
                    </a>
                </div>
            </div>

            <!-- Sponsor section removed -->
            <footer class="mt-20 text-slate-600 text-[10px] uppercase tracking-[0.3em] text-center">
                <p>&copy; 2026 The Cotswold Swimming League | Built by Lewis Plume</p>
                <a href="privacy.php" class="inline-block mt-3 text-slate-400 hover:text-sky-400 transition-colors normal-case tracking-normal text-xs">
                    Privacy Policy
                </a>
            </footer>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // Mobile Menu Toggle Logic is now handled in nav.php but added here as fallback/init
        // Countdown now targets Round 1 of the 2027 season (13/02/2027)
        const targetDate = new Date("February 13, 2027 00:00:00").getTime();

        const countdown = setInterval(function () {
            const now = new Date().getTime();
            const distance = targetDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("days").innerHTML = days;
            document.getElementById("hours").innerHTML = hours;
            document.getElementById("minutes").innerHTML = minutes;
            document.getElementById("seconds").innerHTML = seconds;

            if (distance < 0) {
                clearInterval(countdown);
                document.querySelector(".grid").innerHTML = "<div class='col-span-4 text-2xl font-bold text-sky-500 uppercase'>Round 1 Underway!</div>";
            }
        }, 1000);


    </script>

</body>

</html>