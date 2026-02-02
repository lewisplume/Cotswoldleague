<?php
include 'db.php';
// Fetch Round 1 points for Spectators page
$r1_points = [];
$sql = "SELECT c.name, r.round_1 FROM results r JOIN clubs c ON r.club_id = c.id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $r1_points[$row['name']] = $row['round_1'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Spectators</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .card-gradient { background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%); }
        .glass-panel { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="text-white font-sans min-h-screen">

    <?php include 'nav.php'; ?>

    <!-- HEADER -->
    <div class="py-12 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            Spectator <span class="text-sky-500">Information</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto">
            Everything you need to know for the 2026 Cotswold League rounds.
        </p>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <div class="lg:col-span-2 space-y-6">
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="info" class="text-sky-500"></i> Essential Gala Info
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2">Admission Pricing</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Host teams set their own entry prices for preliminary rounds. Most clubs charge around £5.00. Please check with your club representative for venue-specific pricing.
                        </p>
                    </div>
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2">Raffles & Fundraising</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Many host clubs organize raffles during the rounds. These are optional, and proceeds support the hosting club's fundraising efforts.
                        </p>
                    </div>
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2">Warm-Up Times</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Warm-ups typically last 30 minutes. Please refer to your club's coach or TM for the specific arrival time for your swimmer.
                        </p>
                    </div>
                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-sky-400 mb-2">Photography & Conduct</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            The League adopts the Swim England Child Protection Policy. Please follow venue-specific rules regarding photography and video recording.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="download" class="text-sky-500"></i> Downloads
                </h2>
                <div class="glass-panel rounded-2xl overflow-hidden">
                    <a href="https://drive.google.com/file/d/15OL3Wlb26Tiyqic2AJjeMAlh0e_LVwT8/view?usp=drive_link" target="_blank" class="flex items-center p-5 hover:bg-white/5 transition-colors group">
                        <div class="bg-sky-500/10 p-3 rounded-xl mr-4 group-hover:bg-sky-500/20">
                            <i data-lucide="file-text" class="text-sky-500 w-6 h-6"></i>
                        </div>
                        <div class="flex-grow">
                            <p class="font-bold text-white">Spectator Programme</p>
                            <p class="text-xs text-slate-500">Full Season Event List</p>
                        </div>
                        <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                <div>
                    <h2 class="text-3xl font-bold">Season <span class="text-sky-500">Draw</span></h2>
                    <p class="text-slate-400">All preliminary rounds for the 2026 season.</p>
                </div>
                <div class="flex gap-2 bg-slate-800/50 p-1 rounded-xl border border-slate-700">
                    <button onclick="filterDraw(1)" id="btnR1" class="px-4 py-2 rounded-lg text-sm font-bold transition-all bg-sky-600 text-white">R1</button>
                    <button onclick="filterDraw(2)" id="btnR2" class="px-4 py-2 rounded-lg text-sm font-bold transition-all text-slate-400 hover:text-white">R2</button>
                    <button onclick="filterDraw(3)" id="btnR3" class="px-4 py-2 rounded-lg text-sm font-bold transition-all text-slate-400 hover:text-white">R3</button>
                    <button onclick="filterDraw(4)" id="btnR4" class="px-4 py-2 rounded-lg text-sm font-bold transition-all text-slate-400 hover:text-white">R4</button>
                </div>
            </div>
            <div id="drawContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            </div>
            
            <!-- LIVE RESULTS VIEWER (Full Width) -->
            <div id="liveViewer" class="hidden mt-8 glass-panel overflow-hidden rounded-2xl border border-sky-500/30 shadow-[0_0_50px_rgba(14,165,233,0.15)] transition-all duration-500"></div>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        const round1Results = <?php echo json_encode($r1_points); ?>;
        lucide.createIcons();

        // Nav JS logic (same as other files)
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

        const drawData = [
            { 
                round: 1, 
                date: "31/01/2026", 
                galas: [
                    { host: "Cwmbran", details: "Halo Pontypool Active Living Centre (NP4 8AT). Doors 5:30PM. Cash Only. Free Parking.", teams: ["Cwmbran", "Yeovil", "Dursley", "Monnow SC"] },
                    { host: "Backwell", details: "Backwell Leisure Ctr (BS48 3PB). Doors 5pm, W/U 5:15pm. Spectators £3 (Cash).", teams: ["Backwell", "Brockworth", "Bridgwater", "Forest of Dean"] },
                    { host: "Corsham", details: "Trowbridge Sports Centre, Frome Road, Trowbridge. Wilts. BA14 0DN. Doors Open 2:15pm. Spectators: £3 (Cash&Card). Free Parking.", teams: ["Corsham", "Swindon ASC", "Burnham-On-Sea", "Bristol North"] },
                    { host: "Bath Dolphin", details: "Bath Leisure Centre (BA2 4ET). Doors 4:30pm, W/U 5pm. Spectators £3 (Cash). Paid parking available.", teams: ["Bath Dolphin", "Clevedon", "Wells", "Newport"] },
                    { host: "COB (City of Bristol)", details: "Hengrove Park LC (BS14 0DE). Doors 2:15pm. Spectators £3 (Cash/Card). Free parking (3hrs) - Register car at reception.", teams: ["COB (City of Bristol)", "Academy Swim Team", "Southwold SC", "Severnside Tritons"] }
                ]
            },
            { 
                round: 2, 
                date: "14/02/2026", 
                galas: [
                    { host: "Yeovil", details: "Sherbourne Sports Centre (DT9 3QN). Doors 5pm, W/U 5:30pm. Spectators £3 (Cash).", teams: ["Yeovil", "Bath Dolphin", "Bridgwater", "Bristol North"] },
                    { host: "Brockworth", details: "Leisure at Cheltenham (GL50 4RN). Doors 17:45.", teams: ["Brockworth", "COB (City of Bristol)", "Burnham-On-Sea", "Newport"] },
                    { host: "Swindon ASC", details: "Health Hydro (SN1 5JA). Doors 1:15pm, W/U 1:30pm. Spectators £3. Parking nearby.", teams: ["Swindon ASC", "Cwmbran", "Wells", "Severnside Tritons"] },
                    { host: "Clevedon", details: "Hutton Moor LC. Doors 18:15. Card preferred. Free parking (get permit from reception).", teams: ["Clevedon", "Backwell", "Southwold SC", "Monnow SC"] },
                    { host: "Academy Swim Team", details: "Burnham Swim & Sports Academy (Berrow Rd). Doors Open 3PM. Cash & Card accepted. Paid council car park on site.", teams: ["Academy Swim Team", "Corsham", "Dursley", "Forest of Dean"], embedUrl: "https://1drv.ms/x/c/7c197ed7ec71ffca/IQS9Tesj4d9aQpR_yNS7S-xEAWiRlEIXnmRg-k9zpSfUlIU?em=2&wdAllowInteractivity=True&Item='NO-TEAMS%2004'!A1%3AL116&wdHideGridlines=True&wdInConfigurator=True&wdInConfigurator=True&edaebf=rslc0" }
                ]
            },
            { 
                round: 3, 
                date: "07/03/2026", 
                galas: [
                    { host: "Dursley", details: "Keynsham Leisure Centre, Temple Street, BS31 1HE. Doors open 12.30pm", teams: ["Dursley", "COB (City of Bristol)", "Clevedon", "Bristol North"] },
                    { host: "Bridgwater", details: "Trinity Sports Centre, Bridgwater. 5PM Doors Open", teams: ["Bridgwater", "Cwmbran", "Academy Swim Team", "Newport"] },
                    { host: "Burnham-On-Sea", details: "Millfield School (BA16 0ST). Doors 6pm. Card preferred. Free parking.", teams: ["Burnham-On-Sea", "Backwell", "Yeovil", "Severnside Tritons"] },
                    { host: "Wells", details: "Millfield School (BA16 0ST). Doors 6pm. Card preferred. Free parking.", teams: ["Wells", "Corsham", "Brockworth", "Monnow SC"] },
                    { host: "Southwold SC", details: "Yate Leisure Centre (BS37 4DQ). Doors 6pm. Rear car park free from 6pm.", teams: ["Southwold SC", "Bath Dolphin", "Swindon ASC", "Forest of Dean"] }
                ]
            },
            { 
                round: 4, 
                date: "28/03/2026", 
                galas: [
                    { host: "Monnow SC", details: "Newport Regional Pool (NP19 4RA). Doors 16:00. Cash Only. Free parking.", teams: ["Monnow SC", "Bath Dolphin", "Academy Swim Team", "Burnham-On-Sea"] },
                    { host: "Forest of Dean", details: "GL1 Leisure Centre (GL1 1DT). Doors open 17.00.", teams: ["Forest of Dean", "COB (City of Bristol)", "Yeovil", "Wells"] },
                    { host: "Bristol North", details: "Keynsham Leisure Centre, Temple Street, BS31 1HE. 12.30pm-15.30pm", teams: ["Bristol North", "Cwmbran", "Brockworth", "Southwold SC"] },
                    { host: "Newport", details: "Newport Regional Pool (NP19 4RA). Doors 16:00. Cash Only. Free parking.", teams: ["Newport", "Backwell", "Swindon ASC", "Dursley"] },
                    { host: "Severnside Tritons", details: "GL1 Leisure Centre (GL1 1DT). Doors open 17.00", teams: ["Severnside Tritons", "Corsham", "Clevedon", "Bridgwater"] }
                ]
            }
        ];

        let currentActiveGala = null;
        let liveRefreshInterval = null;

        function filterDraw(roundNum) {
            const container = document.getElementById('drawContainer');
            
            // Update buttons
            for (let i = 1; i <= 4; i++) {
                const btn = document.getElementById(`btnR${i}`);
                if (i === roundNum) {
                    btn.classList.add('bg-sky-600', 'text-white');
                    btn.classList.remove('text-slate-400');
                } else {
                    btn.classList.remove('bg-sky-600', 'text-white');
                    btn.classList.add('text-slate-400');
                }
            }

            const round = drawData.find(r => r.round === roundNum);
            
            // Generate all cards at once
            container.innerHTML = round.galas.map((gala, index) => `
                <div class="glass-panel rounded-2xl overflow-hidden border border-white/5 hover:border-sky-500/30 transition-all group">
                    <div class="bg-sky-500/10 px-5 py-3 border-b border-white/5 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-tighter text-sky-400">Host Club</span>
                            ${roundNum === 1 ? '<span class="bg-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider border border-emerald-500/30">Completed</span>' : ''}
                        </div>
                        <span class="text-xs text-slate-500 font-medium">${round.date}</span>
                    </div>
                    <div class="p-5">
                        <h3 class="text-xl font-bold mb-4 group-hover:text-sky-400 transition-colors">${gala.host}</h3>
                        <div class="space-y-2 mb-4">
                            ${gala.teams.map(team => {
                                const points = (roundNum === 1 && round1Results && round1Results[team] !== undefined) ? round1Results[team] : null;
                                return `
                                <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                    <div class="flex items-center gap-3 text-sm">
                                        <div class="w-1.5 h-1.5 rounded-full ${team === gala.host ? 'bg-sky-500' : 'bg-slate-600'}"></div>
                                        <span class="${team === gala.host ? 'text-white font-bold' : 'text-slate-400'}">${team}</span>
                                        ${team === gala.host ? '<span class="text-[10px] bg-sky-500/20 text-sky-400 px-2 rounded-full font-black uppercase">Host</span>' : ''}
                                    </div>
                                    ${points !== null ? `<span class="text-sm font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded">${points} pts</span>` : ''}
                                </div>
                            `}).join('')}
                        </div>
                        
                        <!-- VENUE DETAILS -->
                        <div class="mt-4 pt-3 border-t border-white/10">
                            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-1 flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3 h-3"></i> Venue Info
                            </p>
                            <p class="text-xs text-slate-300 leading-relaxed">${gala.details}</p>
                        </div>

                        ${gala.embedUrl ? `
                            <div class="mt-4 pt-4 border-t border-white/5">
                                <button onclick="toggleLive(${roundNum}, ${index})" class="w-full py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-xs font-bold uppercase rounded-lg transition-all flex items-center justify-center gap-2 border border-red-500/20 hover:border-red-500/50">
                                    <i data-lucide="radio" class="w-4 h-4 animate-pulse"></i> <span>Live Results</span>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');

            lucide.createIcons();
            
            // Close live viewer when switching rounds
            closeLive();
        }

        function toggleLive(roundNum, galaIndex) {
            const viewer = document.getElementById('liveViewer');
            const round = drawData.find(r => r.round === roundNum);
            const gala = round.galas[galaIndex];
            const galaId = `${roundNum}-${galaIndex}`;

            // If clicking the same active button, close it
            if (currentActiveGala === galaId && !viewer.classList.contains('hidden')) {
                closeLive();
                return;
            }

            currentActiveGala = galaId;
            viewer.classList.remove('hidden');
            
            // Inject content
            viewer.innerHTML = `
                <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-white/5">
                    <div class="flex items-center gap-4">
                        <div class="bg-red-500/20 p-2 rounded-lg border border-red-500/30">
                            <i data-lucide="radio" class="w-6 h-6 text-red-500 animate-pulse"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white leading-none">Live Results</h3>
                            <p class="text-xs text-sky-400 font-bold uppercase tracking-wider mt-1">${gala.host} Gala • Round ${roundNum}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 flex items-center gap-1"><i data-lucide="refresh-cw" class="w-3 h-3"></i> Auto-refreshing every 60s</p>
                        </div>
                    </div>
                    <button onclick="closeLive()" class="group bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white p-2 rounded-lg transition-all border border-slate-700 hover:border-slate-500">
                        <span class="sr-only">Close</span>
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="bg-white w-full h-[800px] md:h-[900px] relative">
                     <iframe id="live-iframe" width="100%" height="100%" frameborder="0" scrolling="no" src="${gala.embedUrl}" class="absolute inset-0"></iframe>
                </div>
            `;
            
            lucide.createIcons();

            // Set up auto-refresh
            if (liveRefreshInterval) clearInterval(liveRefreshInterval);
            liveRefreshInterval = setInterval(() => {
                const iframe = document.getElementById('live-iframe');
                if (iframe) {
                    iframe.src = iframe.src;
                }
            }, 60000); // 60 seconds
            
            // Smooth scroll to the viewer with a slight delay to ensure rendering
            setTimeout(() => {
                viewer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 50);
        }

        function closeLive() {
             const viewer = document.getElementById('liveViewer');
             if (viewer) {
                viewer.classList.add('hidden');
                viewer.innerHTML = '';
             }
             currentActiveGala = null;
             if (liveRefreshInterval) {
                 clearInterval(liveRefreshInterval);
                 liveRefreshInterval = null;
             }
        }

        filterDraw(1);
    </script>
</body>
</html>