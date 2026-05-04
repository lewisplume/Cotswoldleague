<?php
session_start();
include 'db.php';

$active_season_year = $current_season_year ?? 2026;

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: teamportal.php");
    exit;
}

// Variables for alerts
$success_msg = '';
$error_msg = '';

// 1. Teamsheet Links Mapping
$teamsheets = [
    "Academy Swim Team" => "https://docs.google.com/spreadsheets/d/1i8XiwXvW6UVdjqnXHG0DwrFWIwVik9aAUxExHKkR778/edit?usp=drive_web",
    "Backwell" => "https://docs.google.com/spreadsheets/d/1hmCtCGgSpQfIVzy-a0nbgpToVJXDb6M9k3a3ULGvSgk/edit?usp=drive_web",
    "Bath Dolphin" => "https://docs.google.com/spreadsheets/d/1iczsTghg46ISBxtTIjGhU3pg-qX2z2We7IE9tdbGHe0/edit?usp=drive_web",
    "Bridgwater" => "https://docs.google.com/spreadsheets/d/1Itz6yPVQbtrUcIQumiTQj-juO8ayCJKcLp3G1II25dY/edit?usp=drive_web",
    "Bristol North" => "https://docs.google.com/spreadsheets/d/109pMY2kj4FZHcbVmFqFiWHTz--NkkfllhxeRtSKt5E4/edit?usp=drive_web",
    "Brockworth" => "https://docs.google.com/spreadsheets/d/18fYT_o3rR1Z8MBMiTBRTiA8ssOmbXkuIvMDmnTzUfHo/edit?usp=drive_web",
    "Burnham-On-Sea" => "https://docs.google.com/spreadsheets/d/1EQTrCVxUv9DSFJQOe_Dha-fi8T1xsm7e73lwaJhnH9A/edit?usp=drive_web",
    "COB (City of Bristol)" => "https://docs.google.com/spreadsheets/d/1L4yEk8hV-BHc6KNYdMz3Z_FdM4AbPnWTf2VtbORz984/edit?usp=drive_web",
    "City of Bristol" => "https://docs.google.com/spreadsheets/d/1L4yEk8hV-BHc6KNYdMz3Z_FdM4AbPnWTf2VtbORz984/edit?usp=drive_web",
    "Clevedon" => "https://docs.google.com/spreadsheets/d/1KMuZFQyt-JBLoyREyqPUBNmOUWGyexFFdPw5IhScHfE/edit?usp=drive_web",
    "Corsham" => "https://docs.google.com/spreadsheets/d/1NDZLL8IAGIH-mRNWjeKRfomg8FyAPvnoRxhPK178SYY/edit?usp=drive_web",
    "Cwmbran" => "https://docs.google.com/spreadsheets/d/1iKdSH5aQr1LY_PdH6w0rhKq2-bVdS0Cz25WivXHKWl8/edit?usp=drive_web",
    "Dursley" => "https://docs.google.com/spreadsheets/d/1ofBJ09URsgDV1tgOdFWVf83Cv_GQgKfYG5i13zUNUBk/edit?usp=drive_web",
    "Forest of Dean" => "https://docs.google.com/spreadsheets/d/1ohMOSAEtNkBXt40CjBBEoFDizF3LzsssltN5XyKNMGc/edit?usp=drive_web",
    "Monnow SC" => "https://docs.google.com/spreadsheets/d/1qSw1UbIvCyrnqIK8CQo9_G8UuOxrFgx623YZm50W_wQ/edit?usp=drive_web",
    "Monnow" => "https://docs.google.com/spreadsheets/d/1qSw1UbIvCyrnqIK8CQo9_G8UuOxrFgx623YZm50W_wQ/edit?usp=drive_web",
    "Newport" => "https://docs.google.com/spreadsheets/d/1siyNpNaW4e6MNeLziyYbso1HB7cA_sVCDAEhvUjQKfw/edit?usp=drive_web",
    "Severnside Tritons" => "https://docs.google.com/spreadsheets/d/1Q8_8ZO_AMEWIKYaHj6lyRMu0pTiC8u2nHvxrZC8heeo/edit?usp=drive_web",
    "Southwold SC" => "https://docs.google.com/spreadsheets/d/10nb25eFXcsLMA-Z5W8W5GDTztdCpOJCmoUZSc9Txbdw/edit?usp=drive_web",
    "Southwold" => "https://docs.google.com/spreadsheets/d/10nb25eFXcsLMA-Z5W8W5GDTztdCpOJCmoUZSc9Txbdw/edit?usp=drive_web",
    "Swindon ASC" => "https://docs.google.com/spreadsheets/d/1_Ies54ItzAFeOlDSo1dJ5LyC2Wj16cST2VQRkUC1Gbc/edit?usp=drive_web",
    "Swindon" => "https://docs.google.com/spreadsheets/d/1_Ies54ItzAFeOlDSo1dJ5LyC2Wj16cST2VQRkUC1Gbc/edit?usp=drive_web",
    "Wells" => "https://docs.google.com/spreadsheets/d/1cI9CoLIt5FE-hR1VKjORb0bJQBnW-eTyvLTiTf7_L_c/edit?usp=drive_web",
    "Yeovil" => "https://docs.google.com/spreadsheets/d/1gPk0gqfQeDHKISyTBuYlUo5gcHGoYmP2MQGgiAeKT40/edit?usp=drive_web"
];
$master_teamsheet_link = "https://docs.google.com/spreadsheets/d/1HWqc4Lw8Iule7tv2mHRkfDWmQUs9fo5OO8uI2QnwC0U/edit?usp=drive_web";


// 2. Club DB Shortname Mapping handled directly by club_id JOIN

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

            header("Location: teamportal.php");
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
$current_club_name = $_SESSION['club_name'] ?? '';

