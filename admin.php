<?php
session_start();
include 'db.php';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === LEAGUE_PASSWORD) {
        $_SESSION['logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = true;
    }
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Club Login</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .swim-gradient { background: linear-gradient(135deg, #075985 0%, #0ea5e9 100%); }
        .admin-card { background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); }
        .glass-panel { background: rgba(15, 23, 42, 0.8); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <!-- CONTENT AREA -->
    <div class="flex-grow flex flex-col items-center justify-center p-4">
        
        <?php if (!isset($_SESSION['logged_in'])): ?>
            <!-- LOGIN SCREEN -->
            <div id="loginScreen" class="max-w-md w-full text-center py-12">
                <div class="admin-card p-8 rounded-3xl shadow-2xl backdrop-blur-xl">
                    <h1 class="text-2xl font-bold mb-2 uppercase tracking-tight">Club Rep Login</h1>
                    <p class="text-slate-400 text-sm mb-6 leading-relaxed">Enter the league password to access official resources and Google Drive folders. Contact your club representative if you need access. Or contact Lewis.</p>
                    <form method="POST" class="space-y-4">
                        <input type="password" name="password" class="w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600" placeholder="Enter Password" required>
                        <button type="submit" class="w-full swim-gradient text-white font-bold py-3 rounded-xl hover:opacity-90 transition-opacity shadow-lg">Login</button>
                        <?php if (isset($login_error)): ?>
                            <p class="text-red-400 text-xs font-medium">Incorrect password. Please try again.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- PROTECTED CONTENT -->
            <div id="protectedContent" class="w-full max-w-7xl my-8 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4 text-center md:text-left">
                    <div>
                        <h1 class="text-3xl font-bold">Representative <span class="text-sky-500">Portal</span></h1>
                        <p class="text-slate-500 text-xs uppercase tracking-widest mt-1">Official League Resources 2026</p>
                    </div>
                    <a href="?action=logout" class="bg-slate-800 hover:bg-red-500/10 hover:text-red-400 border border-slate-700 px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition-all">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                    </a>
                </div>
                
                <!-- TOP ACTIONS GRID -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    
                    <!-- Venue Management Card -->
                    <div class="p-6 bg-sky-900/20 border border-sky-500/30 rounded-2xl flex flex-col justify-between relative overflow-hidden group">
                         <div class="absolute inset-0 bg-gradient-to-br from-sky-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                         <div class="flex items-start gap-4 relative z-10 mb-4">
                             <div class="p-3 bg-sky-500/20 rounded-xl flex-shrink-0">
                                 <i data-lucide="map-pin" class="w-8 h-8 text-sky-400"></i>
                             </div>
                             <div>
                                 <h2 class="text-xl font-bold text-white">Manage Venues</h2>
                                 <p class="text-slate-300 text-sm mt-1">Update venue details, warm-up times, and parking info.</p>
                             </div>
                         </div>
                         <a href="edit_venue.php" class="relative z-10 w-full py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition-colors shadow-lg shadow-sky-900/20 flex items-center justify-center gap-2">
                             Manage Venues <i data-lucide="arrow-right" class="w-4 h-4"></i>
                         </a>
                    </div>

                    <!-- Contact Details Card -->
                    <div class="p-6 bg-emerald-900/20 border border-emerald-500/30 rounded-2xl flex flex-col justify-between relative overflow-hidden group">
                         <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                         <div class="flex items-start gap-4 relative z-10 mb-4">
                             <div class="p-3 bg-emerald-500/20 rounded-xl flex-shrink-0">
                                 <i data-lucide="users" class="w-8 h-8 text-emerald-400"></i>
                             </div>
                             <div>
                                 <h2 class="text-xl font-bold text-white">Team Contacts</h2>
                                 <p class="text-slate-300 text-sm mt-1">Update your club's contact details and view the directory.</p>
                             </div>
                         </div>
                         <a href="contacts.php" class="relative z-10 w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl transition-colors shadow-lg shadow-emerald-900/20 flex items-center justify-center gap-2">
                             Manage Contacts <i data-lucide="arrow-right" class="w-4 h-4"></i>
                         </a>
                    </div>

                </div>

                <!-- NEW: Audit Log Summary -->
                <?php
                // Fetch recent logs
                $recent_logs = [];
                $log_sql = "SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 3";
                $log_res = $conn->query($log_sql);
                if ($log_res && $log_res->num_rows > 0) {
                    while($l = $log_res->fetch_assoc()) {
                        $recent_logs[] = $l;
                    }
                }
                ?>
                <?php if (!empty($recent_logs)): ?>
                    <div class="mb-8 p-6 glass-panel rounded-2xl border border-white/5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold flex items-center gap-2">
                                <i data-lucide="activity" class="w-5 h-5 text-emerald-400"></i> Recent Venue Updates
                            </h3>
                            <a href="audit_log.php" class="text-xs text-slate-400 hover:text-white transition-colors flex items-center gap-1">
                                View Full Log <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($recent_logs as $log): ?>
                                <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl border border-white/5">
                                    <div class="bg-emerald-500/10 p-2 rounded-lg mt-0.5">
                                        <i data-lucide="edit-3" class="w-4 h-4 text-emerald-400"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <div class="flex justify-between items-start">
                                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($log['club_name']); ?></p>
                                            <span class="text-[10px] text-slate-500 font-mono"><?php echo date('d M H:i', strtotime($log['timestamp'])); ?></span>
                                        </div>
                                        <p class="text-xs text-slate-400 truncate mt-1"><?php echo htmlspecialchars($log['change_details']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- STATISTICS BAR -->
                <?php
                // Fetch stats
                $prog_count = 0;
                $rep_count = 0;
                
                $sql_stats = "SELECT action_name, count FROM tracking_stats";
                $result_stats = $conn->query($sql_stats);
                
                if ($result_stats && $result_stats->num_rows > 0) {
                    while($row = $result_stats->fetch_assoc()) {
                        if($row['action_name'] == 'programme_generated') $prog_count = $row['count'];
                        if($row['action_name'] == 'report_generated') $rep_count = $row['count'];
                    }
                }
                ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div class="glass-panel p-4 rounded-xl flex items-center justify-between border border-white/5 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="bg-emerald-500/20 p-3 rounded-lg">
                                <i data-lucide="printer" class="w-6 h-6 text-emerald-400"></i>
                            </div>
                            <div>
                                <p class="text-slate-400 text-xs uppercase tracking-wider font-semibold">Smart Programmes Generated</p>
                                <p class="text-2xl font-bold text-white"><?php echo number_format($prog_count); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel p-4 rounded-xl flex items-center justify-between border border-white/5 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="bg-purple-500/20 p-3 rounded-lg">
                                <i data-lucide="bar-chart-2" class="w-6 h-6 text-purple-400"></i>
                            </div>
                            <div>
                                <p class="text-slate-400 text-xs uppercase tracking-wider font-semibold">Results Matcher Reports Generated</p>
                                <p class="text-2xl font-bold text-white"><?php echo number_format($rep_count); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-12">
                    <!-- ROW 1: Governance & Planning -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-sky-400">
                            <i data-lucide="landmark" class="w-5 h-5"></i> Governance & Info
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-white/5">
                            <a href="https://docs.google.com/document/d/1RkI13CvpiXTln3UioCIdhvs-aUEwHUZqyOOlcRfJI8A/edit?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group">
                                <div class="bg-sky-500/10 p-2 rounded-lg mr-4 group-hover:bg-sky-500/20">
                                    <i data-lucide="gavel" class="text-sky-500 w-5 h-5"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-sm font-medium truncate">League Rules 2026</p>
                                    <p class="text-xs text-slate-500 truncate">Official Constitution</p>
                                </div>
                                <i data-lucide="external-link" class="w-4 h-4 text-slate-600 flex-shrink-0"></i>
                            </a>
                            
                            <a href="https://docs.google.com/spreadsheets/d/1ihRDTmrKMc9VAsvqA3-TYJD53AARAJdT/edit?usp=drive_link&ouid=106844982787765338918&rtpof=true&sd=true" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group">
                                <div class="bg-sky-500/10 p-2 rounded-lg mr-4 group-hover:bg-sky-500/20">
                                    <i data-lucide="map" class="text-sky-500 w-5 h-5"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-sm font-medium truncate">Host Venue List</p>
                                    <p class="text-xs text-slate-500 truncate">Teams to update details</p>
                                </div>
                                <i data-lucide="external-link" class="w-4 h-4 text-slate-600 flex-shrink-0"></i>
                            </a>

                            <a href="https://docs.google.com/document/d/1-YlK_WXOpi_DG-KGR3JZTTTPxoGRW6rIB8ay4VKm1N0/edit?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group">
                                <div class="bg-sky-500/10 p-2 rounded-lg mr-4 group-hover:bg-sky-500/20">
                                    <i data-lucide="help-circle" class="text-sky-500 w-5 h-5"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-sm font-medium truncate">How-To Guide</p>
                                    <p class="text-xs text-slate-500 truncate">For new teams & volunteers</p>
                                </div>
                                <i data-lucide="external-link" class="w-4 h-4 text-slate-600 flex-shrink-0"></i>
                            </a>
                        </div>
                    </div>

                    <!-- ROW 2: Teamsheets & Results -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-emerald-400">
                            <i data-lucide="calculator" class="w-5 h-5"></i> Teamsheets & Results
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-white/5">
                            
                            <!-- Teamsheets Column -->
                            <div class="flex flex-col">
                                <div class="bg-emerald-900/20 px-4 py-3 text-xs font-bold text-emerald-400 uppercase tracking-wider border-b border-white/5">Teamsheets</div>
                                <div class="flex-grow flex flex-col">
                                    <a href="https://docs.google.com/spreadsheets/u/0/d/1hsoW_x12MH1B-qzGAjXUwcdtsK5h1jWDN1_qYBSluBI/copy" target="_blank" class="flex items-center p-3 hover:bg-white/5 border-b border-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="file-plus" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Google Sheets Version</p>
                                            <p class="text-xs text-slate-500">Full Featured Version</p>
                                        </div>
                                    </a>
                                    <a href="https://docs.google.com/spreadsheets/u/0/d/10urOBlt_49ZMCLPxQ9sjZw9vJKUTMh_R/edit" target="_blank" class="flex items-center p-3 hover:bg-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="file-down" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Excel Version</p>
                                            <p class="text-xs text-slate-500">Reduced Functionality</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Guides Column -->
                            <div class="flex flex-col">
                                <div class="bg-emerald-900/20 px-4 py-3 text-xs font-bold text-emerald-400 uppercase tracking-wider border-b border-white/5">Teamsheet Guides</div>
                                <div class="flex-grow flex flex-col">
                                    <a href="https://docs.google.com/spreadsheets/u/0/d/1hsoW_x12MH1B-qzGAjXUwcdtsK5h1jWDN1_qYBSluBI/copy" target="_blank" class="flex items-center p-3 hover:bg-white/5 border-b border-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="sheet" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Google Sheets Guide</p>
                                            <p class="text-xs text-slate-500">Step-by-Step Instructions</p>
                                        </div>
                                    </a>
                                    <a href="https://docs.google.com/spreadsheets/u/0/d/10urOBlt_49ZMCLPxQ9sjZw9vJKUTMh_R/edit" target="_blank" class="flex items-center p-3 hover:bg-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="file-spreadsheet" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Excel Guide</p>
                                            <p class="text-xs text-slate-500">Step-by-Step Instructions</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Programme Column -->
                            <div class="flex flex-col">
                                <div class="bg-emerald-900/20 px-4 py-3 text-xs font-bold text-emerald-400 uppercase tracking-wider border-b border-white/5">Gala Programme</div>
                                <div class="flex-grow flex flex-col">
                                    <a href="smartprogramme.html" target="_blank" class="flex items-center p-3 hover:bg-white/5 border-b border-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="printer" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Smart Programme Tool</p>
                                            <p class="text-xs text-slate-500">Generate a printable programme with names for your TMs</p>
                                        </div>
                                    </a>
                                    <a href="https://docs.google.com/document/d/1yRye4lhpNyeKlhrQ2ZkzmcxEYqEBm52T/edit?usp=drive_link&ouid=106844982787765338918&rtpof=true&sd=true" target="_blank" class="flex items-center p-3 hover:bg-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="file-text" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Manual Programme</p>
                                            <p class="text-xs text-slate-500">Manual Version - For automated see Smart Programme Tool</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Calculator Column -->
                            <div class="flex flex-col">
                                <div class="bg-emerald-900/20 px-4 py-3 text-xs font-bold text-emerald-400 uppercase tracking-wider border-b border-white/5">Results Calculator</div>
                                <div class="flex-grow flex flex-col">
                                    <a href="https://1drv.ms/x/c/7c197ed7ec71ffca/IQDK_3Hs134ZIIB8vRYCAAAAAfETmDjTVlWJiPf8iIyF0Gs?e=ivrYWT" target="_blank" class="flex items-center p-3 hover:bg-white/5 border-b border-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="download-cloud" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Results Calculator</p>
                                            <p class="text-xs text-slate-500">Main Software Download</p>
                                        </div>
                                    </a>
                                    <a href="Results-matcher.html" target="_blank" class="flex items-center p-3 hover:bg-white/5 border-b border-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="users" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Results Matcher</p>
                                            <p class="text-xs text-slate-500">Match Swimmers to Results automatically</p>
                                        </div>
                                    </a>
                                    <a href="https://docs.google.com/document/d/10CvL07WJMVqDPZJU7LXIhFinBAcUarDiF7jNj03fdb4/edit?usp=sharing" target="_blank" class="flex items-center p-3 hover:bg-white/5 border-b border-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="book-open" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Full User Guide</p>
                                            <p class="text-xs text-slate-500">Comprehensive instructions</p>
                                        </div>
                                    </a>
                                    <a href="https://docs.google.com/document/d/1ReJU7dmTqPgHe9ICvgy8jYD1mhccBx7g94E6izE5YLM/edit?usp=sharing" target="_blank" class="flex items-center p-3 hover:bg-white/5 transition-colors group h-full">
                                        <div class="bg-emerald-500/10 p-2 rounded-lg mr-3 flex-shrink-0"><i data-lucide="zap" class="text-emerald-500 w-5 h-5"></i></div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-sm font-medium">Poolside Quick Guide</p>
                                            <p class="text-xs text-slate-500">Essential cheat sheet</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ROW 3: Community & Support -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-amber-400">
                            <i data-lucide="users" class="w-5 h-5"></i> Community & Support
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden">
                            <a href="https://chat.whatsapp.com/KGftukKhKYHGWQgjsoemZz" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group">
                                <div class="bg-emerald-500/10 p-2 rounded-lg mr-4 group-hover:bg-emerald-500/20">
                                    <i data-lucide="message-circle" class="text-emerald-500 w-5 h-5"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-sm font-medium truncate">WhatsApp Community</p>
                                    <p class="text-xs text-slate-500 truncate">Join the representative group</p>
                                </div>
                                <i data-lucide="external-link" class="w-4 h-4 text-slate-600 flex-shrink-0"></i>
                            </a>
                        </div>
                    </div>

                    <!-- ROW 4: Helpful Documents -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-amber-400">
                            <i data-lucide="files" class="w-5 h-5"></i> Helpful Documents
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 divide-y md:divide-y-0 gap-x-px gap-y-px bg-white/5">
                            
                            <div class="bg-[#0f172a]/80 backdrop-blur-xl">
                                <a href="Officials Sign-in.html" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group h-full">
                                    <div class="bg-amber-500/10 p-2 rounded-lg mr-3 group-hover:bg-amber-500/20 flex-shrink-0">
                                        <i data-lucide="user-check" class="text-amber-500 w-5 h-5"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm font-medium truncate">Officials Sign-in</p>
                                        <p class="text-xs text-slate-500 truncate">Printable Sign-in Form</p>
                                    </div>
                                </a>
                            </div>

                            <div class="bg-[#0f172a]/80 backdrop-blur-xl">
                                <a href="spectator-programme.html" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group h-full">
                                    <div class="bg-amber-500/10 p-2 rounded-lg mr-3 group-hover:bg-amber-500/20 flex-shrink-0">
                                        <i data-lucide="file-text" class="text-amber-500 w-5 h-5"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm font-medium truncate">Spectator Programme</p>
                                        <p class="text-xs text-slate-500 truncate">Printable Event List</p>
                                    </div>
                                </a>
                            </div>

                            <div class="bg-[#0f172a]/80 backdrop-blur-xl">
                                <a href="https://drive.google.com/file/d/1rC1xdY6Y2hxoyDJAFdx_P24we9tVvq0P/view?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group h-full">
                                    <div class="bg-amber-500/10 p-2 rounded-lg mr-3 group-hover:bg-amber-500/20 flex-shrink-0">
                                        <i data-lucide="alert-triangle" class="text-amber-500 w-5 h-5"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm font-medium truncate">DQ Report Form</p>
                                        <p class="text-xs text-slate-500 truncate">PDF Printout</p>
                                    </div>
                                </a>
                            </div>

                            <div class="bg-[#0f172a]/80 backdrop-blur-xl">
                                <a href="Timekeeper-sheets.html" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group h-full">
                                    <div class="bg-amber-500/10 p-2 rounded-lg mr-3 group-hover:bg-amber-500/20 flex-shrink-0">
                                        <i data-lucide="clock" class="text-amber-500 w-5 h-5"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm font-medium truncate">Timekeeper Sheet</p>
                                        <p class="text-xs text-slate-500 truncate">Printable Form Tool</p>
                                    </div>
                                </a>
                            </div>

                            <div class="bg-[#0f172a]/80 backdrop-blur-xl">
                                <a href="ChiefTKSlips.html" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group h-full">
                                    <div class="bg-amber-500/10 p-2 rounded-lg mr-3 group-hover:bg-amber-500/20 flex-shrink-0">
                                        <i data-lucide="clipboard" class="text-amber-500 w-5 h-5"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm font-medium truncate">Chief Timekeeper Slips</p>
                                        <p class="text-xs text-slate-500 truncate">Printable Slips for Rounds & Finals</p>
                                    </div>
                                </a>
                            </div>

                            <div class="bg-[#0f172a]/80 backdrop-blur-xl">
                                <a href="https://docs.google.com/document/d/1T3Hc26p8ftaMS05G-lfinmExE_7UGnB3pG6UusN8xKA/edit?usp=sharing" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group h-full">
                                    <div class="bg-amber-500/10 p-2 rounded-lg mr-3 group-hover:bg-amber-500/20 flex-shrink-0">
                                        <i data-lucide="mic" class="text-amber-500 w-5 h-5"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm font-medium truncate">Announcers Guide</p>
                                        <p class="text-xs text-slate-500 truncate">Script for volunteers</p>
                                    </div>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Host Team Checklist -->
                <div class="mt-12 glass-panel p-8 rounded-3xl border border-white/5">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="bg-indigo-500/10 p-2 rounded-lg">
                            <i data-lucide="list-checks" class="text-indigo-400 w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Host Team Checklist</h2>
                            <p class="text-slate-400 text-sm">Essential items for gala day preparation. Your progress is saved automatically.</p>
                        </div>
                        <button onclick="resetChecklist()" class="ml-auto text-xs text-slate-500 hover:text-red-400 transition-colors">Reset</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Rules -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="rules-printed">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">League Rules</span>
                                    <span class="text-xs text-slate-500 block">Copy printed for referee</span>
                                </div>
                            </label>
                            <a href="https://docs.google.com/document/d/1RkI13CvpiXTln3UioCIdhvs-aUEwHUZqyOOlcRfJI8A/edit?usp=drive_link" target="_blank" class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                <i data-lucide="external-link" class="w-3 h-3"></i> Print Rules
                            </a>
                        </div>

                        <!-- Teamsheets -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="teamsheets-marked">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">Teamsheets</span>
                                    <span class="text-xs text-slate-500 block">You will have received the teamsheets by email. Check and flag any time limit issues to the referee.</span>
                                </div>
                            </label>
                        </div>

                        <!-- Results Calculator -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="results-calc">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">Results Calculator</span>
                                    <span class="text-xs text-slate-500 block">Ready on Excel + Guide</span>
                                </div>
                            </label>
                            <div class="flex gap-2 mt-3">
                                <a href="https://1drv.ms/x/c/7c197ed7ec71ffca/IQDK_3Hs134ZIIB8vRYCAAAAAfETmDjTVlWJiPf8iIyF0Gs?e=ivrYWT" target="_blank" class="flex-1 flex items-center justify-center py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-1">
                                    <i data-lucide="download" class="w-3 h-3"></i> Download
                                </a>
                                <a href="https://docs.google.com/document/d/1ReJU7dmTqPgHe9ICvgy8jYD1mhccBx7g94E6izE5YLM/edit?usp=sharing" target="_blank" class="flex-1 flex items-center justify-center py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-1">
                                    <i data-lucide="book" class="w-3 h-3"></i> Guide
                                </a>
                            </div>
                        </div>

                        <!-- Officials Sign-In -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="officials-signin">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">Officials Sign-In Sheet</span>
                                    <span class="text-xs text-slate-500 block">Printed for officials</span>
                                </div>
                            </label>
                            <a href="Officials Sign-in.html" target="_blank" class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                <i data-lucide="file-text" class="w-3 h-3"></i> Print Form
                            </a>
                        </div>

                        <!-- DQ Reports -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="dq-forms">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">DQ Report Forms</span>
                                    <span class="text-xs text-slate-500 block">Printed for officials</span>
                                </div>
                            </label>
                            <a href="https://drive.google.com/file/d/1rC1xdY6Y2hxoyDJAFdx_P24we9tVvq0P/view?usp=drive_link" target="_blank" class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                <i data-lucide="file-warning" class="w-3 h-3"></i> Print Form
                            </a>
                        </div>

                        <!-- Timekeeper Sheets -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="timekeeper-sheets">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">Timekeeper Sheets</span>
                                    <span class="text-xs text-slate-500 block">Print 4x(Rounds) or 6-8x(Finals)</span>
                                </div>
                            </label>
                            <a href="Timekeeper-sheets.html" target="_blank" class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                <i data-lucide="clock" class="w-3 h-3"></i> Generate Sheets
                            </a>
                        </div>

                        <!-- Chief TK Slips -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="chief-tk-slips">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">Chief Timekeeper Slips</span>
                                    <span class="text-xs text-slate-500 block">53 Required</span>
                                </div>
                            </label>
                            <a href="ChiefTKSlips.html" target="_blank" class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                <i data-lucide="clipboard-list" class="w-3 h-3"></i> Generate Slips
                            </a>
                        </div>

                        <!-- Blank Programmes -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="blank-programmes">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">Blank Programmes</span>
                                    <span class="text-xs text-slate-500 block">Printed for Ref/Judges</span>
                                </div>
                            </label>
                            <a href="spectator-programme.html" target="_blank" class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                <i data-lucide="printer" class="w-3 h-3"></i> Print Programme
                            </a>
                        </div>

                        <!-- Announcers Guide -->
                        <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all group relative">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500" data-id="announcers-guide">
                                <div>
                                    <span class="text-slate-200 font-medium block mb-1">Announcers Guide</span>
                                    <span class="text-xs text-slate-500 block">Script for volunteers</span>
                                </div>
                            </label>
                            <a href="https://docs.google.com/document/d/1T3Hc26p8ftaMS05G-lfinmExE_7UGnB3pG6UusN8xKA/edit?usp=sharing" target="_blank" class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                <i data-lucide="mic" class="w-3 h-3"></i> View Script
                            </a>
                        </div>

                    </div>
                </div>

                <div class="mt-12 glass-panel p-6 rounded-2xl text-center border border-white/5">
                    <p class="text-slate-400 text-sm leading-relaxed">
                        <i data-lucide="help-circle" class="w-4 h-4 inline mr-1 text-sky-500 relative -top-0.5"></i>
                        You can reach me (Lewis) via the WhatsApp link above, email at <a href="mailto:lewisplume@gmail.com" class="text-sky-400 hover:text-sky-300 transition-colors font-medium">lewisplume@gmail.com</a>, or if you have contacts interested in joining, please provide them with the league email: <a href="mailto:admin@thecotswoldleague.co.uk" class="text-sky-400 hover:text-sky-300 transition-colors font-medium">admin@thecotswoldleague.co.uk</a>.
                    </p>
                </div>
            </div>
        <?php endif; ?>
        
        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();


        
        <?php if (isset($_SESSION['logged_in'])): ?>
        // Checklist Logic
        function initChecklist() {
            const checklistItems = document.querySelectorAll('.checklist-item');
            checklistItems.forEach(item => {
                const id = item.dataset.id;
                const savedState = localStorage.getItem('checklist_' + id);
                if (savedState === 'true') {
                    item.checked = true;
                }
                item.addEventListener('change', (e) => {
                    localStorage.setItem('checklist_' + id, e.target.checked);
                });
            });
        }

        function resetChecklist() {
            if(confirm('Are you sure you want to clear all checkboxes?')) {
                const checklistItems = document.querySelectorAll('.checklist-item');
                checklistItems.forEach(item => {
                    item.checked = false;
                    localStorage.removeItem('checklist_' + item.dataset.id);
                });
            }
        }
        
        window.onload = function() {
            initChecklist();
        }
        <?php endif; ?>
    </script>
</body>
</html>