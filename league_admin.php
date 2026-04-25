<?php
session_start();
include 'db.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: league_admin.php");
    exit;
}

// Handle Login
$login_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['super_password'])) {
    if ($_POST['super_password'] === SUPER_ADMIN_PASSWORD) {
        $_SESSION['super_admin_logged_in'] = true;
        header("Location: league_admin.php");
        exit;
    } else {
        $login_error = true;
    }
}

$is_logged_in = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;

// Variables for alerts
$success_msg = '';
$error_msg = '';

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    
    // --- CLUBS CRUD ---
    if ($_POST['admin_action'] === 'update_club') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $pool_name = $_POST['pool_name'];
        $postcode = $_POST['postcode'];
        $website = $_POST['website'];
        $logo = $_POST['logo'];

        $stmt = $conn->prepare("UPDATE clubs SET name=?, pool_name=?, postcode=?, website=?, logo=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $pool_name, $postcode, $website, $logo, $id);
        if ($stmt->execute()) {
            $success_msg = "Club '$name' updated successfully.";
            // Also update club_contacts name just in case sync is needed
            $conn->query("UPDATE club_contacts SET club_name='" . $conn->real_escape_string($name) . "' WHERE club_id=$id");
        } else {
            $error_msg = "Failed to update club: " . $conn->error;
        }
    }

    if ($_POST['admin_action'] === 'add_club') {
        $name = $_POST['name'];
        $pool_name = $_POST['pool_name'];
        $postcode = $_POST['postcode'];
        $website = $_POST['website'];
        $logo = $_POST['logo'];

        $stmt = $conn->prepare("INSERT INTO clubs (name, pool_name, postcode, website, logo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $pool_name, $postcode, $website, $logo);
        if ($stmt->execute()) {
            $new_club_id = $conn->insert_id;
            // Create a stub entry in club_contacts
            $conn->query("INSERT INTO club_contacts (club_id, club_name, access_pin) VALUES ($new_club_id, '" . $conn->real_escape_string($name) . "', '0000')");
            // Create a stub entry in results
            $conn->query("INSERT INTO results (club_id) VALUES ($new_club_id)");
            
            $success_msg = "Club '$name' added successfully.";
        } else {
            $error_msg = "Failed to add club: " . $conn->error;
        }
    }

    if ($_POST['admin_action'] === 'delete_club') {
        $id = $_POST['id'];
        // Cascade delete (In a real app, strict foreign keys handle this, but manual cleanup ensures no orphans just in case)
        $conn->query("DELETE FROM venue_details WHERE club_id=$id OR team_1_id=$id OR team_2_id=$id OR team_3_id=$id OR team_4_id=$id");
        $conn->query("DELETE FROM club_contacts WHERE club_id=$id");
        $conn->query("DELETE FROM results WHERE club_id=$id");
        if ($conn->query("DELETE FROM clubs WHERE id=$id")) {
            $success_msg = "Club deleted successfully.";
        } else {
            $error_msg = "Failed to delete club: " . $conn->error;
        }
    }

    // --- CONTACTS CRUD ---
    if ($_POST['admin_action'] === 'update_contact') {
        $id = $_POST['contact_id']; // ID of club_contacts row
        $pin = $_POST['access_pin'];
        $c1_n = $_POST['c1_n'];
        $c1_e = $_POST['c1_e'];
        $c2_n = $_POST['c2_n'];
        $c2_e = $_POST['c2_e'];
        $c3_n = $_POST['c3_n'];
        $c3_e = $_POST['c3_e'];

        $stmt = $conn->prepare("UPDATE club_contacts SET access_pin=?, contact1_name=?, contact1_email=?, contact2_name=?, contact2_email=?, contact3_name=?, contact3_email=? WHERE id=?");
        $stmt->bind_param("sssssssi", $pin, $c1_n, $c1_e, $c2_n, $c2_e, $c3_n, $c3_e, $id);
        if ($stmt->execute()) {
            $success_msg = "Contacts & PIN updated successfully.";
        } else {
            $error_msg = "Failed to update contacts: " . $conn->error;
        }
    }

    // --- VENUE CRUD ---
    if ($_POST['admin_action'] === 'update_venue') {
        $id = $_POST['venue_id'];
        $round_num = $_POST['round_number'] ?? null;
        $host_id = $_POST['host_club_id'] ?? null;
        $v_name = $_POST['venue_name'];
        $addr = $_POST['address'];
        $warm = $_POST['warmup_time'];
        $start = $_POST['start_time'];
        $pay = $_POST['payment_info'];
        $park = $_POST['parking_info'];
        $r_date = $_POST['round_date'] ?? null;
        $t1 = !empty($_POST['team_1_id']) ? $_POST['team_1_id'] : null;
        $t2 = !empty($_POST['team_2_id']) ? $_POST['team_2_id'] : null;
        $t3 = !empty($_POST['team_3_id']) ? $_POST['team_3_id'] : null;
        $t4 = !empty($_POST['team_4_id']) ? $_POST['team_4_id'] : null;
        $teamsheet_link = !empty($_POST['teamsheet_link']) ? $_POST['teamsheet_link'] : null;

        if ($host_id === null || $round_num === null) {
            $error_msg = "Error: Stale data detected. Please refresh the page and try again to prevent data loss.";
        } else {
            if ($r_date !== null) {
                $stmt_date = $conn->prepare("UPDATE venue_details SET round_date=? WHERE round_number=?");
                $stmt_date->bind_param("si", $r_date, $round_num);
                $stmt_date->execute();
            }

            // FILE UPLOAD LOGIC
            $results_file_name = null;
            if (isset($_FILES['results_file']) && $_FILES['results_file']['error'] === UPLOAD_ERR_OK) {
                // Fetch the actual club name for the filename
                $host_name_query = $conn->query("SELECT name FROM clubs WHERE id=" . (int)$host_id);
                $host_name_row = $host_name_query->fetch_assoc();
                $host_club_name = preg_replace('/[^a-zA-Z0-9]/', '', $host_name_row['name']); // Clean for filename

                $upload_dir = 'uploads/results/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_info = pathinfo($_FILES['results_file']['name']);
                $ext = strtolower($file_info['extension']);
                $new_filename = 'R' . $round_num . '_' . $host_club_name . '_Results.' . $ext;
                
                if (move_uploaded_file($_FILES['results_file']['tmp_name'], $upload_dir . $new_filename)) {
                    $results_file_name = $new_filename;
                }
            }

            if ($results_file_name !== null) {
                $stmt = $conn->prepare("UPDATE venue_details SET club_id=?, venue_name=?, address=?, warmup_time=?, start_time=?, payment_info=?, parking_info=?, team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, results_file=?, teamsheet_link=? WHERE id=?");
                $stmt->bind_param("issssssiiiissi", $host_id, $v_name, $addr, $warm, $start, $pay, $park, $t1, $t2, $t3, $t4, $results_file_name, $teamsheet_link, $id);
            } else {
                $stmt = $conn->prepare("UPDATE venue_details SET club_id=?, venue_name=?, address=?, warmup_time=?, start_time=?, payment_info=?, parking_info=?, team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, teamsheet_link=? WHERE id=?");
                $stmt->bind_param("issssssiiiisi", $host_id, $v_name, $addr, $warm, $start, $pay, $park, $t1, $t2, $t3, $t4, $teamsheet_link, $id);
            }
            if ($stmt->execute()) {
                $success_msg = "Venue details updated successfully.";
                $conn->query("INSERT INTO audit_log (club_name, action, change_details) VALUES ('Super Admin', 'Venue Override', 'Overridden details for venue id: $id')");
            } else {
                $error_msg = "Failed to update venue: " . $conn->error;
            }
        }
    }

    // --- FINALS RESULTS UPLOAD ---
    if ($_POST['admin_action'] === 'upload_final_results') {
        $tier = $_POST['final_tier']; // A, B, or C
        $success_msgs = [];
        
        // Handle Teamsheet Link
        if (isset($_POST['teamsheet_link']) && trim($_POST['teamsheet_link']) !== '') {
            $json_file = 'uploads/results/finals_teamsheets.json';
            $links = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
            $links[$tier] = trim($_POST['teamsheet_link']);
            file_put_contents($json_file, json_encode($links, JSON_PRETTY_PRINT));
            $success_msgs[] = "Final {$tier} teamsheet link saved.";
        }
        
        // Handle File Upload
        if (isset($_FILES['results_file']) && $_FILES['results_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/results/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_info = pathinfo($_FILES['results_file']['name']);
            $ext = strtolower($file_info['extension']);
            
            // Delete old files for this final
            $existing = glob($upload_dir . "Final_{$tier}_Results.*");
            if ($existing) {
                foreach ($existing as $old_file) {
                    unlink($old_file);
                }
            }
            
            $new_filename = "Final_{$tier}_Results." . $ext;
            
            if (move_uploaded_file($_FILES['results_file']['tmp_name'], $upload_dir . $new_filename)) {
                $success_msgs[] = "Final {$tier} Results uploaded successfully.";
            } else {
                $error_msg = "Failed to move uploaded file.";
            }
        }
        
        if (!empty($success_msgs)) {
            $success_msg = implode(" ", $success_msgs);
        } elseif (empty($error_msg) && empty($_FILES['results_file']['name']) && empty($_POST['teamsheet_link'])) {
            $error_msg = "No file or link provided.";
        }
    }
}

// Fetch all necessary data
$clubs_data = [];
$contacts_data = [];
$venues_data = [];

if ($is_logged_in) {
    // Clubs
    $res = $conn->query("SELECT * FROM clubs ORDER BY name ASC");
    if ($res) while($r = $res->fetch_assoc()) $clubs_data[] = $r;

    // Contacts
    $res = $conn->query("SELECT cc.*, c.name as real_club_name, c.logo FROM club_contacts cc JOIN clubs c ON cc.club_id = c.id ORDER BY c.name ASC");
    if ($res) while($r = $res->fetch_assoc()) $contacts_data[] = $r;

    // Venues
    $res = $conn->query("SELECT vd.*, c.name as host_club_name FROM venue_details vd JOIN clubs c ON vd.club_id = c.id ORDER BY round_number ASC, c.name ASC");
    if ($res) while($r = $res->fetch_assoc()) $venues_data[] = $r;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin | Cotswold League</title>
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
        /* Tab transitions */
        .tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .tab-content.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
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
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-sky-500"></div>
                    
                    <div class="text-center mb-8">
                        <div class="bg-slate-800 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/10 shadow-inner">
                            <i data-lucide="shield-alert" class="w-8 h-8 text-emerald-400"></i>
                        </div>
                        <h1 class="text-2xl font-bold mb-2">Super Admin</h1>
                        <p class="text-slate-400 text-sm">Centralized database management.</p>
                    </div>

                    <form method="POST" class="space-y-5">
                        <input type="password" name="super_password" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600 text-center" placeholder="Admin Password" required>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg flex items-center justify-center gap-2 mt-2">
                            <i data-lucide="lock-open" class="w-4 h-4"></i> Authenticate
                        </button>
                        <?php if ($login_error): ?>
                            <p class="text-red-400 text-xs font-medium text-center mt-2">Incorrect admin password.</p>
                        <?php endif; ?>
                    </form>
                    <div class="mt-6 pt-4 border-t border-slate-700/50 text-center">
                        <a href="admin.php" class="text-slate-500 hover:text-slate-400 text-sm flex items-center justify-center gap-1 transition-colors">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Return to Rep Portal
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- DASHBOARD -->
            <div class="w-full max-w-7xl animate-fade-in-up">
                
                <!-- HEADER CARD -->
                <div class="glass-panel p-6 rounded-3xl flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden mb-8">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent pointer-events-none"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="bg-emerald-500/20 p-3 rounded-2xl">
                            <i data-lucide="database" class="w-8 h-8 text-emerald-400"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-1">League Database</h1>
                            <p class="text-emerald-400 text-sm font-medium">Super Administrator Dashboard</p>
                        </div>
                    </div>
                    <div class="relative z-10">
                         <a href="?action=logout" class="bg-slate-800 hover:bg-red-500/10 hover:text-red-400 border border-slate-700 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all">
                             <i data-lucide="log-out" class="w-4 h-4"></i> Secure Logout
                         </a>
                    </div>
                </div>

                <!-- ALERTS -->
                <?php if ($success_msg): ?>
                    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-5 py-3 rounded-xl text-sm flex items-center gap-3 shadow-lg mb-6">
                        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-5 py-3 rounded-xl text-sm flex items-center gap-3 shadow-lg mb-6">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- NAVIGATION TABS -->
                <div class="flex flex-wrap gap-2 mb-8 bg-slate-900/50 p-2 rounded-2xl border border-white/5 inline-flex">
                    <button onclick="openTab(event, 'tab-links')" class="tab-btn px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all bg-sky-500/20 text-sky-400" id="defaultOpen">
                        <i data-lucide="zap" class="w-4 h-4"></i> Quick Links
                    </button>
                    <button onclick="openTab(event, 'tab-clubs')" class="tab-btn px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all text-slate-400 hover:bg-white/5 hover:text-white">
                        <i data-lucide="building-2" class="w-4 h-4"></i> Clubs Database
                    </button>
                    <button onclick="openTab(event, 'tab-contacts')" class="tab-btn px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all text-slate-400 hover:bg-white/5 hover:text-white">
                        <i data-lucide="users" class="w-4 h-4"></i> Team Contacts & PINs
                    </button>
                    <button onclick="openTab(event, 'tab-venues')" class="tab-btn px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all text-slate-400 hover:bg-white/5 hover:text-white">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> Host Venues
                    </button>
                </div>

                <!-- TAB: QUICK LINKS -->
                <div id="tab-links" class="tab-content active space-y-6">
                    <h2 class="text-xl font-bold flex items-center gap-2 mb-4"><i data-lucide="zap" class="w-5 h-5 text-sky-400"></i> Admin Shortcuts</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="update_scores.php" target="_blank" class="glass-panel p-6 rounded-2xl hover:bg-sky-900/30 transition-all group border border-sky-500/20">
                            <div class="h-12 w-12 bg-sky-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="calculator" class="w-6 h-6 text-sky-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Update Scores</h3>
                            <p class="text-xs text-slate-400 mt-1">Submit live gala results to the main table.</p>
                        </a>
                        <a href="table.php" target="_blank" class="glass-panel p-6 rounded-2xl hover:bg-indigo-900/30 transition-all group border border-indigo-500/20">
                            <div class="h-12 w-12 bg-indigo-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="table" class="w-6 h-6 text-indigo-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Live League Table</h3>
                            <p class="text-xs text-slate-400 mt-1">View the current standings live.</p>
                        </a>
                        <a href="audit_log.php" target="_blank" class="glass-panel p-6 rounded-2xl hover:bg-orange-900/30 transition-all group border border-orange-500/20">
                            <div class="h-12 w-12 bg-orange-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="history" class="w-6 h-6 text-orange-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Audit Log</h3>
                            <p class="text-xs text-slate-400 mt-1">Review actions taken by team representatives.</p>
                        </a>
                        <a href="admin.php" target="_blank" class="glass-panel p-6 rounded-2xl hover:bg-emerald-900/30 transition-all group border border-emerald-500/20">
                            <div class="h-12 w-12 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="layout-dashboard" class="w-6 h-6 text-emerald-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Club Rep Portal</h3>
                            <p class="text-xs text-slate-400 mt-1">The standard portal for club representatives.</p>
                        </a>
                        <a href="showcase.php" target="_blank" class="glass-panel p-6 rounded-2xl hover:bg-violet-900/30 transition-all group border border-violet-500/20">
                            <div class="h-12 w-12 bg-violet-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="monitor-play" class="w-6 h-6 text-violet-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Showcase</h3>
                            <p class="text-xs text-slate-400 mt-1">Configure and launch results presentations.</p>
                        </a>
                    </div>
                </div>

                <!-- TAB: CLUBS DATABASE -->
                <div id="tab-clubs" class="tab-content space-y-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="building-2" class="w-5 h-5 text-indigo-400"></i> Clubs Directory</h2>
                        <button onclick="document.getElementById('addClubForm').classList.toggle('hidden')" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add New Club
                        </button>
                    </div>

                    <!-- Add Club Form (Hidden by default) -->
                    <div id="addClubForm" class="hidden glass-panel p-6 rounded-2xl mb-6 border border-indigo-500/30">
                        <h3 class="font-bold text-lg mb-4 text-indigo-400">Add New Participating Club</h3>
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <input type="hidden" name="admin_action" value="add_club">
                            <input type="text" name="name" placeholder="Club Name*" required class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <input type="text" name="pool_name" placeholder="Pool Name*" required class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <input type="text" name="postcode" placeholder="Postcode*" required class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <input type="text" name="website" placeholder="Website URL" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <input type="text" name="logo" placeholder="Logo filename (e.g., logo.webp)" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg font-bold">Save Club</button>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach($clubs_data as $club): ?>
                            <div class="glass-panel p-5 rounded-2xl border border-white/5 relative group">
                                <form method="POST" class="space-y-3 relative z-10">
                                    <input type="hidden" name="admin_action" value="update_club">
                                    <input type="hidden" name="id" value="<?php echo $club['id']; ?>">
                                    
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-white rounded-lg p-1 flex-shrink-0 border border-slate-600">
                                            <?php if($club['logo']): ?>
                                                <img src="images/Teams/<?php echo htmlspecialchars($club['logo']); ?>" class="w-full h-full object-contain">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-slate-200 rounded"></div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($club['name']); ?>" class="font-bold bg-transparent border-b border-transparent hover:border-slate-600 focus:border-indigo-500 focus:outline-none w-full text-white pb-1">
                                    </div>

                                    <div class="grid grid-cols-[80px_1fr] items-center gap-2 text-xs">
                                        <span class="text-slate-500">Pool:</span>
                                        <input type="text" name="pool_name" value="<?php echo htmlspecialchars($club['pool_name']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">
                                        
                                        <span class="text-slate-500">Postcode:</span>
                                        <input type="text" name="postcode" value="<?php echo htmlspecialchars($club['postcode']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">
                                        
                                        <span class="text-slate-500">Website:</span>
                                        <input type="text" name="website" value="<?php echo htmlspecialchars($club['website']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">
                                        
                                        <span class="text-slate-500">Logo file:</span>
                                        <input type="text" name="logo" value="<?php echo htmlspecialchars($club['logo']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">
                                    </div>

                                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-white/5 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="submit" class="text-xs bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded-lg font-bold flex items-center gap-1 transition-colors">
                                            <i data-lucide="save" class="w-3 h-3"></i> Sync Details
                                        </button>
                                </form>
                                        <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete this club? This cascades to contacts and venues!');">
                                            <input type="hidden" name="admin_action" value="delete_club">
                                            <input type="hidden" name="id" value="<?php echo $club['id']; ?>">
                                            <button type="submit" class="text-xs bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-lg flex items-center gap-1 transition-colors">
                                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                                            </button>
                                        </form>
                                    </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TAB: CONTACTS & PINS -->
                <div id="tab-contacts" class="tab-content space-y-6">
                    <h2 class="text-xl font-bold flex items-center gap-2 mb-4"><i data-lucide="users" class="w-5 h-5 text-orange-400"></i> Team Contacts & PIN Manager</h2>
                    <div class="space-y-4">
                        <?php foreach($contacts_data as $contact): ?>
                            <div class="glass-panel p-5 rounded-2xl border border-white/5 relative">
                                <form method="POST" class="grid grid-cols-1 lg:grid-cols-[250px_1fr_120px] gap-6 items-start">
                                    <input type="hidden" name="admin_action" value="update_contact">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">

                                    <!-- Club Identity -->
                                    <div>
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-8 h-8 bg-white/10 rounded overflow-hidden flex-shrink-0 p-1">
                                                <?php if($contact['logo']): ?><img src="images/Teams/<?php echo htmlspecialchars($contact['logo']); ?>" class="w-full h-full object-contain"><?php endif; ?>
                                            </div>
                                            <h3 class="font-bold text-white"><?php echo htmlspecialchars($contact['real_club_name']); ?></h3>
                                        </div>
                                        <div class="bg-orange-500/10 border border-orange-500/30 p-3 rounded-xl inline-block mt-2">
                                            <label class="text-[10px] uppercase text-orange-400 font-bold block mb-1">Access PIN</label>
                                            <input type="text" name="access_pin" value="<?php echo htmlspecialchars($contact['access_pin']); ?>" class="bg-transparent border-b border-orange-500/50 text-white font-mono font-bold text-lg w-20 text-center tracking-widest focus:outline-none" maxlength="4">
                                        </div>
                                    </div>

                                    <!-- Contacts Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-2">
                                            <label class="text-xs text-slate-500 font-bold">Contact 1 (Primary)</label>
                                            <input type="text" name="c1_n" value="<?php echo htmlspecialchars($contact['contact1_name']); ?>" class="w-full bg-slate-900 border border-slate-800 rounded px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none" placeholder="Name">
                                            <input type="email" name="c1_e" value="<?php echo htmlspecialchars($contact['contact1_email']); ?>" class="w-full bg-slate-900 border border-slate-800 rounded px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none" placeholder="Email">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs text-slate-500 font-bold">Contact 2</label>
                                            <input type="text" name="c2_n" value="<?php echo htmlspecialchars($contact['contact2_name']); ?>" class="w-full bg-slate-900 border border-slate-800 rounded px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none" placeholder="Name">
                                            <input type="email" name="c2_e" value="<?php echo htmlspecialchars($contact['contact2_email']); ?>" class="w-full bg-slate-900 border border-slate-800 rounded px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none" placeholder="Email">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs text-slate-500 font-bold">Contact 3</label>
                                            <input type="text" name="c3_n" value="<?php echo htmlspecialchars($contact['contact3_name']); ?>" class="w-full bg-slate-900 border border-slate-800 rounded px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none" placeholder="Name">
                                            <input type="email" name="c3_e" value="<?php echo htmlspecialchars($contact['contact3_email']); ?>" class="w-full bg-slate-900 border border-slate-800 rounded px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none" placeholder="Email">
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="h-full flex items-center justify-end">
                                        <button type="submit" class="bg-orange-600/20 text-orange-400 hover:bg-orange-600 hover:text-white px-4 py-8 rounded-xl font-bold flex flex-col items-center gap-2 transition-colors border border-orange-500/30">
                                            <i data-lucide="save" class="w-5 h-5"></i> <span class="text-xs">Save</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TAB: HOST VENUES -->
                <div id="tab-venues" class="tab-content space-y-6">
                    <h2 class="text-xl font-bold flex items-center gap-2 mb-4"><i data-lucide="map-pin" class="w-5 h-5 text-emerald-400"></i> Event Venues Scheduler</h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php foreach($venues_data as $venue): ?>
                            <div class="glass-panel p-5 rounded-2xl border border-white/5 relative group">
                                <form method="POST" class="space-y-4" enctype="multipart/form-data">
                                    <input type="hidden" name="admin_action" value="update_venue">
                                    <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">
                                    <input type="hidden" name="round_number" value="<?php echo $venue['round_number']; ?>">
                                    
                                    <div class="flex justify-between items-start border-b border-white/5 pb-3">
                                        <div>
                                            <div class="bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mb-1 inline-flex items-center gap-2">
                                                Round <?php echo $venue['round_number']; ?> - 
                                                <input type="text" name="round_date" value="<?php echo htmlspecialchars($venue['round_date'] ?? ''); ?>" class="bg-transparent border-b border-emerald-500/50 w-24 focus:outline-none focus:border-emerald-300 text-emerald-300 placeholder-emerald-700/50" placeholder="DD/MM/YYYY">
                                            </div>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Host:</span>
                                                <select name="host_club_id" onchange="const form = this.closest('form'); form.querySelector('input[name=venue_name]').value = ''; form.querySelector('textarea[name=address]').value = ''; form.querySelector('input[name=warmup_time]').value = ''; form.querySelector('input[name=start_time]').value = ''; form.querySelector('input[name=payment_info]').value = ''; form.querySelector('input[name=parking_info]').value = '';" class="bg-transparent text-white text-lg font-bold focus:outline-none appearance-none border-b border-transparent hover:border-emerald-500 focus:border-emerald-500 cursor-pointer">
                                                    <?php foreach($clubs_data as $c): ?>
                                                        <option value="<?php echo $c['id']; ?>" <?php echo ($venue['club_id'] == $c['id']) ? 'selected' : ''; ?> class="text-black bg-white"><?php echo htmlspecialchars($c['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i data-lucide="chevron-down" class="w-4 h-4 text-emerald-500/50"></i>
                                            </div>
                                        </div>
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white p-2 rounded-lg transition-colors group-hover:scale-105 shadow-lg flex-shrink-0">
                                            <i data-lucide="save" class="w-4 h-4"></i>
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Venue Location</label>
                                            <input type="text" name="venue_name" value="<?php echo htmlspecialchars($venue['venue_name']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 mb-2">
                                            <textarea name="address" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500"><?php echo htmlspecialchars($venue['address']); ?></textarea>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Warm Up</label>
                                                <input type="text" name="warmup_time" value="<?php echo htmlspecialchars($venue['warmup_time']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                            </div>
                                            <div>
                                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Start Time</label>
                                                <input type="text" name="start_time" value="<?php echo htmlspecialchars($venue['start_time']); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Payment</label>
                                                <input type="text" name="payment_info" value="<?php echo htmlspecialchars($venue['payment_info'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                            </div>
                                            <div>
                                                <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Parking</label>
                                                <input type="text" name="parking_info" value="<?php echo htmlspecialchars($venue['parking_info'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1 flex items-center justify-between">
                                                <span>Results Spreadsheet Upload</span>
                                                <?php if(!empty($venue['results_file'])): ?>
                                                    <span class="text-emerald-400 normal-case">File: <?php echo htmlspecialchars($venue['results_file']); ?></span>
                                                <?php endif; ?>
                                            </label>
                                            <input type="file" name="results_file" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 cursor-pointer">
                                        </div>
                                        <div class="mt-3">
                                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Collated Teamsheet Link (Google Sheets)</label>
                                            <input type="url" name="teamsheet_link" value="<?php echo htmlspecialchars($venue['teamsheet_link'] ?? ''); ?>" placeholder="https://docs.google.com/spreadsheets/d/..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-white/5 space-y-2">
                                        <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block">Competing Teams (Draw)</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php for($t=1; $t<=4; $t++): 
                                                $team_key = "team_{$t}_id"; 
                                                $selected_id = $venue[$team_key];
                                            ?>
                                            <select name="<?php echo $team_key; ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-500 appearance-none">
                                                <option value="">- Select Team <?php echo $t; ?> -</option>
                                                <?php foreach($clubs_data as $c): ?>
                                                    <option value="<?php echo $c['id']; ?>" <?php echo ($selected_id == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- FINALS RESULTS PANEL -->
                    <div class="mt-10 border-t border-slate-700/50 pt-8">
                        <h2 class="text-xl font-bold flex items-center gap-2 mb-6"><i data-lucide="trophy" class="w-5 h-5 text-amber-400"></i> Finals Results & Teamsheets Upload</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <?php 
                            $finals_links_file = 'uploads/results/finals_teamsheets.json';
                            $finals_links = file_exists($finals_links_file) ? json_decode(file_get_contents($finals_links_file), true) : [];
                            foreach(['A', 'B', 'C'] as $tier): 
                                $existing = glob('uploads/results/Final_' . $tier . '_Results.*');
                                $has_file = count($existing) > 0;
                            ?>
                                <div class="glass-panel p-5 rounded-2xl border border-white/5 relative">
                                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                        <input type="hidden" name="admin_action" value="upload_final_results">
                                        <input type="hidden" name="final_tier" value="<?php echo $tier; ?>">
                                        
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="font-bold text-lg text-white">Final <?php echo $tier; ?></h3>
                                            <?php if($has_file): ?>
                                                <span class="bg-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Results Active</span>
                                            <?php else: ?>
                                                <span class="bg-slate-500/20 text-slate-400 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">No Results</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div>
                                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Results File</label>
                                            <input type="file" name="results_file" accept=".xlsx,.xls,.csv" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-sky-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-sky-600 file:text-white hover:file:bg-sky-500 cursor-pointer">
                                        </div>

                                        <div>
                                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Collated Teamsheet Link</label>
                                            <input type="url" name="teamsheet_link" placeholder="https://docs.google.com/spreadsheets/d/..." value="<?php echo htmlspecialchars($finals_links[$tier] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-sky-500">
                                        </div>
                                        
                                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2 rounded-lg text-sm border border-slate-700 transition-colors">Save Updates</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();

        function openTab(evt, tabId) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                // Reset all to unselected state
                tablinks[i].className = tablinks[i].className.replace("bg-sky-500/20 text-sky-400", "text-slate-400 hover:bg-white/5 hover:text-white");
            }
            document.getElementById(tabId).style.display = "block";
            document.getElementById(tabId).classList.add("active");
            
            // Set active state on clicked button
            evt.currentTarget.className = evt.currentTarget.className.replace("text-slate-400 hover:bg-white/5 hover:text-white", "bg-sky-500/20 text-sky-400");
        }
        
        // Ensure first tab works dynamically
        <?php if ($is_logged_in): ?>
            document.getElementById("defaultOpen").click();
        <?php endif; ?>
    </script>
</body>
</html>
