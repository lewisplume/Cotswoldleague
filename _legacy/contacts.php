<?php
require_once __DIR__ . '/../security_headers.php';
cotswold_secure_session_start();
include 'db.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: contacts.php");
    exit;
}

// Variables for alerts
$success_msg = '';
$error_msg = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $club_id = $_POST['club_id'] ?? '';
    $pin = $_POST['pin'] ?? '';

    if ($club_id && $pin) {
        $stmt = $conn->prepare("SELECT id, club_name FROM club_contacts WHERE club_id = ? AND access_pin = ?");
        $stmt->bind_param("is", $club_id, $pin);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $_SESSION['club_logged_in'] = true;
            $_SESSION['club_id'] = $club_id;
            $_SESSION['club_name'] = $row['club_name'];
            
            header("Location: contacts.php");
            exit;
        } else {
            $error_msg = "Invalid Club or PIN. Please try again.";
        }
        $stmt->close();
    } else {
        $error_msg = "Please select a club and enter your PIN.";
    }
}

// Check Login State
$is_logged_in = isset($_SESSION['club_logged_in']) && $_SESSION['club_logged_in'] === true;
$current_club_id = $_SESSION['club_id'] ?? 0;

// HANDLE AUTHENTICATED ACTIONS
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update Contacts
    if (isset($_POST['action']) && $_POST['action'] === 'update_contacts') {
        $c1_name = $_POST['c1_name'] ?? '';
        $c1_email = $_POST['c1_email'] ?? '';
        $c2_name = $_POST['c2_name'] ?? '';
        $c2_email = $_POST['c2_email'] ?? '';
        $c3_name = $_POST['c3_name'] ?? '';
        $c3_email = $_POST['c3_email'] ?? '';

        $stmt = $conn->prepare("UPDATE club_contacts SET contact1_name=?, contact1_email=?, contact2_name=?, contact2_email=?, contact3_name=?, contact3_email=? WHERE club_id=?");
        $stmt->bind_param("ssssssi", $c1_name, $c1_email, $c2_name, $c2_email, $c3_name, $c3_email, $current_club_id);
        
        if ($stmt->execute()) {
            $success_msg = "Contact details updated successfully.";
        } else {
            $error_msg = "Failed to update details. Please try again.";
        }
        $stmt->close();
    }

    // Change PIN
    if (isset($_POST['action']) && $_POST['action'] === 'change_pin') {
        $new_pin = $_POST['new_pin'] ?? '';
        
        if (preg_match('/^\d{4}$/', $new_pin)) {
            $stmt = $conn->prepare("UPDATE club_contacts SET access_pin=? WHERE club_id=?");
            $stmt->bind_param("si", $new_pin, $current_club_id);
            if ($stmt->execute()) {
                $success_msg = "Security PIN changed successfully.";
            } else {
                $error_msg = "Failed to update PIN.";
            }
            $stmt->close();
        } else {
            $error_msg = "PIN must be exactly 4 digits.";
        }
    }
}

// Fetch Data for View
$my_club_data = null;
$directory_data = [];
$clubs_dropdown = [];