// HANDLE AUTHENTICATED ACTIONS
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Action: Update Contacts
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

    // Action: Change PIN
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

    // Action: Update Venue
    if (isset($_POST['action']) && $_POST['action'] === 'update_venue') {
        $venue_id = intval($_POST['venue_id']);
        $venue_name = $_POST['venue_name'];
        $address = $_POST['address'];
        $warm_up = $_POST['warmup_time'];
        $start_time = $_POST['start_time'];
        $payment = $_POST['payment_info'];
        $parking = $_POST['parking_info'];
        $target_host_name = $_POST['target_host_name']; // Extracted from form for log

        // Audit check old values
        $old_sql = "SELECT * FROM venue_details WHERE id = ? AND club_id = ? AND season_year = ?";
        $old_stmt = $conn->prepare($old_sql);
        $old_stmt->bind_param("iii", $venue_id, $current_club_id, $active_season_year);
        $old_stmt->execute();
        $old_res = $old_stmt->get_result();
        $old_row = $old_res->fetch_assoc();

        if (!$old_row) {
            $error_msg = "Venue not found for your club in the active season.";
        } else {
            $changes = [];
            if ($old_row['venue_name'] != $venue_name)
                $changes[] = "Name: '{$old_row['venue_name']}' -> '$venue_name'";
            if ($old_row['address'] != $address)
                $changes[] = "Address: '{$old_row['address']}' -> '$address'";
            if ($old_row['warmup_time'] != $warm_up)
                $changes[] = "WarmUp: '{$old_row['warmup_time']}' -> '$warm_up'";
            if ($old_row['start_time'] != $start_time)
                $changes[] = "Start: '{$old_row['start_time']}' -> '$start_time'";
            if ($old_row['payment_info'] != $payment)
                $changes[] = "Payment: '{$old_row['payment_info']}' -> '$payment'";
            if ($old_row['parking_info'] != $parking)
                $changes[] = "Parking: '{$old_row['parking_info']}' -> '$parking'";

            if (empty($changes)) {
                $error_msg = "No changes detected for the venue.";
            } else {
                $update_sql = "UPDATE venue_details SET venue_name=?, address=?, warmup_time=?, start_time=?, payment_info=?, parking_info=? WHERE id=? AND club_id=? AND season_year=?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssssssiii", $venue_name, $address, $warm_up, $start_time, $payment, $parking, $venue_id, $current_club_id, $active_season_year);

                if ($stmt->execute()) {
                    $success_msg = "Venue details updated successfully.";

                    // Audit Log
                    $rep_name = $current_club_name . " Rep";
                    $change_str = "[$target_host_name] " . implode(", ", $changes);
                    $log_sql = "INSERT INTO audit_log (club_name, action, change_details, timestamp) VALUES (?, 'Venue Update', ?, NOW())";
                    $log_stmt = $conn->prepare($log_sql);
                    if ($log_stmt) {
                        $log_stmt->bind_param("ss", $rep_name, $change_str);
                        $log_stmt->execute();
                    }
                } else {
                    $error_msg = "Database Error updating venue.";
                }
            }
        }
    }
}

// Fetch Data for View
$my_club_data = null;
$directory_data = [];
$clubs_dropdown = [];
$venues = [];
$my_teamsheet_link = null;

