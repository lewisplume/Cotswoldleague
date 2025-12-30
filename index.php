<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Cotswold League | Official Site</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
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

    <?php include 'nav.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow flex items-center justify-center">
        <div class="max-w-4xl px-6 text-center py-12">
            
            <div class="mb-6">
                <img src="images/league-logo.png" alt="The Cotswold League Logo" class="h-32 md:h-48 w-auto mx-auto drop-shadow-2xl">
            </div>

            <div class="mb-10">
                <h1 class="text-4xl md:text-6xl font-extrabold mb-2 tracking-tight">
                    THE <span class="text-sky-500">COTSWOLD</span> LEAGUE
                </h1>
                <p class="text-lg text-slate-400 uppercase tracking-widest mb-6">2026 Season</p>
                
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="clubs.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full text-white bg-sky-600 hover:bg-sky-700 transition-colors shadow-lg hover:shadow-sky-500/30">
                        View Participating Teams
                    </a>
                    <a href="join.php" class="inline-flex items-center px-6 py-3 border border-slate-700 text-base font-medium rounded-full text-white bg-transparent hover:bg-slate-800 transition-colors">
                        Join The Waiting List
                    </a>
                </div>
            </div>

            <!-- COUNTDOWN SECTION -->
            <div class="mb-16">
                <h2 class="text-xl font-bold mb-6 text-sky-400 uppercase tracking-tighter italic text-center">Round 1 Begins In:</h2>
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
                <p class="mt-4 text-slate-500 font-medium text-sm">Saturday, January 31st, 2026</p>
            </div>

            <!-- SPONSOR SECTION -->
            <div class="border-t border-slate-800 pt-12">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 mb-6 font-bold tracking-widest">Main League Sponsor</p>
                <div class="flex flex-col items-center">
                    <a href="https://www.wyvernswimwear.co.uk/collections/cotswold-swim-league" target="_blank" class="group transition-transform hover:scale-105 duration-300">
                        <div class="bg-white px-8 py-4 rounded-lg shadow-xl border-4 border-transparent group-hover:border-sky-400">
                            <span class="text-slate-900 font-black text-2xl tracking-tighter">WYVERN SWIMWEAR</span>
                        </div>
                    </a>
                    
                    <a href="https://www.wyvernswimwear.co.uk/collections/cotswold-swim-league" target="_blank" class="mt-4 text-sky-400 hover:text-sky-300 text-sm font-medium tracking-wide flex items-center gap-2">
                        Shop Official Merchandise 
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>

            <footer class="mt-20 text-slate-600 text-[10px] uppercase tracking-[0.3em]">
                &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
            </footer>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // Mobile Menu Toggle Logic is now handled in nav.php but added here as fallback/init
        const targetDate = new Date("January 31, 2026 00:00:00").getTime();

        const countdown = setInterval(function() {
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
        
        // Initializing mobile menu listener for included nav
        const menuBtn = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                const isHidden = mobileMenu.classList.contains('hidden');
                if (isHidden) {
                    mobileMenu.classList.remove('hidden');
                    menuIcon.classList.add('hidden');
                    closeIcon.classList.remove('hidden');
                } else {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                }
            });
        }
    </script>

</body>
</html>