if ($is_logged_in) {
    // Fetch My Club Data (Joined with clubs for Logo)
    $stmt = $conn->prepare("SELECT cc.*, c.logo FROM club_contacts cc LEFT JOIN clubs c ON cc.club_id = c.id WHERE cc.club_id = ?");
    $stmt->bind_param("i", $current_club_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $my_club_data = $res->fetch_assoc();
    }
    $stmt->close();

    // Fetch Directory Data
    $sql = "SELECT cc.*, c.logo, c.name as real_club_name FROM club_contacts cc LEFT JOIN clubs c ON cc.club_id = c.id ORDER BY c.name ASC";
    $dir_res = $conn->query($sql);
    if ($dir_res) {
        while ($d = $dir_res->fetch_assoc()) {
            $directory_data[] = $d;
        }
    }
} else {
    // Populate Dropdown for Login
    $sql = "SELECT club_id, club_name FROM club_contacts ORDER BY club_name ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clubs_dropdown[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Contact Portal | Cotswold League</title>
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

        .form-label { display: block; font-size: 0.75rem; line-height: 1rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <!-- NAVBAR -->
    <?php include 'nav.php'; ?>

    <!-- CONTENT -->
    <div class="flex-grow flex flex-col items-center p-4 sm:p-6 lg:p-8">

        <?php if (!$is_logged_in): ?>
            <!-- LOGIN SCREEN -->
            <div class="w-full max-w-md mt-10">
                <div class="glass-panel p-8 rounded-3xl shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-sky-500 to-emerald-500"></div>
                    
                    <div class="text-center mb-8">
                        <div class="bg-slate-800 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/10 shadow-inner">
                            <i data-lucide="users" class="w-8 h-8 text-sky-400"></i>
                        </div>
                        <h1 class="text-2xl font-bold mb-2">Team Portal</h1>
                        <p class="text-slate-400 text-sm">Secure access for club representatives.</p>
                    </div>

                    <?php if ($error_msg): ?>
                        <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl text-sm mb-6 flex items-start gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <p><?php echo htmlspecialchars($error_msg); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="login">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Select Your Club</label>
                            <div class="relative">
                                <select name="club_id" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-sky-500 transition-all appearance-none cursor-pointer hover:border-slate-600" required>
                                    <option value="" disabled selected>Choose club...</option>
                                    <?php foreach ($clubs_dropdown as $club): ?>
                                        <option value="<?php echo $club['club_id']; ?>"><?php echo htmlspecialchars($club['club_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-4 h-4 text-slate-500 pointer-events-none"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Access PIN</label>
                            <input type="password" name="pin" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-sky-500 transition-all text-center tracking-[0.5em] font-mono text-lg placeholder-slate-700" placeholder="••••" maxlength="4" pattern="\d{4}" inputmode="numeric" required>
                        </div>
                        <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-sky-900/20 flex items-center justify-center gap-2 mt-2 group">
                            <span>Login</span> <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- DASHBOARD -->
            <div class="w-full max-w-7xl space-y-8 animate-fade-in-up">
                
                <!-- HEADER CARD -->
                <div class="glass-panel p-6 rounded-3xl flex flex-col md:flex-row items-center gap-6 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-500/5 to-transparent pointer-events-none"></div>
                    
                    <div class="w-20 h-20 bg-white rounded-2xl p-2 shadow-lg flex-shrink-0">
                        <?php if($my_club_data['logo']): ?>
                            <img src="images/Teams/<?php echo htmlspecialchars($my_club_data['logo']); ?>" alt="Club Logo" class="w-full h-full object-contain">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-300">
                                <i data-lucide="image" class="w-8 h-8"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center md:text-left flex-grow">
                        <h1 class="text-3xl font-bold text-white mb-1"><?php echo htmlspecialchars($my_club_data['club_name']); ?></h1>
                        <p class="text-sky-400 text-sm font-medium">Team Contact Portal</p>
                    </div>

                    <?php if ($success_msg): ?>
                        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-2 rounded-xl text-sm flex items-center gap-2 animate-pulse">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <?php echo htmlspecialchars($success_msg); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-2 rounded-xl text-sm flex items-center gap-2 animate-pulse">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- LEFT COL: EDIT CONTACTS -->
                    <div class="lg:col-span-2 space-y-8">
                        <form method="POST" class="glass-panel p-6 rounded-2xl">
                            <input type="hidden" name="action" value="update_contacts">
                            <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                <i data-lucide="edit-3" class="w-5 h-5 text-sky-400"></i> Edit Team Contacts
                            </h2>
                            
                            <div class="space-y-6">
                                <!-- Contact 1 -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Contact 1 Name</label>
                                        <input type="text" name="c1_name" value="<?php echo htmlspecialchars($my_club_data['contact1_name']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                                    </div>
                                    <div>
                                        <label class="form-label">Contact 1 Email</label>
                                        <input type="email" name="c1_email" value="<?php echo htmlspecialchars($my_club_data['contact1_email']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                                    </div>
                                </div>
                                <hr class="border-white/5">
                                <!-- Contact 2 -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Contact 2 Name</label>
                                        <input type="text" name="c2_name" value="<?php echo htmlspecialchars($my_club_data['contact2_name']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                                    </div>
                                    <div>
                                        <label class="form-label">Contact 2 Email</label>
                                        <input type="email" name="c2_email" value="<?php echo htmlspecialchars($my_club_data['contact2_email']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                                    </div>
                                </div>
                                <hr class="border-white/5">
                                <!-- Contact 3 -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Contact 3 Name</label>
                                        <input type="text" name="c3_name" value="<?php echo htmlspecialchars($my_club_data['contact3_name']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                                    </div>
                                    <div>
                                        <label class="form-label">Contact 3 Email</label>
                                        <input type="email" name="c3_email" value="<?php echo htmlspecialchars($my_club_data['contact3_email']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white font-bold py-2 px-6 rounded-xl transition-all shadow-lg shadow-sky-900/20">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- RIGHT COL: SECURITY & HELP -->
                    <div class="space-y-8">
                        <!-- Security -->
                        <form method="POST" class="glass-panel p-6 rounded-2xl border-orange-500/10">
                            <input type="hidden" name="action" value="change_pin">
                            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                <i data-lucide="lock" class="w-5 h-5 text-orange-400"></i> Security
                            </h2>
                            <p class="text-xs text-slate-400 mb-4">Update your 4-digit access PIN. Keep this secure.</p>
                            
                            <div class="mb-4">
                                <label class="form-label">New PIN</label>
                                <input type="text" name="new_pin" placeholder="0000" maxlength="4" pattern="\d{4}" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-center tracking-[0.3em] font-mono" required>
                            </div>
                            <button type="submit" class="w-full bg-orange-600/80 hover:bg-orange-500 text-white font-bold py-2 rounded-xl transition-all">
                                Update PIN
                            </button>
                        </form>

                        <!-- Info -->
                        <div class="bg-slate-800/50 p-6 rounded-2xl border border-white/5 text-center">
                            <div class="bg-indigo-500/10 w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="info" class="w-6 h-6 text-indigo-400"></i>
                            </div>
                            <h3 class="font-bold text-white mb-1">Need Help?</h3>
                            <p class="text-xs text-slate-400">Contact the League Secretary for assistance with your account.</p>
                        </div>
                    </div>

                </div>

                <!-- DIRECTORY SECTION -->
                <div class="glass-panel rounded-3xl overflow-hidden border border-white/5 mb-20">
                    <div class="p-6 border-b border-white/5 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div>
                            <h2 class="text-xl font-bold flex items-center gap-2">
                                <i data-lucide="book-open" class="w-6 h-6 text-emerald-400"></i> League Directory
                            </h2>
                            <p class="text-slate-400 text-xs mt-1">Contact details for all league clubs.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="emailSelected()" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-2 shadow-lg shadow-emerald-900/20">
                                <i data-lucide="mail" class="w-4 h-4"></i> Email Selected
                            </button>
                            <button onclick="copyEmails()" class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-2">
                                <i data-lucide="copy" class="w-4 h-4"></i> Copy List
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900/50 text-slate-400 text-xs uppercase tracking-wider border-b border-white/5">
                                    <th class="p-4 w-12 text-center">
                                        <input type="checkbox" id="selectAll" class="rounded bg-slate-800 border-slate-600 text-sky-500 focus:ring-0 focus:ring-offset-0 cursor-pointer w-4 h-4">
                                    </th>
                                    <th class="p-4">Club</th>
                                    <th class="p-4">Contact 1</th>
                                    <th class="p-4">Contact 2</th>
                                    <th class="p-4">Contact 3</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-sm">
                                <?php foreach ($directory_data as $row): ?>
                                    <tr class="hover:bg-white/5 transition-colors group">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" onchange="toggleRow(this)" class="row-checkbox rounded bg-slate-800 border-slate-600 text-sky-500 focus:ring-0 focus:ring-offset-0 cursor-pointer w-4 h-4">
                                        </td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-white/10 rounded-lg p-1 flex-shrink-0">
                                                    <?php if($row['logo']): ?>
                                                        <img src="images/Teams/<?php echo htmlspecialchars($row['logo']); ?>" class="w-full h-full object-contain">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center"><i data-lucide="shield" class="w-4 h-4 text-slate-500"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="font-bold text-white"><?php echo htmlspecialchars($row['real_club_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <?php if($row['contact1_name']): ?>
                                                <div class="font-medium text-slate-200"><?php echo htmlspecialchars($row['contact1_name']); ?></div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <?php if($row['contact1_email']): ?>
                                                        <input type="checkbox" class="email-checkbox rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-0 focus:ring-offset-0 cursor-pointer w-3.5 h-3.5" value="<?php echo htmlspecialchars($row['contact1_email']); ?>">
                                                        <a href="mailto:<?php echo htmlspecialchars($row['contact1_email']); ?>" class="text-sky-400 text-xs hover:underline"><?php echo htmlspecialchars($row['contact1_email']); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-600">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if($row['contact2_name']): ?>
                                                <div class="font-medium text-slate-200"><?php echo htmlspecialchars($row['contact2_name']); ?></div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <?php if($row['contact2_email']): ?>
                                                        <input type="checkbox" class="email-checkbox rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-0 focus:ring-offset-0 cursor-pointer w-3.5 h-3.5" value="<?php echo htmlspecialchars($row['contact2_email']); ?>">
                                                        <a href="mailto:<?php echo htmlspecialchars($row['contact2_email']); ?>" class="text-sky-400 text-xs hover:underline"><?php echo htmlspecialchars($row['contact2_email']); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-600">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if($row['contact3_name']): ?>
                                                <div class="font-medium text-slate-200"><?php echo htmlspecialchars($row['contact3_name']); ?></div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <?php if($row['contact3_email']): ?>
                                                        <input type="checkbox" class="email-checkbox rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-0 focus:ring-offset-0 cursor-pointer w-3.5 h-3.5" value="<?php echo htmlspecialchars($row['contact3_email']); ?>">
                                                        <a href="mailto:<?php echo htmlspecialchars($row['contact3_email']); ?>" class="text-sky-400 text-xs hover:underline"><?php echo htmlspecialchars($row['contact3_email']); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-600">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>

    <script>
        lucide.createIcons();

        // Checkbox Logic
        function toggleAll(source) {
            // Toggle all row checkboxes
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            rowCheckboxes.forEach(cb => cb.checked = source.checked);
            
            // Toggle all email checkboxes
            const emailCheckboxes = document.querySelectorAll('.email-checkbox');
            emailCheckboxes.forEach(cb => cb.checked = source.checked);
        }

        function toggleRow(source) {
            // Find the parent tr
            const row = source.closest('tr');
            // Find all email checkboxes within this row
            const emailCheckboxes = row.querySelectorAll('.email-checkbox');
            emailCheckboxes.forEach(cb => cb.checked = source.checked);
            
            updateMasterCheckbox();
        }

        // Add event listener to selectAll independently
        const selectAll = document.getElementById('selectAll');
        if(selectAll) {
            selectAll.addEventListener('change', (e) => toggleAll(e.target));
        }

        // Update master checkbox based on sub-checkboxes (optional polish)
        function updateMasterCheckbox() {
            // Logic to uncheck master if not all are checked could go here
            // simplified: if any row is unchecked, uncheck master
        }

        function getSelectedEmails() {
            let emails = [];
            const checkboxes = document.querySelectorAll('.email-checkbox');
            checkboxes.forEach(cb => {
                if(cb.checked && cb.value) {
                    if(cb.value.trim()) emails.push(cb.value.trim());
                }
            });
            // Remove duplicates
            return [...new Set(emails)];
        }

        function emailSelected() {
            const emails = getSelectedEmails();
            if(emails.length === 0) {
                alert('Please select at least one club to email.');
                return;
            }
            const mailtoLink = `mailto:?bcc=${emails.join(';')}`;
            window.location.href = mailtoLink;
        }

        function copyEmails() {
            const emails = getSelectedEmails();
            if(emails.length === 0) {
                alert('Please select at least one club to copy.');
                return;
            }
            const emailString = emails.join('; ');
            navigator.clipboard.writeText(emailString).then(() => {
                alert('Emails copied to clipboard: ' + emails.length + ' addresses.');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
    </script>
</body>
</html>
