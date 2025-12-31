<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Club Login</title>
    <link rel="icon" href="images/league-logo.png" type="image/png">
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
        <!-- LOGIN SCREEN -->
        <div id="loginScreen" class="max-w-md w-full text-center py-12">
            <div class="admin-card p-8 rounded-3xl shadow-2xl backdrop-blur-xl">
                <h1 class="text-2xl font-bold mb-2 uppercase tracking-tight">Club Rep Login</h1>
                <p class="text-slate-400 text-sm mb-6 leading-relaxed">Enter the league password to access official resources and Google Drive folders. Contact your club representative if you need access. Or contact Lewis.</p>
                <div class="space-y-4">
                    <input type="password" id="passwordInput" class="w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600" placeholder="Enter Password" onkeydown="if(event.key === 'Enter') checkPassword()">
                    <button onclick="checkPassword()" class="w-full swim-gradient text-white font-bold py-3 rounded-xl hover:opacity-90 transition-opacity shadow-lg">Login</button>
                    <p id="errorMessage" class="text-red-400 text-xs font-medium hidden">Incorrect password. Please try again.</p>
                </div>
            </div>
        </div>

        <!-- PROTECTED CONTENT -->
        <div id="protectedContent" class="hidden w-full max-w-7xl my-8 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4 text-center md:text-left">
                <div>
                    <h1 class="text-3xl font-bold">Representative <span class="text-sky-500">Portal</span></h1>
                    <p class="text-slate-500 text-xs uppercase tracking-widest mt-1">Official League Resources 2026</p>
                </div>
                <button onclick="logout()" class="bg-slate-800 hover:bg-red-500/10 hover:text-red-400 border border-slate-700 px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition-all">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                </button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- COLUMN 1: Governance & Planning -->
                <div class="space-y-6">
                    <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-sky-400">
                        <i data-lucide="landmark" class="w-5 h-5"></i> Governance & Info
                    </h2>
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <a href="https://docs.google.com/document/d/1RkI13CvpiXTln3UioCIdhvs-aUEwHUZqyOOlcRfJI8A/edit?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-sky-500/10 p-2 rounded-lg mr-4 group-hover:bg-sky-500/20">
                                <i data-lucide="gavel" class="text-sky-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">League Rules 2026</p>
                                <p class="text-xs text-slate-500">Official Constitution</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        
                        <a href="https://docs.google.com/spreadsheets/d/1ihRDTmrKMc9VAsvqA3-TYJD53AARAJdT/edit?usp=drive_link&ouid=106844982787765338918&rtpof=true&sd=true" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-sky-500/10 p-2 rounded-lg mr-4 group-hover:bg-sky-500/20">
                                <i data-lucide="map" class="text-sky-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Host Venue List</p>
                                <p class="text-xs text-slate-500">Teams to update details</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>

                        <a href="https://docs.google.com/document/d/1-YlK_WXOpi_DG-KGR3JZTTTPxoGRW6rIB8ay4VKm1N0/edit?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-sky-500/10 p-2 rounded-lg mr-4 group-hover:bg-sky-500/20">
                                <i data-lucide="help-circle" class="text-sky-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">How-To Guide</p>
                                <p class="text-xs text-slate-500">For new teams & volunteers</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                    </div>
                </div>

                <!-- COLUMN 2: Teamsheets & Results -->
                <div class="space-y-6">
                    <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-emerald-400">
                        <i data-lucide="calculator" class="w-5 h-5"></i> Teamsheets & Results
                    </h2>
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <div class="bg-emerald-900/20 px-4 py-2 text-xs font-bold text-emerald-400 uppercase tracking-wider">Direct Teamsheets</div>
                        <a href="https://docs.google.com/spreadsheets/u/0/d/1hsoW_x12MH1B-qzGAjXUwcdtsK5h1jWDN1_qYBSluBI/copy" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-emerald-500/10 p-2 rounded-lg mr-4"><i data-lucide="file-plus" class="text-emerald-500 w-5 h-5"></i></div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Google Sheets Version</p>
                                <p class="text-xs text-slate-500">Direct Link (Create Copy)</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        <a href="https://docs.google.com/spreadsheets/u/0/d/10urOBlt_49ZMCLPxQ9sjZw9vJKUTMh_R/edit" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-emerald-500/10 p-2 rounded-lg mr-4"><i data-lucide="file-down" class="text-emerald-500 w-5 h-5"></i></div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Excel Version</p>
                                <p class="text-xs text-slate-500">Direct Link (Stripped Version)</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>

                        <div class="bg-emerald-900/20 px-4 py-2 text-xs font-bold text-emerald-400 uppercase tracking-wider mt-2">Teamsheet Guides</div>
                        <a href="https://docs.google.com/spreadsheets/u/0/d/1hsoW_x12MH1B-qzGAjXUwcdtsK5h1jWDN1_qYBSluBI/copy" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-emerald-500/10 p-2 rounded-lg mr-4"><i data-lucide="sheet" class="text-emerald-500 w-5 h-5"></i></div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Google Sheets Guide</p>
                                <p class="text-xs text-slate-500">Step-by-Step Instructions</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        <a href="https://docs.google.com/spreadsheets/u/0/d/10urOBlt_49ZMCLPxQ9sjZw9vJKUTMh_R/edit" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-emerald-500/10 p-2 rounded-lg mr-4"><i data-lucide="file-spreadsheet" class="text-emerald-500 w-5 h-5"></i></div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Excel Guide (Stripped)</p>
                                <p class="text-xs text-slate-500">Step-by-Step Instructions</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>

                        <div class="bg-emerald-900/20 px-4 py-2 text-xs font-bold text-emerald-400 uppercase tracking-wider mt-2">Results Calculator</div>
                        <a href="https://drive.google.com/file/d/1OcotXLlR8aUAIsVUk8vK-vyKYaO8XnJ3/view?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-emerald-500/10 p-2 rounded-lg mr-4"><i data-lucide="download-cloud" class="text-emerald-500 w-5 h-5"></i></div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Calculator MASTER.zip</p>
                                <p class="text-xs text-slate-500">Main Software Download</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        <a href="https://docs.google.com/document/d/11rLnUl9JXNNNdJPYAGrBO9ikds5XRkFh/view?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group">
                            <div class="bg-emerald-500/10 p-2 rounded-lg mr-4"><i data-lucide="zap" class="text-emerald-500 w-5 h-5"></i></div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Poolside Quick Guide</p>
                                <p class="text-xs text-slate-500">Essential cheat sheet</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                    </div>
                </div>

                <!-- COLUMN 3: Gala Day Docs & Community -->
                <div class="space-y-6">
                    <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-amber-400">
                        <i data-lucide="users" class="w-5 h-5"></i> Community & Support
                    </h2>
                    <div class="glass-panel rounded-2xl overflow-hidden mb-6">
                        <a href="https://chat.whatsapp.com/KGftukKhKYHGWQgjsoemZz" target="_blank" class="flex items-center p-4 hover:bg-white/5 transition-colors group">
                            <div class="bg-emerald-500/10 p-2 rounded-lg mr-4 group-hover:bg-emerald-500/20">
                                <i data-lucide="message-circle" class="text-emerald-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">WhatsApp Community</p>
                                <p class="text-xs text-slate-500">Join the representative group</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                    </div>

                    <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-amber-400">
                        <i data-lucide="files" class="w-5 h-5"></i> Helpful Documents
                    </h2>
                    <div class="glass-panel rounded-2xl overflow-hidden">
                        <a href="https://docs.google.com/document/d/1yRye4lhpNyeKlhrQ2ZkzmcxEYqEBm52T/edit?usp=drive_link&ouid=106844982787765338918&rtpof=true&sd=true" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-amber-500/10 p-2 rounded-lg mr-4 group-hover:bg-amber-500/20">
                                <i data-lucide="file-text" class="text-amber-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Coach/TM Programme</p>
                                <p class="text-xs text-slate-500">Word Document</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        <a href="https://drive.google.com/file/d/1-jSVYJXAFnE5IbkbKzUMkQOWOxUcTSw-/view?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-amber-500/10 p-2 rounded-lg mr-4 group-hover:bg-amber-500/20">
                                <i data-lucide="alert-triangle" class="text-amber-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">DQ Report Form</p>
                                <p class="text-xs text-slate-500">PDF Printout</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        <a href="https://drive.google.com/file/d/11rLnUl9JXNNNdJPYAGrBO9ikds5XRkFh/view?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-amber-500/10 p-2 rounded-lg mr-4 group-hover:bg-amber-500/20">
                                <i data-lucide="clock" class="text-amber-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Timekeeper Sheet</p>
                                <p class="text-xs text-slate-500">PDF Printout</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        <a href="https://drive.google.com/file/d/1-k3n51SVyMO6nYI0nIZUqs7j1e6CdZ45/view?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-amber-500/10 p-2 rounded-lg mr-4 group-hover:bg-amber-500/20">
                                <i data-lucide="clipboard" class="text-amber-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Chief TK Slips</p>
                                <p class="text-xs text-slate-500">PDF Printout</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                        <a href="https://drive.google.com/file/d/1kEluz8_lO-xkq9H3n3qViDtkbcoTQx2s/view?usp=drive_link" target="_blank" class="flex items-center p-4 hover:bg-white/5 border-b border-white/5 transition-colors group">
                            <div class="bg-amber-500/10 p-2 rounded-lg mr-4 group-hover:bg-amber-500/20">
                                <i data-lucide="columns" class="text-amber-500 w-5 h-5"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-medium">Chief TK Slips (6 Lane)</p>
                                <p class="text-xs text-slate-500">For larger pools</p>
                            </div>
                            <i data-lucide="external-link" class="w-4 h-4 text-slate-600"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();

        // Mobile Menu Toggle Logic
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

        const LEAGUE_PASSWORD = "Cotswold2026Galas"; 
        function checkPassword() {
            const input = document.getElementById('passwordInput').value;
            if (input === LEAGUE_PASSWORD) {
                document.getElementById('loginScreen').classList.add('hidden');
                document.getElementById('protectedContent').classList.remove('hidden');
                sessionStorage.setItem('isLoggedIn', 'true');
                lucide.createIcons();
            } else {
                document.getElementById('errorMessage').classList.remove('hidden');
            }
        }

        function logout() { 
            sessionStorage.removeItem('isLoggedIn'); 
            window.location.reload(); 
        }

        window.onload = function() {
            if (sessionStorage.getItem('isLoggedIn') === 'true') {
                document.getElementById('loginScreen').classList.add('hidden');
                document.getElementById('protectedContent').classList.remove('hidden');
                lucide.createIcons();
            }
        }
    </script>
</body>
</html>