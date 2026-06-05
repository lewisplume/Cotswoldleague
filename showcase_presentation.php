<!-- PRESENTATION VIEW -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Showcase</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .gradient-text {
            background: linear-gradient(135deg, #38bdf8 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-gradient { background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%); }
        
        .showcase-card {
            opacity: 0;
            transform: translateY(30px) scale(0.98);
            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .showcase-card.revealed {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .rank-1-glow {
            box-shadow: 0 0 25px -5px rgba(56, 189, 248, 0.4);
            border-color: rgba(56, 189, 248, 0.6);
        }

        #showcaseContainer {
            transition: opacity 0.6s ease;
        }
        
        .final-grid-card {
            width: calc(50% - 0.5rem) !important;
            padding: 0.5rem 1rem !important;
        }
        @media (min-width: 768px) {
            .final-grid-card {
                width: calc(25% - 0.75rem) !important;
            }
        }
        @media (min-width: 1024px) {
            .final-grid-card {
                width: calc(20% - 1rem) !important;
            }
        }
        .final-grid-card h3 {
            display: none !important;
        }
        .final-grid-card .uppercase {
            display: none !important;
        }
        .final-grid-card .score-display {
            font-size: 1.5rem !important;
        }
        .final-grid-card .bg-white {
            width: 3rem !important;
            height: 3rem !important;
            padding: 0.25rem !important;
        }
        .final-grid-card .text-4xl, .final-grid-card .text-2xl {
            font-size: 1.25rem !important;
        }
        .final-grid-card .w-10, .final-grid-card .md\:w-16 {
            width: auto !important;
            margin-right: 0.25rem !important;
        }
        .final-grid-card > div {
            gap: 0.5rem !important;
            justify-content: space-between !important;
        }
        .final-grid-card .ml-auto {
            margin-left: 0 !important;
            padding-left: 0 !important;
        }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <!-- Extremely subtle minimal nav for recording, or they can embed -->
    <div class="w-full flex justify-between p-4 absolute top-0 left-0 z-50 pointer-events-none opacity-50">
       <img src="images/league-logo.svg" class="h-8 w-8" alt="Logo">
       <span class="text-xs uppercase tracking-widest text-slate-500 font-bold">Cotswold League #Official</span>
    </div>

    <!-- Main Content wrapper -->
    <div class="flex-grow flex flex-col items-center justify-center py-16 px-4 max-w-4xl mx-auto w-full relative z-10">
        
        <!-- Header Section -->
        <div class="text-center mb-12 showcase-card revealed delay-0 relative z-20">
            <h2 class="text-sky-500 font-bold uppercase tracking-widest text-sm mb-2"><?php echo htmlspecialchars($subtitle); ?></h2>
            <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight gradient-text mb-4">
                <?php echo htmlspecialchars($title); ?>
            </h1>
        </div>

        <?php if (!$auto_play): ?>
        <div class="mb-8 z-30 sticky top-4">
            <button id="nextRevealBtn" class="bg-sky-600 hover:bg-sky-500 text-white px-8 py-3 rounded-full font-bold shadow-lg shadow-sky-500/20 transition-all flex items-center gap-2 group">
                Reveal Next <i data-lucide="chevron-down" class="group-hover:translate-y-1 transition-transform"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Results Container -->
        <div id="showcaseContainer" class="w-full flex flex-col gap-4 pb-20 relative z-20">
            <?php foreach ($display_results as $index => $row): ?>
                <?php 
                    $isFirstPlace = $row['rank'] === 1;
                    $borderClass = $isFirstPlace ? 'rank-1-glow' : 'border-slate-700/50 hover:border-slate-600';
                    $bgClass = $isFirstPlace ? 'bg-slate-800' : 'card-gradient';
                ?>
                <div class="showcase-card w-full rounded-2xl p-4 md:p-6 border <?php echo $borderClass; ?> <?php echo $bgClass; ?> flex items-center gap-4 md:gap-8 justify-between relative overflow-hidden" 
                     data-points="<?php echo $row['points']; ?>">
                    
                    <?php if ($isFirstPlace): ?>
                        <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-transparent pointer-events-none"></div>
                    <?php endif; ?>

                    <div class="flex items-center gap-4 md:gap-6 z-10 w-full max-w-full">
                        <div class="w-10 md:w-16 text-center shrink-0">
                            <span class="text-2xl md:text-4xl font-bold <?php echo $isFirstPlace ? 'text-sky-400' : 'text-slate-500'; ?>">
                                #<?php echo $row['rank']; ?>
                            </span>
                        </div>
                        
                        <div class="h-16 w-16 md:h-20 md:w-20 bg-white rounded-xl p-1 md:p-2 flex items-center justify-center shrink-0 shadow-md">
                            <img src="images/Teams/<?php echo htmlspecialchars($row['logo']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="object-contain h-full w-full">
                        </div>
                        
                        <h3 class="text-2xl md:text-4xl font-bold truncate tracking-tight">
                            <?php echo htmlspecialchars($row['name']); ?>
                        </h3>

                        <div class="ml-auto flex items-end flex-col shrink-0 pl-4">
                            <span class="text-xs md:text-sm text-slate-400 uppercase font-semibold tracking-wider">Points</span>
                            <span class="score-display text-4xl md:text-6xl font-black text-sky-400 leading-none" data-target="<?php echo $row['points']; ?>">
                                0
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div id="endMessage" class="showcase-card w-full text-center py-12 mt-8 hidden flex justify-center">
                <img src="images/league-logo.svg" alt="Cotswold League Logo" class="h-32 w-32 mx-auto opacity-90 drop-shadow-[0_0_25px_rgba(56,189,248,0.4)]">
            </div>
        </div>
    </div>

    <!-- Background Decoration -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-1/4 -right-1/4 w-1/2 h-1/2 bg-sky-500/10 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-1/4 -left-1/4 w-1/2 h-1/2 bg-sky-800/10 rounded-full blur-3xl opacity-50"></div>
    </div>

    <script>
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', () => {
            const config = {
                autoPlay: <?php echo $auto_play ? 'true' : 'false'; ?>,
                delayBetween: 2500
            };

            const cards = Array.from(document.querySelectorAll('.showcase-card:not(#endMessage):not(.delay-0)'));
            const endMessage = document.getElementById('endMessage');
            let currentIndex = 0;

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

            function revealCard(index) {
                if (index >= cards.length) {
                    endMessage.classList.remove('hidden');
                    setTimeout(() => {
                        endMessage.classList.add('revealed');
                        endMessage.scrollIntoView({ behavior: 'smooth', block: 'end' });
                    }, 100);
                    const btn = document.getElementById('nextRevealBtn');
                    if (btn) btn.style.display = 'none';
                    
                    // Reform into neat grid after suspense delay
                    setTimeout(() => {
                        const container = document.getElementById('showcaseContainer');
                        const wrapperElement = document.querySelector('.max-w-4xl');
                        
                        container.style.opacity = '0';
                        
                        setTimeout(() => {
                            if(wrapperElement) wrapperElement.classList.replace('max-w-4xl', 'max-w-7xl');
                            container.classList.remove('flex-col');
                            container.classList.add('flex-row', 'flex-wrap', 'justify-center');
                            cards.forEach(c => c.classList.add('final-grid-card'));
                            
                            window.scrollTo({ top: 0, behavior: 'instant' });
                            container.style.opacity = '1';
                        }, 600);
                    }, 2000);
                    
                    return;
                }

                const card = cards[index];
                card.classList.add('revealed');
                
                // Smoothly scroll down so the new card is centered
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 50);
                
                const scoreDisplay = card.querySelector('.score-display');
                if (scoreDisplay) {
                    const targetScore = parseInt(scoreDisplay.getAttribute('data-target'), 10);
                    animateValue(scoreDisplay, 0, targetScore, 1500);
                }

                currentIndex++;

                if (config.autoPlay) {
                    setTimeout(() => revealCard(currentIndex), config.delayBetween);
                }
            }

            if (config.autoPlay) {
                setTimeout(() => revealCard(0), 1000);
            } else {
                const nextBtn = document.getElementById('nextRevealBtn');
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => revealCard(currentIndex));
                }
            }
        });
    </script>
</body>
</html>
