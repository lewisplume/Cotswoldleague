<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Join Us</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { background: rgba(15, 23, 42, 0.8); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .swim-gradient { background: linear-gradient(135deg, #075985 0%, #0ea5e9 100%); }
    </style>
</head>
<body class="text-white font-sans min-h-screen">

    <?php include 'nav.php'; ?>

    <!-- HERO SECTION -->
    <div class="relative py-20 overflow-hidden text-center px-4">
        <h1 class="text-5xl font-extrabold tracking-tight text-white sm:text-6xl mb-6">
            Join the <span class="text-sky-500">Cotswold League</span>
        </h1>
        <p class="text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed">
            A unique swimming league focused on development, sporting spirit, and providing a platform for less experienced swimmers to compete.
        </p>
        <p class="text-sm text-sky-400/90 max-w-3xl mx-auto mt-4 font-semibold tracking-wide uppercase">
            Unlicensed, team-first competition with full league support for clubs and volunteers.
        </p>
    </div>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        
        <!-- ETHOS -->
        <div class="mb-20 space-y-6">
            <h2 class="text-3xl font-bold flex items-center justify-center gap-3 text-center">
                <i data-lucide="heart" class="text-rose-500"></i> Our Ethos
            </h2>

            <div class="glass-panel rounded-3xl p-7">
                <p class="text-slate-300 text-base leading-relaxed">
                    The Cotswold League is built for <strong>development first</strong>. We give less experienced swimmers a supportive, team-focused race environment where confidence can grow.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5 text-sm">
                    <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4">
                        <p class="text-sky-400 font-bold uppercase text-[11px] tracking-wider mb-1">Relaxed Competition</p>
                        <p class="text-slate-300">Our galas are unlicensed, keeping race nights structured but less intense for newer swimmers and families.</p>
                    </div>
                    <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4">
                        <p class="text-sky-400 font-bold uppercase text-[11px] tracking-wider mb-1">Learning Environment</p>
                        <p class="text-slate-300">Swimmers build race confidence, team habits, and event knowledge through regular season competition.</p>
                    </div>
                    <div class="bg-slate-900/40 border border-white/5 rounded-xl p-4">
                        <p class="text-sky-400 font-bold uppercase text-[11px] tracking-wider mb-1">Sportsmanship</p>
                        <p class="text-slate-300">We value positive, respectful racing and a friendly rivalry that supports all clubs in the league.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- HOW IT WORKS SECTION -->
        <div class="mb-20">
            <h2 class="text-3xl font-bold mb-8 text-center">How the League <span class="text-sky-500">Operates</span></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-sky-500/10 rounded-xl flex items-center justify-center mb-4 text-sky-500">
                        <i data-lucide="calendar" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">4 Rounds & Finals</h3>
                    <p class="text-sm text-slate-400">Preliminary rounds run Jan-March. Points accumulate to determine placement in the A, B, or C Finals in April.</p>
                </div>
                <!-- Card 2 -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-4 text-emerald-500">
                        <i data-lucide="home" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Hosting Duties</h3>
                    <p class="text-sm text-slate-400">It is a condition of membership that every club hosts one round and attends three others each season.</p>
                </div>
                <!-- Card 3 -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center mb-4 text-amber-500">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Governance</h3>
                    <p class="text-sm text-slate-400">The league is run by a committee of 4 teams, randomly selected at the AGM. New clubs are exempt in year 1.</p>
                </div>
                <!-- Card 4 -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center mb-4 text-purple-500">
                        <i data-lucide="pound-sterling" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Fees</h3>
                    <p class="text-sm text-slate-400">Membership fees are typically £100 per year & due by Nov 30th. <strong>Note:</strong> 2026 Season fees have been waived.</p>
                </div>
            </div>
        </div>

        <!-- CLUB BENEFITS -->
        <div class="mb-20">
            <h2 class="text-3xl font-bold mb-8 text-center">Why Clubs Choose <span class="text-sky-500">Cotswold</span></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-cyan-500/10 rounded-xl flex items-center justify-center mb-4 text-cyan-400">
                        <i data-lucide="laptop" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Software Included</h3>
                    <p class="text-sm text-slate-400">All software needed to operate in the league is provided to member clubs. We do not use Meet Manager, helping reduce cost and setup barriers for teams.</p>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-4 text-emerald-400">
                        <i data-lucide="user-plus" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Volunteer Development</h3>
                    <p class="text-sm text-slate-400">We actively encourage new volunteers to get involved with timekeeping and recording. Many clubs use the Cotswold League as a great pathway for training race-night officials.</p>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center mb-4 text-amber-400">
                        <i data-lucide="trophy" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Finals Recognition</h3>
                    <p class="text-sm text-slate-400">All teams receive trophies at the Finals, celebrating contribution and season performance across every level of the league.</p>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-sky-500/10 rounded-xl flex items-center justify-center mb-4 text-sky-400">
                        <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Team Portal Access</h3>
                    <p class="text-sm text-slate-400">Member teams gain access to dedicated team portals with fixtures, resources, downloads, and operational information in one location.</p>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-rose-500/10 rounded-xl flex items-center justify-center mb-4 text-rose-400">
                        <i data-lucide="megaphone" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Strong Club Promotion</h3>
                    <p class="text-sm text-slate-400">Clubs are promoted heavily across our website and social channels. The league now generates 100,000+ views over a season, giving excellent visibility to member teams.</p>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors">
                    <div class="w-12 h-12 bg-indigo-500/10 rounded-xl flex items-center justify-center mb-4 text-indigo-400">
                        <i data-lucide="clipboard-check" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Clear, Structured Racing</h3>
                    <p class="text-sm text-slate-400">Each gala runs a clear 53-event programme lasting just 2 hours with age-group and relay racing, giving swimmers consistent team-competition experience throughout the season.</p>
                </div>
            </div>
        </div>

        <!-- PRIORITY POLICY CARD -->
        <div class="mb-20">
            <div class="glass-panel p-8 rounded-3xl border-l-4 border-l-rose-500 h-fit">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="shield-alert" class="text-rose-500"></i> Membership Priority
                </h3>
                <p class="text-slate-400 mb-4">
                    The league is capped at 20 clubs to ensure efficient gala management. When vacancies arise, we adhere to a strict priority policy:
                </p>
                <ul class="space-y-3 text-sm text-slate-300">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check-circle" class="text-rose-500 w-4 h-4 mt-0.5"></i>
                        <span><strong>Priority 1:</strong> Independent local clubs with a desire to allow lower level swimmers entries into competitive swimming events.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="x-circle" class="text-slate-500 w-4 h-4 mt-0.5"></i>
                        <span><strong>Secondary:</strong> 'B' teams from higher-ranked clubs are considered only if no independent local clubs are on the waiting list.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- CONTACT / CTA -->
        <div class="glass-panel rounded-[2rem] p-8 md:p-12 text-center relative overflow-hidden border border-slate-700/50">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                <i data-lucide="mail" class="w-64 h-64"></i>
            </div>
            
            <h2 class="text-3xl font-bold mb-4">Interested in Joining?</h2>
            <p class="text-slate-400 max-w-2xl mx-auto mb-8 text-lg">
                While vacancies are rare due to our 20-club cap, we maintain an active waiting list. Applications are reviewed at the AGM.
            </p>
            
            <div class="flex flex-col items-center gap-4">
                <a href="mailto:admin@thecotswoldleague.co.uk" class="swim-gradient px-8 py-4 rounded-full font-bold text-lg flex items-center gap-3 shadow-lg shadow-sky-500/20 hover:scale-105 transition-transform">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    Contact Lewis the League Administrator
                </a>
                <span class="text-slate-500 font-mono text-sm">admin@thecotswoldleague.co.uk</span>
            </div>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();

    </script>
</body>
</html>