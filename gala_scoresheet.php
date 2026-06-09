<?php
require_once __DIR__ . '/security_headers.php';
cotswold_secure_session_start();
include 'db.php';

// Auth Check
$is_super_admin = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;
$is_club_logged_in = isset($_SESSION['club_logged_in']) && $_SESSION['club_logged_in'] === true;
$current_club_id = $_SESSION['club_id'] ?? 0;

if (!$is_super_admin && !$is_club_logged_in) {
    header("Location: teamportal.php");
    exit;
}

$scoresheet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$venue_detail_id = isset($_GET['venue_id']) ? intval($_GET['venue_id']) : 0;
$is_sandbox = isset($_GET['sandbox']) && $_GET['sandbox'] === '1';

// Need at least one identifier to load or create
if (!$scoresheet_id && !$venue_detail_id && !$is_sandbox) {
    die("Invalid scoresheet reference.");
}

// Fetch all clubs for the substitution dropdown
$all_clubs = [];
$res_clubs = $conn->query("SELECT id, name FROM clubs ORDER BY name ASC");
while ($row = $res_clubs->fetch_assoc()) {
    $all_clubs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gala Scoresheet | Cotswold League</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <!-- Include the scoring engine -->
    <script src="gala_scoresheet.js?v=20260504-deadheat"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { 
            background: rgba(30, 41, 59, 0.7); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
        }
        .time-input {
            font-family: monospace;
            text-align: center;
            letter-spacing: 1px;
        }
        .dq-btn.active {
            background-color: #ef4444 !important;
            color: white !important;
            border-color: #ef4444 !important;
        }
        .status-too-fast { background-color: rgba(234, 179, 8, 0.2); border: 1px solid #eab308; }
        .status-dq { background-color: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; }
        
        /* Layout for scoresheet matrix */
        .scoresheet-grid {
            display: grid;
            gap: 1px;
            background-color: rgba(255,255,255,0.1);
        }
        .cell {
            background-color: #0f172a;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header-cell {
            background-color: #1e293b;
            font-weight: bold;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #94a3b8;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col overflow-x-hidden">

    <!-- NAVBAR -->
    <?php include 'nav.php'; ?>

    <!-- INSTALL PWA PROMPT -->
    <div id="install-prompt" class="hidden glass-panel mx-4 sm:mx-6 xl:mx-auto max-w-[1600px] mt-4 p-4 rounded-xl border border-amber-400/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-amber-500/10">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 bg-amber-400/20 rounded-full flex items-center justify-center shrink-0 border border-amber-400/30">
                <i data-lucide="download-cloud" class="w-5 h-5 text-amber-300"></i>
            </div>
            <div>
                <h3 class="text-base font-black text-white">No internet at the venue?</h3>
                <p class="text-sm text-amber-50/90 leading-relaxed" id="install-desc">Click <strong class="text-white">Install App</strong> before gala day so this scoresheet can reopen and keep working offline.</p>
                <p class="text-xs text-slate-300 mt-1">Open this page once while online first so the gala data is saved on this device.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button id="btn-install" class="bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-black py-2.5 px-5 rounded-lg transition-colors hidden shadow-lg shadow-amber-900/20 whitespace-nowrap">Install App</button>
            <button onclick="document.getElementById('install-prompt').style.display='none'" class="p-2 text-slate-400 hover:text-white rounded-lg transition-colors bg-slate-800/50 hover:bg-slate-700/50">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <div class="flex-grow flex flex-col p-4 sm:p-6 w-full max-w-[1600px] mx-auto relative">

        <!-- HEADER -->
        <div class="glass-panel p-4 sm:p-6 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 sticky top-4 z-50">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <i data-lucide="calculator" class="w-6 h-6 text-sky-400"></i>
                    <span id="gala-title">Loading Gala...</span>
                </h1>
                <p id="gala-subtitle" class="text-slate-400 text-sm mt-1">Initializing database connection...</p>
            </div>
            <div class="flex items-center gap-4">
                <div id="sync-status" class="flex items-center gap-2 text-xs font-bold px-3 py-1.5 rounded-full bg-slate-800 border border-slate-700 text-slate-400">
                    <div class="w-2 h-2 rounded-full bg-slate-500 animate-pulse"></div>
                    Connecting...
                </div>
                <button type="button" onclick="openScoresheetHelp()" class="bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 px-4 py-2 rounded-lg font-bold text-sm transition-all shadow-lg flex items-center gap-2">
                    <i data-lucide="circle-help" class="w-4 h-4"></i> Help
                </button>
                <button id="btn-edit-setup" class="hidden bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-bold text-sm transition-all shadow-lg flex items-center gap-2">
                    <i data-lucide="settings" class="w-4 h-4"></i> Edit Setup
                </button>
                <button id="btn-live-public" class="hidden bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white border border-red-500/30 px-4 py-2 rounded-lg font-bold text-sm transition-all shadow-lg flex items-center gap-2">
                    <i data-lucide="radio" class="w-4 h-4"></i> Publish Live
                </button>
                <button id="btn-submit-league" class="hidden bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2 rounded-lg font-bold text-sm transition-all shadow-lg flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Submit to League
                </button>
            </div>
        </div>

        <div id="submission-sync-notice" class="hidden mb-6 rounded-xl border px-4 py-3 text-sm font-bold flex items-center gap-3"></div>

        <!-- STAGE 0: SANDBOX INITIALIZATION -->
        <?php if ($is_sandbox && !$scoresheet_id): ?>
        <div id="stage-sandbox-init" class="glass-panel p-8 sm:p-12 rounded-3xl max-w-2xl mx-auto w-full mt-10 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-amber-500/20 text-amber-400 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-amber-500/30 shadow-lg rotate-3">
                    <i data-lucide="test-tube" class="w-10 h-10"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-3">Testing Sandbox</h2>
                <p class="text-slate-400">Configure a temporary gala to test the scoring system. Results created here will <span class="text-amber-400 font-bold italic">not</span> affect league tables.</p>
            </div>

            <form id="sandbox-form" class="space-y-6">
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-3">1. Select Host Team</label>
                    <select id="sandbox-host" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition-all">
                        <option value="">- Select Host -</option>
                        <?php foreach($all_clubs as $club): ?>
                            <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-3">2. Select Participating Teams</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="sandbox-teams-list">
                        <?php foreach($all_clubs as $club): ?>
                            <label class="flex items-center gap-3 p-3 bg-slate-900/50 border border-slate-800 rounded-xl cursor-pointer hover:bg-slate-800 transition-colors">
                                <input type="checkbox" name="sandbox_teams[]" value="<?php echo $club['id']; ?>" class="w-4 h-4 rounded border-slate-700 text-amber-500 focus:ring-amber-500 focus:ring-offset-slate-900">
                                <span class="text-sm text-slate-300"><?php echo htmlspecialchars($club['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" class="flex-grow bg-amber-600 hover:bg-amber-500 text-white font-black py-4 rounded-xl transition-all shadow-lg flex items-center justify-center gap-3">
                        <i data-lucide="play-circle" class="w-5 h-5"></i> START NEW SESSION
                    </button>
                    <div id="resume-container" class="hidden">
                        <button type="button" id="btn-resume-sandbox" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-700 text-white font-bold py-4 px-6 rounded-xl border border-slate-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="history" class="w-5 h-5 text-sky-400"></i> RESUME PREVIOUS
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- STAGE 1: LANE ASSIGNMENT (SETUP) -->
        <div id="stage-setup" class="hidden glass-panel p-6 sm:p-10 rounded-2xl max-w-3xl mx-auto w-full mt-10">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-sky-500/20 text-sky-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-sky-500/30">
                    <i data-lucide="git-merge" class="w-8 h-8"></i>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">Gala Setup: Lane Assignments</h2>
                <p class="text-slate-400 text-sm">Assign each team to their physical pool lane. This determines the column order on your scoresheet.</p>
            </div>

            <div id="lane-assignment-container" class="space-y-4 mb-8">
                <!-- Populated by JS -->
            </div>

            <div class="mb-8">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Recorder Name (for audit)</label>
                <input type="text" id="recorder-name" class="w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600" placeholder="Your Name">
            </div>

            <button id="btn-lock-setup" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-sky-900/20 flex items-center justify-center gap-2 text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="lock" class="w-5 h-5"></i> Lock Setup & Start Recording
            </button>
        </div>

        <!-- STAGE 2: MAIN SCORESHEET -->
        <div id="stage-recording" class="hidden flex flex-col xl:flex-row gap-6 items-start overflow-hidden">
            
            <!-- SCORESHEET MATRIX -->
            <div class="flex-grow w-full glass-panel rounded-2xl border border-white/5 flex flex-col overflow-hidden" style="max-height: 80vh;">
                <!-- Toolbar -->
                <div class="bg-slate-900/80 p-2 flex justify-between items-center border-b border-white/5 z-50 relative">
                    <span class="text-xs font-bold text-slate-400 pl-2">View Controls</span>
                    <div class="flex gap-2">
                        <button onclick="setZoom(0.7)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded text-xs font-bold transition-colors flex items-center gap-1" title="Zoom Out"><i data-lucide="zoom-out" class="w-3.5 h-3.5"></i> Zoom Out</button>
                        <button onclick="setZoom(1.0)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded text-xs font-bold transition-colors flex items-center gap-1" title="Reset Zoom"><i data-lucide="maximize" class="w-3.5 h-3.5"></i> Reset</button>
                        <button onclick="setZoom(1.3)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded text-xs font-bold transition-colors flex items-center gap-1" title="Zoom In"><i data-lucide="zoom-in" class="w-3.5 h-3.5"></i> Zoom In</button>
                    </div>
                </div>

                <!-- Scroll Container -->
                <div class="overflow-auto w-full flex-grow relative scoresheet-scroll-area">
                    <div id="scoresheet-master-grid" class="scoresheet-grid pb-20 relative w-fit">
                        <!-- Header (Sticky) -->
                        <div id="scoresheet-header" class="contents">
                            <!-- Populated by JS: Event | Lane 1 | Lane 2 | ... -->
                        </div>
                        
                        <!-- Body (Scrollable) -->
                        <div id="scoresheet-body" class="contents">
                            <!-- Populated by JS: Row per event -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: LEADERBOARD & SUMMARY -->
            <div class="w-full xl:w-80 flex-shrink-0 flex flex-col gap-6 sticky top-32">
                <!-- Leaderboard -->
                <div class="glass-panel p-5 rounded-2xl border border-white/5">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="trophy" class="w-5 h-5 text-amber-400"></i> Live Standings
                    </h3>
                    <div id="leaderboard-container" class="space-y-3">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Match Stats -->
                <div class="glass-panel p-5 rounded-2xl border border-white/5">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="bar-chart-2" class="w-4 h-4"></i> Session Stats
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-900 p-3 rounded-xl border border-white/5 text-center">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">Events Done</p>
                            <p id="stat-events-done" class="text-2xl font-bold text-white">0<span class="text-sm text-slate-500">/53</span></p>
                        </div>
                        <div class="bg-slate-900 p-3 rounded-xl border border-red-500/10 text-center">
                            <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">Total DQs</p>
                            <p id="stat-total-dqs" class="text-2xl font-bold text-red-400">0</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- SCORESHEET HELP MODAL -->
    <div id="scoresheet-help-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
        <div class="glass-panel border border-sky-500/30 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl shadow-slate-950/60">
            <div class="p-5 border-b border-white/10 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i data-lucide="circle-help" class="w-5 h-5 text-sky-300"></i> Digital Scoresheet Guide
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Poolside recording, live spectator results, offline use, lane setup, DQs, and league submission.</p>
                </div>
                <button type="button" onclick="closeScoresheetHelp()" aria-label="Close scoresheet guide"
                    class="bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 p-2 rounded-lg">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-5 space-y-5 text-sm text-slate-300 leading-relaxed">
                <section class="bg-slate-950/40 border border-white/5 rounded-xl p-4">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-3">Before Gala Day</h4>
                    <ol class="space-y-2 list-decimal list-inside">
                        <li>Open the scoresheet once while online from the Team Portal gala card.</li>
                        <li>If prompted, use <strong class="text-white">Install App</strong> on the venue device so the page can reopen if the pool has poor internet.</li>
                        <li>Check the gala title, host, round or final, and listed teams before assigning lanes.</li>
                        <li>Leave the stable scoresheet URL open or reopen the installed app on the same device on gala day.</li>
                    </ol>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-950/40 border border-white/5 rounded-xl p-4">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-3">Setup</h4>
                        <ol class="space-y-2 list-decimal list-inside">
                            <li>Assign each participating team to its physical pool lane.</li>
                            <li>Use <strong class="text-white">Mark Absent</strong> if a team is not swimming; it is removed from lane requirements.</li>
                            <li>Use <strong class="text-white">Substitute</strong> or <strong class="text-white">Add Extra Team</strong> when the real gala differs from the draw.</li>
                            <li>Enter the recorder name, then press <strong class="text-white">Lock Setup &amp; Start Recording</strong>.</li>
                        </ol>
                    </div>

                    <div class="bg-slate-950/40 border border-white/5 rounded-xl p-4">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-3">Recording Results</h4>
                        <ol class="space-y-2 list-decimal list-inside">
                            <li>Enter times in each event/lane cell; values are formatted when you leave the box.</li>
                            <li>The grid recalculates places, points, running totals, DQs, and too-fast checks automatically.</li>
                            <li>Use the zoom controls if the gala has many teams or the screen is small.</li>
                            <li>The live standings panel updates as results are entered.</li>
                        </ol>
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-950/40 border border-white/5 rounded-xl p-4">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-3">DQs And Edits</h4>
                        <ol class="space-y-2 list-decimal list-inside">
                            <li>Press <strong class="text-white">DQ</strong> on the relevant event and lane.</li>
                            <li>Choose a common reason such as false start, did not start, or enter a custom reason.</li>
                            <li>Press an active DQ button again to clear the DQ for that event/lane.</li>
                            <li>If lane setup needs changing later, use <strong class="text-white">Edit Setup</strong>, adjust lanes, and lock again.</li>
                        </ol>
                    </div>

                    <div class="bg-slate-950/40 border border-white/5 rounded-xl p-4">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-3">Offline And Sync</h4>
                        <ol class="space-y-2 list-decimal list-inside">
                            <li>The Online/Offline badge shows the current connection state.</li>
                            <li>Results and lane setup are saved locally on this device while offline.</li>
                            <li>When the device reconnects, pending lane assignments and results sync back to the server.</li>
                            <li>Avoid switching devices mid-gala unless the first device has come back online and synced.</li>
                        </ol>
                    </div>
                </section>

                <section class="bg-red-500/10 border border-red-500/20 rounded-xl p-4">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-red-300 mb-3">Public Live Results</h4>
                    <ol class="space-y-2 list-decimal list-inside">
                        <li>After setup is locked, use <strong class="text-white">Publish Live</strong> if spectators should follow the gala from the public Season Draw page.</li>
                        <li>The Season Draw page shows a <strong class="text-white">Live Results</strong> button on this gala card while public live results are switched on.</li>
                        <li>Spectators can view the current standings, entered event times, DQs, places, and points; the public view refreshes automatically.</li>
                        <li>Use <strong class="text-white">Live On</strong> to stop publishing if you need to hide the public view during the gala.</li>
                        <li>Public live results need an internet connection; offline entries will appear publicly after this device reconnects and syncs.</li>
                    </ol>
                </section>

                <section class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-emerald-300 mb-3">Submitting To The League</h4>
                    <ol class="space-y-2 list-decimal list-inside">
                        <li>Review all event times, DQs, and the live standings before submitting.</li>
                        <li>Use <strong class="text-white">Submit to League</strong> when the gala record is complete.</li>
                        <li>Submitting switches off the public live results view automatically.</li>
                        <li>The submitted scoresheet is then available for league verification and downstream results tools.</li>
                    </ol>
                </section>
            </div>
        </div>
    </div>

    <!-- DQ REASON MODAL -->
    <div id="dq-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div class="glass-panel border border-red-500/30 p-6 rounded-2xl max-w-sm w-full">
            <h3 class="text-xl font-bold text-white mb-2 flex items-center gap-2 text-red-400">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i> Disqualification
            </h3>
            <p id="dq-modal-context" class="text-sm text-slate-400 mb-4">Event 1 - Lane 2 (Academy Swim Team)</p>
            
            <input type="hidden" id="dq-event-id">
            <input type="hidden" id="dq-club-id">

            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Reason (Optional)</label>
            <select id="dq-reason-select" onchange="toggleDqCustomInput()" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-red-500 mb-4">
                <option value="">- Select common reason -</option>
                <option value="False Start">False Start</option>
                <option value="Incorrect Stroke">Incorrect Stroke</option>
                <option value="Non-simultaneous touch">Non-simultaneous touch</option>
                <option value="One hand touch">One hand touch</option>
                <option value="Did not finish">Did not finish</option>
                <option value="Did not start">Did not start</option>
                <option value="Other">Other (type below)</option>
            </select>
            <input type="text" id="dq-reason-custom" onkeydown="if(event.key === 'Enter') submitDq();" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-red-500 mb-6 placeholder-slate-600 hidden" placeholder="Type reason...">
            
            <div class="flex gap-3">
                <button type="button" onclick="closeDqModal()" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2 rounded-lg transition-colors">Cancel</button>
                <button type="button" onclick="submitDq()" class="flex-1 bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded-lg transition-colors">Confirm DQ</button>
            </div>
        </div>
    </div>

    <!-- MAIN SCORESHEET LOGIC -->
    <script>
        window.lucide = window.lucide || { createIcons() {} };
        lucide.createIcons();

        // Pass PHP variables to JS
        const initParams = {
            scoresheet_id: <?php echo $scoresheet_id; ?>,
            venue_detail_id: <?php echo $venue_detail_id; ?>,
            is_super_admin: <?php echo $is_super_admin ? 'true' : 'false'; ?>,
            current_club_id: <?php echo $current_club_id; ?>,
            all_clubs: <?php echo json_encode($all_clubs); ?>,
            is_sandbox: <?php echo $is_sandbox ? 'true' : 'false'; ?>
        };

        // App State
        let appState = {
            scoresheet: null,
            teams: [],
            events: [],
            results: {}, // key: "eventId_clubId"
            online: navigator.onLine,
            isSetupLocked: false,
            lastCalc: null,
            pendingSaves: new Set()
        };

        // DOM Elements
        const elSyncStatus = document.getElementById('sync-status');
        const elGalaTitle = document.getElementById('gala-title');
        const elGalaSubtitle = document.getElementById('gala-subtitle');
        const stageSetup = document.getElementById('stage-setup');
        const stageRecording = document.getElementById('stage-recording');
        const btnLockSetup = document.getElementById('btn-lock-setup');

        document.getElementById('btn-edit-setup').addEventListener('click', unlockSetup);
        document.getElementById('btn-live-public').addEventListener('click', togglePublicLiveResults);

        async function substituteTeam(oldClubId) {
            const sel = document.getElementById(`sub-${oldClubId}`);
            const newClubId = parseInt(sel.value);
            if (!newClubId) return alert('Please select a team to substitute.');

            if (!confirm('Are you sure you want to substitute this team? This will change the scoresheet to record times for the new team instead.')) {
                return;
            }

            try {
                const fd = new FormData();
                fd.append('action', 'substitute_team');
                fd.append('scoresheet_id', initParams.scoresheet_id);
                fd.append('old_club_id', oldClubId);
                fd.append('new_club_id', newClubId);

                const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
                const result = await resp.json();

                if (result.success) {
                    await loadScoresheetData(initParams.scoresheet_id);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (err) {
                console.error(err);
                alert('Error submitting substitution.');
            }
        }

        async function markTeamAbsent(clubId) {
            if (!confirm('Are you sure you want to mark this team as Absent? They will be removed from the lane assignment requirements.')) {
                return;
            }

            try {
                const fd = new FormData();
                fd.append('action', 'mark_absent');
                fd.append('scoresheet_id', initParams.scoresheet_id);
                fd.append('club_id', clubId);

                const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
                const result = await resp.json();

                if (result.success) {
                    await loadScoresheetData(initParams.scoresheet_id);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (err) {
                console.error(err);
                alert('Error marking team as absent.');
            }
        }

        async function addExtraTeam() {
            const newClubId = parseInt(document.getElementById('extra-team-select').value);
            if (!newClubId) return alert('Please select a team to add.');

            try {
                const fd = new FormData();
                fd.append('action', 'add_team');
                fd.append('scoresheet_id', initParams.scoresheet_id);
                fd.append('new_club_id', newClubId);

                const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
                const result = await resp.json();

                if (result.success) {
                    await loadScoresheetData(initParams.scoresheet_id);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (err) {
                console.error(err);
                alert('Error adding extra team.');
            }
        }

        // Initialize
        async function init() {
            updateNetworkStatus();
            window.addEventListener('online', updateNetworkStatus);
            window.addEventListener('offline', updateNetworkStatus);
            window.addEventListener('focus', () => {
                if (initParams.scoresheet_id) {
                    syncAllPendingWork(initParams.scoresheet_id);
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && initParams.scoresheet_id) {
                    syncAllPendingWork(initParams.scoresheet_id);
                }
            });
            window.setInterval(() => {
                if (navigator.onLine && initParams.scoresheet_id) {
                    syncAllPendingWork(initParams.scoresheet_id);
                }
            }, 15000);

            // Navigation Protection
            window.addEventListener('beforeunload', (e) => {
                if (appState.scoresheet && appState.scoresheet.status !== 'published') {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Sandbox Logic
            if (initParams.is_sandbox && !initParams.scoresheet_id) {
                const lastId = localStorage.getItem('last_sandbox_id');
                if (lastId) {
                    const resumeContainer = document.getElementById('resume-container');
                    resumeContainer.classList.remove('hidden');
                    document.getElementById('btn-resume-sandbox').onclick = () => {
                        window.location.href = `gala_scoresheet.php?id=${lastId}&sandbox=1`;
                    };
                }

                document.getElementById('sandbox-form').onsubmit = async (e) => {
                    e.preventDefault();
                    const hostId = document.getElementById('sandbox-host').value;
                    const teams = Array.from(document.querySelectorAll('input[name="sandbox_teams[]"]:checked')).map(cb => cb.value);
                    
                    if (teams.length < 2) {
                        alert("Please select at least 2 teams.");
                        return;
                    }

                    const fd = new FormData();
                    fd.append('action', 'create_sandbox');
                    fd.append('host_club_id', hostId);
                    teams.forEach(t => fd.append('team_ids[]', t));

                    const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
                    const res = await resp.json();
                    if (res.scoresheet_id) {
                        localStorage.setItem('last_sandbox_id', res.scoresheet_id);
                        window.location.href = `gala_scoresheet.php?id=${res.scoresheet_id}&sandbox=1`;
                    }
                };
            }

            try {
                // Skip regular init if we are in initial sandbox selection stage
                if (initParams.is_sandbox && !initParams.scoresheet_id) {
                    elGalaTitle.innerText = "Sandbox Environment";
                    elGalaSubtitle.innerText = "Setting up a test session...";
                    return;
                }

                // If we don't have a scoresheet_id, we need to create or find one via venue_detail_id
                if (!initParams.scoresheet_id && initParams.venue_detail_id) {
                    await createOrFindScoresheet(initParams.venue_detail_id);
                }

                if (initParams.scoresheet_id) {
                    if (initParams.is_sandbox) {
                        localStorage.setItem('last_sandbox_id', initParams.scoresheet_id);
                    }
                    prepareStableOfflineUrl(initParams.scoresheet_id);
                    await loadScoresheetData(initParams.scoresheet_id);
                } else {
                    showError("Failed to initialize scoresheet.");
                }
            } catch (err) {
                console.error("Init error:", err);
                showError("Error connecting to server. Please try again.");
            }
        }

        function updateNetworkStatus() {
            appState.online = navigator.onLine;
            if (appState.online) {
                elSyncStatus.innerHTML = '<div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></div> Online';
                elSyncStatus.className = 'flex items-center gap-2 text-xs font-bold px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400';
                if (initParams.scoresheet_id) {
                    syncAllPendingWork(initParams.scoresheet_id);
                }
            } else {
                elSyncStatus.innerHTML = '<div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div> Offline Mode';
                elSyncStatus.className = 'flex items-center gap-2 text-xs font-bold px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400';
            }
        }

        function showError(msg) {
            elGalaSubtitle.innerHTML = `<span class="text-red-400 font-bold"><i data-lucide="alert-circle" class="w-4 h-4 inline pb-1"></i> ${msg}</span>`;
            lucide.createIcons();
        }

        function stableScoresheetUrl(id) {
            const params = new URLSearchParams({ id });
            if (initParams.is_sandbox) params.set('sandbox', '1');
            return `gala_scoresheet.php?${params.toString()}`;
        }

        function prepareStableOfflineUrl(id) {
            if (!id) return;

            const stableUrl = stableScoresheetUrl(id);
            if (window.location.pathname.endsWith('gala_scoresheet.php') && window.location.search !== `?${stableUrl.split('?')[1]}`) {
                history.replaceState(null, '', stableUrl);
            }

            if (navigator.onLine) {
                // Warm the cache for the exact id URL, so an offline reload has PHP init data.
                fetch(stableUrl, { credentials: 'same-origin', cache: 'reload' })
                    .then((response) => {
                        if (response.ok && 'caches' in window) {
                            caches.open('gala-scoresheet-v3').then((cache) => cache.put(stableUrl, response.clone()));
                        }
                    })
                    .catch(() => {});
            }
        }

        // =========================================================
        // DATA LOADING
        // =========================================================
        async function createOrFindScoresheet(venue_id) {
            // First check if one exists
            const findResp = await fetch(`gala_scoresheet_api.php?action=find_by_venue&venue_detail_id=${venue_id}`);
            const findData = await findResp.json();
            
            if (findData.scoresheet_id) {
                initParams.scoresheet_id = findData.scoresheet_id;
                prepareStableOfflineUrl(initParams.scoresheet_id);
            } else if (findData.venue_detail_id) {
                // Auto-create: fetch venue info from PHP to get round_number and host_club_id
                elGalaSubtitle.innerText = 'Creating scoresheet for first use...';
                
                const fd = new FormData();
                fd.append('action', 'create');
                fd.append('venue_detail_id', venue_id);
                fd.append('round_number', '<?php
                    // Resolve round_number from venue_detail_id server-side for the auto-create
                    if ($venue_detail_id) {
                        $vd_info = $conn->query("SELECT round_number, club_id FROM venue_details WHERE id = $venue_detail_id");
                        if ($vd_info && $vd_row = $vd_info->fetch_assoc()) {
                            echo $vd_row["round_number"];
                        } else { echo "0"; }
                    } else { echo "0"; }
                ?>');
                fd.append('host_club_id', '<?php
                    if ($venue_detail_id && isset($vd_row)) {
                        echo $vd_row["club_id"];
                    } else { echo "0"; }
                ?>');
                fd.append('gala_type', 'round');
                fd.append('team_count', '4');
                
                const createResp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
                const createData = await createResp.json();
                
                if (createData.scoresheet_id) {
                    initParams.scoresheet_id = createData.scoresheet_id;
                    prepareStableOfflineUrl(initParams.scoresheet_id);
                } else {
                    showError("Failed to create scoresheet: " + (createData.error || 'Unknown error'));
                    throw new Error("Cannot proceed without scoresheet_id");
                }
            } else {
                showError("No scoresheet found for this venue and it couldn't be auto-created. Please contact admin.");
                throw new Error("Cannot proceed without scoresheet_id");
            }
        }

        async function loadScoresheetData(id) {
            // Try fetching from server
            if (appState.online) {
                try {
                    const resp = await fetch(`gala_scoresheet_api.php?action=load&scoresheet_id=${id}`);
                    const data = await resp.json();
                    if (data.error) throw new Error(data.error);

                    // Transform results array into map
                    const resMap = {};
                    data.results.forEach(r => {
                        resMap[`${r.event_id}_${r.club_id}`] = r;
                    });

                    appState.scoresheet = data.scoresheet;
                    appState.teams = data.teams;
                    appState.events = data.events;
                    appState.results = resMap;

                    // Save to IndexedDB for offline use
                    await GalaEngine.saveToLocal(id, {
                        scoresheet: data.scoresheet,
                        teams: data.teams,
                        events: data.events,
                        results: resMap
                    });

                } catch (e) {
                    console.warn("Failed to load from server, falling back to local:", e);
                    await loadFromLocalFallback(id);
                }
            } else {
                await loadFromLocalFallback(id);
            }

            applyPendingSubmissionState(id);
            renderUI();
            if (appState.online) {
                await syncAllPendingWork(id);
            }
        }

        // Submission Listener
        document.getElementById('btn-submit-league').addEventListener('click', async () => {
            const isResubmission = appState.scoresheet?.status === 'submitted';
            const submitMessage = isResubmission
                ? "Resubmit these results to the league? This will update the submitted totals using the latest saved edits."
                : "Are you sure you want to submit these results to the league? This will remove any public live view for spectators.";
            if (!confirm(submitMessage)) return;
            
            try {
                if (appState.pendingSaves.size) {
                    await Promise.allSettled(Array.from(appState.pendingSaves));
                }
                if (appState.online) {
                    await GalaEngine.syncToServer(appState.scoresheet.id);
                }
                const calc = appState.lastCalc || GalaEngine.calculateFullScoresheet(appState.events, appState.teams, appState.results);
                const totalPoints = {};
                Object.values(calc.totals || {}).forEach(team => {
                    totalPoints[team.club_id] = team.total_points;
                });

                try {
                    await postSubmission(appState.scoresheet.id, totalPoints);
                    appState.scoresheet.status = 'submitted';
                    appState.scoresheet.live_public_enabled = 0;
                    localStorage.removeItem(pendingSubmitKey(appState.scoresheet.id));
                    await persistCurrentScoresheetLocally();
                    renderUI();
                    alert(isResubmission
                        ? "Gala results resubmitted to the league."
                        : "Gala results submitted to the league. The public live view has been switched off.");
                } catch (submitErr) {
                    if (!navigator.onLine || !appState.online || submitErr.name === 'TypeError') {
                        await queueSubmission(appState.scoresheet.id, totalPoints);
                        alert("You are offline. The submission has been saved on this device and will send automatically when the internet connection returns.");
                    } else {
                        throw submitErr;
                    }
                }
            } catch (err) {
                console.error(err);
                alert("Error connecting to server.");
            }
        });

        async function togglePublicLiveResults() {
            if (!appState.scoresheet) return;

            const isLive = !!parseInt(appState.scoresheet.live_public_enabled || 0);
            const nextState = isLive ? 0 : 1;
            const message = nextState
                ? 'Publish these live results to the Season Draw page for spectators?'
                : 'Stop showing these live results on the Season Draw page?';

            if (!confirm(message)) return;

            try {
                const fd = new FormData();
                fd.append('action', 'set_live_public');
                fd.append('scoresheet_id', appState.scoresheet.id);
                fd.append('enabled', nextState);

                const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
                const res = await resp.json();
                if (res.success) {
                    appState.scoresheet.live_public_enabled = res.live_public_enabled;
                    renderUI();
                } else {
                    alert("Live publish failed: " + (res.error || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert("Error connecting to server.");
            }
        }

        async function loadFromLocalFallback(id) {
            const localData = await GalaEngine.loadFromLocal(id);
            if (localData) {
                appState.scoresheet = localData.scoresheet;
                appState.teams = localData.teams;
                appState.events = localData.events;
                appState.results = localData.results;
            } else {
                throw new Error("Offline and no local data found.");
            }
        }

        function pendingLanesKey(id) {
            return `pending_lanes_${id}`;
        }

        function pendingSubmitKey(id) {
            return `pending_submit_${id}`;
        }

        function submitSuccessKey(id) {
            return `submit_success_${id}`;
        }

        function hasPendingSubmission(id) {
            return !!localStorage.getItem(pendingSubmitKey(id));
        }

        function applyPendingSubmissionState(id) {
            if (!appState.scoresheet || !hasPendingSubmission(id)) return;
            appState.scoresheet.status = 'submitted';
            appState.scoresheet.live_public_enabled = 0;
        }

        async function postSubmission(id, totalPoints) {
            const fd = new FormData();
            fd.append('action', 'submit');
            fd.append('scoresheet_id', id);
            fd.append('total_points_json', JSON.stringify(totalPoints));

            const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
            const result = await resp.json().catch(() => ({}));
            if (!resp.ok || !result.success) {
                throw new Error(result.error || 'Submission failed');
            }
            return result;
        }

        async function queueSubmission(id, totalPoints) {
            appState.scoresheet.status = 'submitted';
            appState.scoresheet.live_public_enabled = 0;
            localStorage.removeItem(submitSuccessKey(id));
            localStorage.setItem(pendingSubmitKey(id), JSON.stringify({
                totalPoints,
                submittedAt: Date.now()
            }));
            await persistCurrentScoresheetLocally();
            renderUI();
        }

        function applyLaneAssignmentsLocally(lanes, recorderName = '') {
            lanes.forEach((lane) => {
                const team = appState.teams.find(t => t.club_id === parseInt(lane.club_id));
                if (team) team.lane_number = parseInt(lane.lane_number);
            });
            appState.scoresheet.status = 'in_progress';
            if (recorderName) appState.scoresheet.recorder_name = recorderName;
        }

        async function persistCurrentScoresheetLocally() {
            await GalaEngine.saveToLocal(appState.scoresheet.id, {
                scoresheet: appState.scoresheet,
                teams: appState.teams,
                events: appState.events,
                results: appState.results
            });
        }

        async function postLaneAssignments(id, lanes, recorderName = '') {
            const fd = new FormData();
            fd.append('action', 'save_lanes');
            fd.append('scoresheet_id', id);
            fd.append('lanes', JSON.stringify(lanes));
            if (recorderName) fd.append('recorder_name', recorderName);

            const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: fd });
            const result = await resp.json();
            if (!result.success) throw new Error(result.error || 'Failed to save lanes');
            return result;
        }

        async function syncPendingLaneAssignments(id) {
            const pending = localStorage.getItem(pendingLanesKey(id));
            if (!pending || !navigator.onLine) return;

            try {
                const data = JSON.parse(pending);
                await postLaneAssignments(id, data.lanes, data.recorderName || '');
                localStorage.removeItem(pendingLanesKey(id));
            } catch (err) {
                console.warn('Lane assignment sync failed, will retry:', err);
            }
        }

        async function syncPendingSubmission(id) {
            const pending = localStorage.getItem(pendingSubmitKey(id));
            if (!pending || !navigator.onLine) return;

            try {
                const data = JSON.parse(pending);
                await postSubmission(id, data.totalPoints || {});
                localStorage.removeItem(pendingSubmitKey(id));
                localStorage.setItem(submitSuccessKey(id), String(Date.now()));
                if (appState.scoresheet?.id == id) {
                    appState.scoresheet.status = 'submitted';
                    appState.scoresheet.live_public_enabled = 0;
                    await persistCurrentScoresheetLocally();
                    renderUI();
                }
            } catch (err) {
                console.warn('Submission sync failed, will retry:', err);
            }
        }

        async function syncAllPendingWork(id) {
            await syncPendingLaneAssignments(id);
            await GalaEngine.syncToServer(id);
            await syncPendingSubmission(id);
        }

        function updateSubmissionNotice() {
            const notice = document.getElementById('submission-sync-notice');
            if (!notice || !appState.scoresheet?.id) return;

            const id = appState.scoresheet.id;
            const pending = hasPendingSubmission(id);
            const successAt = parseInt(localStorage.getItem(submitSuccessKey(id)) || '0', 10);
            const recentSuccess = successAt && (Date.now() - successAt < 10 * 60 * 1000);

            notice.className = 'hidden mb-6 rounded-xl border px-4 py-3 text-sm font-bold flex items-center gap-3';
            notice.innerHTML = '';

            if (pending) {
                notice.className = 'mb-6 rounded-xl border px-4 py-3 text-sm font-bold flex items-center gap-3 bg-amber-500/10 border-amber-400/40 text-amber-100';
                notice.innerHTML = '<i data-lucide="clock" class="w-5 h-5 text-amber-300"></i><span>Pending submission - saved on this device and waiting for internet.</span>';
            } else if (appState.scoresheet.status === 'submitted' || recentSuccess) {
                notice.className = 'mb-6 rounded-xl border px-4 py-3 text-sm font-bold flex items-center gap-3 bg-emerald-500/10 border-emerald-400/40 text-emerald-100';
                notice.innerHTML = '<i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-300"></i><span>Submitted successfully.</span>';
            }

            lucide.createIcons();
        }

        // =========================================================
        // UI RENDERING
        // =========================================================
        function renderUI() {
            // Update Header
            const titlePrefix = initParams.is_sandbox ? '[SANDBOX] ' : '';
            elGalaTitle.innerText = titlePrefix + `Round ${appState.scoresheet.round_number} @ ${appState.scoresheet.host_club_name}`;
            const liveLabel = parseInt(appState.scoresheet.live_public_enabled || 0) ? ' | PUBLIC LIVE ON' : '';
            elGalaSubtitle.innerText = `Gala Type: ${appState.scoresheet.gala_type.toUpperCase()} | Status: ${appState.scoresheet.status.toUpperCase()}${liveLabel}`;
            updateSubmissionNotice();
            
            // Determine which stage to show
            // Are all non-absent teams assigned a lane?
            const unassignedTeams = appState.teams.filter(t => !t.is_absent && t.lane_number === null);
            
            if (appState.forceSetupOpen || (appState.scoresheet.status === 'draft' && unassignedTeams.length > 0)) {
                appState.isSetupLocked = false;
            } else {
                appState.isSetupLocked = true;
            }

            // Button Visibility
            const submitButton = document.getElementById('btn-submit-league');
            if (appState.scoresheet.status === 'in_progress' || appState.scoresheet.status === 'submitted') {
                submitButton.classList.remove('hidden');
                submitButton.innerHTML = appState.scoresheet.status === 'submitted'
                    ? '<i data-lucide="send" class="w-4 h-4"></i> Resubmit to League'
                    : '<i data-lucide="send" class="w-4 h-4"></i> Submit to League';
            } else {
                submitButton.classList.add('hidden');
            }

            const liveBtn = document.getElementById('btn-live-public');
            if (!initParams.is_sandbox && appState.scoresheet.status === 'in_progress') {
                const isLive = parseInt(appState.scoresheet.live_public_enabled || 0) === 1;
                liveBtn.classList.remove('hidden');
                liveBtn.classList.toggle('bg-red-600', isLive);
                liveBtn.classList.toggle('text-white', isLive);
                liveBtn.innerHTML = isLive
                    ? '<i data-lucide="radio" class="w-4 h-4 animate-pulse"></i> Live On'
                    : '<i data-lucide="radio" class="w-4 h-4"></i> Publish Live';
            } else {
                liveBtn.classList.add('hidden');
            }

            if (appState.isSetupLocked) {
                document.getElementById('btn-edit-setup').classList.remove('hidden');
            } else {
                document.getElementById('btn-edit-setup').classList.add('hidden');
            }

            if (!appState.isSetupLocked) {
                renderSetupStage();
                stageSetup.classList.remove('hidden');
                stageRecording.classList.add('hidden');
            } else {
                stageSetup.classList.add('hidden');
                stageRecording.classList.remove('hidden');
                appState.forceSetupOpen = false;
                
                // Sort teams by lane number for display
                appState.teams.sort((a, b) => {
                    if (a.is_absent) return 1; // Put absent at end
                    if (b.is_absent) return -1;
                    return (a.lane_number || 99) - (b.lane_number || 99);
                });
                
                renderRecordingStage();
                recalculateAll(); // Run scoring engine to populate initial UI state
            }
        }

        function unlockSetup() {
            appState.forceSetupOpen = true;
            renderUI();
        }

        // =========================================================
        // STAGE 1: SETUP
        // =========================================================
        function renderSetupStage() {
            const container = document.getElementById('lane-assignment-container');
            container.innerHTML = '';
            btnLockSetup.disabled = false;
            btnLockSetup.innerHTML = '<i data-lucide="lock" class="w-5 h-5"></i> Lock Setup & Start Recording';

            appState.teams.forEach(team => {
                if (team.is_absent) {
                    let subOptions = '<option value="">- Substitute Team -</option>';
                    initParams.all_clubs.forEach(c => {
                        // Don't show teams that are already in this gala
                        if (!appState.teams.find(t => t.club_id === parseInt(c.id))) {
                            subOptions += `<option value="${c.id}">${c.name}</option>`;
                        }
                    });

                    container.innerHTML += `
                        <div class="bg-slate-900/50 border border-slate-800 p-4 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-4 opacity-75">
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                <div class="w-10 h-10 bg-white/10 rounded p-1"><img src="images/Teams/${team.logo}" class="w-full h-full object-contain grayscale"></div>
                                <div class="flex flex-col">
                                    <span class="font-bold line-through text-slate-500">${team.club_name}</span>
                                    <span class="text-[10px] uppercase tracking-wider text-red-400 font-bold">Absent</span>
                                </div>
                            </div>
                            <div class="w-full sm:w-auto flex items-center gap-2">
                                <select id="sub-${team.club_id}" class="bg-slate-800 border border-sky-500/30 rounded-lg py-2 px-3 text-sky-400 focus:outline-none focus:border-sky-500 text-sm font-bold flex-grow sm:flex-grow-0">
                                    ${subOptions}
                                </select>
                                <button onclick="substituteTeam(${team.club_id})" class="bg-sky-600 hover:bg-sky-500 text-white p-2 rounded-lg transition-colors flex-shrink-0" title="Substitute">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>`;
                    return;
                }

                // Dropdown for lanes 1 to 8
                let options = '<option value="">- Select Lane -</option>';
                for (let i = 1; i <= 8; i++) {
                    const sel = (team.lane_number === i) ? 'selected' : '';
                    options += `<option value="${i}" ${sel}>Lane ${i}</option>`;
                }

                // Dropdown for substitution (for non-absent teams as well)
                let subOptions = '<option value="">- Substitute Team -</option>';
                initParams.all_clubs.forEach(c => {
                    if (!appState.teams.find(t => t.club_id === parseInt(c.id))) {
                        subOptions += `<option value="${c.id}">${c.name}</option>`;
                    }
                });

                container.innerHTML += `
                    <div class="bg-slate-800/80 border border-slate-700 p-4 rounded-xl flex flex-col md:flex-row items-center justify-between shadow-lg gap-4">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <div class="w-10 h-10 bg-white rounded p-1"><img src="images/Teams/${team.logo}" class="w-full h-full object-contain"></div>
                            <span class="font-bold text-lg">${team.club_name}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                            <select class="lane-select bg-slate-950 border border-sky-500/50 rounded-lg py-2 px-4 text-sky-400 font-bold focus:outline-none focus:border-sky-400 cursor-pointer text-center w-full sm:w-auto" data-club-id="${team.club_id}">
                                ${options}
                            </select>
                            
                            <div class="flex items-center gap-1 w-full sm:w-auto">
                                <select id="sub-${team.club_id}" class="bg-slate-900 border border-slate-700 rounded-lg py-2 px-2 text-slate-400 focus:outline-none focus:border-slate-500 text-xs w-full sm:w-auto">
                                    ${subOptions}
                                </select>
                                <button onclick="substituteTeam(${team.club_id})" class="bg-slate-700 hover:bg-slate-600 text-white p-2 rounded-lg transition-colors flex-shrink-0" title="Substitute">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                </button>
                                <button onclick="markTeamAbsent(${team.club_id})" class="bg-red-900/30 hover:bg-red-800/50 text-red-400 p-2 rounded-lg transition-colors flex-shrink-0 ml-1 border border-red-900/50" title="Mark Absent">
                                    <i data-lucide="user-x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>`;
            });

            // Add Extra Team Section
            let extraOptions = '<option value="">- Select Team to Add -</option>';
            initParams.all_clubs.forEach(c => {
                if (!appState.teams.find(t => t.club_id === parseInt(c.id))) {
                    extraOptions += `<option value="${c.id}">${c.name}</option>`;
                }
            });

            container.innerHTML += `
                <div class="mt-6 pt-6 border-t border-slate-700/50 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <select id="extra-team-select" class="bg-slate-900 border border-slate-700 rounded-lg py-2 px-4 text-slate-300 focus:outline-none focus:border-emerald-500 font-bold w-full sm:w-64">
                        ${extraOptions}
                    </select>
                    <button onclick="addExtraTeam()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-5 rounded-lg transition-colors flex items-center gap-2 w-full sm:w-auto justify-center">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Extra Team to Gala
                    </button>
                </div>
            `;

            // Handle Setup Lock
            btnLockSetup.onclick = async () => {
                // Collect lanes
                const selects = document.querySelectorAll('.lane-select');
                const lanes = [];
                const assigned = new Set();
                let valid = true;

                selects.forEach(sel => {
                    const cid = sel.getAttribute('data-club-id');
                    const val = sel.value;
                    if (!val) { valid = false; }
                    else if (assigned.has(val)) { valid = false; alert("Duplicate lane assignments found."); }
                    else {
                        assigned.add(val);
                        lanes.push({ club_id: cid, lane_number: val });
                    }
                });

                if (!valid) {
                    if (assigned.size !== selects.length) {
                        alert("Please assign a unique lane to every participating team.");
                    }
                    return;
                }

                const recName = document.getElementById('recorder-name').value;

                if (appState.online) {
                    btnLockSetup.disabled = true;
                    btnLockSetup.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Saving...';
                    lucide.createIcons();

                    try {
                        await postLaneAssignments(appState.scoresheet.id, lanes, recName);
                        appState.forceSetupOpen = false;
                        // Reload data to ensure sync
                        await loadScoresheetData(appState.scoresheet.id);
                    } catch (e) {
                        alert("Failed to save lane assignments.");
                        btnLockSetup.disabled = false;
                        btnLockSetup.innerHTML = '<i data-lucide="lock" class="w-5 h-5"></i> Lock Setup & Start Recording';
                        lucide.createIcons();
                    }
                } else {
                    applyLaneAssignmentsLocally(lanes, recName);
                    appState.forceSetupOpen = false;
                    localStorage.setItem(pendingLanesKey(appState.scoresheet.id), JSON.stringify({ lanes, recorderName: recName }));
                    await persistCurrentScoresheetLocally();
                    renderUI();
                }
            };
        }

        // Global Zoom level
        window.setZoom = function(level) {
            appState.zoomLevel = level;
            // Force a full re-render of the recording stage with new dimensions
            if (document.getElementById('stage-recording').offsetParent !== null) {
                renderRecordingStage();
                recalculateAll(); 
            }
        };

        // =========================================================
        // STAGE 2: RECORDING (SCORESHEET)
        // =========================================================
        function renderRecordingStage() {
            const activeTeams = appState.teams.filter(t => !t.is_absent);
            const cols = activeTeams.length + 1; // Events col + team cols
            
            // Set grid template on the master grid
            const masterGrid = document.getElementById('scoresheet-master-grid');
            const zoom = appState.zoomLevel || 1.0;
            const colWidth = Math.floor(180 * zoom);
            const eventWidth = Math.floor(250 * zoom);
            
            masterGrid.style.gridTemplateColumns = `minmax(${eventWidth}px, 1.5fr) repeat(${activeTeams.length}, minmax(${colWidth}px, 1fr))`;
            
            const head = document.getElementById('scoresheet-header');
            const body = document.getElementById('scoresheet-body');
            
            // Render Header
            let headHTML = `<div class="header-cell sticky top-0 left-0 z-50 flex items-center justify-start pl-6 border-r border-slate-700 bg-slate-900 shadow-md">
                                <span class="text-white font-black text-sm uppercase tracking-widest flex items-center gap-2">
                                    <i data-lucide="list-ordered" class="w-4 h-4 text-sky-400"></i> Events
                                </span>
                            </div>`;
            activeTeams.forEach(t => {
                headHTML += `
                    <div class="header-cell sticky top-0 z-40 flex-col gap-1 py-2 text-center border-r border-slate-700/50 relative bg-slate-900 shadow-md min-w-0">
                        <span class="text-sky-400 text-[10px] font-bold tracking-widest">LANE ${t.lane_number}</span>
                        <div class="w-6 h-6 bg-white rounded p-0.5 mx-auto opacity-90 shadow-inner hidden lg:block"><img src="images/Teams/${t.logo}" class="w-full h-full object-contain"></div>
                        <span class="text-white text-xs whitespace-nowrap overflow-hidden text-ellipsis w-full px-1 leading-tight min-w-0">${t.club_name}</span>
                    </div>`;
            });
            head.innerHTML = headHTML;

            // Render Body (Events)
            let bodyHTML = '';
            appState.events.forEach((ev, idx) => {
                
                // Event Header Column
                bodyHTML += `
                    <div class="cell bg-slate-900 sticky left-0 z-30 justify-start px-4 border-r border-slate-700 border-b border-slate-700/50 shadow-[2px_0_5px_rgba(0,0,0,0.2)]">
                        <div class="flex items-center gap-3 w-full">
                            <div class="bg-slate-950 text-slate-400 text-xs font-bold w-7 h-7 flex items-center justify-center rounded-lg border border-slate-700/50 flex-shrink-0">${ev.event_number}</div>
                            <div class="flex-grow min-w-0">
                                <div class="font-bold text-sm text-white truncate" title="${ev.event_name}">${ev.event_name}</div>
                                <div class="flex gap-2 text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">
                                    <span class="bg-slate-800 px-1.5 rounded">${ev.distance}</span>
                                    <span class="bg-slate-800 px-1.5 rounded border border-red-500/20 text-red-400">CUT: ${GalaEngine.formatTime(ev.cut_off_time_ms)}</span>
                                </div>
                            </div>
                        </div>
                    </div>`;

                // Team Columns for this event
                activeTeams.forEach(t => {
                    const key = `${ev.id}_${t.club_id}`;
                    const res = appState.results[key] || {};
                    const val = GalaEngine.formatTimeForInput(res.time_ms);
                    const dqClass = res.is_dq ? 'active' : '';

                    bodyHTML += `
                        <div class="cell border-r border-slate-700/50 border-b border-slate-700/50 flex-col gap-2 relative group p-3 z-10 min-w-0" id="cell_${key}">
                            <div class="flex w-full gap-2 relative min-w-0">
                                <input type="text" 
                                       class="time-input min-w-0 w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-2 text-white focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors" 
                                       placeholder="MM:SS.xx" 
                                       value="${val}" 
                                       data-event="${ev.id}" 
                                       data-club="${t.club_id}">
                                
                                <button class="dq-btn ${dqClass} w-10 h-10 flex-shrink-0 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-red-400 hover:border-red-500/50 transition-colors flex items-center justify-center font-bold text-xs" 
                                        data-event="${ev.id}" 
                                        data-club="${t.club_id}" title="Disqualify">DQ</button>
                            </div>
                            
                            <!-- Scoring Output Overlay -->
                            <div class="w-full flex justify-between items-center px-1 text-xs font-bold" id="scoreout_${key}">
                                <span class="text-slate-500 output-place">-</span>
                                <span class="text-sky-400 output-points">- pts</span>
                            </div>
                        </div>`;
                });

                // Insert Checkpoint Totals every 10 events
                if ([10, 20, 30, 40].includes(ev.event_number)) {
                    bodyHTML += `<div class="cell bg-emerald-900/40 border-r border-slate-700 border-b border-emerald-500/20 justify-start pl-4 py-2 sticky left-0 z-30 text-emerald-400 font-bold text-xs uppercase tracking-widest shadow-[2px_0_5px_rgba(0,0,0,0.2)]"><i data-lucide="flag" class="w-3 h-3 inline mr-1"></i> Running Total (Event ${ev.event_number})</div>`;
                    activeTeams.forEach(t => {
                        bodyHTML += `<div class="cell bg-emerald-900/10 border-r border-slate-700/50 border-b border-emerald-500/20 py-2 font-bold text-emerald-400 text-lg" id="cp_${ev.event_number}_${t.club_id}">0</div>`;
                    });
                }
            });

            body.innerHTML = bodyHTML;
            lucide.createIcons();

            // Attach Event Listeners
            document.querySelectorAll('.time-input').forEach(inp => {
                inp.addEventListener('blur', handleTimeInputBlur);
                inp.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        inp.blur();
                        // Find next input and focus it (down the column)
                        // Simplified navigation: down by default
                    }
                });
            });

            document.querySelectorAll('.dq-btn').forEach(btn => {
                btn.addEventListener('click', handleDqClick);
            });
        }

        // =========================================================
        // INTERACTION HANDLERS
        // =========================================================
        async function handleTimeInputBlur(e) {
            const inp = e.target;
            const eventId = parseInt(inp.getAttribute('data-event'));
            const clubId = parseInt(inp.getAttribute('data-club'));
            const rawVal = inp.value;

            // Parse time using engine
            const parsedMs = GalaEngine.parseTime(rawVal);
            
            // Format back to input
            if (parsedMs === 'DQ') {
                // User typed DQ in the time box
                inp.value = '';
                await updateResult(eventId, clubId, null, 1, null);
            } else if (parsedMs !== null) {
                inp.value = GalaEngine.formatTimeForInput(parsedMs);
                await updateResult(eventId, clubId, parsedMs, 0, null);
            } else if (rawVal.trim() === '') {
                inp.value = '';
                await updateResult(eventId, clubId, null, 0, null);
            } else {
                // Invalid input, reset to previous state
                inp.style.borderColor = 'red';
                setTimeout(() => inp.style.borderColor = '', 1000);
                const prev = appState.results[`${eventId}_${clubId}`];
                inp.value = prev ? GalaEngine.formatTimeForInput(prev.time_ms) : '';
            }
        }

        async function handleDqClick(e) {
            const btn = e.currentTarget;
            const eventId = parseInt(btn.getAttribute('data-event'));
            const clubId = parseInt(btn.getAttribute('data-club'));
            const isCurrentlyDq = btn.classList.contains('active');

            if (isCurrentlyDq) {
                // Remove DQ
                await updateResult(eventId, clubId, null, 0, null);
            } else {
                // Find event and team info for context
                const ev = appState.events.find(ev => ev.id === eventId);
                const team = appState.teams.find(t => t.club_id === clubId);
                
                if (ev && team) {
                    document.getElementById('dq-modal-context').innerText = `Event ${ev.event_number} - Lane ${team.lane_number} (${team.club_name})`;
                }

                // Add DQ via Modal
                document.getElementById('dq-event-id').value = eventId;
                document.getElementById('dq-club-id').value = clubId;
                
                // Reset fields
                document.getElementById('dq-reason-select').value = '';
                document.getElementById('dq-reason-custom').value = '';
                document.getElementById('dq-reason-custom').classList.add('hidden');

                document.getElementById('dq-modal').classList.remove('hidden');
                setTimeout(() => document.getElementById('dq-reason-select').focus(), 100);
            }
        }

        function toggleDqCustomInput() {
            const select = document.getElementById('dq-reason-select');
            const custom = document.getElementById('dq-reason-custom');
            if (select.value === 'Other') {
                custom.classList.remove('hidden');
                custom.focus();
            } else {
                custom.classList.add('hidden');
            }
        }

        function closeDqModal() {
            document.getElementById('dq-modal').classList.add('hidden');
        }

        function openScoresheetHelp() {
            const modal = document.getElementById('scoresheet-help-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            lucide.createIcons();
        }

        function closeScoresheetHelp() {
            const modal = document.getElementById('scoresheet-help-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeScoresheetHelp();
            }
        });

        async function submitDq() {
            const eventId = parseInt(document.getElementById('dq-event-id').value);
            const clubId = parseInt(document.getElementById('dq-club-id').value);
            
            const selectVal = document.getElementById('dq-reason-select').value;
            const customVal = document.getElementById('dq-reason-custom').value.trim();
            
            const reason = selectVal === 'Other' ? customVal : selectVal;
            
            closeDqModal();
            await updateResult(eventId, clubId, null, 1, reason || null);
        }

        async function updateResult(eventId, clubId, timeMs, isDq, dqReason) {
            const key = `${eventId}_${clubId}`;
            
            if (!appState.results[key]) appState.results[key] = { event_id: eventId, club_id: clubId };
            
            appState.results[key].time_ms = timeMs;
            appState.results[key].is_dq = isDq;
            appState.results[key].dq_reason = dqReason;

            // Recalculate everything and update UI
            recalculateAll();

            const affectedResults = appState.teams
                .filter(team => !team.is_absent)
                .map(team => {
                    const affectedKey = `${eventId}_${team.club_id}`;
                    if (!appState.results[affectedKey]) {
                        appState.results[affectedKey] = { event_id: eventId, club_id: team.club_id };
                    }
                    const scored = appState.lastCalc?.scored?.[affectedKey] || {};
                    appState.results[affectedKey].points = scored.points || 0;
                    appState.results[affectedKey].place = scored.place || null;
                    appState.results[affectedKey].status = scored.status || 'pending';
                    appState.results[affectedKey].is_verified = appState.results[affectedKey].is_verified || 0;
                    return { ...appState.results[affectedKey] };
                });

            // Save mechanism (DB + IndexedDB sync queue)
            try {
                // Always save to IndexedDB state
                await GalaEngine.saveToLocal(appState.scoresheet.id, {
                    scoresheet: appState.scoresheet,
                    teams: appState.teams,
                    events: appState.events,
                    results: appState.results
                });

                if (appState.online) {
                    const fd = new FormData();
                    fd.append('action', 'save_batch');
                    fd.append('scoresheet_id', appState.scoresheet.id);
                    fd.append('results', JSON.stringify(affectedResults));

                    const savePromise = fetch('gala_scoresheet_api.php', { method: 'POST', body: fd })
                        .then(async response => {
                            const payload = await response.json().catch(() => ({}));
                            if (!response.ok || !payload.success) {
                                throw new Error(payload.error || 'Save request failed');
                            }
                        })
                        .catch(async err => {
                            console.warn("Save request failed, queueing for sync", err);
                            await Promise.all(affectedResults.map(result => GalaEngine.queueForSync(appState.scoresheet.id, result)));
                            updateNetworkStatus(); // Might be offline now
                        })
                        .finally(() => appState.pendingSaves.delete(savePromise));
                    appState.pendingSaves.add(savePromise);
                    await savePromise;
                } else {
                    // Queue for background sync when back online
                    await Promise.all(affectedResults.map(result => GalaEngine.queueForSync(appState.scoresheet.id, result)));
                }
            } catch (e) {
                console.error("Save error:", e);
            }
        }

        // =========================================================
        // SCORING & UI UPDATES
        // =========================================================
        function recalculateAll() {
            // Run data through Engine
            const calc = GalaEngine.calculateFullScoresheet(appState.events, appState.teams, appState.results);
            appState.lastCalc = calc;
            
            // 1. Update Cell UIs (Points, Places, Backgrounds)
            Object.keys(calc.scored).forEach(key => {
                const s = calc.scored[key];
                const parts = key.split('_');
                const eventId = parts[0];
                const clubId = parts[1];
                
                const cell = document.getElementById(`cell_${key}`);
                const outPlace = document.querySelector(`#scoreout_${key} .output-place`);
                const outPts = document.querySelector(`#scoreout_${key} .output-points`);
                const dqBtn = document.querySelector(`.dq-btn[data-event="${eventId}"][data-club="${clubId}"]`);
                const inp = document.querySelector(`.time-input[data-event="${eventId}"][data-club="${clubId}"]`);

                if (!cell) return;

                // Reset styles
                cell.className = 'cell border-r border-slate-700/50 border-b border-slate-700/50 flex-col gap-2 relative group p-3 transition-colors';
                dqBtn.classList.remove('active');

                if (s.status === 'pending') {
                    outPlace.innerText = '-';
                    outPts.innerText = '- pts';
                } else if (s.status === 'dq') {
                    cell.classList.add('status-dq');
                    dqBtn.classList.add('active');
                    inp.value = '';
                    const reason = appState.results[key]?.dq_reason;
                    if (reason) {
                        outPlace.innerHTML = `<div class="flex flex-col"><span class="text-red-400 uppercase leading-none">DQ</span><span class="text-[9px] text-red-500/70 truncate max-w-[80px] normal-case font-normal mt-0.5" title="${reason}">${reason}</span></div>`;
                    } else {
                        outPlace.innerHTML = '<span class="text-red-400 uppercase">DQ</span>';
                    }
                    outPts.innerText = '0 pts';
                } else if (s.status === 'too_fast') {
                    cell.classList.add('status-too-fast');
                    outPlace.innerHTML = '<span class="text-amber-500 uppercase">Too Fast</span>';
                    outPts.innerText = '0 pts';
                } else {
                    // Valid
                    outPlace.innerText = ordinalSuffix(s.place);
                    outPts.innerText = `${s.points} pts`;
                    
                    // Highlight 1st place
                    if (s.place === 1) {
                        outPlace.classList.remove('text-slate-500');
                        outPlace.classList.add('text-emerald-400');
                    }
                }
            });

            // 2. Update Checkpoints
            Object.keys(calc.checkpoints).forEach(cp => {
                Object.keys(calc.checkpoints[cp]).forEach(clubId => {
                    const el = document.getElementById(`cp_${cp}_${clubId}`);
                    if (el) {
                        el.innerText = calc.checkpoints[cp][clubId];
                    }
                });
            });

            // 3. Update Leaderboard Panel
            const lbContainer = document.getElementById('leaderboard-container');
            let lbHTML = '';
            calc.leaderboard.forEach((t, idx) => {
                const pos = idx + 1;
                const posClass = pos === 1 ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 
                               pos === 2 ? 'bg-slate-300/20 text-slate-300 border-slate-300/30' : 
                               pos === 3 ? 'bg-orange-700/20 text-orange-400 border-orange-700/30' : 
                               'bg-slate-800 text-slate-400 border-slate-700';
                               
                lbHTML += `
                    <div class="flex flex-col p-3 rounded-xl border border-white/5 bg-slate-900/50 gap-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 flex items-center justify-center rounded-full border ${posClass} font-bold text-xs shrink-0">${pos}</div>
                                <div class="font-bold text-sm text-white truncate max-w-[150px]" title="${t.club_name}">${t.club_name}</div>
                            </div>
                            <div class="text-xl font-bold text-sky-400 shrink-0">${t.total_points}</div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1 px-1">
                            <div class="flex gap-2">
                                <span title="1st Place"><span class="text-amber-400">1st:</span> ${t.firsts}</span>
                                <span title="2nd Place"><span class="text-slate-300">2nd:</span> ${t.seconds}</span>
                                <span title="3rd Place"><span class="text-orange-400">3rd:</span> ${t.thirds}</span>
                                <span title="4th Place">4th: ${t.fourths}</span>
                            </div>
                            <div class="flex gap-2">
                                <span title="Disqualifications" class="text-red-400/80">DQ: ${t.dqs}</span>
                            </div>
                        </div>
                    </div>`;
            });
            lbContainer.innerHTML = lbHTML;

            // 4. Update Stats Panel
            let totalDqs = 0;
            let eventsWithData = new Set();
            Object.values(calc.scored).forEach(s => {
                if (s.status !== 'pending') eventsWithData.add(s.eventId); // Need to extract eventId properly if used, simplify:
            });
            
            // Simplified events done count
            const filledEvents = new Set();
            Object.keys(appState.results).forEach(k => {
                if (appState.results[k].time_ms !== null || appState.results[k].is_dq) {
                    filledEvents.add(k.split('_')[0]);
                }
                if (appState.results[k].is_dq) totalDqs++;
            });
            
            document.getElementById('stat-events-done').innerHTML = `${filledEvents.size}<span class="text-sm text-slate-500">/53</span>`;
            document.getElementById('stat-total-dqs').innerText = totalDqs;
        }

        // Utils
        function ordinalSuffix(i) {
            var j = i % 10, k = i % 100;
            if (j == 1 && k != 11) return i + "st";
            if (j == 2 && k != 12) return i + "nd";
            if (j == 3 && k != 13) return i + "rd";
            return i + "th";
        }

        // Run
        document.addEventListener('DOMContentLoaded', init);

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js', { scope: 'gala_scoresheet.php' })
                    .then(reg => console.log('SW registered!', reg))
                    .catch(err => console.log('SW registration failed', err));
            });
        }

        // =========================================================
        // PWA INSTALL PROMPT
        // =========================================================
        window.addEventListener('DOMContentLoaded', () => {
            let deferredPrompt;
            const installPrompt = document.getElementById('install-prompt');
            const btnInstall = document.getElementById('btn-install');
            const installDesc = document.getElementById('install-desc');

            // Check if already in standalone mode (installed)
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            
            if (!isStandalone) {
                // Show banner
                installPrompt.classList.remove('hidden');

                // Detect iOS
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                if (isIOS) {
                    installDesc.innerHTML = 'No internet at the venue? Tap the <strong class="text-white">Share</strong> icon in Safari, then choose <strong class="text-white">Add to Home Screen</strong> so this scoresheet can reopen offline.';
                }

                // Catch Chrome/Android native prompt
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    deferredPrompt = e;
                    btnInstall.classList.remove('hidden');
                });

                btnInstall.addEventListener('click', async () => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        const { outcome } = await deferredPrompt.userChoice;
                        if (outcome === 'accepted') {
                            installPrompt.style.display = 'none';
                        }
                        deferredPrompt = null;
                    }
                });
            }
        });
    </script>

</body>
</html>