if ($is_logged_in) {
    // 1. Fetch My Club Data (Joined with clubs for Logo)
    $stmt = $conn->prepare("SELECT cc.*, c.logo FROM club_contacts cc LEFT JOIN clubs c ON cc.club_id = c.id WHERE cc.club_id = ?");
    $stmt->bind_param("i", $current_club_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $my_club_data = $res->fetch_assoc();
    }
    $stmt->close();

    // 2. Map Teamsheet Link
    if (isset($teamsheets[$current_club_name])) {
        $my_teamsheet_link = $teamsheets[$current_club_name];
    }

    // 3. Fetch My Venues
    $v_sql = "SELECT vd.*, c.name AS host_club_name FROM venue_details vd JOIN clubs c ON vd.club_id = c.id WHERE vd.club_id = ? AND vd.season_year = ? ORDER BY vd.round_number ASC";
    $v_stmt = $conn->prepare($v_sql);
    $v_stmt->bind_param("ii", $current_club_id, $active_season_year);
    $v_stmt->execute();
    $v_res = $v_stmt->get_result();
    if ($v_res->num_rows > 0) {
        while ($row = $v_res->fetch_assoc()) {
            $venues[] = $row;
        }
    }
    $v_stmt->close();

    // 3b. Fetch Round Draws (Where this club is competing)
    $draws = [];
    $draws_sql = "SELECT vd.*, 
                         c_host.name AS host_name,
                         c1.name AS team1_name, 
                         c2.name AS team2_name, 
                         c3.name AS team3_name, 
                         c4.name AS team4_name,
                        c5.name AS team5_name,
                        c6.name AS team6_name,
                        c7.name AS team7_name,
                        c8.name AS team8_name,
                         gs.id AS scoresheet_id,
                         gs.status AS scoresheet_status
                  FROM venue_details vd
                  LEFT JOIN clubs c_host ON vd.club_id = c_host.id
                  LEFT JOIN clubs c1 ON vd.team_1_id = c1.id
                  LEFT JOIN clubs c2 ON vd.team_2_id = c2.id
                  LEFT JOIN clubs c3 ON vd.team_3_id = c3.id
                  LEFT JOIN clubs c4 ON vd.team_4_id = c4.id
                    LEFT JOIN clubs c5 ON vd.team_5_id = c5.id
                    LEFT JOIN clubs c6 ON vd.team_6_id = c6.id
                    LEFT JOIN clubs c7 ON vd.team_7_id = c7.id
                    LEFT JOIN clubs c8 ON vd.team_8_id = c8.id
                  LEFT JOIN gala_scoresheets gs ON vd.id = gs.venue_detail_id
                  WHERE vd.season_year = ? AND (vd.club_id = ? OR vd.team_1_id = ? OR vd.team_2_id = ? OR vd.team_3_id = ? OR vd.team_4_id = ? OR vd.team_5_id = ? OR vd.team_6_id = ? OR vd.team_7_id = ? OR vd.team_8_id = ?)
                  ORDER BY vd.round_number ASC";
    $d_stmt = $conn->prepare($draws_sql);
    $d_stmt->bind_param("iiiiiiiiii", $active_season_year, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id);
    $d_stmt->execute();
    $d_res = $d_stmt->get_result();
    if ($d_res->num_rows > 0) {
        while ($row = $d_res->fetch_assoc()) {
            $draws[] = $row;
        }
    }
    $d_stmt->close();

    // 4. Fetch Directory Data
    $sql = "SELECT cc.*, c.logo, c.name as real_club_name FROM club_contacts cc LEFT JOIN clubs c ON cc.club_id = c.id ORDER BY c.name ASC";
    $dir_res = $conn->query($sql);
    if ($dir_res) {
        while ($d = $dir_res->fetch_assoc()) {
            $directory_data[] = $d;
        }
    }

    // 5. Build Live Matrix for Directory Filtering
    $filter_matrix = [
        'finals' => ['A' => [], 'B' => [], 'C' => []],
        'rounds' => [1 => [], 2 => [], 3 => [], 4 => []]
    ];

    // 5a. A/B/C Finals dynamically pulled from live results
    $standings_sql = "SELECT c.id FROM results r JOIN clubs c ON r.club_id = c.id ORDER BY (r.round_1 + r.round_2 + r.round_3 + r.round_4) DESC, c.name ASC";
    $s_res = $conn->query($standings_sql);
    if ($s_res) {
        $pos = 1;
        while ($r = $s_res->fetch_assoc()) {
            if ($pos <= 8)
                $filter_matrix['finals']['A'][] = (int) $r['id'];
            elseif ($pos <= 14)
                $filter_matrix['finals']['B'][] = (int) $r['id'];
            else
                $filter_matrix['finals']['C'][] = (int) $r['id'];
            $pos++;
        }
    }

    // 5c. Determine My Final Tier
    $my_final_tier = null;
    $my_final_file = null;
    $my_final_teamsheet_link = null;
    if (in_array($current_club_id, $filter_matrix['finals']['A'])) $my_final_tier = 'A';
    elseif (in_array($current_club_id, $filter_matrix['finals']['B'])) $my_final_tier = 'B';
    elseif (in_array($current_club_id, $filter_matrix['finals']['C'])) $my_final_tier = 'C';
    
    if ($my_final_tier) {
        $final_files = glob('uploads/results/Final_' . $my_final_tier . '_Results.*');
        if (!empty($final_files)) {
            $my_final_file = basename($final_files[0]);
        }
        $finals_links_file = 'uploads/results/finals_teamsheets.json';
        if (file_exists($finals_links_file)) {
            $links = json_decode(file_get_contents($finals_links_file), true);
            $my_final_teamsheet_link = $links[$my_final_tier] ?? null;
        }
    }

    // 5b. Hosted Rounds pulled from venue_details
    $rounds_sql = "SELECT vd.round_number, c.name AS host_name, vd.team_1_id, vd.team_2_id, vd.team_3_id, vd.team_4_id 
                   FROM venue_details vd JOIN clubs c ON vd.club_id = c.id WHERE vd.season_year = $active_season_year";
    $r_res = $conn->query($rounds_sql);
    if ($r_res) {
        while ($row = $r_res->fetch_assoc()) {
            $rn = $row['round_number'];
            $host = $row['host_name'];
            $teams = [];
            if ($row['team_1_id'])
                $teams[] = (int) $row['team_1_id'];
            if ($row['team_2_id'])
                $teams[] = (int) $row['team_2_id'];
            if ($row['team_3_id'])
                $teams[] = (int) $row['team_3_id'];
            if ($row['team_4_id'])
                $teams[] = (int) $row['team_4_id'];

            $filter_matrix['rounds'][$rn][$host] = $teams;
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
    <title>Team Portal | Cotswold League</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body {
            background-color: #0f172a;
        }

        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
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
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-sky-500 to-emerald-500"></div>

                    <div class="text-center mb-8">
                        <div
                            class="bg-slate-800 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/10 shadow-inner">
                            <i data-lucide="shield" class="w-8 h-8 text-sky-400"></i>
                        </div>
                        <h1 class="text-2xl font-bold mb-2">Team Portal</h1>
                        <p class="text-slate-400 text-sm">Secure access for club representatives.</p>
                    </div>

                    <?php if ($error_msg): ?>
                        <div
                            class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl text-sm mb-6 flex items-start gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <p><?php echo htmlspecialchars($error_msg); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="login">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Select Your
                                Club</label>
                            <div class="relative">
                                <select name="club_id"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-sky-500 transition-all appearance-none cursor-pointer hover:border-slate-600"
                                    required>
                                    <option value="" disabled selected>Choose club...</option>
                                    <?php foreach ($clubs_dropdown as $club): ?>
                                        <option value="<?php echo $club['club_id']; ?>">
                                            <?php echo htmlspecialchars($club['club_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i data-lucide="chevron-down"
                                    class="absolute right-4 top-3.5 w-4 h-4 text-slate-500 pointer-events-none"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Access
                                PIN</label>
                            <input type="password" name="pin"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-sky-500 transition-all text-center tracking-[0.5em] font-mono text-lg placeholder-slate-700"
                                placeholder="••••" maxlength="4" pattern="\d{4}" inputmode="numeric" required>
                        </div>
                        <button type="submit"
                            class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-sky-900/20 flex items-center justify-center gap-2 mt-2 group">
                            <span>Login</span> <i data-lucide="arrow-right"
                                class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- DASHBOARD -->
            <div class="w-full max-w-7xl space-y-8 animate-fade-in-up">

                <!-- HEADER CARD -->
                <div
                    class="glass-panel p-6 rounded-3xl flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-500/5 to-transparent pointer-events-none"></div>

                    <div class="flex flex-col md:flex-row items-center gap-6 relative z-10 w-full md:w-auto">
                        <div class="w-20 h-20 bg-white rounded-2xl p-2 shadow-lg flex-shrink-0">
                            <?php if ($my_club_data['logo']): ?>
                                <img src="images/Teams/<?php echo htmlspecialchars($my_club_data['logo']); ?>" alt="Club Logo"
                                    class="w-full h-full object-contain">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-300">
                                    <i data-lucide="image" class="w-8 h-8"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center md:text-left">
                            <h1 class="text-3xl font-bold text-white mb-1">
                                <?php echo htmlspecialchars($my_club_data['club_name']); ?>
                            </h1>
                            <p class="text-sky-400 text-sm font-medium">Team Dashboard</p>
                        </div>
                    </div>

                    <div class="relative z-10 flex flex-wrap justify-center gap-2">
                        <a href="admin.php"
                            class="bg-slate-800 hover:bg-sky-500/10 hover:text-sky-400 border border-slate-700 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Club Rep Portal
                        </a>
                        <a href="?action=logout"
                            class="bg-slate-800 hover:bg-red-500/10 hover:text-red-400 border border-slate-700 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all">
                            <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- ALERTS -->
                <?php if ($success_msg): ?>
                    <div
                        class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-5 py-3 rounded-xl text-sm flex items-center gap-3 shadow-lg shadow-emerald-900/10">
                        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div
                        class="bg-red-500/10 border border-red-500/20 text-red-400 px-5 py-3 rounded-xl text-sm flex items-center gap-3 shadow-lg shadow-red-900/10">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- LEFT COLUMN (Spans 2): Teamsheets & Venues -->
                    <div class="lg:col-span-2 space-y-8">

                        <!-- TEAMSHEET PORTAL -->
                        <div
                            class="glass-panel p-8 rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-900/20 to-transparent relative overflow-hidden group">
                            <div
                                class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-colors pointer-events-none">
                            </div>

                            <div
                                class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                                <div class="flex items-start gap-5">
                                    <div
                                        class="bg-emerald-500/20 p-4 rounded-2xl flex-shrink-0 border border-emerald-500/30 shadow-inner">
                                        <i data-lucide="file-spreadsheet" class="w-8 h-8 text-emerald-400"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-white mb-1">Club Teamsheet</h2>
                                        <p class="text-slate-300 text-sm max-w-md leading-relaxed">Access your personal
                                            <?php echo $active_season_year; ?> club teamsheet. This is a live Google Sheet — no login or saving required.
                                            All changes are tracked automatically.</p>
                                    </div>
                                </div>

                                <div class="w-full sm:w-64 flex-shrink-0 flex flex-col gap-3">
                                    <?php
                                    $sheet_id = '';
                                    if ($my_teamsheet_link && preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $my_teamsheet_link, $match)) {
                                        $sheet_id = $match[1];
                                    }
                                    ?>
                                    <?php if ($my_teamsheet_link): ?>
                                        <a href="<?php echo htmlspecialchars($my_teamsheet_link); ?>" target="_blank"
                                            class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 px-6 rounded-xl transition-all shadow-lg shadow-emerald-900/30 flex items-center justify-center gap-3">
                                            <span>Open Teamsheet</span>
                                            <i data-lucide="external-link" class="w-4 h-4"></i>
                                        </a>

                                        <div class="flex flex-col gap-2 mt-1">
                                            <a href="smartprogrammenew.php?sheet_id=<?php echo htmlspecialchars($sheet_id); ?>"
                                                target="_blank"
                                                class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-sky-900/30 flex items-center justify-center gap-2 text-sm">
                                                <span>Print Smart Programme</span>
                                                <i data-lucide="printer" class="w-4 h-4"></i>
                                            </a>
                                            <p
                                                class="text-[11px] text-slate-400 text-center leading-tight px-1 border-b border-white/5 pb-2 mb-1">
                                                Automatically imports your live Teamsheet for printing.
                                            </p>

                                            <a href="smart-results-matcher.php?sheet_id=<?php echo htmlspecialchars($sheet_id); ?>"
                                                target="_blank"
                                                class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-purple-900/30 flex items-center justify-center gap-2 text-sm">
                                                <span>Smart Results Matcher</span>
                                                <i data-lucide="check-square" class="w-4 h-4"></i>
                                            </a>
                                            <p class="text-[11px] text-slate-400 text-center leading-tight px-1">
                                                Automatically fetches swimmers and matches times from gala results.
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="bg-amber-500/10 border border-amber-500/20 text-amber-400 p-3 rounded-lg text-sm text-center w-full">
                                            Link unavailable. Contact admin.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div
                                class="relative z-10 mt-6 pt-5 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <p class="text-xs text-emerald-400/80 font-medium flex items-center gap-2">
                                    <i data-lucide="info" class="w-4 h-4"></i> Round entries will be taken automatically on
                                    the submission deadline.
                                </p>
                            </div>
                        </div>

                        <!-- GALA SCORESHEET -->
                        <?php if (!empty($venues)): ?>
                            <div class="glass-panel p-8 rounded-2xl border border-sky-500/30 bg-gradient-to-br from-sky-900/20 to-transparent relative overflow-hidden group">
                                <div class="absolute right-0 top-0 w-64 h-64 bg-sky-500/5 rounded-full blur-3xl group-hover:bg-sky-500/10 transition-colors pointer-events-none"></div>

                                <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                                    <div class="flex items-start gap-5">
                                        <div class="bg-sky-500/20 p-4 rounded-2xl flex-shrink-0 border border-sky-500/30 shadow-inner">
                                            <i data-lucide="calculator" class="w-8 h-8 text-sky-400"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-2xl font-bold text-white mb-1">Gala Scoresheet</h2>
                                            <p class="text-slate-300 text-sm max-w-md leading-relaxed">Access the live results calculator for the galas you are hosting. Works offline at the pool.</p>
                                        </div>
                                    </div>

                                    <div class="w-full sm:w-64 flex-shrink-0 flex flex-col gap-3">
                                        <?php foreach ($venues as $v): ?>
                                            <a href="gala_scoresheet.php?venue_id=<?php echo $v['id']; ?>" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-sky-900/30 flex items-center justify-center gap-2">
                                                <span>Round <?php echo $v['round_number']; ?> Scoresheet</span>
                                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- VENUE MANAGEMENT -->
                        <div class="glass-panel p-6 rounded-2xl border border-white/5">
                            <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-5 h-5 text-sky-400"></i> Edit Host Venues
                            </h2>

                            <?php if (empty($venues)): ?>
                                <div class="bg-slate-800/50 rounded-xl p-8 text-center border border-white/5">
                                    <div
                                        class="bg-slate-700/50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i data-lucide="map" class="w-6 h-6 text-slate-400"></i>
                                    </div>
                                    <p class="text-slate-300 font-medium mb-1">No Venues Assigned</p>
                                    <p class="text-xs text-slate-500">Your club is not currently scheduled to host any rounds.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-6">
                                    <?php foreach ($venues as $venue): ?>
                                        <div class="bg-slate-900/50 p-5 rounded-2xl border border-white/5">
                                            <div class="flex justify-between items-center mb-4 pb-3 border-b border-white/5">
                                                <div>
                                                    <span
                                                        class="bg-sky-500/20 text-sky-400 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider mb-1 inline-block">Round
                                                        <?php echo $venue['round_number']; ?></span>
                                                    <h3 class="font-bold text-white">
                                                        <?php echo htmlspecialchars($venue['host_club_name']); ?>
                                                    </h3>
                                                </div>
                                            </div>

                                            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <input type="hidden" name="action" value="update_venue">
                                                <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">
                                                <input type="hidden" name="target_host_name"
                                                    value="<?php echo htmlspecialchars($venue['host_club_name']); ?>">

                                                <!-- Col 1 -->
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="form-label">Venue Name</label>
                                                        <div class="relative">
                                                            <i data-lucide="building-2"
                                                                class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                            <input type="text" name="venue_name"
                                                                value="<?php echo htmlspecialchars($venue['venue_name'] ?? ''); ?>"
                                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                placeholder="Leisure Centre Name">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Address</label>
                                                        <div class="relative">
                                                            <i data-lucide="map-pin"
                                                                class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                            <textarea name="address" rows="3"
                                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                placeholder="Full Address with Postcode"><?php echo htmlspecialchars($venue['address'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Col 2 -->
                                                <div class="space-y-4">
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label class="form-label">Warm Up</label>
                                                            <div class="relative">
                                                                <i data-lucide="clock"
                                                                    class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                                <input type="text" name="warmup_time"
                                                                    value="<?php echo htmlspecialchars($venue['warmup_time'] ?? ''); ?>"
                                                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                    placeholder="18:00">
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="form-label">Start Time</label>
                                                            <div class="relative">
                                                                <i data-lucide="play-circle"
                                                                    class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                                <input type="text" name="start_time"
                                                                    value="<?php echo htmlspecialchars($venue['start_time'] ?? ''); ?>"
                                                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                    placeholder="18:30">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="form-label">Payment Info</label>
                                                        <div class="relative">
                                                            <i data-lucide="credit-card"
                                                                class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                            <input type="text" name="payment_info"
                                                                value="<?php echo htmlspecialchars($venue['payment_info'] ?? ''); ?>"
                                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                placeholder="Cash Only / Card Accepted">
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="form-label">Parking Info</label>
                                                        <div class="relative">
                                                            <i data-lucide="car"
                                                                class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                            <input type="text" name="parking_info"
                                                                value="<?php echo htmlspecialchars($venue['parking_info'] ?? ''); ?>"
                                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                placeholder="Free 3hrs / Pay & Display">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="md:col-span-2 mt-2 flex justify-end">
                                                    <button type="submit"
                                                        class="bg-sky-600 hover:bg-sky-500 text-white font-bold py-2.5 px-6 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-sky-900/20 text-sm">
                                                        <i data-lucide="save" class="w-4 h-4"></i> Save Venue Details
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>



                    </div>

                    <!-- RIGHT COLUMN: Contacts & Security -->
                    <div class="space-y-8">

                        <!-- EDIT CONTACTS -->
                        <form method="POST" class="glass-panel p-6 rounded-2xl border border-white/5">
                            <input type="hidden" name="action" value="update_contacts">
                            <h2 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                                <i data-lucide="users" class="w-5 h-5 text-indigo-400"></i> Edit Team Contacts
                            </h2>

                            <div class="space-y-5">
                                <!-- Contact 1 -->
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Primary
                                        Contact</h3>
                                    <div class="space-y-3">
                                        <div>
                                            <input type="text" name="c1_name"
                                                value="<?php echo htmlspecialchars($my_club_data['contact1_name']); ?>"
                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600"
                                                placeholder="Name">
                                        </div>
                                        <div>
                                            <input type="email" name="c1_email"
                                                value="<?php echo htmlspecialchars($my_club_data['contact1_email']); ?>"
                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600"
                                                placeholder="Email">
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact 2 -->
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Contact 2
                                    </h3>
                                    <div class="space-y-3">
                                        <div>
                                            <input type="text" name="c2_name"
                                                value="<?php echo htmlspecialchars($my_club_data['contact2_name']); ?>"
                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600"
                                                placeholder="Name">
                                        </div>
                                        <div>
                                            <input type="email" name="c2_email"
                                                value="<?php echo htmlspecialchars($my_club_data['contact2_email']); ?>"
                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600"
                                                placeholder="Email">
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact 3 -->
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Contact 3
                                    </h3>
                                    <div class="space-y-3">
                                        <div>
                                            <input type="text" name="c3_name"
                                                value="<?php echo htmlspecialchars($my_club_data['contact3_name']); ?>"
                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600"
                                                placeholder="Name">
                                        </div>
                                        <div>
                                            <input type="email" name="c3_email"
                                                value="<?php echo htmlspecialchars($my_club_data['contact3_email']); ?>"
                                                class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600"
                                                placeholder="Email">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-900/20 text-sm">
                                    Save Contacts
                                </button>
                            </div>
                        </form>

                        <!-- SECURITY -->
                        <form method="POST" class="glass-panel p-6 rounded-2xl border border-orange-500/20 bg-orange-900/5">
                            <input type="hidden" name="action" value="change_pin">
                            <h2 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                                <i data-lucide="lock" class="w-5 h-5 text-orange-400"></i> Security PIN
                            </h2>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">Update your 4-digit dashboard access PIN.
                                Share this only with authorized club representatives.</p>

                            <div class="flex gap-3">
                                <input type="text" name="new_pin" placeholder="0000" maxlength="4" pattern="\d{4}"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white focus:outline-none focus:border-orange-500 transition-all placeholder-slate-600 text-center tracking-[0.3em] font-mono font-bold"
                                    required>
                                <button type="submit"
                                    class="bg-orange-600 hover:bg-orange-500 text-white font-bold px-4 rounded-xl transition-all flex-shrink-0 text-sm">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- ROUND DRAWS FULL WIDTH -->
                <div class="glass-panel p-6 rounded-3xl overflow-hidden border border-white/5 mb-8">
                    <h2 class="text-xl font-bold flex items-center gap-2 mb-6">
                        <i data-lucide="calendar-days" class="w-6 h-6 text-purple-400"></i> My Round Draws & Results
                    </h2>

                    <?php if (empty($draws)): ?>
                        <div class="bg-slate-800/50 rounded-xl p-8 text-center border border-white/5">
                            <div class="bg-slate-700/50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="calendar-x" class="w-6 h-6 text-slate-400"></i>
                            </div>
                            <p class="text-slate-300 font-medium mb-1">No Draws Available</p>
                            <p class="text-xs text-slate-500">Your club has not been assigned to any rounds yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                            <?php foreach ($draws as $draw): ?>
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="bg-purple-500/20 text-purple-400 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">Round <?php echo $draw['round_number']; ?></span>
                                        </div>
                                        <div class="mb-3">
                                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest block mb-1">Host Venue</span>
                                            <div class="text-white text-sm font-bold flex items-center gap-1.5">
                                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i> <?php echo htmlspecialchars($draw['host_name']); ?>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest block mb-1.5">Competing Teams</span>
                                            <div class="text-xs text-slate-300 flex flex-col gap-1">
                                                <?php 
                                                $competing = [];
                                                if ($draw['host_name']) $competing[] = $draw['host_name'];
                                                if ($draw['team1_name']) $competing[] = $draw['team1_name'];
                                                if ($draw['team2_name']) $competing[] = $draw['team2_name'];
                                                if ($draw['team3_name']) $competing[] = $draw['team3_name'];
                                                if ($draw['team4_name']) $competing[] = $draw['team4_name'];
                                                if ($draw['team5_name']) $competing[] = $draw['team5_name'];
                                                if ($draw['team6_name']) $competing[] = $draw['team6_name'];
                                                if ($draw['team7_name']) $competing[] = $draw['team7_name'];
                                                if ($draw['team8_name']) $competing[] = $draw['team8_name'];
                                                
                                                $competing = array_unique($competing);
                                                
                                                foreach($competing as $team) {
                                                    echo '<div class="flex items-center gap-1.5"><div class="w-1 h-1 rounded-full bg-slate-600"></div>' . htmlspecialchars($team) . '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-3 border-t border-white/5 mt-auto space-y-2">
                                        <?php if (!empty($draw['teamsheet_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($draw['teamsheet_link']); ?>" target="_blank" class="w-full bg-sky-600/20 hover:bg-sky-600 text-sky-400 hover:text-white border border-sky-500/30 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                                                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> View Teamsheets
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($draw['scoresheet_id']) && $draw['scoresheet_status'] === 'published'): ?>
                                            <a href="gala_scoresheet.php?id=<?php echo htmlspecialchars($draw['scoresheet_id']); ?>" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white border border-emerald-500 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-900/20">
                                                <i data-lucide="bar-chart" class="w-3.5 h-3.5"></i> Web Results
                                            </a>
                                        <?php elseif (!empty($draw['results_file'])): ?>
                                            <a href="uploads/results/<?php echo htmlspecialchars($draw['results_file']); ?>" download class="w-full bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/30 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Excel Results
                                            </a>
                                        <?php else: ?>
                                            <div class="w-full text-center text-[11px] text-slate-500 border border-slate-700/50 py-2 rounded-lg bg-slate-800/30">
                                                Results pending
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- FINALS CARD -->
                            <?php if ($my_final_tier): ?>
                                <div class="bg-sky-900/30 p-4 rounded-xl border border-sky-500/30 flex flex-col justify-between relative overflow-hidden group">
                                    <div class="absolute inset-x-0 -top-px h-px bg-gradient-to-r from-transparent via-sky-500 to-transparent opacity-50"></div>
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="bg-sky-500/20 text-sky-400 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">Final <?php echo $my_final_tier; ?></span>
                                            <i data-lucide="trophy" class="w-4 h-4 text-amber-400"></i>
                                        </div>
                                        <div class="mb-3">
                                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest block mb-1">Status</span>
                                            <div class="text-white text-sm font-bold flex items-center gap-1.5">
                                                Qualified
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-3 border-t border-white/5 mt-auto space-y-2">
                                        <?php if ($my_final_teamsheet_link): ?>
                                            <a href="<?php echo htmlspecialchars($my_final_teamsheet_link); ?>" target="_blank" class="w-full bg-sky-600/20 hover:bg-sky-600 text-sky-400 hover:text-white border border-sky-500/30 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                                                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> View Teamsheets
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($my_final_file): ?>
                                            <a href="uploads/results/<?php echo htmlspecialchars($my_final_file); ?>" download class="w-full bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/30 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download Results
                                            </a>
                                        <?php else: ?>
                                            <div class="w-full text-center text-[11px] text-slate-500 border border-slate-700/50 py-2 rounded-lg bg-slate-800/30">
                                                Results pending
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- DIRECTORY SECTION (From Contacts) -->
                <div class="glass-panel rounded-3xl overflow-hidden border border-white/5 mb-20">
                    <div
                        class="p-6 border-b border-white/5 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-900/50">
                        <div>
                            <h2 class="text-xl font-bold flex items-center gap-2">
                                <i data-lucide="book-open" class="w-6 h-6 text-slate-300"></i> League Directory
                            </h2>
                            <p class="text-slate-400 text-xs mt-1">Contact details for all league clubs.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="emailSelected()"
                                class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-2 shadow-lg">
                                <i data-lucide="mail" class="w-4 h-4"></i> Email Selected
                            </button>
                            <button type="button" onclick="copyEmails()"
                                class="bg-slate-800 hover:bg-slate-700 border border-slate-600 text-white text-sm font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-2">
                                <i data-lucide="copy" class="w-4 h-4"></i> Copy List
                            </button>
                        </div>
                    </div>

                    <!-- NEW FILTER BAR -->
                    <div class="bg-indigo-950/30 p-4 border-b border-white/5 flex flex-col md:flex-row gap-4 items-end">
                        <div class="w-full md:w-auto">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Filter
                                Directory By:</label>
                            <select id="mainFilter"
                                class="w-full md:w-64 bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:border-indigo-500 focus:outline-none transition-colors"
                                onchange="updateFilters()">
                                <option value="all">Show All Teams</option>
                                <option value="finals_A">A Final</option>
                                <option value="finals_B">B Final</option>
                                <option value="finals_C">C Final</option>
                                <option value="round_1">Round 1</option>
                                <option value="round_2">Round 2</option>
                                <option value="round_3">Round 3</option>
                                <option value="round_4">Round 4</option>
                            </select>
                        </div>
                        <div id="hostFilterContainer" class="w-full md:w-auto hidden">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Host
                                Venue:</label>
                            <select id="hostFilter"
                                class="w-full md:w-64 bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:border-indigo-500 focus:outline-none transition-colors"
                                onchange="applyFilters()">
                                <option value="">- Select Host -</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-slate-950/80 text-slate-400 text-xs uppercase tracking-wider border-b border-white/5">
                                    <th class="p-4 w-12 text-center">
                                        <input type="checkbox" id="selectAll"
                                            class="rounded bg-slate-800 border-slate-600 text-sky-500 focus:ring-sky-500 cursor-pointer w-4 h-4">
                                    </th>
                                    <th class="p-4">Club</th>
                                    <th class="p-4">Contact 1</th>
                                    <th class="p-4">Contact 2</th>
                                    <th class="p-4">Contact 3</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-sm bg-slate-900/20">
                                <?php foreach ($directory_data as $row): ?>
                                    <tr class="dir-row hover:bg-white/5 transition-colors group"
                                        data-club-id="<?php echo $row['club_id']; ?>">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" onchange="toggleRow(this)"
                                                class="row-checkbox rounded bg-slate-800 border-slate-600 text-sky-500 focus:ring-sky-500 cursor-pointer w-4 h-4">
                                        </td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-white/10 rounded-lg p-1 flex-shrink-0">
                                                    <?php if ($row['logo']): ?>
                                                        <img src="images/Teams/<?php echo htmlspecialchars($row['logo']); ?>"
                                                            class="w-full h-full object-contain">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center"><i
                                                                data-lucide="shield" class="w-4 h-4 text-slate-500"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <span
                                                    class="font-bold text-white"><?php echo htmlspecialchars($row['real_club_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($row['contact1_name']): ?>
                                                <div class="font-medium text-slate-200">
                                                    <?php echo htmlspecialchars($row['contact1_name']); ?>
                                                </div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <?php if ($row['contact1_email']): ?>
                                                        <input type="checkbox"
                                                            class="email-checkbox rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-emerald-500 cursor-pointer w-3.5 h-3.5"
                                                            value="<?php echo htmlspecialchars($row['contact1_email']); ?>">
                                                        <a href="mailto:<?php echo htmlspecialchars($row['contact1_email']); ?>"
                                                            class="text-sky-400 text-xs hover:underline"><?php echo htmlspecialchars($row['contact1_email']); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-600">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($row['contact2_name']): ?>
                                                <div class="font-medium text-slate-200">
                                                    <?php echo htmlspecialchars($row['contact2_name']); ?>
                                                </div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <?php if ($row['contact2_email']): ?>
                                                        <input type="checkbox"
                                                            class="email-checkbox rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-emerald-500 cursor-pointer w-3.5 h-3.5"
                                                            value="<?php echo htmlspecialchars($row['contact2_email']); ?>">
                                                        <a href="mailto:<?php echo htmlspecialchars($row['contact2_email']); ?>"
                                                            class="text-sky-400 text-xs hover:underline"><?php echo htmlspecialchars($row['contact2_email']); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-600">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($row['contact3_name']): ?>
                                                <div class="font-medium text-slate-200">
                                                    <?php echo htmlspecialchars($row['contact3_name']); ?>
                                                </div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <?php if ($row['contact3_email']): ?>
                                                        <input type="checkbox"
                                                            class="email-checkbox rounded bg-slate-800 border-slate-600 text-emerald-500 focus:ring-emerald-500 cursor-pointer w-3.5 h-3.5"
                                                            value="<?php echo htmlspecialchars($row['contact3_email']); ?>">
                                                        <a href="mailto:<?php echo htmlspecialchars($row['contact3_email']); ?>"
                                                            class="text-sky-400 text-xs hover:underline"><?php echo htmlspecialchars($row['contact3_email']); ?></a>
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

        // Checkbox Logic for Directory
        function toggleAll(source) {
            const rows = document.querySelectorAll('.dir-row');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const rowCheckbox = row.querySelector('.row-checkbox');
                    if (rowCheckbox) rowCheckbox.checked = source.checked;

                    const emailCheckboxes = row.querySelectorAll('.email-checkbox');
                    emailCheckboxes.forEach(cb => cb.checked = source.checked);
                }
            });
        }

        function toggleRow(source) {
            const row = source.closest('tr');
            const emailCheckboxes = row.querySelectorAll('.email-checkbox');
            emailCheckboxes.forEach(cb => cb.checked = source.checked);
        }

        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => toggleAll(e.target));
        }

        function getSelectedEmails() {
            let emails = [];
            document.querySelectorAll('.email-checkbox').forEach(cb => {
                const tr = cb.closest('tr');
                if (tr && tr.style.display === 'none') return; // Ignore hidden rows

                if (cb.checked && cb.value && cb.value.trim() !== '') {
                    emails.push(cb.value.trim());
                }
            });
            return [...new Set(emails)];
        }

        function emailSelected() {
            const emails = getSelectedEmails();
            if (emails.length === 0) {
                alert('Please select at least one contact to email.');
                return;
            }
            // Standard mailto string uses commas
            const bccList = emails.join(',');
            const mailtoLink = `mailto:?bcc=${encodeURIComponent(bccList)}`;
            const a = document.createElement('a');
            a.href = mailtoLink;
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function copyEmails() {
            const emails = getSelectedEmails();
            if (emails.length === 0) {
                alert('Please select at least one contact to copy.');
                return;
            }
            // Most email clients prefer comma separated list when pasting
            const emailString = emails.join(', ');

            if (navigator.clipboard) {
                try {
                    navigator.clipboard.writeText(emailString).then(() => {
                        alert('Emails copied to clipboard: ' + emails.length + ' addresses.');
                    }).catch(err => {
                        console.error('Clipboard API failed: ', err);
                        fallbackCopyTextToClipboard(emailString, emails.length);
                    });
                } catch (err) {
                    fallbackCopyTextToClipboard(emailString, emails.length);
                }
            } else {
                fallbackCopyTextToClipboard(emailString, emails.length);
            }
        }

        function fallbackCopyTextToClipboard(text, count) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.top = "-9999px";
            textArea.style.left = "-9999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('Emails copied to clipboard: ' + count + ' addresses.');
                } else {
                    prompt('Failed to copy automatically. Please copy the emails manually:', text);
                }
            } catch (err) {
                prompt('Failed to copy. Please copy manually:', text);
            }

            document.body.removeChild(textArea);
        }

        <?php if ($is_logged_in): ?>
            const filterMatrix = <?php echo json_encode($filter_matrix); ?>;

            function updateFilters() {
                const mainValue = document.getElementById('mainFilter').value;
                const hostContainer = document.getElementById('hostFilterContainer');
                const hostFilter = document.getElementById('hostFilter');

                if (mainValue.startsWith('round_')) {
                    const roundNum = mainValue.split('_')[1];
                    const hosts = filterMatrix.rounds[roundNum] || {};

                    // Populate hosts dropdown
                    hostFilter.innerHTML = '<option value="">- Select Host -</option>';
                    Object.keys(hosts).sort().forEach(host => {
                        const opt = document.createElement('option');
                        opt.value = host;
                        opt.textContent = host;
                        hostFilter.appendChild(opt);
                    });

                    hostContainer.classList.remove('hidden');
                } else {
                    hostContainer.classList.add('hidden');
                    hostFilter.value = "";
                }

                applyFilters();
            }

            function applyFilters() {
                const mainValue = document.getElementById('mainFilter').value;
                const hostValue = document.getElementById('hostFilter').value;
                const rows = document.querySelectorAll('.dir-row');

                let visibleIds = null; // null means show all

                if (mainValue.startsWith('finals_')) {
                    const finalType = mainValue.split('_')[1];
                    visibleIds = filterMatrix.finals[finalType] || [];
                } else if (mainValue.startsWith('round_')) {
                    if (hostValue !== "") {
                        const roundNum = mainValue.split('_')[1];
                        visibleIds = filterMatrix.rounds[roundNum][hostValue] || [];
                    } else {
                        visibleIds = null; // Show all until host is chosen
                    }
                }

                // Apply visibility
                rows.forEach(row => {
                    const rowClubId = parseInt(row.getAttribute('data-club-id'));
                    const checkbox = row.querySelector('.row-checkbox');

                    if (visibleIds === null || visibleIds.includes(rowClubId)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                        // Optional: Uncheck hidden rows so they aren't accidentally emailed
                        if (checkbox && checkbox.checked) {
                            checkbox.checked = false;
                            row.classList.remove('bg-sky-500/10');
                        }
                    }
                });

                // Update "Select All" checkbox state
                const allVisibleCheckboxes = document.querySelectorAll('.dir-row:not([style*="display: none"]) .row-checkbox');
                const checkedVisible = document.querySelectorAll('.dir-row:not([style*="display: none"]) .row-checkbox:checked');
                const selectAllCb = document.getElementById('selectAll');

                if (selectAllCb && allVisibleCheckboxes.length > 0) {
                    selectAllCb.checked = (allVisibleCheckboxes.length === checkedVisible.length);
                } else if (selectAllCb) {
                    selectAllCb.checked = false;
                }
            }
        <?php endif; ?>
    </script>
</body>

</html>
