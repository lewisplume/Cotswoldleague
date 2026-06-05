<?php
require_once 'db.php';
$clubs = [];
$c_res = $conn->query("SELECT name FROM clubs ORDER BY name ASC");
if ($c_res) {
    while ($row = $c_res->fetch_assoc()) {
        $clubs[] = htmlspecialchars($row['name']);
    }
}
$clubOptions = '<option value="">[Select Club]</option>';
foreach ($clubs as $c) {
    $clubOptions .= '<option value="' . $c . '">' . $c . '</option>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Announcer Script Generator</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { 
            background: rgba(30, 41, 59, 0.7); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* Print Specific Styling */
        @media print {
            @page { margin: 1cm; }
            html, body { 
                display: block !important; 
                background-color: white !important; 
                color: black !important; 
                font-size: 11pt !important; 
                line-height: 1.3 !important; 
                height: auto !important; 
                min-height: 0 !important; 
                margin: 0 !important; 
                padding: 0 !important; 
            }
            nav, header, footer, .no-print, #setup-form, .glass-panel { display: none !important; }
            
            #generated-script { 
                display: block !important; 
                border: none !important; 
                background-color: white !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                height: auto !important; 
                min-height: 0 !important;
            }
            #script-container { 
                display: block !important;
                padding: 0 !important; 
                margin: 0 !important;
                box-shadow: none !important; 
                width: 100% !important; 
                max-width: 100% !important; 
            }
            
            .script-section { page-break-inside: avoid; margin-bottom: 12px !important; }
            .script-notes { font-style: italic; color: #555 !important; background: #f9f9f9 !important; padding: 4px 8px !important; border-left: 3px solid #333 !important; font-size: 10pt !important; margin-bottom: 8px !important; }
            h2 { color: black !important; font-size: 16pt !important; margin-bottom: 8px !important; padding-bottom: 8px !important; border-bottom: 1px solid #ccc !important; text-align: center !important; }
            h3 { color: black !important; font-size: 12pt !important; margin-bottom: 6px !important; border-bottom: none !important; }
            
            p, li { margin-bottom: 4px !important; margin-top: 0 !important; }
            .fill-blank { border-bottom: 1px solid #333 !important; display: inline-block !important; min-width: 50px !important; }
            
            /* Override Tailwind spaces */
            .space-y-4 > :not([hidden]) ~ :not([hidden]) { margin-top: 8px !important; }
            .space-y-3 > :not([hidden]) ~ :not([hidden]) { margin-top: 6px !important; }
            .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 12px !important; }
            .mt-8 { margin-top: 12px !important; }
            .mb-8 { margin-bottom: 12px !important; }
            .mt-4 { margin-top: 6px !important; }
            .my-4 { margin-top: 6px !important; margin-bottom: 6px !important; }
            .my-3 { margin-top: 4px !important; margin-bottom: 4px !important; }
        }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">
    <?php include 'nav.php'; ?>

    <div class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-8 flex-grow no-print">
        
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <a href="admin.php" class="p-2 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-slate-400"></i>
                </a>
                Script <span class="text-sky-500">Generator</span>
            </h1>
            <div class="text-end">
                <p class="text-xs text-slate-500 uppercase tracking-widest hidden sm:block">Announcer's Guide</p>
            </div>
        </div>

        <div id="setup-form" class="glass-panel p-6 sm:p-8 rounded-3xl shadow-2xl relative overflow-hidden mb-8">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-sky-500 to-emerald-500"></div>
            
            <div class="mb-8">
                <div class="bg-slate-800 w-12 h-12 rounded-xl flex items-center justify-center mb-4 border border-white/10 shadow-inner">
                    <i data-lucide="mic" class="w-6 h-6 text-sky-400"></i>
                </div>
                <h2 class="text-xl font-bold mb-2 text-white">Gala Details</h2>
                <p class="text-slate-400 text-sm">Please fill in the details below to generate your custom printable script for tonight's gala.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Location & Info -->
                <div class="space-y-5">
                    <div>
                        <label for="poolName" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Pool / Leisure Centre</label>
                        <div class="relative">
                            <i data-lucide="map-pin" class="absolute left-4 top-3.5 w-4 h-4 text-slate-500"></i>
                            <input type="text" id="poolName" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-11 pr-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-700" placeholder="e.g., Stratford Park Leisure Centre">
                        </div>
                    </div>

                    <div>
                        <label for="roundNum" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Round Number</label>
                        <div class="relative">
                            <i data-lucide="calendar" class="absolute left-4 top-3.5 w-4 h-4 text-slate-500"></i>
                            <select id="roundNum" onchange="updateFormDisplay()" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-11 pr-4 text-white focus:outline-none focus:border-sky-500 transition-all appearance-none cursor-pointer">
                                <option value="Round 1">Round 1</option>
                                <option value="Round 2">Round 2</option>
                                <option value="Round 3">Round 3</option>
                                <option value="Round 4">Round 4</option>
                                <option value="C Final">C Final (6 Lanes)</option>
                                <option value="B Final">B Final (6 Lanes)</option>
                                <option value="A Final">A Final (8 Lanes)</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-4 h-4 text-slate-500 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <label for="announcerName" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Announcer's Name</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-4 top-3.5 w-4 h-4 text-slate-500"></i>
                            <input type="text" id="announcerName" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-11 pr-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-700" placeholder="e.g., John Smith">
                        </div>
                    </div>

                    <div>
                        <label for="refereeName" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Referee's Name</label>
                        <div class="relative">
                            <i data-lucide="shield-check" class="absolute left-4 top-3.5 w-4 h-4 text-slate-500"></i>
                            <input type="text" id="refereeName" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-11 pr-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-700" placeholder="e.g., Jane Doe">
                        </div>
                    </div>
                </div>

                <!-- Clubs -->
                <div class="space-y-5 bg-slate-900/50 p-5 rounded-2xl border border-white/5 disabled-clubs">
                    
                    <div id="group-host1">
                        <label for="hostClub" class="block text-xs font-bold text-emerald-500 uppercase tracking-wider mb-2">Host Club 1</label>
                        <select id="hostClub" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer">
                            <?php echo $clubOptions; ?>
                        </select>
                    </div>

                    <div id="group-host2" style="display: none;">
                        <label for="hostClub2" class="block text-xs font-bold text-emerald-500 uppercase tracking-wider mb-2">Host Club 2</label>
                        <select id="hostClub2" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer">
                            <?php echo $clubOptions; ?>
                        </select>
                    </div>

                    <hr class="border-white/5 my-4">

                    <div>
                        <label for="visitingClub1" class="block text-xs font-bold text-sky-400 uppercase tracking-wider mb-2">Visiting Club 1</label>
                        <select id="visitingClub1" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer"><?php echo $clubOptions; ?></select>
                    </div>

                    <div>
                        <label for="visitingClub2" class="block text-xs font-bold text-sky-400 uppercase tracking-wider mb-2">Visiting Club 2</label>
                        <select id="visitingClub2" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer"><?php echo $clubOptions; ?></select>
                    </div>

                    <div>
                        <label for="visitingClub3" class="block text-xs font-bold text-sky-400 uppercase tracking-wider mb-2">Visiting Club 3</label>
                        <select id="visitingClub3" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer"><?php echo $clubOptions; ?></select>
                    </div>

                    <div id="group-vis4" style="display: none;">
                        <label for="visitingClub4" class="block text-xs font-bold text-sky-400 uppercase tracking-wider mb-2">Visiting Club 4</label>
                        <select id="visitingClub4" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer"><?php echo $clubOptions; ?></select>
                    </div>

                    <div id="group-vis5" style="display: none;">
                        <label for="visitingClub5" class="block text-xs font-bold text-sky-400 uppercase tracking-wider mb-2">Visiting Club 5</label>
                        <select id="visitingClub5" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer"><?php echo $clubOptions; ?></select>
                    </div>

                    <div id="group-vis6" style="display: none;">
                        <label for="visitingClub6" class="block text-xs font-bold text-sky-400 uppercase tracking-wider mb-2">Visiting Club 6</label>
                        <select id="visitingClub6" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-all cursor-pointer"><?php echo $clubOptions; ?></select>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-white/5">
                <button onclick="generateScript()" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-lg shadow-sky-900/20 flex items-center justify-center gap-2 group">
                    <i data-lucide="sparkles" class="w-5 h-5"></i> Generate Announcer Script
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden by default, displayed when generated -->
    <div id="generated-script" style="display: none;" class="max-w-4xl mx-auto w-full px-4 sm:px-6 py-8 flex-grow">
        <div class="no-print flex items-center justify-between mb-6 glass-panel p-4 rounded-2xl border border-white/5">
            <h2 class="text-xl font-bold text-white flex items-center gap-2"><i data-lucide="file-text" class="w-5 h-5 text-emerald-400"></i> Script Generated Successfully</h2>
            <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-5 rounded-xl transition-colors flex items-center gap-2 shadow-lg shadow-emerald-900/20">
                <i data-lucide="printer" class="w-4 h-4"></i> Print Script
            </button>
        </div>
        
        <!-- The actual printable content -->
        <div class="bg-white text-black p-8 sm:p-12 rounded-lg shadow-lg" id="script-container">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-2 border-b-2 border-slate-200 pb-4">Cotswold Swimming League <span id="year-span"><?php echo $current_season_year; ?></span>: Announcer’s Guide & Script</h2>
            <p class="text-sm text-gray-600 text-center mb-8"><strong>Welcome to the Cotswold League!</strong> As the announcer, you are the voice of the gala. Your role is to keep the event moving, keep the spectators informed, and—most importantly—create a positive, encouraging atmosphere for the children.</p>
            
            <div id="script-content" class="space-y-6"></div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const activeSeasonYear = <?php echo (int)$current_season_year; ?>;
        document.getElementById('year-span').textContent = activeSeasonYear;

        function updateFormDisplay() {
            const round = document.getElementById('roundNum').value;
            const isFinal = round.includes('Final');
            const maxLanes = round === 'A Final' ? 8 : (isFinal ? 6 : 4);
            const hosts = isFinal ? 2 : 1;
            const visitors = round === 'A Final' ? 6 : (isFinal ? 4 : 3);

            document.getElementById('group-host2').style.display = hosts >= 2 ? 'block' : 'none';
            document.getElementById('group-vis4').style.display = visitors >= 4 ? 'block' : 'none';
            document.getElementById('group-vis5').style.display = visitors >= 6 ? 'block' : 'none';
            document.getElementById('group-vis6').style.display = visitors >= 6 ? 'block' : 'none';
        }

        // Call once on load
        document.addEventListener('DOMContentLoaded', updateFormDisplay);

        function generateScript() {
            // Get values
            const pool = document.getElementById('poolName').value || '[Name of Pool]';
            const round = document.getElementById('roundNum').value || '[Round]';
            const announcer = document.getElementById('announcerName').value || '[Your Name]';
            const referee = document.getElementById('refereeName').value || '[Referee Name]';

            let maxLanes = 4;
            if (round === 'A Final') maxLanes = 8;
            else if (round === 'B Final' || round === 'C Final') maxLanes = 6;

            let activeClubs = [];
            const h1 = document.getElementById('hostClub').value;
            if (h1) activeClubs.push(h1);
            if (maxLanes >= 6) {
                const h2 = document.getElementById('hostClub2').value;
                if (h2) activeClubs.push(h2);
            }
            for (let i = 1; i <= 6; i++) {
                if (i <= 3 || (maxLanes >= 6 && i === 4) || (maxLanes === 8 && i >= 5)) {
                    let v = document.getElementById('visitingClub' + i).value;
                    if (v) activeClubs.push(v);
                }
            }
            
            // This is the variable that was missing and causing the bug
            const host = activeClubs[0] || '[Host Club]';

            let clubsLi = '';
            activeClubs.forEach(c => {
                clubsLi += `<li><strong>${c}</strong></li>\n`;
            });
            if (clubsLi === '') clubsLi = '<li><strong>[Clubs list will appear here]</strong></li>';
            
            const numClubsStr = activeClubs.length > 0 ? activeClubs.length.toString() : "[number of]";

            let lanesLi = '';
            for (let i = 1; i <= maxLanes; i++) {
                lanesLi += `<li>Lane ${i}: ___________________________________________</li>\n`;
            }

            let currentPointsHtml = '';
            for (let i = maxLanes; i >= 1; i--) {
                const suffix = i === 1 ? 'st' : i === 2 ? 'nd' : i === 3 ? 'rd' : 'th';
                const prefix = i === 1 ? 'And currently in' : 'In';
                currentPointsHtml += `<p>${prefix} ${i}${suffix} place with _______ points: ________________________________</p>\n`;
            }

            let finalResultsHtml = '';
            for (let i = maxLanes; i >= 1; i--) {
                const suffix = i === 1 ? 'st' : i === 2 ? 'nd' : i === 3 ? 'rd' : 'th';
                finalResultsHtml += `<p>${i}${suffix} Place: ____________________________ with _______ points.</p>\n`;
            }

            // Build HTML Script
            const scriptHTML = `
                <div class="script-section">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 border-b border-gray-200 pb-1">1. Pre-Gala Welcomes</h3>
                    <p class="script-notes mb-3">(Announce 10-15 minutes before warm-up starts)</p>
                    <p class="mb-2">"Good afternoon/evening everyone, and a very warm welcome to <strong>${pool}</strong> for <strong>${round}</strong> of the ${activeSeasonYear} Cotswold Swimming League.</p>
                    <p class="mb-2">We are delighted to host tonight’s gala. I am <strong>${announcer}</strong>, and I’ll be your announcer for the evening. We have ${numClubsStr} fantastic clubs competing today. Please give a warm welcome to:</p>
                    <ul class="list-disc pl-8 my-3 space-y-1">
                        ${clubsLi}
                    </ul>
                    <p class="mb-2">The Cotswold League is all about fun, sportsmanship, and giving our younger and less experienced swimmers a chance to shine. Let’s make sure we cheer loudly for every single swimmer in the water tonight!"</p>
                </div>

                <div class="script-section mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 border-b border-gray-200 pb-1">2. Warm-Up Information</h3>
                    <p class="script-notes mb-3">(Fill in the blanks below after the random lane draw is conducted on the night!)</p>
                    <p class="mb-2">"We are about to begin the warm-up. Following the random lane draw conducted earlier, the lane assignments for the evening are as follows:</p>
                    <ul class="list-none pl-4 my-3 space-y-3 font-mono">
                        ${lanesLi}
                    </ul>
                    <p class="mb-2">Warm-up will last for 30 minutes. Coaches, please ensure your swimmers are aware that diving is only permitted in designated sprint lanes. Over to the coaches for the warm-up."</p>
                    <p class="script-notes mt-2">Coaches control their own lanes during warm-up, nothing further to do for 30 minutes.</p>
                </div>

                <div class="script-section mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 border-b border-gray-200 pb-1">3. Mandatory Safety & Photography Notices</h3>
                    <p class="script-notes mb-3">(Announce immediately before the first event)</p>
                    <p class="mb-2"><strong>Safety Notice:</strong><br>
                    "A few quick safety reminders from the pool management: <em>[Read the specific pool rules provided by the leisure centre staff on the night]</em>. In the unlikely event of an emergency, please follow the instructions of the lifeguards and leisure centre staff. The fire exits are located [Point them out]."</p>
                    <p class="mb-2 mt-4"><strong>Swim England Photography Policy:</strong><br>
                    "In line with Swim England’s Wavepower policy, we would like to remind all spectators that photography and video recording are permitted for personal use only. Please ensure you are only focusing on your own child. If you have any concerns regarding photography, please speak to the Gala Refreshments/Front Desk team or the Lead Official. If you are posting to social media, please remember to celebrate the efforts of all our swimmers!"</p>
                </div>

                <div class="script-section mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 border-b border-gray-200 pb-1">4. The Racing Script</h3>
                    <p class="script-notes mb-3">Format to repeat for each race:</p>
                    <p class="mb-2 font-mono bg-gray-50 p-3 border border-gray-200 rounded">"Event <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;</span>, we have the <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Age Group), <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Gender), <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Distance), <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Stroke), over to you Mr/Madam Referee."</p>
                    <p class="mb-2 text-gray-600 text-sm"><em>Example: "Event 1, we have the Girls 15 & Under 4 by 1 Individual Medley. Over to you, Referee."</em></p>
                    <p class="script-notes mt-2">Check with the referee about when to ask swimmers to clear the pool.</p>
                </div>

                <div class="script-section mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 border-b border-gray-200 pb-1">5. Scoring Updates</h3>
                    <p class="script-notes mb-3">(Announce every 10 events. Write scores in below during the gala!)</p>
                    <p class="mb-2">"That brings us to the end of Event 10 / 20 / 30 / 40. Here are the current points standings:</p>
                    <div class="pl-4 my-4 space-y-4 font-mono">
${currentPointsHtml}                    </div>
                    <p class="mb-2">Keep up the great swimming, everyone!"</p>
                </div>

                <div class="script-section mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 border-b border-gray-200 pb-1">6. Raffle Announcement</h3>
                    <p class="script-notes mb-3">(Best announced around Event 30, typically before or after the 25m races)</p>
                    <p class="mb-2">"While our officials check the latest scores, it’s time to announce the winners of our raffle! Thank you to everyone who purchased a ticket; your support helps <strong>${host}</strong> continue to provide great opportunities for our young swimmers.</p>
                    <p class="mb-2 mt-2">The winning numbers are...</p>
                    <div class="pl-4 my-4 space-y-4 font-mono">
                        <p>Ticket: ___________ - Prize: ___________________________________________</p>
                        <p>Ticket: ___________ - Prize: ___________________________________________</p>
                        <p>Ticket: ___________ - Prize: ___________________________________________</p>
                    </div>
                    <p class="mb-2">Please come to the front desk at the end of the gala to collect your prizes!"</p>
                </div>

                <div class="script-section mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 border-b border-gray-200 pb-1">7. Final Results & Closing</h3>
                    <p class="mb-2">"That concludes the racing for tonight! A huge well done to every swimmer. Before we announce the final results, a few thank yous.</p>
                    <p class="mb-2">Thank you to our Referee, <strong>${referee}</strong>, and all the officials and timekeepers who volunteered their time. Thank you to the staff here at <strong>${pool}</strong>, and of course, to all the parents and coaches for your support.</p>
                    <p class="mb-2 mt-4">And now, the final results for <strong>${round}</strong> of the ${activeSeasonYear} Cotswold League:</p>
                    <div class="pl-4 my-4 space-y-4 font-mono">
${finalResultsHtml}                    </div>
                    <p class="mb-2 mt-4">Congratulations to ________________________! Safe travels home everyone, and we look forward to seeing you next time!"</p>
                </div>
            `;

            document.getElementById('script-content').innerHTML = scriptHTML;
            document.getElementById('generated-script').style.display = 'block';
            
            // Scroll down to the script smoothly
            document.getElementById('generated-script').scrollIntoView({ behavior: 'smooth' });
        }
    </script>

</body>
</html>
