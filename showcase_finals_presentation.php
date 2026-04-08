<!-- TRI-FINALS PRESENTATION VIEW -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Finals Showcase</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; overflow-x: hidden; }
        .gradient-text {
            background: linear-gradient(135deg, #38bdf8 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-gradient { background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%); }
        
        /* Announcer Cards */
        .showcase-card {
            opacity: 0;
            transform: translateY(30px) scale(0.98);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .showcase-card.revealed {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* 1st Place Glow (If we want it) */
        .rank-1-glow {
            box-shadow: 0 0 25px -5px rgba(56, 189, 248, 0.4);
            border-color: rgba(56, 189, 248, 0.6);
        }

        /* Finale Grid Cards - Small & Dense */
        .finale-card {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        #announcerStage {
            transition: opacity 0.5s ease;
        }
        #grandFinaleStage {
            opacity: 0;
            transition: opacity 1.5s ease;
            display: none;
        }
        #grandFinaleStage.active {
            display: flex;
        }

        /* Minimal Scrollbar for the records */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <!-- Minimal overlay logo/branding -->
    <div class="w-full flex justify-between p-4 fixed top-0 left-0 z-50 pointer-events-none opacity-50">
       <img src="images/league-logo.svg" class="h-8 w-8" alt="Logo">
       <span class="text-xs uppercase tracking-widest text-slate-500 font-bold">Cotswold League #Official</span>
    </div>

    <!-- STAGE 1: THE ANNOUNCER (Sequential Tiers) -->
    <div id="announcerStage" class="flex-grow flex flex-col items-center justify-center py-16 px-4 max-w-4xl mx-auto w-full relative z-10">
        
        <div class="text-center mb-12 showcase-card revealed delay-0 relative z-20">
            <!-- Dynamic Main Title built by JS -->
            <h2 class="text-sky-500 font-bold uppercase tracking-widest text-sm mb-2"><?php echo htmlspecialchars($title); ?></h2>
            <h1 id="dynamicMainTitle" class="text-6xl md:text-8xl font-extrabold tracking-tight gradient-text mb-4">
                Final
            </h1>
        </div>

        <?php if (!$auto_play): ?>
        <div class="mb-8 z-30 sticky top-4" id="manualControls">
            <button id="nextRevealBtn" class="bg-sky-600 hover:bg-sky-500 text-white px-8 py-3 rounded-full font-bold shadow-lg shadow-sky-500/20 transition-all flex items-center gap-2 group">
                Reveal Next <i data-lucide="chevron-down" class="group-hover:translate-y-1 transition-transform"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Container where JS will stamp out cards -->
        <div id="showcaseContainer" class="w-full flex flex-col gap-4 pb-20 relative z-20"></div>

    </div>


    <!-- STAGE 2: THE GRAND FINALE GRID (3 Column Layout) -->
    <div id="grandFinaleStage" class="w-full flex-col items-center py-12 px-4 max-w-[1600px] mx-auto z-20">
        
        <!-- Big Finale Header completely removed per user request -->

        <!-- 3 Columns (B | A | C) -->
        <div class="flex flex-col md:flex-row w-full gap-4 md:gap-6 justify-center items-start md:pt-[17.5rem]">
            
            <?php 
                // Define the column rendering order
                $columns = [
                    ['tier' => 'B', 'title' => 'B Final'],
                    ['tier' => 'A', 'title' => 'A Final'],
                    ['tier' => 'C', 'title' => 'C Final']
                ];
            ?>

            <?php foreach ($columns as $col): ?>
                <?php 
                    $tier_data = $finals_data[$col['tier']] ?? []; 
                    // Force final grid to always display highest point teams first (Rank 1 at top)
                    usort($tier_data, function($a, $b) {
                        return $a['rank'] <=> $b['rank'];
                    });
                ?>
                <!-- If it's A final, use massive negative margin-top to protrude upward and fit logo above -->
                <div class="w-full md:w-1/3 flex flex-col gap-2 <?php echo $col['tier'] === 'A' ? 'md:-mt-[17.5rem]' : 'mt-4 md:mt-2'; ?>">
                    
                    <?php if ($col['tier'] === 'A'): ?>
                        <div class="text-center mb-1 flex flex-col items-center justify-end">
                            <img src="images/league-logo.svg" alt="League Logo" class="h-32 w-32 mx-auto mb-6 opacity-90 drop-shadow-[0_0_30px_rgba(56,189,248,0.5)]">
                            <div class="bg-slate-800/80 px-4 py-2 rounded-lg border border-slate-700 text-center shadow-lg w-full">
                                <h3 class="text-xl font-black text-amber-500 tracking-wider uppercase"><?php echo $col['title']; ?></h3>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-slate-800/80 px-4 py-2 rounded-lg border border-slate-700 text-center mb-1 shadow-lg">
                            <h3 class="text-lg font-black text-amber-500 tracking-wider uppercase"><?php echo $col['title']; ?></h3>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($tier_data as $row): ?>
                        <div class="finale-card w-full rounded-xl p-2 flex items-center justify-between gap-3 shadow-md hover:border-slate-500 transition-colors">
                            <div class="w-6 text-center shrink-0">
                                <span class="text-lg font-bold text-slate-400">#<?php echo $row['rank']; ?></span>
                            </div>
                            <div class="h-10 w-10 bg-white rounded-lg p-1 flex items-center justify-center shrink-0">
                                <img src="images/Teams/<?php echo htmlspecialchars($row['logo']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="object-contain h-full w-full">
                            </div>
                            <!-- Team Name Visible in this design -->
                            <h4 class="text-base font-bold truncate flex-grow text-white">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </h4>
                            <div class="text-right shrink-0 pr-2">
                                <span class="text-xl font-black text-sky-400">
                                    <?php echo $row['points']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- Background Decoration -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-1/4 -right-1/4 w-1/2 h-1/2 bg-sky-500/10 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-1/4 -left-1/4 w-1/2 h-1/2 bg-sky-800/10 rounded-full blur-3xl opacity-50"></div>
    </div>

    <!-- PASSED DATA FROM PHP TO JS -->
    <script>
        const finalsData = <?php echo json_encode($finals_data); ?>;
        // The order we want to announce them in: C -> B -> A
        const announceOrder = ['C', 'B', 'A'];
        const autoPlay = <?php echo $auto_play ? 'true' : 'false'; ?>;
        const delayBetween = 2500; // ms between teams
        const delayBetweenTiers = 4000; // ms to pause before wiping to next tier
        
        let currentTierIndex = 0;
        let currentTeamIndex = 0;
        let animatedCards = []; // DOM objects for current tier
    </script>

    <script>
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('showcaseContainer');
            const subtitleEl = document.getElementById('dynamicSubtitle');
            const nextBtn = document.getElementById('nextRevealBtn');
            const announcerStage = document.getElementById('announcerStage');
            const grandFinaleStage = document.getElementById('grandFinaleStage');

            function animateValue(obj, start, end, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const easeOut = progress * (2 - progress);
                    obj.innerHTML = Math.floor(easeOut * (end - start) + start);
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    } else {
                        obj.innerHTML = end; 
                    }
                };
                window.requestAnimationFrame(step);
            }

            // Function to generate the exact HTML for a team card
            function generateCardHTML(team) {
                const isFirstPlace = team.rank === 1;
                const borderClass = isFirstPlace ? 'rank-1-glow' : 'border-slate-700/50';
                const bgClass = isFirstPlace ? 'bg-slate-800' : 'card-gradient';
                const rankColor = isFirstPlace ? 'text-sky-400' : 'text-slate-500';
                const glowBg = isFirstPlace ? `<div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-transparent pointer-events-none"></div>` : '';

                return `
                    <div class="showcase-card w-full rounded-2xl p-4 md:p-6 border ${borderClass} ${bgClass} flex items-center gap-4 md:gap-8 justify-between relative overflow-hidden" data-points="${team.points}">
                        ${glowBg}
                        <div class="flex items-center gap-4 md:gap-6 z-10 w-full max-w-full">
                            <div class="w-10 md:w-16 text-center shrink-0">
                                <span class="text-2xl md:text-4xl font-bold ${rankColor}">#${team.rank}</span>
                            </div>
                            <div class="h-16 w-16 md:h-20 md:w-20 bg-white rounded-xl p-1 md:p-2 flex items-center justify-center shrink-0 shadow-md">
                                <img src="images/Teams/${team.logo}" class="object-contain h-full w-full">
                            </div>
                            <h3 class="text-2xl md:text-4xl font-bold truncate tracking-tight text-white">${team.name}</h3>
                            <div class="ml-auto flex items-end flex-col shrink-0 pl-4">
                                <span class="text-xs md:text-sm text-slate-400 uppercase font-semibold tracking-wider">Points</span>
                                <span class="score-display text-4xl md:text-6xl font-black text-sky-400 leading-none" data-target="${team.points}">0</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Function to Setup a Tier
            function setupTier() {
                if (currentTierIndex >= announceOrder.length) {
                    launchGrandFinale();
                    return;
                }

                const currentTierId = announceOrder[currentTierIndex];
                const teams = finalsData[currentTierId];
                
                // Set subtitle
                const mainTitleEl = document.getElementById('dynamicMainTitle');
                if(mainTitleEl) mainTitleEl.innerHTML = `${currentTierId} Final`;

                // Wipe container and insert new cards (hidden)
                container.innerHTML = '';
                
                if (teams && teams.length > 0) {
                    teams.forEach(t => {
                        container.insertAdjacentHTML('beforeend', generateCardHTML(t));
                    });
                }
                
                // Add a completion message for the tier at the bottom
                container.insertAdjacentHTML('beforeend', `
                    <div class="tier-complete showcase-card w-full text-center py-10 mt-4 text-slate-400 hidden flex flex-col items-center">
                        <img src="images/league-logo.svg" alt="League Logo" class="h-24 w-24 mx-auto mb-4 opacity-90 drop-shadow-[0_0_20px_rgba(56,189,248,0.4)]">
                        <h3 class="text-3xl font-extrabold text-white mb-2 tracking-tight">Congratulations all ${currentTierId} Finalists!</h3>
                    </div>
                `);

                lucide.createIcons();

                animatedCards = Array.from(container.querySelectorAll('.showcase-card'));
                currentTeamIndex = 0;
                
                window.scrollTo({ top: 0, behavior: 'instant' });

                // Restart animation loop
                if (autoPlay) {
                    setTimeout(() => revealNextTeam(), 1000);
                } else {
                    if (nextBtn) nextBtn.style.display = 'flex';
                }
            }

            function revealNextTeam() {
                if (currentTeamIndex >= animatedCards.length) {
                    if (nextBtn) nextBtn.style.display = 'none';

                    // We finished this tier.
                    setTimeout(() => {
                        // Fade out the announcer stage gracefully
                        announcerStage.style.opacity = '0';
                        
                        setTimeout(() => {
                            // Move to next tier
                            currentTierIndex++;
                            setupTier();
                            // Fade announcer stage back in
                            announcerStage.style.opacity = '1';
                        }, 500); // match transition time
                    }, delayBetweenTiers);

                    return;
                }

                const card = animatedCards[currentTeamIndex];
                
                // If it's the checkmark card, handle slightly differently
                if (card.classList.contains('tier-complete')) {
                    card.classList.remove('hidden');
                    setTimeout(() => card.classList.add('revealed'), 50);
                    setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'end' }), 100);
                } else {
                    card.classList.add('revealed');
                    setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 50);

                    const scoreDisplay = card.querySelector('.score-display');
                    if (scoreDisplay) {
                        const targetScore = parseInt(scoreDisplay.getAttribute('data-target'), 10);
                        animateValue(scoreDisplay, 0, targetScore, 1500); // 1.5 seconds roll up
                    }
                }

                currentTeamIndex++;

                if (autoPlay) {
                    setTimeout(() => revealNextTeam(), delayBetween);
                }
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => revealNextTeam());
            }

            function launchGrandFinale() {
                // Instantly zero out and hide the announcer stage
                announcerStage.style.display = 'none';
                
                window.scrollTo({ top: 0, behavior: 'instant' });

                // Make Grand Finale display:flex but opacity 0 
                grandFinaleStage.classList.add('active');
                
                // Slight delay to ensure layout is painted, then transition opacity
                setTimeout(() => {
                    grandFinaleStage.style.opacity = '1';
                }, 100);
            }

            // START ENGINE
            setupTier();
            
        });
    </script>
</body>
</html>
