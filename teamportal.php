<?php
require_once __DIR__ . '/security_headers.php';
cotswold_secure_session_start();
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

// Club DB shortname mapping is handled directly by club_id joins.

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $club_id = $_POST['club_id'] ?? '';
    $pin = $_POST['pin'] ?? '';

    if ($club_id && $pin) {
        $stmt = $conn->prepare("SELECT cc.id, cc.club_name FROM club_contacts cc JOIN clubs c ON cc.club_id = c.id WHERE cc.club_id = ? AND cc.access_pin = ? AND c.is_active = 1");
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
$digital_teamsheets_standalone = defined('DIGITAL_TEAMSHEETS_STANDALONE') && DIGITAL_TEAMSHEETS_STANDALONE;
$is_super_admin = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;
$admin_teamsheet_mode = false;

if ($digital_teamsheets_standalone && $is_super_admin && isset($_GET['admin_club_id'])) {
    $admin_club_id = (int)$_GET['admin_club_id'];
    $admin_club_stmt = $conn->prepare("SELECT name FROM clubs WHERE id = ? AND is_active = 1 LIMIT 1");
    $admin_club_stmt->bind_param("i", $admin_club_id);
    $admin_club_stmt->execute();
    $admin_club_row = $admin_club_stmt->get_result()->fetch_assoc();
    $admin_club_stmt->close();

    if ($admin_club_row) {
        $is_logged_in = true;
        $admin_teamsheet_mode = true;
        $current_club_id = $admin_club_id;
        $current_club_name = $admin_club_row['name'];
    } else {
        $error_msg = "Please choose an active club to manage digital teamsheets.";
    }
}

if ($is_logged_in && !$admin_teamsheet_mode) {
    $active_stmt = $conn->prepare("SELECT c.is_active FROM clubs c WHERE c.id = ?");
    $active_stmt->bind_param("i", $current_club_id);
    $active_stmt->execute();
    $active_res = $active_stmt->get_result();
    $active_row = $active_res->fetch_assoc();
    $active_stmt->close();
    if (!$active_row || (int) $active_row['is_active'] !== 1) {
        session_destroy();
        header("Location: teamportal.php");
        exit;
    }
}

function cotswold_portal_gala_label($round_number, $gala_type = 'round') {
    $final_labels = [
        'a_final' => 'A Final',
        'b_final' => 'B Final',
        'c_final' => 'C Final',
    ];
    if ((int)$round_number === 99 || isset($final_labels[$gala_type])) {
        return $final_labels[$gala_type] ?? 'Final';
    }
    return 'Round ' . (int)$round_number;
}

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
        $other_info = $_POST['other_info'] ?? '';
        $target_host_name = $_POST['target_host_name']; // Extracted from form for log

        // Audit check old values
        $old_sql = "SELECT * FROM venue_details
                    WHERE id = ?
                      AND season_year = ?
                      AND COALESCE(gala_type, 'round') = 'round'
                      AND round_number <> 99
                      AND club_id = ?";
        $old_stmt = $conn->prepare($old_sql);
        $old_stmt->bind_param("iii", $venue_id, $active_season_year, $current_club_id);
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
            if (($old_row['other_info'] ?? '') != $other_info)
                $changes[] = "Other Info: '" . ($old_row['other_info'] ?? '') . "' -> '$other_info'";

            if (empty($changes)) {
                $error_msg = "No changes detected for the venue.";
            } else {
                $update_sql = "UPDATE venue_details
                               SET venue_name=?, address=?, warmup_time=?, start_time=?, payment_info=?, parking_info=?, other_info=?
                               WHERE id=?
                                 AND season_year=?
                                 AND COALESCE(gala_type, 'round') = 'round'
                                 AND round_number <> 99
                                 AND club_id=?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("sssssssiii", $venue_name, $address, $warm_up, $start_time, $payment, $parking, $other_info, $venue_id, $active_season_year, $current_club_id);

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
$scoresheet_venues = [];

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

    // 3. Fetch My Venues
    $v_sql = "SELECT vd.*,
                     CASE
                         WHEN (vd.round_number = 99 OR vd.gala_type IN ('a_final','b_final','c_final'))
                         THEN COALESCE(c_scoresheet.name, c.name)
                         ELSE c.name
                     END AS host_club_name
              FROM venue_details vd
              JOIN clubs c ON vd.club_id = c.id
              LEFT JOIN clubs c_scoresheet ON vd.final_scoresheet_club_id = c_scoresheet.id
              WHERE vd.season_year = ?
                AND (
                    (
                        COALESCE(vd.gala_type, 'round') = 'round'
                        AND vd.round_number <> 99
                        AND vd.club_id = ?
                    )
                    OR
                    (
                        (vd.round_number = 99 OR vd.gala_type IN ('a_final','b_final','c_final'))
                        AND vd.final_scoresheet_club_id = ?
                    )
                )
              ORDER BY vd.round_number ASC, FIELD(vd.gala_type, 'round', 'a_final', 'b_final', 'c_final')";
    $v_stmt = $conn->prepare($v_sql);
    $v_stmt->bind_param("iii", $active_season_year, $current_club_id, $current_club_id);
    $v_stmt->execute();
    $v_res = $v_stmt->get_result();
    if ($v_res->num_rows > 0) {
        while ($row = $v_res->fetch_assoc()) {
            $scoresheet_venues[] = $row;
            if (($row['gala_type'] ?? 'round') === 'round' && (int)$row['round_number'] !== 99) {
                $venues[] = $row;
            }
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
                         gs.status AS scoresheet_status,
                         cts.id AS digital_teamsheet_id,
                         cts.status AS digital_teamsheet_status,
                         cts.submission_type AS digital_teamsheet_submission_type
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
                  LEFT JOIN gala_scoresheets gs ON gs.id = (
                      SELECT gs2.id
                      FROM gala_scoresheets gs2
                      WHERE gs2.venue_detail_id = vd.id
                        AND gs2.season_year = vd.season_year
                      ORDER BY gs2.updated_at DESC, gs2.id DESC
                      LIMIT 1
                  )
                  LEFT JOIN club_teamsheets cts ON cts.id = (
                      SELECT cts2.id
                      FROM club_teamsheets cts2
                      WHERE cts2.club_id = ?
                        AND cts2.season_year = vd.season_year
                        AND cts2.venue_detail_id = vd.id
                      ORDER BY cts2.updated_at DESC, cts2.id DESC
                      LIMIT 1
                  )
                  WHERE vd.season_year = ?
                    AND (
                        (
                            COALESCE(vd.gala_type, 'round') = 'round'
                            AND vd.round_number <> 99
                            AND (vd.club_id = ? OR vd.team_1_id = ? OR vd.team_2_id = ? OR vd.team_3_id = ? OR vd.team_4_id = ? OR vd.team_5_id = ? OR vd.team_6_id = ? OR vd.team_7_id = ? OR vd.team_8_id = ?)
                        )
                        OR
                        (
                            (vd.round_number = 99 OR vd.gala_type IN ('a_final','b_final','c_final'))
                            AND (vd.team_1_id = ? OR vd.team_2_id = ? OR vd.team_3_id = ? OR vd.team_4_id = ? OR vd.team_5_id = ? OR vd.team_6_id = ? OR vd.team_7_id = ? OR vd.team_8_id = ?)
                        )
                    )
                  ORDER BY vd.round_number ASC, FIELD(vd.gala_type, 'round', 'a_final', 'b_final', 'c_final')";
    $d_stmt = $conn->prepare($draws_sql);
    $d_stmt->bind_param(
        "iiiiiiiiiiiiiiiiiii",
        $current_club_id,
        $active_season_year,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id,
        $current_club_id
    );
    $d_stmt->execute();
    $d_res = $d_stmt->get_result();
    $seen_draw_venue_ids = [];
    if ($d_res->num_rows > 0) {
        while ($row = $d_res->fetch_assoc()) {
            $venue_id = (int)$row['id'];
            if (isset($seen_draw_venue_ids[$venue_id])) {
                continue;
            }
            $seen_draw_venue_ids[$venue_id] = true;
            $draws[] = $row;
        }
    }
    $d_stmt->close();

    // 4. Fetch Directory Data
    $sql = "SELECT cc.*, c.logo, c.name as real_club_name FROM club_contacts cc JOIN clubs c ON cc.club_id = c.id WHERE c.is_active = 1 ORDER BY c.name ASC";
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
    $standings_sql = "SELECT c.id FROM results r JOIN clubs c ON r.club_id = c.id WHERE r.season_year = $active_season_year ORDER BY (r.round_1 + r.round_2 + r.round_3 + r.round_4) DESC, c.name ASC";
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

    // 6. Recent venue audit logs (Documents tab)
    $recent_logs = [];
    $log_res = $conn->query("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 3");
    if ($log_res && $log_res->num_rows > 0) {
        while ($l = $log_res->fetch_assoc()) {
            $recent_logs[] = $l;
        }
    }
} else {
    // Populate Dropdown for Login
    $sql = "SELECT cc.club_id, cc.club_name FROM club_contacts cc JOIN clubs c ON cc.club_id = c.id WHERE c.is_active = 1 ORDER BY cc.club_name ASC";
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
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
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

        .portal-tab-btn.active {
            background: rgba(14, 165, 233, 0.2);
            color: #fff;
            border-color: rgba(14, 165, 233, 0.4);
        }

        .portal-doc-row .portal-doc-title {
            word-break: break-word;
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
                    <div class="mt-6 pt-4 border-t border-slate-700/50 text-center">
                        <a href="league_admin.php" class="text-slate-500 hover:text-slate-400 text-sm flex items-center justify-center gap-1 transition-colors">
                            <i data-lucide="shield-alert" class="w-4 h-4"></i> League Admin Login
                        </a>
                    </div>
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
                            <p class="text-sky-400 text-sm font-medium"><?php echo $admin_teamsheet_mode ? 'Super Admin Digital Teamsheets' : ($digital_teamsheets_standalone ? 'Digital Teamsheets' : 'Team Dashboard'); ?></p>
                        </div>
                    </div>

                    <div class="relative z-10 flex flex-wrap justify-center gap-2">
                        <?php if (!$admin_teamsheet_mode): ?>
                        <?php if ($digital_teamsheets_standalone): ?>
                        <a href="teamportal.php"
                            class="bg-slate-800 hover:bg-sky-500/10 hover:text-sky-400 border border-slate-700 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Portal Home
                        </a>
                        <?php endif; ?>
                        <a href="?action=logout"
                            class="bg-slate-800 hover:bg-red-500/10 hover:text-red-400 border border-slate-700 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all">
                            <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                        </a>
                        <?php else: ?>
                        <span class="bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4"></i> Editing as Super Admin
                        </span>
                        <?php endif; ?>
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

                <?php $show_portal_tabs = !$digital_teamsheets_standalone && !$admin_teamsheet_mode; ?>

                <?php if ($show_portal_tabs): ?>
                <nav class="glass-panel p-2 rounded-2xl border border-white/5 flex flex-wrap gap-2" aria-label="Team Portal sections">
                    <button type="button" data-portal-tab="overview" onclick="switchPortalTab('overview')"
                        class="portal-tab-btn active flex-1 min-w-[7rem] px-4 py-2.5 rounded-xl text-sm font-bold border border-transparent text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Overview
                    </button>
                    <button type="button" data-portal-tab="documents" onclick="switchPortalTab('documents')"
                        class="portal-tab-btn flex-1 min-w-[7rem] px-4 py-2.5 rounded-xl text-sm font-bold border border-transparent text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="files" class="w-4 h-4"></i> Documents
                    </button>
                    <button type="button" data-portal-tab="checklist" onclick="switchPortalTab('checklist')"
                        class="portal-tab-btn flex-1 min-w-[7rem] px-4 py-2.5 rounded-xl text-sm font-bold border border-transparent text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="list-checks" class="w-4 h-4"></i> Host Checklist
                    </button>
                    <button type="button" data-portal-tab="directory" onclick="switchPortalTab('directory')"
                        class="portal-tab-btn flex-1 min-w-[7rem] px-4 py-2.5 rounded-xl text-sm font-bold border border-transparent text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="book-open" class="w-4 h-4"></i> Directory
                    </button>
                    <button type="button" data-portal-tab="account" onclick="switchPortalTab('account')"
                        class="portal-tab-btn flex-1 min-w-[7rem] px-4 py-2.5 rounded-xl text-sm font-bold border border-transparent text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="user-cog" class="w-4 h-4"></i> Account
                    </button>
                </nav>
                <?php endif; ?>

                <section id="portal-overview" class="portal-section <?php echo $show_portal_tabs ? '' : 'block'; ?> space-y-8">

                <div class="space-y-8">

                        <!-- DIGITAL TEAMSHEETS -->
                        <?php if ($digital_teamsheets_standalone): ?>
                        <div id="digital-teamsheets"
                            class="glass-panel p-8 rounded-2xl border border-cyan-500/30 bg-gradient-to-br from-cyan-900/20 to-transparent relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-64 h-64 bg-cyan-500/5 rounded-full blur-3xl pointer-events-none"></div>

                            <div class="relative z-10 flex flex-col gap-6">
                                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-5">
                                    <div class="flex items-start gap-5">
                                        <div class="bg-cyan-500/20 p-4 rounded-2xl flex-shrink-0 border border-cyan-500/30 shadow-inner">
                                            <i data-lucide="clipboard-list" class="w-8 h-8 text-cyan-300"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-3 mb-1">
                                                <h2 class="text-2xl font-bold text-white">Digital Teamsheets</h2>
                                            </div>
                                            <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                                                Build swimmer lists and round teamsheets inside the portal.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-2 w-full xl:w-auto xl:flex-shrink-0">
                                        <button type="button" onclick="openDigitalTeamsheetsHelp()"
                                            class="bg-cyan-600/20 hover:bg-cyan-600 text-cyan-300 hover:text-white border border-cyan-500/30 font-bold py-2.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2 text-sm whitespace-nowrap">
                                            <i data-lucide="circle-help" class="w-4 h-4"></i> Help
                                        </button>
                                        <button type="button" onclick="loadDigitalTeamsheets(true)"
                                            class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 font-bold py-2.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2 text-sm whitespace-nowrap">
                                            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Refresh
                                        </button>
                                        <button type="button" onclick="copyPreviousSeasonSwimmers()"
                                            class="bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-2.5 px-4 rounded-xl transition-all shadow-lg shadow-cyan-900/20 flex items-center justify-center gap-2 text-sm whitespace-nowrap">
                                            <i data-lucide="copy" class="w-4 h-4"></i> Copy Previous Season
                                        </button>
                                    </div>
                                </div>

                                <div id="dts-alert" class="hidden rounded-xl px-4 py-3 text-sm font-semibold"></div>

                                <div id="dts-help-modal" class="hidden fixed inset-0 z-[120] bg-slate-950/80 backdrop-blur-sm p-4 items-center justify-center">
                                    <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto bg-slate-950 border border-cyan-500/30 rounded-2xl shadow-2xl shadow-slate-950/60">
                                        <div class="p-5 border-b border-white/10 flex items-start justify-between gap-4">
                                            <div>
                                                <h4 class="text-lg font-bold text-white flex items-center gap-2">
                                                    <i data-lucide="circle-help" class="w-5 h-5 text-cyan-300"></i> Digital Teamsheets Guide
                                                </h4>
                                                <p class="text-xs text-slate-400 mt-1">Swimmer lists, teamsheet building, imports, submission, sharing, and audit history.</p>
                                            </div>
                                            <button type="button" onclick="closeDigitalTeamsheetsHelp()" aria-label="Close Digital Teamsheets guide"
                                                class="bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 p-2 rounded-lg">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                        <div class="p-5 space-y-5 text-sm text-slate-300 leading-relaxed">
                                            <section class="bg-slate-900/70 border border-white/5 rounded-xl p-4">
                                                <h5 class="text-xs font-bold uppercase tracking-widest text-cyan-300 mb-3">Recommended Order</h5>
                                                <ol class="space-y-2 list-decimal list-inside">
                                                    <li>Start in <strong class="text-white">Swimmer List</strong> and build the season swimmer database.</li>
                                                    <li>Use <strong class="text-white">Copy Previous Season</strong> if returning swimmers exist, then update ages, PBs, and availability.</li>
                                                    <li>Open <strong class="text-white">Teamsheet Builder</strong>, select the round or final, choose swimmers, and save a draft.</li>
                                                    <li>Submit the teamsheet once checked. It is then shared with the clubs in that gala.</li>
                                                </ol>
                                            </section>

                                            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="bg-slate-900/70 border border-white/5 rounded-xl p-4">
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-cyan-300 mb-3">Swimmer List</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>Add each swimmer once for the active season.</li>
                                                        <li>Set the age group with the dropdown and enter PBs for supported league events.</li>
                                                        <li>Tick availability for each round and final so the builder can filter selections.</li>
                                                        <li>Changes autosave shortly after editing, and <strong class="text-white">Save List</strong> is available for a manual save.</li>
                                                    </ol>
                                                </div>

                                                <div class="bg-slate-900/70 border border-white/5 rounded-xl p-4">
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-cyan-300 mb-3">Importing Times</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>Choose the correct provider from the import dropdown, then upload that platform's best-times export.</li>
                                                        <li><strong class="text-white">TeamUnify CSV</strong> calculates age groups from DOB when a finals date is available.</li>
                                                        <li><strong class="text-white">Swim Club Manager XLSX</strong> uses ages in the workbook; <strong class="text-white">Hy-Tek Team Manager CSV</strong> leaves age groups blank.</li>
                                                        <li>All importers match existing swimmers by name, add new swimmers, map supported PB events, and show unsupported events before applying.</li>
                                                    </ol>
                                                </div>
                                            </section>

                                            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="bg-slate-900/70 border border-white/5 rounded-xl p-4">
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-cyan-300 mb-3">Teamsheet Builder</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>Select the correct round or A/B/C Final from the dropdown.</li>
                                                        <li>Individual events take one swimmer; relays and cannons use ordered swimmer dropdowns.</li>
                                                        <li>Relay and cannon PB fields are greyed out because they are not required.</li>
                                                        <li>Use <strong class="text-white">Show Available Only</strong>, <strong class="text-white">Copy Round</strong>, and per-event minimise buttons to move through the list faster.</li>
                                                    </ol>
                                                </div>

                                                <div class="bg-slate-900/70 border border-white/5 rounded-xl p-4">
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-cyan-300 mb-3">Validation And Autosave</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>Warnings appear when an event has too few swimmers, duplicate swimmers, or unavailable swimmers.</li>
                                                        <li>You can ignore a warning for an event when the choice is intentional.</li>
                                                        <li>Teamsheet edits autosave after selection, PB, or note changes.</li>
                                                        <li>Editing a submitted teamsheet requires a reason, which is recorded in the audit history.</li>
                                                    </ol>
                                                </div>
                                            </section>

                                            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="bg-slate-900/70 border border-white/5 rounded-xl p-4">
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-cyan-300 mb-3">Submitting And Sharing</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li><strong class="text-white">Save Draft</strong> keeps the teamsheet private to your club while you continue editing.</li>
                                                        <li><strong class="text-white">Submit</strong> shares the teamsheet automatically with the clubs in the same gala.</li>
                                                        <li>The <strong class="text-white">Shared Teamsheets</strong> tab shows submitted sheets from your club and the other clubs in your gala group.</li>
                                                        <li>Submitted digital teamsheets can feed programme generation and the results matcher once the downstream tools are available.</li>
                                                    </ol>
                                                </div>

                                                <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-amber-300 mb-3">Finals Visibility</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>A, B, and C Finals appear only when your club has been assigned to that final.</li>
                                                        <li>Final team assignments are synced from league standings as rounds are published.</li>
                                                        <li>If a final is missing after qualification is known, refresh the page first, then ask the league admin to check finals sync.</li>
                                                    </ol>
                                                </div>
                                            </section>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-slate-950/40 border border-white/5 rounded-2xl p-2 flex flex-col sm:flex-row gap-2">
                                    <button type="button" data-dts-tab="swimmers" onclick="switchDtsTab('swimmers')"
                                        class="dts-tab-btn flex-1 px-4 py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-300 hover:text-white hover:bg-slate-800">
                                        <i data-lucide="users" class="w-4 h-4"></i> Swimmer List
                                    </button>
                                    <button type="button" data-dts-tab="builder" onclick="switchDtsTab('builder')"
                                        class="dts-tab-btn flex-1 px-4 py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-300 hover:text-white hover:bg-slate-800">
                                        <i data-lucide="list-checks" class="w-4 h-4"></i> Teamsheet Builder
                                    </button>
                                    <button type="button" data-dts-tab="shared" onclick="switchDtsTab('shared')"
                                        class="dts-tab-btn flex-1 px-4 py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-300 hover:text-white hover:bg-slate-800">
                                        <i data-lucide="share-2" class="w-4 h-4"></i> Shared Teamsheets
                                    </button>
                                </div>

                                <section id="dts-tab-swimmers" class="dts-tab-panel bg-slate-950/40 border border-white/5 rounded-2xl overflow-hidden">
                                    <div class="p-4 border-b border-white/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                                <i data-lucide="users" class="w-5 h-5 text-cyan-300"></i> Swimmer List
                                            </h3>
                                            <p class="text-xs text-slate-400 mt-1">Edit names, age groups, PBs, and availability in one place.</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <div class="flex flex-wrap sm:flex-nowrap">
                                                <input id="dts-teamunify-file" type="file" accept=".csv,text/csv" class="hidden" onchange="previewTeamunifyImport(this.files[0])">
                                                <input id="dts-scm-file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" onchange="previewSwimClubManagerImport(this.files[0])">
                                                <input id="dts-hytek-file" type="file" accept=".csv,text/csv" class="hidden" onchange="previewHyTekImport(this.files[0])">
                                                <select id="dts-import-provider" onchange="updateImportProviderHelp()"
                                                    class="bg-slate-900 border border-slate-700 border-r-0 rounded-l-lg px-3 py-2 text-xs font-bold text-white min-w-[190px] focus:outline-none focus:border-cyan-400">
                                                    <option value="teamunify">TeamUnify CSV</option>
                                                    <option value="scm">Swim Club Manager XLSX</option>
                                                    <option value="hytek">Hy-Tek Team Manager CSV</option>
                                                </select>
                                                <button id="dts-import-button" type="button" onclick="startSelectedImport()"
                                                    class="bg-cyan-600/20 hover:bg-cyan-600 text-cyan-300 hover:text-white border border-cyan-500/30 border-r-0 font-bold py-2 px-3 text-xs flex items-center gap-1.5">
                                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i> Import
                                                </button>
                                                <button id="dts-teamunify-guide-button" type="button" onclick="openTeamunifyGuide()" title="TeamUnify import guide" aria-label="TeamUnify import guide"
                                                    class="bg-cyan-600/20 hover:bg-cyan-600 text-cyan-300 hover:text-white border border-cyan-500/30 font-bold py-2 px-2 rounded-r-lg text-xs flex items-center">
                                                    <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                                                </button>
                                                <button id="dts-scm-guide-button" type="button" onclick="openSwimClubManagerGuide()" title="Swim Club Manager import guide" aria-label="Swim Club Manager import guide"
                                                    class="hidden bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-bold py-2 px-2 rounded-r-lg text-xs flex items-center">
                                                    <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </div>
                                            <?php if (strcasecmp((string)$current_club_name, 'Academy Swim Team') === 0): ?>
                                                <button id="dts-ast-import-button" type="button" onclick="importAstSwimmers()"
                                                    class="bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Import from AST
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" onclick="addSwimmerRow()"
                                                class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Swimmer
                                            </button>
                                            <button type="button" onclick="saveSwimmers()"
                                                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                                <i data-lucide="save" class="w-3.5 h-3.5"></i> Save List
                                            </button>
                                            <span id="dts-swimmers-autosave-status" class="hidden self-center text-[11px] font-semibold text-slate-500"></span>
                                        </div>
                                    </div>
                                    <div id="dts-teamunify-preview" class="hidden border-b border-sky-500/20 bg-sky-500/10 p-4"></div>
                                    <div id="dts-scm-preview" class="hidden border-b border-indigo-500/20 bg-indigo-500/10 p-4"></div>
                                    <div id="dts-hytek-preview" class="hidden border-b border-emerald-500/20 bg-emerald-500/10 p-4"></div>
                                        <div id="dts-teamunify-guide-modal" class="hidden fixed inset-0 z-[120] bg-slate-950/80 backdrop-blur-sm p-4 items-center justify-center">
                                            <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-slate-950 border border-sky-500/30 rounded-2xl shadow-2xl shadow-slate-950/60">
                                            <div class="p-5 border-b border-white/10 flex items-start justify-between gap-4">
                                                <div>
                                                    <h4 class="text-lg font-bold text-white flex items-center gap-2">
                                                        <i data-lucide="circle-help" class="w-5 h-5 text-sky-300"></i> TeamUnify Import Guide
                                                    </h4>
                                                    <p class="text-xs text-slate-400 mt-1">Exporting times for the Cotswold League teamsheet.</p>
                                                </div>
                                                <button type="button" onclick="closeTeamunifyGuide()" aria-label="Close TeamUnify import guide"
                                                    class="bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 p-2 rounded-lg">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                            <div class="p-5 space-y-5 text-sm text-slate-300">
                                                <div>
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-2">Phase 1: Generate The Report In TeamUnify</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>From the left-hand sidebar, select <strong class="text-white">Events &amp; Competition</strong>, then click <strong class="text-white">Time Reports</strong>.</li>
                                                        <li>Choose <strong class="text-white">Top Times By Athletes</strong>.</li>
                                                        <li>Click <strong class="text-white">Configure this page</strong> and set <strong class="text-white">Course</strong> to <strong class="text-white">SCM</strong>, set <strong class="text-white">Age Up Date</strong> to the finals date, and make sure <strong class="text-white">Show birth date</strong> is checked.</li>
                                                        <li>Click <strong class="text-white">Report Now</strong>. On the report page, click <strong class="text-white">Save as Excel</strong>. This opens the data in a new Excel Online tab.</li>
                                                    </ol>
                                                </div>
                                                <div>
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-2">Phase 2: Save As CSV</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>In the Excel Online tab, go to <strong class="text-white">File</strong> &gt; <strong class="text-white">Export</strong>.</li>
                                                        <li>Select <strong class="text-white">Download as CSV</strong>. The file will save to your computer, usually in Downloads.</li>
                                                    </ol>
                                                </div>
                                                <div>
                                                    <h5 class="text-xs font-bold uppercase tracking-widest text-sky-300 mb-2">Phase 3: Import</h5>
                                                    <ol class="space-y-2 list-decimal list-inside">
                                                        <li>Open the Cotswold League Teamsheet builder.</li>
                                                        <li>Click <strong class="text-white">Import TeamUnify CSV</strong> and upload the CSV file you downloaded.</li>
                                                    </ol>
                                                </div>
                                                <div class="bg-sky-500/10 border border-sky-500/20 rounded-xl p-4 text-xs text-sky-100 leading-relaxed">
                                                    <strong class="text-white">Pro tip:</strong> TeamUnify and the Teamsheet builder are both browser-based, so keeping them in side-by-side tabs makes it easy to verify that swimmer times have carried over correctly.
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                        <div id="dts-scm-guide-modal" class="hidden fixed inset-0 z-[120] bg-slate-950/80 backdrop-blur-sm p-4 items-center justify-center">
                                            <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-slate-950 border border-indigo-500/30 rounded-2xl shadow-2xl shadow-slate-950/60">
                                                <div class="p-5 border-b border-white/10 flex items-start justify-between gap-4">
                                                    <div>
                                                        <h4 class="text-lg font-bold text-white flex items-center gap-2">
                                                            <i data-lucide="circle-help" class="w-5 h-5 text-indigo-300"></i> Swim Club Manager Import Guide
                                                        </h4>
                                                        <p class="text-xs text-slate-400 mt-1">Generating the correct Group PB report for the league teamsheet.</p>
                                                    </div>
                                                    <button type="button" onclick="closeSwimClubManagerGuide()" aria-label="Close Swim Club Manager import guide"
                                                        class="bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 p-2 rounded-lg">
                                                        <i data-lucide="x" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                                <div class="p-5 space-y-5 text-sm text-slate-300">
                                                    <div>
                                                        <h5 class="text-xs font-bold uppercase tracking-widest text-indigo-300 mb-2">Phase 1: Generate The Report In Swim Club Manager</h5>
                                                        <ol class="space-y-2 list-decimal list-inside">
                                                            <li>Open <strong class="text-white">Reports &gt; Swim times &gt; Group PBs</strong>.</li>
                                                            <li>Choose <strong class="text-white">Age as of date</strong>.</li>
                                                            <li>Set the date to the <strong class="text-white">Final</strong> date for the league.</li>
                                                            <li>Export the report as <strong class="text-white">XLSX</strong>, then use the Swim Club Manager import option in the Teamsheet Builder.</li>
                                                        </ol>
                                                    </div>
                                                    <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/10 p-4 text-xs text-indigo-100 leading-relaxed">
                                                        The builder uses the ages in the workbook, so the report needs to be generated using the same final date that the league is using for age-group checks.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <div class="overflow-auto max-h-[68vh]">
                                        <table class="min-w-[1680px] w-full text-xs text-slate-300 border-separate border-spacing-0">
                                            <thead class="bg-slate-900/95 text-slate-400 uppercase tracking-wider sticky top-0 z-10">
                                                <tr>
                                                    <th class="px-3 py-2 text-left w-72 sticky left-0 z-30 bg-slate-900/95 border-r border-white/10">Swimmer Name</th>
                                                    <th class="px-3 py-2 text-left w-28">Age Group</th>
                                                    <th class="px-3 py-2">25 Free</th>
                                                    <th class="px-3 py-2">25 Back</th>
                                                    <th class="px-3 py-2">25 Breast</th>
                                                    <th class="px-3 py-2">25 Fly</th>
                                                    <th class="px-3 py-2">50 Free</th>
                                                    <th class="px-3 py-2">50 Back</th>
                                                    <th class="px-3 py-2">50 Breast</th>
                                                    <th class="px-3 py-2">50 Fly</th>
                                                    <th class="px-3 py-2">IM</th>
                                                    <th class="px-3 py-2">100 Free</th>
                                                    <th class="px-3 py-2">100 Back</th>
                                                    <th class="px-3 py-2">100 Breast</th>
                                                    <th class="px-3 py-2">100 Fly</th>
                                                    <th class="px-3 py-2 normal-case leading-tight">Round 1<br><span class="text-[10px] text-slate-500 uppercase">Available</span></th>
                                                    <th class="px-3 py-2 normal-case leading-tight">Round 2<br><span class="text-[10px] text-slate-500 uppercase">Available</span></th>
                                                    <th class="px-3 py-2 normal-case leading-tight">Round 3<br><span class="text-[10px] text-slate-500 uppercase">Available</span></th>
                                                    <th class="px-3 py-2 normal-case leading-tight">Round 4<br><span class="text-[10px] text-slate-500 uppercase">Available</span></th>
                                                    <th class="px-3 py-2 normal-case leading-tight">Final<br><span class="text-[10px] text-slate-500 uppercase">Available</span></th>
                                                    <th class="px-3 py-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="dts-swimmers-body" class="divide-y divide-white/5"></tbody>
                                        </table>
                                    </div>
                                </section>

                                <section id="dts-tab-builder" class="dts-tab-panel bg-slate-950/40 border border-white/5 rounded-2xl overflow-hidden hidden">
                                    <div class="p-4 border-b border-white/5 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                                <i data-lucide="list-checks" class="w-5 h-5 text-cyan-300"></i> Teamsheet Builder
                                            </h3>
                                            <p class="text-xs text-slate-400 mt-1">Choose a round, select swimmers, save as draft, then submit when ready.</p>
                                        </div>
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <select id="dts-round-select" onchange="selectDigitalRound()"
                                                class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white min-w-[260px] focus:outline-none focus:border-cyan-400">
                                            </select>
                                            <div class="flex">
                                                <select id="dts-copy-source-select"
                                                    class="bg-slate-900 border border-slate-700 border-r-0 rounded-l-lg px-3 py-2 text-sm text-white min-w-[190px] focus:outline-none focus:border-cyan-400">
                                                    <option value="">Copy from...</option>
                                                </select>
                                                <button type="button" onclick="copyRoundTeamsheet()"
                                                    class="bg-slate-800 hover:bg-slate-700 text-cyan-300 hover:text-white border border-slate-700 font-bold py-2 px-3 rounded-r-lg text-xs flex items-center justify-center gap-1.5">
                                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i> Copy Round
                                                </button>
                                            </div>
                                            <label class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-300 flex items-center justify-center gap-2 cursor-pointer hover:border-cyan-500/60">
                                                <input id="dts-available-only-toggle" type="checkbox" onchange="toggleAvailableOnly(this.checked)"
                                                    class="rounded border-slate-700 bg-slate-950 text-cyan-500">
                                                <span class="font-bold text-xs whitespace-nowrap">Show Available Only</span>
                                            </label>
                                            <button type="button" onclick="saveTeamsheet(false)"
                                                class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 font-bold py-2 px-4 rounded-lg text-sm flex items-center justify-center gap-2">
                                                <i data-lucide="save" class="w-4 h-4"></i> Save Draft
                                            </button>
                                            <button type="button" onclick="saveTeamsheet(true)"
                                                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-4 rounded-lg text-sm flex items-center justify-center gap-2">
                                                <i data-lucide="send" class="w-4 h-4"></i> Submit
                                            </button>
                                            <span id="dts-teamsheet-autosave-status" class="hidden self-center text-[11px] font-semibold text-slate-500"></span>
                                        </div>
                                    </div>

                                    <div id="dts-teamsheet-meta" class="px-4 py-3 bg-slate-900/40 border-b border-white/5 text-xs text-slate-400"></div>
                                    <div id="dts-audit-list" class="hidden px-4 py-3 bg-amber-500/10 border-b border-amber-500/20 text-xs text-amber-100"></div>

                                    <div class="px-4 py-3 bg-slate-900/30 border-b border-white/5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <label class="inline-flex items-center gap-2 text-sm text-slate-200 font-bold cursor-pointer">
                                                <input id="dts-upload-mode-toggle" type="checkbox" onchange="toggleTeamsheetUploadMode(this.checked)"
                                                    class="rounded border-slate-700 bg-slate-950 text-cyan-500">
                                                <span>Upload our own teamsheet document</span>
                                            </label>
                                            <button type="button" onclick="toggleUploadHelp()"
                                                class="w-6 h-6 rounded-full bg-slate-800 hover:bg-cyan-600 text-cyan-300 hover:text-white border border-slate-700 hover:border-cyan-500 text-xs font-black flex items-center justify-center"
                                                aria-expanded="false" aria-controls="dts-upload-help" title="How uploaded teamsheets work">
                                                ?
                                            </button>
                                        </div>
                                        <div id="dts-upload-help" class="hidden mt-3 rounded-xl border border-sky-500/20 bg-sky-500/10 p-3 text-xs text-sky-100 leading-relaxed">
                                            <div class="font-bold text-white mb-1">Uploaded teamsheets must match the digital teamsheet information.</div>
                                            <div>Your document should include the same details as the builder: every event, selected swimmer names in order, PBs where needed, and any host notes. Once uploaded, it is submitted for this selected round/final and shared with the other clubs in that gala.</div>
                                            <div class="mt-2 text-sky-200">Uploaded documents cannot be checked by the website event-by-event, copied into another round, exported as a generated CSV, or used by Smart Results Matcher. If you need those tools, use the digital builder instead.</div>
                                        </div>
                                        <div id="dts-upload-panel" class="hidden mt-3 rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-3">
                                            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                                                <input id="dts-upload-file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.rtf,.odt"
                                                    class="flex-1 bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-cyan-400 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-cyan-600 file:text-white hover:file:bg-cyan-500 cursor-pointer">
                                                <button type="button" onclick="uploadOwnTeamsheet()"
                                                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-4 rounded-lg text-sm flex items-center justify-center gap-2">
                                                    <i data-lucide="upload-cloud" class="w-4 h-4"></i> Upload & Submit
                                                </button>
                                            </div>
                                            <div id="dts-upload-current" class="mt-2 text-xs text-slate-400"></div>
                                        </div>
                                    </div>

                                    <div id="dts-builder-table-wrap" class="overflow-auto max-h-[68vh]">
                                        <table class="min-w-[960px] w-full text-xs text-slate-300">
                                            <thead class="bg-slate-900/95 text-slate-400 uppercase tracking-wider sticky top-0 z-10">
                                                <tr>
                                                    <th class="px-3 py-2 w-14 text-center">No</th>
                                                    <th class="px-3 py-2 text-left">Event</th>
                                                    <th class="px-3 py-2 w-24 text-center">Cut Off</th>
                                                    <th class="px-3 py-2 w-[360px] text-left">Swimmer(s)</th>
                                                    <th class="px-3 py-2 w-32 text-left">PB</th>
                                                    <th class="px-3 py-2 w-52 text-left">Host Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody id="dts-events-body" class="divide-y divide-white/5"></tbody>
                                        </table>
                                    </div>

                                    <div class="p-4 border-t border-white/5 flex flex-wrap gap-2">
                                        <a id="dts-export-link" href="#" target="_blank"
                                            class="hidden bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 font-bold py-2 px-3 rounded-lg text-xs items-center gap-1.5">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
                                        </a>
                                        <a id="dts-programme-link" href="#" target="_blank"
                                            class="hidden bg-sky-600 hover:bg-sky-500 text-white font-bold py-2 px-3 rounded-lg text-xs items-center gap-1.5">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i> Generate Programme
                                        </a>
                                    </div>
                                </section>

                                <section id="dts-tab-shared" class="dts-tab-panel bg-slate-950/40 border border-white/5 rounded-2xl overflow-hidden hidden">
                                    <div class="p-4 border-b border-white/5">
                                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                            <i data-lucide="share-2" class="w-5 h-5 text-cyan-300"></i> Shared Teamsheets
                                        </h3>
                                        <p class="text-xs text-slate-400 mt-1">Submitted teamsheets from clubs in your galas appear here.</p>
                                    </div>
                                    <div id="dts-shared-list" class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3"></div>
                                </section>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="glass-panel p-8 rounded-2xl border border-cyan-500/30 bg-gradient-to-br from-cyan-900/20 to-transparent relative overflow-hidden group">
                            <div class="absolute right-0 top-0 w-64 h-64 bg-cyan-500/5 rounded-full blur-3xl group-hover:bg-cyan-500/10 transition-colors pointer-events-none"></div>
                            <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                                <div class="flex items-start gap-5">
                                    <div class="bg-cyan-500/20 p-4 rounded-2xl flex-shrink-0 border border-cyan-500/30 shadow-inner">
                                        <i data-lucide="clipboard-list" class="w-8 h-8 text-cyan-300"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <h2 class="text-2xl font-bold text-white">Digital Teamsheets</h2>
                                        </div>
                                        <p class="text-slate-300 text-sm max-w-md leading-relaxed">
                                            Manage swimmer lists, round selections, submissions, shared sheets, and audit history in a dedicated workspace.
                                        </p>
                                    </div>
                                </div>
                                <a href="digital-teamsheets.php"
                                    class="w-full sm:w-64 bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-4 px-6 rounded-xl transition-all shadow-lg shadow-cyan-900/30 flex items-center justify-center gap-3">
                                    <span>Open Digital Teamsheets</span>
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- GALA SCORESHEET -->
                        <?php if (!$digital_teamsheets_standalone): ?>
                        <?php if (!empty($scoresheet_venues)): ?>
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
                                        <?php foreach ($scoresheet_venues as $v): ?>
                                            <?php $venue_gala_label = cotswold_portal_gala_label($v['round_number'], $v['gala_type'] ?? 'round'); ?>
                                            <a href="gala_scoresheet.php?venue_id=<?php echo $v['id']; ?>" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-sky-900/30 flex items-center justify-center gap-2">
                                                <span><?php echo htmlspecialchars($venue_gala_label); ?> Scoresheet</span>
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
                                        <?php $venue_gala_label = cotswold_portal_gala_label($venue['round_number'], $venue['gala_type'] ?? 'round'); ?>
                                        <div class="bg-slate-900/50 p-5 rounded-2xl border border-white/5">
                                            <div class="flex justify-between items-center mb-4 pb-3 border-b border-white/5">
                                                <div>
                                                    <span
                                                        class="bg-sky-500/20 text-sky-400 text-xs font-bold px-2 py-1 rounded uppercase tracking-wider mb-1 inline-block"><?php echo htmlspecialchars($venue_gala_label); ?></span>
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
                                                        <label class="form-label">Location (Address)</label>
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
                                                            <label class="form-label">Doors Open Time</label>
                                                            <div class="relative">
                                                                <i data-lucide="door-open"
                                                                    class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                                <input type="text" name="start_time"
                                                                    value="<?php echo htmlspecialchars($venue['start_time'] ?? ''); ?>"
                                                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                    placeholder="Doors 6:00pm">
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="form-label">Warm Up Time</label>
                                                            <div class="relative">
                                                                <i data-lucide="clock"
                                                                    class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                                <input type="text" name="warmup_time"
                                                                    value="<?php echo htmlspecialchars($venue['warmup_time'] ?? ''); ?>"
                                                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                                    placeholder="18:00">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="form-label">Payment Details (Amount, Cash/Card accepted)</label>
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
                                                        <label class="form-label">Parking Details</label>
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

                                                <div class="md:col-span-2">
                                                    <label class="form-label">Any Other Information</label>
                                                    <div class="relative">
                                                        <i data-lucide="info"
                                                            class="w-4 h-4 absolute left-3 top-3 text-slate-500"></i>
                                                        <textarea name="other_info" rows="2"
                                                            class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-sky-500 focus:outline-none transition-colors text-white"
                                                            placeholder="Spectator access, cafe details, changing room notes"><?php echo htmlspecialchars($venue['other_info'] ?? ''); ?></textarea>
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
                        <?php endif; ?>

                </div>

                <!-- ROUND DRAWS -->
                <?php if (!$digital_teamsheets_standalone): ?>
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
                                <?php
                                $draw_label = cotswold_portal_gala_label($draw['round_number'], $draw['gala_type'] ?? 'round');
                                ?>
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="bg-purple-500/20 text-purple-400 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider"><?php echo htmlspecialchars($draw_label); ?></span>
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
                                        <?php if (!empty($draw['results_file']) && !empty($draw['digital_teamsheet_id']) && ($draw['digital_teamsheet_status'] ?? '') === 'submitted' && ($draw['digital_teamsheet_submission_type'] ?? 'builder') === 'builder'): ?>
                                            <a href="smart-results-matcher.php?digital_teamsheet_id=<?php echo (int)$draw['digital_teamsheet_id']; ?>" target="_blank" class="w-full bg-purple-600 hover:bg-purple-500 text-white border border-purple-500 py-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-lg shadow-purple-900/20">
                                                <i data-lucide="check-square" class="w-3.5 h-3.5"></i> Smart Results Matcher
                                            </a>
                                        <?php elseif (!empty($draw['results_file']) && ($draw['digital_teamsheet_submission_type'] ?? '') === 'upload'): ?>
                                            <div class="w-full text-center text-[11px] text-slate-500 border border-slate-700/50 py-2 rounded-lg bg-slate-800/30">
                                                Results matcher requires builder teamsheet
                                            </div>
                                        <?php elseif (!empty($draw['results_file'])): ?>
                                            <div class="w-full text-center text-[11px] text-slate-500 border border-slate-700/50 py-2 rounded-lg bg-slate-800/30">
                                                Submit digital teamsheet to enable matcher
                                            </div>
                                        <?php else: ?>
                                            <div class="w-full text-center text-[11px] text-slate-600 border border-slate-800/70 py-2 rounded-lg bg-slate-900/40 cursor-not-allowed">
                                                Results matcher available after upload
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                </section>

                <?php if ($show_portal_tabs): ?>
                <section id="portal-documents" class="portal-section hidden space-y-8 mb-8">

                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-sky-400">
                            <i data-lucide="landmark" class="w-5 h-5"></i> Governance
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden divide-y divide-white/5">
                            <a href="https://docs.google.com/document/d/11Vpu7bLnr_nlMx7_MzTg5SFHsM0qzw7V/edit?usp=sharing&amp;ouid=106844982787765338918&amp;rtpof=true&amp;sd=true" target="_blank" rel="noopener noreferrer"
                                class="portal-doc-row flex items-center p-4 hover:bg-white/5 transition-colors group gap-4">
                                <div class="bg-sky-500/10 p-2 rounded-lg flex-shrink-0"><i data-lucide="gavel" class="text-sky-500 w-5 h-5"></i></div>
                                <div class="flex-grow min-w-0">
                                    <p class="portal-doc-title text-sm font-medium text-white">League Rules <?php echo (int)$active_season_year; ?></p>
                                    <p class="text-xs text-slate-500">Official Rules &amp; Regulations</p>
                                </div>
                                <i data-lucide="external-link" class="w-4 h-4 text-slate-600 flex-shrink-0"></i>
                            </a>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-amber-400">
                            <i data-lucide="printer" class="w-5 h-5"></i> Printable Gala Documents
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden divide-y divide-white/5">
                            <?php
                            $printable_docs = [
                                ['href' => 'Officials Sign-in.php', 'icon' => 'user-check', 'title' => 'Officials Sign-in', 'desc' => 'Printable Sign-in Form', 'external' => false],
                                ['href' => 'spectator-programme.php', 'icon' => 'file-text', 'title' => 'Spectator Programme', 'desc' => 'Printable Event List', 'external' => false],
                                ['href' => 'https://drive.google.com/file/d/1NrkrkY3vOGTMRTDNASrWf7FsRsBRsClO/view?usp=sharing', 'icon' => 'alert-triangle', 'title' => 'DQ Report Form', 'desc' => 'PDF Printout', 'external' => true],
                                ['href' => 'Timekeeper-sheets.php', 'icon' => 'clock', 'title' => 'Timekeeper Sheet', 'desc' => 'Printable Form Tool', 'external' => false],
                                ['href' => 'ChiefTKSlips.php', 'icon' => 'clipboard', 'title' => 'Chief Timekeeper Slips', 'desc' => 'Printable Slips for Rounds &amp; Finals', 'external' => false],
                                ['href' => 'Announcers-guide.php', 'icon' => 'mic', 'title' => 'Announcers Guide', 'desc' => 'Script for volunteers', 'external' => false],
                            ];
                            foreach ($printable_docs as $doc):
                            ?>
                            <a href="<?php echo htmlspecialchars($doc['href']); ?>" <?php echo $doc['external'] ? 'target="_blank" rel="noopener noreferrer"' : 'target="_blank"'; ?>
                                class="portal-doc-row flex items-center p-4 hover:bg-white/5 transition-colors group gap-4">
                                <div class="bg-amber-500/10 p-2 rounded-lg flex-shrink-0"><i data-lucide="<?php echo htmlspecialchars($doc['icon']); ?>" class="text-amber-500 w-5 h-5"></i></div>
                                <div class="flex-grow min-w-0">
                                    <p class="portal-doc-title text-sm font-medium text-white"><?php echo htmlspecialchars($doc['title']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo $doc['desc']; ?></p>
                                </div>
                                <?php if ($doc['external']): ?><i data-lucide="external-link" class="w-4 h-4 text-slate-600 flex-shrink-0"></i><?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-emerald-400">
                            <i data-lucide="calculator" class="w-5 h-5"></i> Teamsheets &amp; Results
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden divide-y divide-white/5">
                            <a href="digital-teamsheets.php"
                                class="portal-doc-row flex items-center p-4 hover:bg-white/5 transition-colors group gap-4">
                                <div class="bg-emerald-500/10 p-2 rounded-lg flex-shrink-0"><i data-lucide="clipboard-list" class="text-emerald-500 w-5 h-5"></i></div>
                                <div class="flex-grow min-w-0">
                                    <p class="portal-doc-title text-sm font-medium text-white">Digital Teamsheets</p>
                                    <p class="text-xs text-slate-500">Manage swimmer lists, submissions, and shared teamsheets.</p>
                                </div>
                            </a>
                            <button type="button" onclick="switchPortalTab('overview')"
                                class="portal-doc-row w-full flex items-center p-4 hover:bg-white/5 transition-colors group gap-4 text-left">
                                <div class="bg-emerald-500/10 p-2 rounded-lg flex-shrink-0"><i data-lucide="layout-dashboard" class="text-emerald-500 w-5 h-5"></i></div>
                                <div class="flex-grow min-w-0">
                                    <p class="portal-doc-title text-sm font-medium text-white">Gala Scoresheets &amp; Results</p>
                                    <p class="text-xs text-slate-500">Open the Overview tab for scoresheet links and round results.</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-amber-400">
                            <i data-lucide="users" class="w-5 h-5"></i> Community &amp; Support
                        </h2>
                        <div class="glass-panel rounded-2xl overflow-hidden divide-y divide-white/5">
                            <?php
                            $community_links = [
                                ['href' => 'https://chat.whatsapp.com/KGftukKhKYHGWQgjsoemZz', 'icon' => 'message-circle', 'color' => 'emerald', 'title' => 'WhatsApp Community', 'desc' => 'Join the representative group'],
                                ['href' => 'https://www.facebook.com/profile.php?id=100094686571540', 'icon' => 'facebook', 'color' => '#1877F2', 'title' => 'Facebook', 'desc' => 'Follow the Cotswold League updates'],
                                ['href' => 'https://www.instagram.com/thecotswoldleague/', 'icon' => 'instagram', 'color' => '#E1306C', 'title' => 'Instagram', 'desc' => 'Follow the latest photos and highlights'],
                            ];
                            foreach ($community_links as $link):
                            ?>
                            <a href="<?php echo htmlspecialchars($link['href']); ?>" target="_blank" rel="noopener noreferrer"
                                class="portal-doc-row flex items-center p-4 hover:bg-white/5 transition-colors group gap-4">
                                <div class="p-2 rounded-lg flex-shrink-0" style="background-color: <?php echo $link['color'] === 'emerald' ? 'rgba(16,185,129,0.1)' : 'rgba(255,255,255,0.05)'; ?>">
                                    <i data-lucide="<?php echo htmlspecialchars($link['icon']); ?>" class="w-5 h-5" style="color: <?php echo $link['color'] === 'emerald' ? '#10b981' : htmlspecialchars($link['color']); ?>"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="portal-doc-title text-sm font-medium text-white"><?php echo htmlspecialchars($link['title']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($link['desc']); ?></p>
                                </div>
                                <i data-lucide="external-link" class="w-4 h-4 text-slate-600 flex-shrink-0"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2 px-2 text-emerald-400">
                            <i data-lucide="activity" class="w-5 h-5"></i> Recent Venue Updates
                        </h2>
                        <div class="glass-panel p-5 rounded-2xl border border-white/5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <i data-lucide="edit-3" class="w-4 h-4 text-emerald-400"></i> Latest changes
                                </h3>
                                <a href="audit_log.php" class="text-[11px] text-slate-400 hover:text-white transition-colors">View Log</a>
                            </div>
                            <?php if (!empty($recent_logs)): ?>
                                <div class="space-y-2.5">
                                    <?php foreach ($recent_logs as $log): ?>
                                        <div class="rounded-xl border border-white/5 bg-white/5 px-3 py-2">
                                            <div class="flex justify-between items-start gap-2">
                                                <p class="text-xs font-semibold text-slate-200 break-words"><?php echo htmlspecialchars($log['club_name']); ?></p>
                                                <span class="text-[10px] text-slate-500 font-mono flex-shrink-0"><?php echo date('d M H:i', strtotime($log['timestamp'])); ?></span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 break-words mt-1"><?php echo htmlspecialchars($log['change_details']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-xs text-slate-500">No recent venue changes logged.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="glass-panel p-6 rounded-2xl text-center border border-white/5">
                        <p class="text-slate-400 text-sm leading-relaxed">
                            <i data-lucide="help-circle" class="w-4 h-4 inline mr-1 text-sky-500 relative -top-0.5"></i>
                            You can reach Lewis via the WhatsApp link above, email at <a href="mailto:lewisplume@gmail.com" class="text-sky-400 hover:text-sky-300 transition-colors font-medium">lewisplume@gmail.com</a>,
                            or if you have contacts interested in joining, please provide them with the league email: <a href="mailto:admin@thecotswoldleague.co.uk" class="text-sky-400 hover:text-sky-300 transition-colors font-medium">admin@thecotswoldleague.co.uk</a>.
                        </p>
                    </div>
                </section>

                <section id="portal-checklist" class="portal-section hidden mb-8">
                    <div class="glass-panel p-8 rounded-3xl border border-white/5">
                        <div class="flex flex-wrap items-center gap-3 mb-6">
                            <div class="bg-indigo-500/10 p-2 rounded-lg">
                                <i data-lucide="list-checks" class="text-indigo-400 w-6 h-6"></i>
                            </div>
                            <div class="flex-grow min-w-0">
                                <h2 class="text-xl font-bold text-white">Host Team Checklist</h2>
                                <p class="text-slate-400 text-sm">Essential items for gala day preparation. Progress is saved per club and season.</p>
                            </div>
                            <button type="button" onclick="resetHostChecklist()"
                                class="text-xs text-slate-500 hover:text-red-400 transition-colors">Reset</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php
                            $checklist_items = [
                                ['id' => 'rules-printed', 'title' => 'League Rules', 'desc' => 'Bring One Copy To The Gala', 'link' => 'https://docs.google.com/document/d/11Vpu7bLnr_nlMx7_MzTg5SFHsM0qzw7V/edit?usp=sharing&ouid=106844982787765338918&rtpof=true&sd=true', 'link_label' => 'Print Rules', 'link_icon' => 'external-link', 'external' => true],
                                ['id' => 'teamsheets-marked', 'title' => 'Teamsheets', 'desc' => 'Teamsheets are available in the Team Portal Overview.', 'link' => 'teamportal.php#overview', 'link_label' => 'Overview', 'link_icon' => 'layout-dashboard', 'external' => false],
                                ['id' => 'results-calc', 'title' => 'Digital Scoresheet', 'desc' => 'Scoresheets and results are handled in the Team Portal Overview.', 'link' => 'teamportal.php#overview', 'link_label' => 'Overview', 'link_icon' => 'layout-dashboard', 'external' => false],
                                ['id' => 'officials-signin', 'title' => 'Officials Sign-In Sheet', 'desc' => 'Printed for officials', 'link' => 'Officials Sign-in.php', 'link_label' => 'Print Form', 'link_icon' => 'file-text', 'external' => false],
                                ['id' => 'dq-forms', 'title' => 'DQ Report Forms', 'desc' => 'Printed for officials', 'link' => 'https://drive.google.com/file/d/1NrkrkY3vOGTMRTDNASrWf7FsRsBRsClO/view?usp=sharing', 'link_label' => 'Print Form', 'link_icon' => 'file-warning', 'external' => true],
                                ['id' => 'timekeeper-sheets', 'title' => 'Timekeeper Sheets', 'desc' => 'Print 4x(Rounds) or 6-8x(Finals)', 'link' => 'Timekeeper-sheets.php', 'link_label' => 'Generate Sheets', 'link_icon' => 'clock', 'external' => false],
                                ['id' => 'chief-tk-slips', 'title' => 'Chief Timekeeper Slips', 'desc' => '53 Required For Each Gala', 'link' => 'ChiefTKSlips.php', 'link_label' => 'Generate Slips', 'link_icon' => 'clipboard-list', 'external' => false],
                                ['id' => 'blank-programmes', 'title' => 'Blank Programmes', 'desc' => 'Perfect for officials or parents', 'link' => 'spectator-programme.php', 'link_label' => 'Print Programme', 'link_icon' => 'printer', 'external' => false],
                                ['id' => 'announcers-guide', 'title' => 'Announcers Guide', 'desc' => 'Customisable Gala Script For Volunteers', 'link' => 'Announcers-guide.php', 'link_label' => 'View Script', 'link_icon' => 'mic', 'external' => false],
                            ];
                            foreach ($checklist_items as $item):
                            ?>
                            <div class="checklist-card bg-slate-900/40 p-4 rounded-xl border border-white/5 hover:border-indigo-500/30 transition-all">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" class="checklist-item mt-1 w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800 accent-indigo-500"
                                        data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                    <div class="min-w-0">
                                        <span class="text-slate-200 font-medium block mb-1"><?php echo htmlspecialchars($item['title']); ?></span>
                                        <span class="text-xs text-slate-500 block break-words"><?php echo htmlspecialchars($item['desc']); ?></span>
                                    </div>
                                </label>
                                <a href="<?php echo htmlspecialchars($item['link']); ?>" <?php echo $item['external'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                                    class="mt-3 flex items-center justify-center w-full py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs rounded-lg transition-colors gap-2">
                                    <i data-lucide="<?php echo htmlspecialchars($item['link_icon']); ?>" class="w-3 h-3"></i> <?php echo htmlspecialchars($item['link_label']); ?>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!$digital_teamsheets_standalone): ?>
                <section id="portal-directory" class="portal-section <?php echo $show_portal_tabs ? 'hidden' : ''; ?> space-y-8 mb-8">
                <div class="glass-panel rounded-3xl overflow-hidden border border-white/5">
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
                                    <th class="p-4 w-12 text-center"></th>
                                    <th class="p-4">Club</th>
                                    <th class="p-4">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" data-contact-header="1"
                                                class="contact-header-checkbox rounded bg-slate-800 border-slate-600 text-sky-500 focus:ring-sky-500 cursor-pointer w-4 h-4">
                                            <span>Contact 1</span>
                                        </div>
                                    </th>
                                    <th class="p-4">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" data-contact-header="2"
                                                class="contact-header-checkbox rounded bg-slate-800 border-slate-600 text-sky-500 focus:ring-sky-500 cursor-pointer w-4 h-4">
                                            <span>Contact 2</span>
                                        </div>
                                    </th>
                                    <th class="p-4">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" data-contact-header="3"
                                                class="contact-header-checkbox rounded bg-slate-800 border-slate-600 text-sky-500 focus:ring-sky-500 cursor-pointer w-4 h-4">
                                            <span>Contact 3</span>
                                        </div>
                                    </th>
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
                                                            data-contact-slot="1"
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
                                                            data-contact-slot="2"
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
                                                            data-contact-slot="3"
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
                </section>

                <?php if ($show_portal_tabs): ?>
                <section id="portal-account" class="portal-section hidden space-y-8 mb-20">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto w-full">
                        <form method="POST" class="glass-panel p-6 rounded-2xl border border-white/5">
                            <input type="hidden" name="action" value="update_contacts">
                            <h2 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                                <i data-lucide="users" class="w-5 h-5 text-indigo-400"></i> Edit Team Contacts
                            </h2>
                            <div class="space-y-5">
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Primary Contact</h3>
                                    <div class="space-y-3">
                                        <input type="text" name="c1_name" value="<?php echo htmlspecialchars($my_club_data['contact1_name']); ?>"
                                            class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600" placeholder="Name">
                                        <input type="email" name="c1_email" value="<?php echo htmlspecialchars($my_club_data['contact1_email']); ?>"
                                            class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600" placeholder="Email">
                                    </div>
                                </div>
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Contact 2</h3>
                                    <div class="space-y-3">
                                        <input type="text" name="c2_name" value="<?php echo htmlspecialchars($my_club_data['contact2_name']); ?>"
                                            class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600" placeholder="Name">
                                        <input type="email" name="c2_email" value="<?php echo htmlspecialchars($my_club_data['contact2_email']); ?>"
                                            class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600" placeholder="Email">
                                    </div>
                                </div>
                                <div class="bg-slate-900/50 p-4 rounded-xl border border-white/5">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Contact 3</h3>
                                    <div class="space-y-3">
                                        <input type="text" name="c3_name" value="<?php echo htmlspecialchars($my_club_data['contact3_name']); ?>"
                                            class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600" placeholder="Name">
                                        <input type="email" name="c3_email" value="<?php echo htmlspecialchars($my_club_data['contact3_email']); ?>"
                                            class="w-full bg-slate-950 border border-slate-700 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600" placeholder="Email">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5">
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-900/20 text-sm">Save Contacts</button>
                            </div>
                        </form>
                        <form method="POST" class="glass-panel p-6 rounded-2xl border border-orange-500/20 bg-orange-900/5">
                            <input type="hidden" name="action" value="change_pin">
                            <h2 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                                <i data-lucide="lock" class="w-5 h-5 text-orange-400"></i> Security PIN
                            </h2>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">Update your 4-digit dashboard access PIN. Share this only with authorized club representatives.</p>
                            <div class="flex gap-3">
                                <input type="text" name="new_pin" placeholder="0000" maxlength="4" pattern="\d{4}"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white focus:outline-none focus:border-orange-500 transition-all placeholder-slate-600 text-center tracking-[0.3em] font-mono font-bold" required>
                                <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white font-bold px-4 rounded-xl transition-all flex-shrink-0 text-sm">Update</button>
                            </div>
                        </form>
                    </div>
                </section>
                <?php endif; ?>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>

    <script>
        lucide.createIcons();

        const PORTAL_TABS = ['overview', 'documents', 'checklist', 'directory', 'account'];
        const portalSeason = <?php echo (int)$active_season_year; ?>;
        const portalClubId = <?php echo (int)$current_club_id; ?>;

        function switchPortalTab(tabId, updateHash = true) {
            if (!PORTAL_TABS.includes(tabId)) tabId = 'overview';
            document.querySelectorAll('.portal-section').forEach(section => {
                section.classList.add('hidden');
            });
            const target = document.getElementById('portal-' + tabId);
            if (target) target.classList.remove('hidden');
            document.querySelectorAll('.portal-tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.portalTab === tabId);
            });
            if (updateHash && history.replaceState) {
                history.replaceState(null, '', tabId === 'overview' ? 'teamportal.php' : 'teamportal.php#' + tabId);
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function initPortalTabsFromHash() {
            if (!document.querySelector('.portal-tab-btn')) return;
            const hash = (window.location.hash || '').replace('#', '');
            switchPortalTab(PORTAL_TABS.includes(hash) ? hash : 'overview', false);
        }

        function hostChecklistStorageKey(itemId) {
            return 'host_checklist_' + portalSeason + '_' + portalClubId + '_' + itemId;
        }

        function initHostChecklist() {
            const items = document.querySelectorAll('.checklist-item');
            if (!items.length) return;
            items.forEach(item => {
                const id = item.dataset.id;
                if (!id) return;
                const savedState = localStorage.getItem(hostChecklistStorageKey(id));
                if (savedState === 'true') item.checked = true;
                item.addEventListener('change', (e) => {
                    localStorage.setItem(hostChecklistStorageKey(id), e.target.checked);
                });
            });
        }

        function resetHostChecklist() {
            if (!confirm('Are you sure you want to clear all checkboxes for this club and season?')) return;
            document.querySelectorAll('.checklist-item').forEach(item => {
                item.checked = false;
                const id = item.dataset.id;
                if (id) localStorage.removeItem(hostChecklistStorageKey(id));
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initPortalTabsFromHash();
            initHostChecklist();
        });
        window.addEventListener('hashchange', () => initPortalTabsFromHash());

        const dtsState = {
            season: <?php echo (int)$active_season_year; ?>,
            clubId: <?php echo (int)$current_club_id; ?>,
            swimmers: [],
            rounds: [],
            events: [],
            teamsheets: {},
            shared: [],
            selectedRound: null,
            activeTeamsheet: null,
            loadedEntries: {},
            showAvailableOnly: false,
            ignoredWarnings: {}
        };

        const dtsAutosave = {
            swimmerTimer: null,
            teamsheetTimer: null,
            swimmerSaving: false,
            teamsheetSaving: false,
            swimmerDirty: false,
            teamsheetDirty: false,
            postSubmitReason: ''
        };

        let dtsTeamunifyPreview = null;
        let dtsSwimClubManagerPreview = null;
        let dtsHyTekPreview = null;

        const dtsPbMap = {
            '25m|Freestyle': 'pb_free_25',
            '25m|Backstroke': 'pb_back_25',
            '25m|Breaststroke': 'pb_breast_25',
            '25m|Butterfly': 'pb_fly_25',
            '50m|Freestyle': 'pb_free_50',
            '50m|Backstroke': 'pb_back_50',
            '50m|Breaststroke': 'pb_breast_50',
            '50m|Butterfly': 'pb_fly_50',
            '100m|Freestyle': 'pb_free_100',
            '100m|Backstroke': 'pb_back_100',
            '100m|Breaststroke': 'pb_breast_100',
            '100m|Butterfly': 'pb_fly_100'
        };

        function dtsEscape(value) {
            return String(value ?? '').replace(/[&<>"']/g, ch => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[ch]));
        }

        function showDtsAlert(message, type = 'info') {
            const el = document.getElementById('dts-alert');
            if (!el) return;
            const classes = {
                info: 'bg-cyan-500/10 border border-cyan-500/20 text-cyan-200',
                success: 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-300',
                error: 'bg-red-500/10 border border-red-500/20 text-red-300',
                warn: 'bg-amber-500/10 border border-amber-500/20 text-amber-200'
            };
            el.className = `rounded-xl px-4 py-3 text-sm font-semibold ${classes[type] || classes.info}`;
            el.textContent = message;
            el.classList.remove('hidden');
        }

        function showAutosaveStatus(target, message, type = 'info') {
            const el = document.getElementById(target === 'swimmers' ? 'dts-swimmers-autosave-status' : 'dts-teamsheet-autosave-status');
            if (!el) return;
            const classes = {
                info: 'text-slate-500',
                saving: 'text-cyan-300',
                saved: 'text-emerald-300',
                warn: 'text-amber-300',
                error: 'text-red-300'
            };
            el.className = `self-center text-[11px] font-semibold ${classes[type] || classes.info}`;
            el.textContent = message;
            el.classList.toggle('hidden', !message);
        }

        function scheduleSwimmerAutosave() {
            dtsAutosave.swimmerDirty = true;
            window.clearTimeout(dtsAutosave.swimmerTimer);
            showAutosaveStatus('swimmers', 'Unsaved changes...', 'info');
            dtsAutosave.swimmerTimer = window.setTimeout(runSwimmerAutosave, 1500);
        }

        async function runSwimmerAutosave() {
            if (dtsAutosave.swimmerSaving) return;
            if (!dtsAutosave.swimmerDirty) return;
            dtsAutosave.swimmerSaving = true;
            dtsAutosave.swimmerDirty = false;
            showAutosaveStatus('swimmers', 'Autosaving...', 'saving');
            try {
                await saveSwimmers({ silent: true, reload: false });
                showAutosaveStatus('swimmers', 'Autosaved', 'saved');
            } catch (err) {
                dtsAutosave.swimmerDirty = true;
                showAutosaveStatus('swimmers', 'Autosave failed', 'error');
            } finally {
                dtsAutosave.swimmerSaving = false;
                if (dtsAutosave.swimmerDirty) scheduleSwimmerAutosave();
            }
        }

        function scheduleTeamsheetAutosave() {
            if (!dtsState.selectedRound) return;
            dtsAutosave.teamsheetDirty = true;
            window.clearTimeout(dtsAutosave.teamsheetTimer);
            showAutosaveStatus('teamsheet', 'Unsaved changes...', 'info');
            dtsAutosave.teamsheetTimer = window.setTimeout(runTeamsheetAutosave, 1800);
        }

        async function runTeamsheetAutosave() {
            if (dtsAutosave.teamsheetSaving) return;
            if (!dtsAutosave.teamsheetDirty) return;
            dtsAutosave.teamsheetSaving = true;
            dtsAutosave.teamsheetDirty = false;
            showAutosaveStatus('teamsheet', 'Autosaving...', 'saving');
            let reschedule = true;
            try {
                let reason = '';
                if (dtsState.activeTeamsheet?.status === 'submitted') {
                    reason = dtsAutosave.postSubmitReason || prompt('Reason for editing this submitted teamsheet:') || '';
                    if (!reason.trim()) {
                        dtsAutosave.teamsheetDirty = true;
                        reschedule = false;
                        showAutosaveStatus('teamsheet', 'Reason needed to autosave submitted changes', 'warn');
                        return;
                    }
                    dtsAutosave.postSubmitReason = reason.trim();
                }
                await saveTeamsheet(false, { silent: true, reload: false, reason });
                showAutosaveStatus('teamsheet', 'Autosaved', 'saved');
            } catch (err) {
                dtsAutosave.teamsheetDirty = true;
                showAutosaveStatus('teamsheet', 'Autosave failed', 'error');
            } finally {
                dtsAutosave.teamsheetSaving = false;
                if (dtsAutosave.teamsheetDirty && reschedule) scheduleTeamsheetAutosave();
            }
        }

        function switchDtsTab(tabName) {
            if (tabName !== 'builder' && !document.getElementById('dts-tab-builder')?.classList.contains('hidden')) {
                cacheCurrentTeamsheetEntries();
            }
            if (tabName === 'builder' && document.getElementById('dts-tab-builder')?.classList.contains('hidden')) {
                dtsState.swimmers = collectSwimmers();
                renderTeamsheetRows();
            }
            document.querySelectorAll('.dts-tab-panel').forEach(panel => {
                panel.classList.toggle('hidden', panel.id !== `dts-tab-${tabName}`);
            });
            document.querySelectorAll('.dts-tab-btn').forEach(button => {
                const isActive = button.dataset.dtsTab === tabName;
                button.classList.toggle('bg-cyan-600', isActive);
                button.classList.toggle('text-white', isActive);
                button.classList.toggle('shadow-lg', isActive);
                button.classList.toggle('shadow-cyan-900/20', isActive);
                button.classList.toggle('text-slate-300', !isActive);
                button.classList.toggle('hover:bg-slate-800', !isActive);
            });
        }

        async function dtsApi(action, data = null, method = 'GET') {
            const opts = { method };
            let url = `digital_teamsheet_api.php?action=${encodeURIComponent(action)}`;
            if (method === 'POST') {
                opts.body = data instanceof FormData ? data : new FormData();
                opts.body.append('club_id', dtsState.clubId);
            } else if (data) {
                data = { ...data, club_id: dtsState.clubId };
                url += '&' + new URLSearchParams(data).toString();
            } else {
                url += '&' + new URLSearchParams({ club_id: dtsState.clubId }).toString();
            }
            const response = await fetch(url, opts);
            const payload = await response.json();
            if (payload.error) throw new Error(payload.error);
            return payload;
        }

        async function loadDigitalTeamsheets(showMessage = false) {
            if (!document.getElementById('digital-teamsheets')) return;
            try {
                const data = await dtsApi('load', { season: dtsState.season });
                Object.assign(dtsState, data, { loadedEntries: {} });
                renderSwimmerList();
                renderRoundSelect();
                renderSharedSheets();
                if (!dtsState.selectedRound && dtsState.rounds.length) {
                    dtsState.selectedRound = dtsState.rounds[0];
                }
                await selectDigitalRound();
                if (showMessage) showDtsAlert('Digital teamsheets refreshed.', 'success');
            } catch (err) {
                showDtsAlert(err.message || 'Could not load digital teamsheets.', 'error');
            }
        }

        function renderSwimmerList() {
            const body = document.getElementById('dts-swimmers-body');
            if (!body) return;
            body.innerHTML = '';
            dtsState.swimmers.forEach(swimmer => body.appendChild(buildSwimmerRow(swimmer)));
            if (dtsState.swimmers.length === 0) {
                addSwimmerRow();
            }
            lucide.createIcons();
        }

        function buildSwimmerRow(swimmer = {}) {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-800/40';
            tr.dataset.id = swimmer.id || '';
            const fields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];
            const ageGroups = ['11/U', '13/U', '15/U', 'Open'];
            const currentAgeGroup = String(swimmer.age_group || '').toLowerCase();
            tr.innerHTML = `
                <td class="px-2 py-2 sticky left-0 z-20 bg-slate-950 border-r border-white/10"><input data-field="swimmer_name" value="${dtsEscape(swimmer.swimmer_name || '')}" class="dts-swimmer-field w-64 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-white"></td>
                <td class="px-2 py-2">
                    <select data-field="age_group" class="dts-swimmer-field w-24 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-white">
                        <option value="">-</option>
                        ${ageGroups.map(age => `<option value="${age}" ${currentAgeGroup === age.toLowerCase() || (age === 'Open' && currentAgeGroup === 'opens') ? 'selected' : ''}>${age}</option>`).join('')}
                    </select>
                </td>
                ${fields.map(field => `<td class="px-2 py-2"><input data-field="${field}" value="${dtsEscape(swimmer[field] || '')}" class="dts-swimmer-field w-20 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-white font-mono"></td>`).join('')}
                ${[
                    ['round_1', 'Round 1 available'],
                    ['round_2', 'Round 2 available'],
                    ['round_3', 'Round 3 available'],
                    ['round_4', 'Round 4 available'],
                    ['final', 'Final available']
                ].map(([key, label]) => `<td class="px-2 py-2 text-center"><label class="inline-flex items-center justify-center" title="${label}"><span class="sr-only">${label}</span><input type="checkbox" aria-label="${label}" data-availability="${key}" ${(swimmer.availability && swimmer.availability[key]) ? 'checked' : ''} class="rounded border-slate-700 bg-slate-900 text-cyan-500"></label></td>`).join('')}
                <td class="px-2 py-2 text-center"><button type="button" onclick="removeSwimmerRow(this)" class="text-slate-500 hover:text-red-400 p-1 rounded"><i data-lucide="trash-2" class="w-4 h-4"></i></button></td>
            `;
            return tr;
        }

        function addSwimmerRow() {
            const body = document.getElementById('dts-swimmers-body');
            if (!body) return;
            body.appendChild(buildSwimmerRow({ availability: {} }));
            lucide.createIcons();
        }

        function removeSwimmerRow(button) {
            button.closest('tr')?.remove();
            scheduleSwimmerAutosave();
        }

        function collectSwimmers() {
            return Array.from(document.querySelectorAll('#dts-swimmers-body tr')).map(row => {
                const swimmer = { id: row.dataset.id ? parseInt(row.dataset.id, 10) : 0, availability: {} };
                row.querySelectorAll('.dts-swimmer-field').forEach(input => {
                    swimmer[input.dataset.field] = input.value.trim();
                });
                row.querySelectorAll('[data-availability]').forEach(input => {
                    swimmer.availability[input.dataset.availability] = input.checked;
                });
                return swimmer;
            }).filter(swimmer => swimmer.swimmer_name);
        }

        function openDigitalTeamsheetsHelp() {
            const modal = document.getElementById('dts-help-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            lucide.createIcons();
        }

        function closeDigitalTeamsheetsHelp() {
            const modal = document.getElementById('dts-help-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openTeamunifyGuide() {
            const modal = document.getElementById('dts-teamunify-guide-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            lucide.createIcons();
        }

        function closeTeamunifyGuide() {
            const modal = document.getElementById('dts-teamunify-guide-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openSwimClubManagerGuide() {
            const modal = document.getElementById('dts-scm-guide-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            lucide.createIcons();
        }

        function closeSwimClubManagerGuide() {
            const modal = document.getElementById('dts-scm-guide-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function updateImportProviderHelp() {
            const provider = document.getElementById('dts-import-provider')?.value || 'teamunify';
            const guideButton = document.getElementById('dts-teamunify-guide-button');
            const scmGuideButton = document.getElementById('dts-scm-guide-button');
            const importButton = document.getElementById('dts-import-button');
            const showTeamunifyGuide = provider === 'teamunify';
            const showScmGuide = provider === 'scm';
            if (guideButton) guideButton.classList.toggle('hidden', !showTeamunifyGuide);
            if (scmGuideButton) scmGuideButton.classList.toggle('hidden', !showScmGuide);
            if (!importButton) return;
            const showGuide = showTeamunifyGuide || showScmGuide;
            importButton.classList.toggle('border-r-0', showGuide);
            importButton.classList.toggle('rounded-r-lg', !showGuide);
        }

        function startSelectedImport() {
            const provider = document.getElementById('dts-import-provider')?.value || 'teamunify';
            const inputId = {
                teamunify: 'dts-teamunify-file',
                scm: 'dts-scm-file',
                hytek: 'dts-hytek-file'
            }[provider];
            if (inputId) document.getElementById(inputId)?.click();
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDigitalTeamsheetsHelp();
                closeTeamunifyGuide();
                closeSwimClubManagerGuide();
            }
        });

        async function previewTeamunifyImport(file) {
            const input = document.getElementById('dts-teamunify-file');
            if (!file) return;
            try {
                const fd = new FormData();
                fd.append('season', dtsState.season);
                fd.append('teamunify_csv', file);
                dtsTeamunifyPreview = await dtsApi('preview_teamunify_import', fd, 'POST');
                renderTeamunifyPreview();
            } catch (err) {
                dtsTeamunifyPreview = null;
                renderTeamunifyPreview();
                showDtsAlert(err.message || 'Could not read the TeamUnify CSV.', 'error');
            } finally {
                if (input) input.value = '';
            }
        }

        function renderTeamunifyPreview() {
            const el = document.getElementById('dts-teamunify-preview');
            if (!el) return;
            if (!dtsTeamunifyPreview?.swimmers?.length) {
                el.classList.add('hidden');
                el.innerHTML = '';
                return;
            }

            const summary = dtsTeamunifyPreview.summary || {};
            const existingNames = new Set(collectSwimmers().map(swimmer => swimmer.swimmer_name.toLowerCase()));
            const updateCount = dtsTeamunifyPreview.swimmers.filter(swimmer => existingNames.has(swimmer.swimmer_name.toLowerCase())).length;
            const newCount = dtsTeamunifyPreview.swimmers.length - updateCount;
            const ignored = Object.entries(summary.ignored_events || {});
            const pbFields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];
            const ageText = summary.age_groups_calculated
                ? `Age groups calculated from finals date ${dtsEscape(summary.finals_date)}`
                : 'Age groups left blank: no parseable finals date found';
            const previewRows = dtsTeamunifyPreview.swimmers.map(swimmer => {
                const pbCount = pbFields.filter(field => swimmer[field]).length;
                const isMatch = existingNames.has(swimmer.swimmer_name.toLowerCase());
                return `
                    <tr class="border-t border-white/5">
                        <td class="px-3 py-2 font-semibold text-white">${dtsEscape(swimmer.swimmer_name)}</td>
                        <td class="px-3 py-2 text-slate-300">${dtsEscape(swimmer.import_meta?.dob || '-')}</td>
                        <td class="px-3 py-2 text-slate-300">${dtsEscape(swimmer.age_group || '-')}</td>
                        <td class="px-3 py-2 text-slate-300">${pbCount}</td>
                        <td class="px-3 py-2">
                            <span class="${isMatch ? 'text-amber-200 bg-amber-500/10 border-amber-500/20' : 'text-emerald-200 bg-emerald-500/10 border-emerald-500/20'} border rounded-md px-2 py-0.5 text-[10px] font-bold uppercase">
                                ${isMatch ? 'Update' : 'New'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');

            el.innerHTML = `
                <div class="space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="space-y-2">
                            <div class="text-sm font-bold text-white flex items-center gap-2">
                                <i data-lucide="file-check-2" class="w-4 h-4 text-sky-300"></i>
                                TeamUnify import preview
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${dtsTeamunifyPreview.swimmers.length}</strong> swimmers</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${newCount}</strong> new</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${updateCount}</strong> matched</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${summary.mapped_rows || 0}</strong> PBs mapped</span>
                            </div>
                            <div class="text-xs text-slate-300">${ageText}</div>
                            ${ignored.length ? `<div class="text-[11px] text-amber-200">Ignored unsupported events: ${ignored.map(([event, count]) => `${dtsEscape(event)} (${count})`).join(', ')}</div>` : ''}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="applyTeamunifyImport()"
                                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> Apply Import
                            </button>
                            <button type="button" onclick="clearTeamunifyPreview()"
                                class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel
                            </button>
                        </div>
                    </div>
                    <div class="max-h-72 overflow-auto rounded-lg border border-white/10 bg-slate-950/40">
                        <table class="w-full min-w-[640px] text-xs text-left">
                            <thead class="sticky top-0 bg-slate-900 text-slate-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2">Swimmer</th>
                                    <th class="px-3 py-2">DOB</th>
                                    <th class="px-3 py-2">Age Group</th>
                                    <th class="px-3 py-2">PBs</th>
                                    <th class="px-3 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${previewRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            el.classList.remove('hidden');
            lucide.createIcons();
        }

        function clearTeamunifyPreview() {
            dtsTeamunifyPreview = null;
            renderTeamunifyPreview();
        }

        function importedAgeGroupFromAge(age) {
            age = parseInt(age, 10);
            if (!age) return '';
            if (age <= 11) return '11/U';
            if (age <= 13) return '13/U';
            if (age <= 15) return '15/U';
            return 'Open';
        }

        function normaliseImportedName(name) {
            name = String(name || '').trim();
            if (name.includes(',')) {
                const parts = name.split(',');
                name = `${parts.slice(1).join(',').trim()} ${parts[0].trim()}`.trim();
            }
            return name.replace(/\s+/g, ' ');
        }

        function normaliseImportedTime(value) {
            if (value instanceof Date && !Number.isNaN(value.getTime())) {
                const hours = value.getUTCHours();
                const minutes = value.getUTCMinutes();
                const seconds = value.getUTCSeconds();
                const hundredths = Math.round(value.getUTCMilliseconds() / 10);
                if (hours > 0) return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}.${String(hundredths).padStart(2, '0')}`;
                if (minutes > 0) return `${minutes}:${String(seconds).padStart(2, '0')}.${String(hundredths).padStart(2, '0')}`;
                return `${seconds}.${String(hundredths).padStart(2, '0')}`;
            }
            if (typeof value === 'number' && Number.isFinite(value)) {
                if (value > 0 && value < 1) {
                    const totalHundredths = Math.round(value * 24 * 60 * 60 * 100);
                    const minutes = Math.floor(totalHundredths / 6000);
                    const seconds = Math.floor((totalHundredths % 6000) / 100);
                    const hundredths = totalHundredths % 100;
                    return minutes > 0
                        ? `${minutes}:${String(seconds).padStart(2, '0')}.${String(hundredths).padStart(2, '0')}`
                        : `${seconds}.${String(hundredths).padStart(2, '0')}`;
                }
                return String(value);
            }
            let time = String(value || '').replace(/\u00a0/g, ' ').trim();
            if (!time) return '';
            time = time.replace(/^'/, '');
            const midnightSeconds = time.match(/^12:00:(\d{1,2})(?:\.(\d{1,2}))?\s*AM$/i);
            if (midnightSeconds) return `${parseInt(midnightSeconds[1], 10)}.${(midnightSeconds[2] || '00').padEnd(2, '0').slice(0, 2)}`;
            const midnightMinutes = time.match(/^12:(\d{1,2}):(\d{2})(?:\.(\d{1,2}))?\s*AM$/i);
            if (midnightMinutes) return `${parseInt(midnightMinutes[1], 10)}:${midnightMinutes[2]}.${(midnightMinutes[3] || '00').padEnd(2, '0').slice(0, 2)}`;
            const zeroMinute = time.match(/^0:([0-5]?\d(?:\.\d+)?)$/);
            if (zeroMinute) return zeroMinute[1];
            return time.toUpperCase().replace(/S$/, '');
        }

        function importedEventField(eventName) {
            return {
                '25 free': 'pb_free_25',
                '25 freestyle': 'pb_free_25',
                '25 back': 'pb_back_25',
                '25 backstroke': 'pb_back_25',
                '25 breast': 'pb_breast_25',
                '25 breaststroke': 'pb_breast_25',
                '25 fly': 'pb_fly_25',
                '25 butterfly': 'pb_fly_25',
                '50 free': 'pb_free_50',
                '50 freestyle': 'pb_free_50',
                '50 back': 'pb_back_50',
                '50 backstroke': 'pb_back_50',
                '50 breast': 'pb_breast_50',
                '50 breaststroke': 'pb_breast_50',
                '50 fly': 'pb_fly_50',
                '50 butterfly': 'pb_fly_50',
                '100 free': 'pb_free_100',
                '100 freestyle': 'pb_free_100',
                '100 back': 'pb_back_100',
                '100 backstroke': 'pb_back_100',
                '100 breast': 'pb_breast_100',
                '100 breaststroke': 'pb_breast_100',
                '100 fly': 'pb_fly_100',
                '100 butterfly': 'pb_fly_100',
                '100 im': 'pb_im'
            }[String(eventName || '').trim().toLowerCase()] || '';
        }

        function isImportedTimeValue(value) {
            const time = normaliseImportedTime(value);
            return /^\d{1,2}(?::[0-5]\d){0,2}\.\d{1,2}$/.test(time);
        }

        function renderGenericImportPreview(preview, elementId, options) {
            const el = document.getElementById(elementId);
            if (!el) return;
            if (!preview?.swimmers?.length) {
                el.classList.add('hidden');
                el.innerHTML = '';
                return;
            }

            const summary = preview.summary || {};
            const existingNames = new Set(collectSwimmers().map(swimmer => swimmer.swimmer_name.toLowerCase()));
            const updateCount = preview.swimmers.filter(swimmer => existingNames.has(swimmer.swimmer_name.toLowerCase())).length;
            const newCount = preview.swimmers.length - updateCount;
            const ignored = Object.entries(summary.ignored_events || {});
            const pbFields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];
            const previewRows = preview.swimmers.map(swimmer => {
                const pbCount = pbFields.filter(field => swimmer[field]).length;
                const isMatch = existingNames.has(swimmer.swimmer_name.toLowerCase());
                return `
                    <tr class="border-t border-white/5">
                        <td class="px-3 py-2 font-semibold text-white">${dtsEscape(swimmer.swimmer_name)}</td>
                        <td class="px-3 py-2 text-slate-300">${dtsEscape(options.meta(swimmer))}</td>
                        <td class="px-3 py-2 text-slate-300">${dtsEscape(swimmer.age_group || '-')}</td>
                        <td class="px-3 py-2 text-slate-300">${pbCount}</td>
                        <td class="px-3 py-2">
                            <span class="${isMatch ? 'text-amber-200 bg-amber-500/10 border-amber-500/20' : 'text-emerald-200 bg-emerald-500/10 border-emerald-500/20'} border rounded-md px-2 py-0.5 text-[10px] font-bold uppercase">
                                ${isMatch ? 'Update' : 'New'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');

            el.innerHTML = `
                <div class="space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="space-y-2">
                            <div class="text-sm font-bold text-white flex items-center gap-2">
                                <i data-lucide="${options.icon}" class="w-4 h-4 ${options.iconClass}"></i>
                                ${options.title}
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${preview.swimmers.length}</strong> swimmers</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${newCount}</strong> new</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${updateCount}</strong> matched</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${summary.mapped_rows || 0}</strong> PBs mapped</span>
                            </div>
                            <div class="text-xs text-amber-100 bg-amber-500/10 border border-amber-500/20 rounded-lg px-3 py-2">${options.note(summary)}</div>
                            ${ignored.length ? `<div class="text-[11px] text-amber-200">Ignored unsupported events: ${ignored.map(([event, count]) => `${dtsEscape(event)} (${count})`).join(', ')}</div>` : ''}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="${options.applyFn}()"
                                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> Apply Import
                            </button>
                            <button type="button" onclick="${options.clearFn}()"
                                class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel
                            </button>
                        </div>
                    </div>
                    <div class="max-h-72 overflow-auto rounded-lg border border-white/10 bg-slate-950/40">
                        <table class="w-full min-w-[640px] text-xs text-left">
                            <thead class="sticky top-0 bg-slate-900 text-slate-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2">Swimmer</th>
                                    <th class="px-3 py-2">${options.metaLabel}</th>
                                    <th class="px-3 py-2">Age Group</th>
                                    <th class="px-3 py-2">PBs</th>
                                    <th class="px-3 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${previewRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            el.classList.remove('hidden');
            lucide.createIcons();
        }

        function parseSwimClubManagerWorkbook(rows) {
            const pbFields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];
            let headers = [];
            let ageOnDate = '';
            let eventRows = 0;
            let mappedRows = 0;
            const ignoredEvents = {};
            const swimmers = [];

            rows.forEach(row => {
                const first = String(row[0] || '').trim();
                if (first.toLowerCase().startsWith('age on date:')) {
                    ageOnDate = first.replace(/^age on date:\s*/i, '').trim();
                    return;
                }
                if (String(row[1] || '').trim().toLowerCase() === 'age' && String(row[2] || '').trim().toLowerCase() === 'se #') {
                    headers = row;
                    return;
                }
                if (!headers.length || !first) return;

                const age = parseInt(row[1], 10);
                if (!age) return;
                const swimmer = {
                    id: 0,
                    swimmer_name: normaliseImportedName(first),
                    age_group: importedAgeGroupFromAge(age),
                    availability: {},
                    import_meta: {
                        age,
                        age_on_date: ageOnDate,
                        se_number: String(row[2] || '').trim()
                    }
                };
                pbFields.forEach(field => swimmer[field] = '');

                for (let col = 3; col < headers.length; col += 1) {
                    const eventName = String(headers[col] || '').trim();
                    const time = normaliseImportedTime(row[col]);
                    if (!eventName || !time) continue;
                    eventRows += 1;
                    const field = importedEventField(eventName);
                    if (field) {
                        swimmer[field] = time;
                        mappedRows += 1;
                    } else {
                        ignoredEvents[eventName] = (ignoredEvents[eventName] || 0) + 1;
                    }
                }
                swimmers.push(swimmer);
            });

            return {
                success: true,
                swimmers,
                summary: {
                    swimmer_count: swimmers.length,
                    event_rows: eventRows,
                    mapped_rows: mappedRows,
                    ignored_events: ignoredEvents,
                    age_on_date: ageOnDate,
                    age_groups_calculated: !!ageOnDate
                }
            };
        }

        function parseHyTekTeamManagerWorkbook(rows) {
            const pbFields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];
            const distanceHeadings = new Set(['25', '50', '100', '200', '400', '800', '1500']);
            const strokeAliases = {
                free: 'Free',
                fr: 'Free',
                freestyle: 'Free',
                back: 'Back',
                bk: 'Back',
                backstroke: 'Back',
                breast: 'Breast',
                br: 'Breast',
                breaststroke: 'Breast',
                fly: 'Fly',
                fl: 'Fly',
                butterfly: 'Fly',
                im: 'IM',
                'i.m.': 'IM'
            };
            const swimmers = [];
            const ignoredEvents = {};
            let eventRows = 0;
            let mappedRows = 0;
            let currentSection = '';
            let reportTitle = '';

            rows.forEach(row => {
                const cells = row.map(cell => String(cell || '').trim());
                if (!reportTitle) {
                    const title = cells.find(cell => /top times spreadsheet/i.test(cell));
                    if (title) reportTitle = title;
                }
                const sectionCell = cells.find(cell => /^(girls|boys)\s+/i.test(cell));
                if (sectionCell) currentSection = sectionCell;
                const nameCell = cells.find(cell => /^.+\s+\(\d{1,2}\)$/.test(cell));
                const swimmerMatch = nameCell ? nameCell.match(/^(.+?)\s+\((\d{1,2})\)$/) : null;

                if (sectionCell && swimmerMatch) {
                    const sectionIndex = cells.indexOf(sectionCell);
                    const nameIndex = cells.indexOf(nameCell);
                    const preName = cells.slice(sectionIndex + 1, nameIndex);
                    const distanceValues = [];
                    let cursor = 0;
                    while (cursor < preName.length && preName[cursor] !== '') {
                        if (distanceHeadings.has(preName[cursor])) distanceValues.push(preName[cursor]);
                        cursor += 1;
                    }
                    while (cursor < preName.length && preName[cursor] === '') cursor += 1;
                    const strokeValues = [];
                    while (cursor < preName.length && preName[cursor] !== '') {
                        const compact = preName[cursor].toLowerCase().replace(/\./g, '').trim();
                        const stroke = strokeAliases[preName[cursor].toLowerCase()] || strokeAliases[compact];
                        if (stroke) strokeValues.push(stroke);
                        cursor += 1;
                    }
                    const rowSequence = distanceValues
                        .slice(0, strokeValues.length)
                        .map((distance, index) => {
                            const eventName = `${distance} ${strokeValues[index]}`;
                            return {
                                eventName,
                                field: importedEventField(eventName)
                            };
                        });
                    if (rowSequence.length) {
                        const age = parseInt(swimmerMatch[2], 10);
                        const swimmer = {
                            id: 0,
                            swimmer_name: normaliseImportedName(swimmerMatch[1]),
                            age_group: '',
                            availability: {},
                            import_meta: {
                                age,
                                section: currentSection,
                                report: reportTitle || 'Top Times Spreadsheet Report'
                            }
                        };
                        pbFields.forEach(field => swimmer[field] = '');
                        rowSequence.forEach((event, index) => {
                            const time = normaliseImportedTime(row[nameIndex + 1 + index] ?? cells[nameIndex + 1 + index]);
                            if (!time || !isImportedTimeValue(time)) return;
                            eventRows += 1;
                            if (event.field) {
                                swimmer[event.field] = time;
                                mappedRows += 1;
                            } else {
                                ignoredEvents[event.eventName] = (ignoredEvents[event.eventName] || 0) + 1;
                            }
                        });
                        swimmers.push(swimmer);
                    }
                }
            });

            return {
                success: true,
                swimmers,
                summary: {
                    swimmer_count: swimmers.length,
                    event_rows: eventRows,
                    mapped_rows: mappedRows,
                    ignored_events: ignoredEvents,
                    report: reportTitle || 'Top Times Spreadsheet Report',
                    age_groups_calculated: true
                }
            };
        }

        async function previewSwimClubManagerImport(file) {
            const input = document.getElementById('dts-scm-file');
            if (!file) return;
            try {
                if (!window.XLSX) {
                    throw new Error('The spreadsheet reader has not loaded yet. Please refresh and try again.');
                }
                const data = await file.arrayBuffer();
                const workbook = XLSX.read(data, { type: 'array' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                dtsSwimClubManagerPreview = parseSwimClubManagerWorkbook(rows);
                if (!dtsSwimClubManagerPreview.swimmers.length) {
                    throw new Error('No swimmers were found in this Swim Club Manager export.');
                }
                renderSwimClubManagerPreview();
            } catch (err) {
                dtsSwimClubManagerPreview = null;
                renderSwimClubManagerPreview();
                showDtsAlert(err.message || 'Could not read the Swim Club Manager XLSX.', 'error');
            } finally {
                if (input) input.value = '';
            }
        }

        function renderSwimClubManagerPreview() {
            const el = document.getElementById('dts-scm-preview');
            if (!el) return;
            if (!dtsSwimClubManagerPreview?.swimmers?.length) {
                el.classList.add('hidden');
                el.innerHTML = '';
                return;
            }

            const summary = dtsSwimClubManagerPreview.summary || {};
            const existingNames = new Set(collectSwimmers().map(swimmer => swimmer.swimmer_name.toLowerCase()));
            const updateCount = dtsSwimClubManagerPreview.swimmers.filter(swimmer => existingNames.has(swimmer.swimmer_name.toLowerCase())).length;
            const newCount = dtsSwimClubManagerPreview.swimmers.length - updateCount;
            const ignored = Object.entries(summary.ignored_events || {});
            const pbFields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];
            const ageText = summary.age_on_date
                ? `Ages taken from export age-on-date ${dtsEscape(summary.age_on_date)}. Check this matches the league finals date before applying.`
                : 'Age groups are based on ages in the file. Check Swim Club Manager was exported using the league finals date.';
            const previewRows = dtsSwimClubManagerPreview.swimmers.map(swimmer => {
                const pbCount = pbFields.filter(field => swimmer[field]).length;
                const isMatch = existingNames.has(swimmer.swimmer_name.toLowerCase());
                return `
                    <tr class="border-t border-white/5">
                        <td class="px-3 py-2 font-semibold text-white">${dtsEscape(swimmer.swimmer_name)}</td>
                        <td class="px-3 py-2 text-slate-300">${dtsEscape(swimmer.import_meta?.age || '-')}</td>
                        <td class="px-3 py-2 text-slate-300">${dtsEscape(swimmer.age_group || '-')}</td>
                        <td class="px-3 py-2 text-slate-300">${pbCount}</td>
                        <td class="px-3 py-2">
                            <span class="${isMatch ? 'text-amber-200 bg-amber-500/10 border-amber-500/20' : 'text-emerald-200 bg-emerald-500/10 border-emerald-500/20'} border rounded-md px-2 py-0.5 text-[10px] font-bold uppercase">
                                ${isMatch ? 'Update' : 'New'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');

            el.innerHTML = `
                <div class="space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="space-y-2">
                            <div class="text-sm font-bold text-white flex items-center gap-2">
                                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-indigo-300"></i>
                                Swim Club Manager import preview
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${dtsSwimClubManagerPreview.swimmers.length}</strong> swimmers</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${newCount}</strong> new</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${updateCount}</strong> matched</span>
                                <span class="bg-slate-950/40 border border-white/10 rounded-lg px-3 py-2 text-slate-300"><strong class="text-white">${summary.mapped_rows || 0}</strong> PBs mapped</span>
                            </div>
                            <div class="text-xs text-amber-100 bg-amber-500/10 border border-amber-500/20 rounded-lg px-3 py-2">${ageText}</div>
                            ${ignored.length ? `<div class="text-[11px] text-amber-200">Ignored unsupported events: ${ignored.map(([event, count]) => `${dtsEscape(event)} (${count})`).join(', ')}</div>` : ''}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="applySwimClubManagerImport()"
                                class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> Apply Import
                            </button>
                            <button type="button" onclick="clearSwimClubManagerPreview()"
                                class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 font-bold py-2 px-3 rounded-lg text-xs flex items-center gap-1.5">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel
                            </button>
                        </div>
                    </div>
                    <div class="max-h-72 overflow-auto rounded-lg border border-white/10 bg-slate-950/40">
                        <table class="w-full min-w-[640px] text-xs text-left">
                            <thead class="sticky top-0 bg-slate-900 text-slate-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-2">Swimmer</th>
                                    <th class="px-3 py-2">Age</th>
                                    <th class="px-3 py-2">Age Group</th>
                                    <th class="px-3 py-2">PBs</th>
                                    <th class="px-3 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${previewRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            el.classList.remove('hidden');
            lucide.createIcons();
        }

        function clearSwimClubManagerPreview() {
            dtsSwimClubManagerPreview = null;
            renderSwimClubManagerPreview();
        }

        async function previewHyTekImport(file) {
            const input = document.getElementById('dts-hytek-file');
            if (!file) return;
            try {
                const filename = String(file.name || '').toLowerCase();
                if (!filename.endsWith('.csv')) {
                    throw new Error('Please export Hy-Tek Team Manager as CSV before importing.');
                }
                const text = await file.text();
                const workbook = XLSX.read(text, { type: 'string' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                dtsHyTekPreview = parseHyTekTeamManagerWorkbook(rows);
                if (!dtsHyTekPreview.swimmers.length) {
                    throw new Error('No swimmers were found in this Hy-Tek Team Manager CSV export.');
                }
                renderHyTekPreview();
            } catch (err) {
                dtsHyTekPreview = null;
                renderHyTekPreview();
                showDtsAlert(err.message || 'Could not read the Hy-Tek Team Manager CSV export.', 'error');
            } finally {
                if (input) input.value = '';
            }
        }

        function renderHyTekPreview() {
            renderGenericImportPreview(dtsHyTekPreview, 'dts-hytek-preview', {
                title: 'Hy-Tek Team Manager import preview',
                icon: 'file-spreadsheet',
                iconClass: 'text-emerald-300',
                metaLabel: 'Age',
                meta: swimmer => swimmer.import_meta?.age || '-',
                note: () => 'Hy-Tek ages are shown for reference only. Age groups are left blank so they can be set manually in the swimmer list.',
                applyFn: 'applyHyTekImport',
                clearFn: 'clearHyTekPreview'
            });
        }

        function clearHyTekPreview() {
            dtsHyTekPreview = null;
            renderHyTekPreview();
        }

        async function applyImportedSwimmers(preview, clearPreview, sourceName) {
            if (!preview?.swimmers?.length) return;
            const current = collectSwimmers();
            const byName = new Map(current.map(swimmer => [swimmer.swimmer_name.toLowerCase(), swimmer]));
            const pbFields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];

            preview.swimmers.forEach(imported => {
                const key = imported.swimmer_name.toLowerCase();
                const existing = byName.get(key) || {
                    id: 0,
                    swimmer_name: imported.swimmer_name,
                    age_group: '',
                    availability: {}
                };
                if (imported.age_group) existing.age_group = imported.age_group;
                pbFields.forEach(field => {
                    if (imported[field]) existing[field] = imported[field];
                });
                byName.set(key, existing);
            });

            dtsState.swimmers = Array.from(byName.values()).sort((a, b) => a.swimmer_name.localeCompare(b.swimmer_name));
            renderSwimmerList();
            clearPreview();
            await saveSwimmers({ silent: true, reload: false });
            showDtsAlert(`${sourceName} swimmers imported and saved.`, 'success');
            showAutosaveStatus('swimmers', 'Imported and saved', 'saved');
        }

        async function applyTeamunifyImport() {
            try {
                await applyImportedSwimmers(dtsTeamunifyPreview, clearTeamunifyPreview, 'TeamUnify');
            } catch (err) {
                showDtsAlert(err.message || 'Could not apply the TeamUnify import.', 'error');
            }
        }

        async function applySwimClubManagerImport() {
            try {
                await applyImportedSwimmers(dtsSwimClubManagerPreview, clearSwimClubManagerPreview, 'Swim Club Manager');
            } catch (err) {
                showDtsAlert(err.message || 'Could not apply the Swim Club Manager import.', 'error');
            }
        }

        async function applyHyTekImport() {
            try {
                await applyImportedSwimmers(dtsHyTekPreview, clearHyTekPreview, 'Hy-Tek Team Manager');
            } catch (err) {
                showDtsAlert(err.message || 'Could not apply the Hy-Tek Team Manager import.', 'error');
            }
        }

        async function importAstSwimmers() {
            const button = document.getElementById('dts-ast-import-button');
            const originalHtml = button ? button.innerHTML : '';
            try {
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Importing';
                    lucide.createIcons();
                }

                const fd = new FormData();
                fd.append('season', dtsState.season);
                const result = await dtsApi('import_ast_swimmers', fd, 'POST');
                await loadDigitalTeamsheets(false);
                showDtsAlert(`AST import complete: ${result.imported || 0} added, ${result.updated || 0} updated. Availability was left unchanged.`, 'success');
                showAutosaveStatus('swimmers', 'AST import complete', 'saved');
            } catch (err) {
                showDtsAlert(err.message || 'Could not import swimmers from AST.', 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            }
        }

        async function saveSwimmers(options = {}) {
            const { silent = false, reload = true } = options;
            try {
                if (!silent) {
                    window.clearTimeout(dtsAutosave.swimmerTimer);
                    dtsAutosave.swimmerDirty = false;
                }
                const fd = new FormData();
                fd.append('season', dtsState.season);
                fd.append('swimmers', JSON.stringify(collectSwimmers()));
                const res = await dtsApi('save_swimmers', fd, 'POST');
                if (Array.isArray(res.swimmers)) {
                    const idsByName = new Map(res.swimmers.map(swimmer => [swimmer.swimmer_name, swimmer.id]));
                    document.querySelectorAll('#dts-swimmers-body tr').forEach(row => {
                        const name = row.querySelector('[data-field="swimmer_name"]')?.value.trim();
                        if (name && idsByName.has(name)) row.dataset.id = idsByName.get(name);
                    });
                }
                dtsState.swimmers = collectSwimmers();
                if (!silent) showDtsAlert('Swimmer list saved.', 'success');
                if (reload) await loadDigitalTeamsheets(false);
                return res;
            } catch (err) {
                if (!silent) showDtsAlert(err.message || 'Could not save swimmer list.', 'error');
                throw err;
            }
        }

        async function copyPreviousSeasonSwimmers() {
            const sourceYear = dtsState.season - 1;
            if (!confirm(`Copy active swimmers from ${sourceYear} into ${dtsState.season}? Existing swimmers with the same name will be left alone.`)) return;
            try {
                const fd = new FormData();
                fd.append('source_year', sourceYear);
                fd.append('target_year', dtsState.season);
                const res = await dtsApi('copy_swimmers', fd, 'POST');
                showDtsAlert(`Copied ${res.copied || 0} swimmers from ${sourceYear}.`, 'success');
                await loadDigitalTeamsheets(false);
            } catch (err) {
                showDtsAlert(err.message || 'Could not copy swimmers.', 'error');
            }
        }

        function renderRoundSelect() {
            const select = document.getElementById('dts-round-select');
            if (!select) return;
            select.innerHTML = dtsState.rounds.length
                ? dtsState.rounds.map((round, index) => `<option value="${index}">${dtsEscape(round.label)}</option>`).join('')
                : '<option value="">No rounds found for this club</option>';
            renderCopyRoundOptions();
        }

        function renderCopyRoundOptions() {
            const select = document.getElementById('dts-copy-source-select');
            if (!select) return;
            const currentVenueId = dtsState.selectedRound?.venue_detail_id ? String(dtsState.selectedRound.venue_detail_id) : '';
            const options = dtsState.rounds
                .map(round => {
                    const sheet = dtsState.teamsheets[round.round_key];
                    if (!sheet?.id) return null;
                    if (currentVenueId && String(round.venue_detail_id) === currentVenueId) return null;
                    return `<option value="${sheet.id}">${dtsEscape(round.label)}</option>`;
                })
                .filter(Boolean);
            select.innerHTML = '<option value="">Copy from...</option>' + options.join('');
        }

        async function selectDigitalRound() {
            const select = document.getElementById('dts-round-select');
            if (!select || dtsState.rounds.length === 0) {
                renderTeamsheetRows();
                return;
            }
            const previousRound = dtsState.selectedRound;
            const previousIndex = previousRound ? dtsState.rounds.indexOf(previousRound) : -1;
            if (dtsState.selectedRound && dtsAutosave.teamsheetDirty && !dtsAutosave.teamsheetSaving) {
                await runTeamsheetAutosave();
                if (dtsAutosave.teamsheetDirty) {
                    if (previousIndex >= 0) select.value = String(previousIndex);
                    showDtsAlert('Save the current teamsheet changes before changing round.', 'warn');
                    return;
                }
            }
            const index = select.value === '' ? 0 : parseInt(select.value, 10);
            dtsState.selectedRound = dtsState.rounds[index] || dtsState.rounds[0];
            select.value = String(dtsState.rounds.indexOf(dtsState.selectedRound));
            dtsState.activeTeamsheet = dtsState.teamsheets[dtsState.selectedRound.round_key] || null;
            dtsState.loadedEntries = {};
            if (dtsState.activeTeamsheet?.id) {
                try {
                    const payload = await dtsApi('teamsheet', { id: dtsState.activeTeamsheet.id });
                    payload.entries.forEach(entry => dtsState.loadedEntries[entry.event_id] = entry);
                    dtsState.activeTeamsheet = payload.teamsheet;
                    renderAudit(payload.audit || []);
                } catch (err) {
                    renderAudit([]);
                }
            } else {
                renderAudit([]);
            }
            renderCopyRoundOptions();
            renderTeamsheetRows();
        }

        async function copyRoundTeamsheet() {
            const select = document.getElementById('dts-copy-source-select');
            const sourceId = parseInt(select?.value || '0', 10);
            if (!sourceId || !dtsState.selectedRound) {
                showDtsAlert('Choose a saved round to copy from first.', 'warn');
                return;
            }
            const hasCurrentSelections = collectTeamsheetEntries().some(entry => entry.selected_swimmers.length || entry.pb_snapshot || entry.notes);
            if (hasCurrentSelections && !confirm('Copying another round will replace the current selections shown in the builder. Continue?')) {
                return;
            }
            try {
                const payload = await dtsApi('teamsheet', { id: sourceId });
                dtsState.loadedEntries = {};
                (payload.entries || []).forEach(entry => {
                    dtsState.loadedEntries[entry.event_id] = {
                        event_id: entry.event_id,
                        selected_swimmers: entry.selected_swimmers || [],
                        pb_snapshot: entry.pb_snapshot || '',
                        notes: entry.notes || ''
                    };
                });
                renderTeamsheetRows();
                scheduleTeamsheetAutosave();
                showDtsAlert('Round copied into the current builder. Review and save when ready.', 'success');
            } catch (err) {
                showDtsAlert(err.message || 'Could not copy that round.', 'error');
            }
        }

        function getSelectedAvailabilityKey() {
            return dtsState.selectedRound?.round_key?.startsWith('round_') ? dtsState.selectedRound.round_key : 'final';
        }

        function isSwimmerAvailableForSelectedRound(swimmer) {
            if (!dtsState.showAvailableOnly) return true;
            const key = getSelectedAvailabilityKey();
            return !!(swimmer?.availability && swimmer.availability[key] === true);
        }

        function toggleAvailableOnly(checked) {
            cacheCurrentTeamsheetEntries();
            dtsState.swimmers = collectSwimmers();
            dtsState.showAvailableOnly = !!checked;
            renderTeamsheetRows();
        }

        function formatFileSize(bytes) {
            bytes = parseInt(bytes || 0, 10);
            if (!bytes) return '';
            if (bytes < 1024) return `${bytes} bytes`;
            if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
            return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
        }

        function toggleTeamsheetUploadMode(checked) {
            const panel = document.getElementById('dts-upload-panel');
            const tableWrap = document.getElementById('dts-builder-table-wrap');
            if (panel) panel.classList.toggle('hidden', !checked);
            if (tableWrap) tableWrap.classList.toggle('hidden', !!checked);
            if (checked) {
                window.clearTimeout(dtsAutosave.teamsheetTimer);
                dtsAutosave.teamsheetDirty = false;
                showAutosaveStatus('teamsheet', '', 'info');
            }
            updateTeamsheetLinks();
            lucide.createIcons();
        }

        function toggleUploadHelp() {
            const help = document.getElementById('dts-upload-help');
            const button = document.querySelector('[aria-controls="dts-upload-help"]');
            if (!help) return;
            const willShow = help.classList.contains('hidden');
            help.classList.toggle('hidden', !willShow);
            if (button) button.setAttribute('aria-expanded', willShow ? 'true' : 'false');
        }

        function renderUploadPanel() {
            const toggle = document.getElementById('dts-upload-mode-toggle');
            const current = document.getElementById('dts-upload-current');
            const isUpload = dtsState.activeTeamsheet?.submission_type === 'upload';
            if (toggle) {
                toggle.checked = !!isUpload;
                toggleTeamsheetUploadMode(toggle.checked);
            }
            if (!current) return;
            if (isUpload && dtsState.activeTeamsheet?.upload_original_name) {
                const size = formatFileSize(dtsState.activeTeamsheet.upload_file_size);
                current.innerHTML = `Current submitted file: <a href="${dtsEscape(dtsState.activeTeamsheet.upload_url)}" target="_blank" class="text-cyan-300 hover:text-white font-bold">${dtsEscape(dtsState.activeTeamsheet.upload_original_name)}</a>${size ? ` · ${dtsEscape(size)}` : ''}`;
            } else {
                current.textContent = 'PDF, Word, Excel, CSV, RTF, and ODT files are accepted, up to 10MB.';
            }
        }

        function getEventName(event) {
            if (dtsState.selectedRound?.gala_type === 'a_final' && event.a_final_event_name) {
                return event.a_final_event_name;
            }
            return event.event_name;
        }

        function getEventCutOff(event) {
            if (event.event_type === 'Cannon') return 'No Limit';
            if (dtsState.selectedRound?.gala_type === 'a_final' && event.a_final_cut_off) {
                return event.a_final_cut_off;
            }
            return event.cut_off;
        }

        function isIndividualMedleyEvent(event) {
            return /\bInd\.?\s*Medley\b/i.test(getEventName(event)) || /\bIndividual\s*Medley\b/i.test(getEventName(event));
        }

        function isMedleyTeamEvent(event) {
            return /\bMedley\s*team\b/i.test(getEventName(event));
        }

        function getTeamsheetEventLimit(event) {
            if (event.event_type === 'Cannon') return 8;
            if (isIndividualMedleyEvent(event)) return 1;
            return event.event_type === 'Relay' ? 4 : 1;
        }

        function getRelayLegLabel(event, index, limit) {
            if (limit === 8) return `Cannon ${index + 1}`;
            if (isMedleyTeamEvent(event)) {
                return ['Backstroke', 'Breaststroke', 'Butterfly', 'Freestyle'][index] || `Leg ${index + 1}`;
            }
            return `Leg ${index + 1}`;
        }

        function toggleCannonHelp(button) {
            const help = button?.closest('td')?.querySelector('.dts-cannon-help');
            if (!help) return;
            const willShow = help.classList.contains('hidden');
            help.classList.toggle('hidden', !willShow);
            button.setAttribute('aria-expanded', willShow ? 'true' : 'false');
        }

        function getPbField(event) {
            const name = getEventName(event);
            if (isIndividualMedleyEvent(event)) return 'pb_im';
            if (event.event_type !== 'Individual') return '';
            const stroke = ['Freestyle', 'Backstroke', 'Breaststroke', 'Butterfly'].find(s => name.includes(s));
            const distance = (dtsState.selectedRound?.gala_type === 'a_final' && event.a_final_distance) ? event.a_final_distance : event.distance;
            if (!stroke) return name.includes('Individual Medley') ? 'pb_im' : '';
            return dtsPbMap[`${distance}|${stroke}`] || '';
        }

        function swimmerOptions(selected = '') {
            const groups = ['11/U', '13/U', '15/U', 'Open', 'Unassigned'];
            const grouped = Object.fromEntries(groups.map(group => [group, []]));
            dtsState.swimmers.forEach(swimmer => {
                if (!isSwimmerAvailableForSelectedRound(swimmer)) return;
                const ageGroup = groups.includes(swimmer.age_group) ? swimmer.age_group : 'Unassigned';
                grouped[ageGroup].push(swimmer);
            });
            return groups.map(group => {
                if (!grouped[group].length) return '';
                const options = grouped[group]
                    .sort((a, b) => a.swimmer_name.localeCompare(b.swimmer_name))
                    .map(swimmer => `<option value="${dtsEscape(swimmer.swimmer_name)}" ${selected === swimmer.swimmer_name ? 'selected' : ''}>${dtsEscape(swimmer.swimmer_name)}</option>`)
                    .join('');
                return `<optgroup label="${dtsEscape(group)}">${options}</optgroup>`;
            }).join('');
        }

        function buildSwimmerPickerHtml(event, limit, selected) {
            if (limit === 1) {
                return `
                    <select onchange="updateTeamsheetRow(this.closest('tr'))"
                        class="dts-event-swimmers w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-white focus:outline-none focus:border-cyan-400">
                        <option value="">- Select swimmer -</option>
                        ${swimmerOptions(selected[0] || '')}
                    </select>
                    <div class="text-[10px] text-slate-500 mt-1">Select 1 swimmer</div>
                `;
            }

            return `
                <div class="dts-relay-picker space-y-2">
                    ${Array.from({ length: limit }, (_, index) => `
                        <div class="grid grid-cols-[7rem_1fr] gap-2 items-center">
                            <label class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">${dtsEscape(getRelayLegLabel(event, index, limit))}</label>
                            <select onchange="updateTeamsheetRow(this.closest('tr'))"
                                class="dts-event-swimmers w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-white focus:outline-none focus:border-cyan-400">
                                <option value="">- Select swimmer -</option>
                                ${swimmerOptions(selected[index] || '')}
                            </select>
                        </div>
                    `).join('')}
                </div>
                <div class="text-[10px] text-slate-500 mt-1">Select ${limit} swimmers in order</div>
            `;
        }

        function renderTeamsheetRows() {
            const body = document.getElementById('dts-events-body');
            const meta = document.getElementById('dts-teamsheet-meta');
            if (!body || !meta) return;
            if (!dtsState.selectedRound) {
                body.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No round draw found for this season.</td></tr>';
                meta.textContent = 'Digital teamsheets will appear once the season draw is available.';
                return;
            }
            const status = dtsState.activeTeamsheet?.status || 'draft';
            const submitted = dtsState.activeTeamsheet?.submitted_at ? `Submitted ${dtsState.activeTeamsheet.submitted_at}` : 'Not submitted yet';
            const availableCount = dtsState.swimmers.filter(swimmer => !!(swimmer.availability && swimmer.availability[getSelectedAvailabilityKey()] === true)).length;
            const filterText = dtsState.showAvailableOnly ? ` · Showing ${availableCount} available swimmer${availableCount === 1 ? '' : 's'}` : '';
            meta.innerHTML = `<strong class="text-cyan-300">${dtsEscape(dtsState.selectedRound.label)}</strong> · ${dtsEscape(status.toUpperCase())} · ${dtsEscape(submitted)} · Shared with: ${dtsEscape(dtsState.selectedRound.teams.join(', '))}${filterText}`;
            renderUploadPanel();
            body.innerHTML = dtsState.events.map(event => {
                const loaded = dtsState.loadedEntries[event.id] || {};
                const selected = loaded.selected_swimmers || [];
                const limit = getTeamsheetEventLimit(event);
                const pbField = getPbField(event);
                const isTeamEvent = limit > 1;
                const pbValue = isTeamEvent ? '' : (loaded.pb_snapshot || '');
                return `
                    <tr data-event-id="${event.id}" data-pb-field="${pbField}" data-limit="${limit}" class="hover:bg-slate-800/30">
                        <td class="px-3 py-2 text-center font-bold text-slate-500">${event.event_number}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="font-semibold text-white flex flex-wrap items-center gap-2">
                                        <span>${dtsEscape(getEventName(event))}</span>
                                        ${event.event_type === 'Cannon' ? `
                                            <button type="button" onclick="toggleCannonHelp(this)"
                                                class="w-5 h-5 rounded-full bg-slate-800 hover:bg-cyan-600 text-cyan-300 hover:text-white border border-slate-700 hover:border-cyan-500 text-[10px] font-black flex items-center justify-center"
                                                aria-expanded="false" title="Cannon event rules">
                                                ?
                                            </button>
                                        ` : ''}
                                    </div>
                                    <div class="text-[10px] text-slate-500">${dtsEscape(event.event_type)} · ${dtsEscape((dtsState.selectedRound.gala_type === 'a_final' && event.a_final_distance) || event.distance)}</div>
                                    ${event.event_type === 'Cannon' ? `
                                        <div class="dts-cannon-help hidden mt-2 rounded-lg border border-sky-500/20 bg-sky-500/10 p-2 text-[11px] leading-relaxed text-sky-100">
                                            <div>8x1 length: 1 boy and 1 girl from each age group.</div>
                                            <div>Swum in age order: 11/u up to Open.</div>
                                            <div>Restriction: swimmers cannot swim up an age group in the Cannon.</div>
                                        </div>
                                    ` : ''}
                                </div>
                                <button type="button" onclick="toggleTeamsheetEvent(this.closest('tr'))"
                                    class="dts-collapse-btn shrink-0 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 px-2 py-1 rounded-md text-[10px] font-bold flex items-center gap-1"
                                    title="Minimise event">
                                    <i data-lucide="chevron-up" class="w-3 h-3"></i> Minimise
                                </button>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center font-mono text-red-300">${dtsEscape(getEventCutOff(event))}</td>
                        <td class="dts-collapsible-cell px-3 py-2">
                            <div class="dts-event-warning hidden mb-2 rounded-lg border border-amber-500/30 bg-amber-500/10 p-2 text-[11px] text-amber-100"></div>
                            ${buildSwimmerPickerHtml(event, limit, selected)}
                        </td>
                        <td class="dts-collapsible-cell px-3 py-2"><input value="${dtsEscape(pbValue)}" ${isTeamEvent ? 'readonly aria-readonly="true" placeholder="No PB needed"' : ''} class="dts-event-pb w-full border rounded px-2 py-1 font-mono ${isTeamEvent ? 'bg-slate-800/70 border-slate-700 text-slate-500 cursor-not-allowed placeholder:text-slate-500' : 'bg-slate-900 border-slate-700 text-white'}"></td>
                        <td class="dts-collapsible-cell px-3 py-2"><input value="${dtsEscape(loaded.notes || '')}" class="dts-event-notes w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-white"></td>
                    </tr>
                `;
            }).join('');
            document.querySelectorAll('#dts-events-body tr').forEach(updateTeamsheetRow);
            lucide.createIcons();
            updateTeamsheetLinks();
        }

        function toggleTeamsheetEvent(row) {
            if (!row) return;
            const isCollapsed = row.classList.toggle('bg-slate-900/60');
            row.querySelectorAll('.dts-collapsible-cell').forEach(cell => {
                cell.classList.toggle('hidden', isCollapsed);
            });
            const button = row.querySelector('.dts-collapse-btn');
            if (button) {
                button.innerHTML = isCollapsed
                    ? '<i data-lucide="chevron-down" class="w-3 h-3"></i> Expand'
                    : '<i data-lucide="chevron-up" class="w-3 h-3"></i> Minimise';
                button.title = isCollapsed ? 'Expand event' : 'Minimise event';
                lucide.createIcons();
            }
        }

        function warningSignature(messages) {
            return messages.map(message => `${message.type}:${message.detail}`).join('|');
        }

        function renderTeamsheetWarning(row, messages) {
            const panel = row.querySelector('.dts-event-warning');
            if (!panel) return;
            const eventId = row.dataset.eventId;
            if (!messages.length) {
                panel.classList.add('hidden');
                panel.innerHTML = '';
                delete dtsState.ignoredWarnings[eventId];
                return;
            }
            const signature = warningSignature(messages);
            if (dtsState.ignoredWarnings[eventId] === signature) {
                panel.classList.add('hidden');
                panel.innerHTML = '';
                return;
            }
            panel.dataset.warningSignature = signature;
            panel.classList.remove('hidden');
            panel.innerHTML = `
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-1">
                        <div class="font-bold text-amber-200 flex items-center gap-1.5">
                            <i data-lucide="triangle-alert" class="w-3.5 h-3.5"></i>
                            Check this entry
                        </div>
                        ${messages.map(message => `<div>${dtsEscape(message.text)}</div>`).join('')}
                    </div>
                    <button type="button" onclick="ignoreTeamsheetWarning(this)"
                        class="shrink-0 bg-amber-500/10 hover:bg-amber-500/20 text-amber-100 border border-amber-400/30 rounded px-2 py-1 text-[10px] font-bold">
                        Ignore
                    </button>
                </div>
            `;
            lucide.createIcons();
        }

        function ignoreTeamsheetWarning(button) {
            const row = button.closest('tr');
            const panel = row?.querySelector('.dts-event-warning');
            if (!row || !panel?.dataset.warningSignature) return;
            dtsState.ignoredWarnings[row.dataset.eventId] = panel.dataset.warningSignature;
            panel.classList.add('hidden');
            panel.innerHTML = '';
        }

        function updateTeamsheetRow(row) {
            if (!row || !row.dataset.eventId) return;
            const selected = Array.from(row.querySelectorAll('.dts-event-swimmers')).map(select => select.value).filter(Boolean);
            const limit = parseInt(row.dataset.limit, 10);
            const pbField = row.dataset.pbField;
            const pbInput = row.querySelector('.dts-event-pb');
            if (pbInput && limit > 1) {
                pbInput.value = '';
            } else if (pbInput && pbField && selected.length === 1) {
                const swimmer = dtsState.swimmers.find(s => s.swimmer_name === selected[0]);
                pbInput.value = swimmer?.[pbField] || '';
            } else if (pbInput && pbField) {
                pbInput.value = '';
            }
            const availabilityKey = getSelectedAvailabilityKey();
            const unavailable = dtsState.showAvailableOnly ? selected.filter(name => {
                const swimmer = dtsState.swimmers.find(s => s.swimmer_name === name);
                return swimmer?.availability && swimmer.availability[availabilityKey] === false;
            }) : [];
            const duplicateCount = selected.length - new Set(selected).size;
            const swimmerWord = limit === 1 ? 'swimmer' : 'swimmers';
            const messages = [];
            if (selected.length && selected.length !== limit) {
                messages.push({
                    type: 'count',
                    detail: `${selected.length}/${limit}`,
                    text: `This event expects ${limit} ${swimmerWord}, but ${selected.length} selected.`
                });
            }
            if (duplicateCount > 0) {
                messages.push({
                    type: 'duplicate',
                    detail: selected.join(','),
                    text: 'The same swimmer has been selected more than once in this event.'
                });
            }
            if (unavailable.length) {
                messages.push({
                    type: 'availability',
                    detail: unavailable.join(','),
                    text: `${unavailable.join(', ')} ${unavailable.length === 1 ? 'is' : 'are'} marked unavailable for this round/final.`
                });
            }
            renderTeamsheetWarning(row, messages);
        }

        function collectTeamsheetEntries() {
            return Array.from(document.querySelectorAll('#dts-events-body tr[data-event-id]')).map(row => ({
                event_id: parseInt(row.dataset.eventId, 10),
                selected_swimmers: Array.from(row.querySelectorAll('.dts-event-swimmers')).map(select => select.value).filter(Boolean),
                pb_snapshot: row.querySelector('.dts-event-pb').value.trim(),
                notes: row.querySelector('.dts-event-notes').value.trim()
            }));
        }

        function cacheCurrentTeamsheetEntries() {
            collectTeamsheetEntries().forEach(entry => {
                dtsState.loadedEntries[entry.event_id] = entry;
            });
        }

        async function saveTeamsheet(shouldSubmit, options = {}) {
            const { silent = false, reload = true, reason: suppliedReason = '' } = options;
            if (!dtsState.selectedRound) return;
            if (document.getElementById('dts-upload-mode-toggle')?.checked) {
                if (!silent) showDtsAlert('Upload mode is selected. Use Upload & Submit for your own teamsheet document.', 'warn');
                return;
            }
            try {
                if (!silent) {
                    window.clearTimeout(dtsAutosave.teamsheetTimer);
                    dtsAutosave.teamsheetDirty = false;
                }
                let reason = suppliedReason;
                if (dtsState.activeTeamsheet?.status === 'submitted') {
                    reason = reason || prompt('Reason for editing this submitted teamsheet:') || '';
                    if (!reason.trim()) {
                        if (!silent) showDtsAlert('A reason is required for post-submission changes.', 'warn');
                        return;
                    }
                }
                const fd = new FormData();
                fd.append('season', dtsState.season);
                fd.append('round_key', dtsState.selectedRound.round_key);
                fd.append('gala_type', dtsState.selectedRound.gala_type);
                fd.append('venue_detail_id', dtsState.selectedRound.venue_detail_id);
                fd.append('reason', reason);
                fd.append('entries', JSON.stringify(collectTeamsheetEntries()));
                const saved = await dtsApi('save_teamsheet', fd, 'POST');
                cacheCurrentTeamsheetEntries();
                const previousStatus = dtsState.activeTeamsheet?.status || 'draft';
                dtsState.activeTeamsheet = {
                    ...(dtsState.activeTeamsheet || {}),
                    id: saved.teamsheet_id,
                    status: previousStatus,
                    round_key: dtsState.selectedRound.round_key,
                    gala_type: dtsState.selectedRound.gala_type,
                    venue_detail_id: dtsState.selectedRound.venue_detail_id
                };
                dtsState.teamsheets[dtsState.selectedRound.round_key] = dtsState.activeTeamsheet;
                updateTeamsheetLinks();
                if (shouldSubmit) {
                    const submitFd = new FormData();
                    submitFd.append('teamsheet_id', saved.teamsheet_id);
                    await dtsApi('submit_teamsheet', submitFd, 'POST');
                    dtsState.activeTeamsheet.status = 'submitted';
                    if (!silent) showDtsAlert('Teamsheet submitted and shared with the clubs in this gala.', 'success');
                } else {
                    if (!silent) showDtsAlert('Teamsheet draft saved.', 'success');
                }
                if (reload) {
                    await loadDigitalTeamsheets(false);
                    const index = dtsState.rounds.findIndex(round => round.round_key === fd.get('round_key') && String(round.venue_detail_id) === String(fd.get('venue_detail_id')));
                    if (index >= 0) {
                        document.getElementById('dts-round-select').value = String(index);
                        await selectDigitalRound();
                    }
                }
                return saved;
            } catch (err) {
                if (!silent) showDtsAlert(err.message || 'Could not save teamsheet.', 'error');
                throw err;
            }
        }

        async function uploadOwnTeamsheet() {
            if (!dtsState.selectedRound) return;
            const input = document.getElementById('dts-upload-file');
            const file = input?.files?.[0];
            if (!file) {
                showDtsAlert('Choose a teamsheet document to upload first.', 'warn');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                showDtsAlert('Uploaded teamsheets must be 10MB or smaller.', 'warn');
                return;
            }
            let reason = '';
            if (dtsState.activeTeamsheet?.status === 'submitted') {
                reason = prompt('Reason for replacing this submitted teamsheet:') || '';
                if (!reason.trim()) {
                    showDtsAlert('A reason is required when replacing a submitted teamsheet.', 'warn');
                    return;
                }
            }
            try {
                const fd = new FormData();
                fd.append('season', dtsState.season);
                fd.append('round_key', dtsState.selectedRound.round_key);
                fd.append('gala_type', dtsState.selectedRound.gala_type);
                fd.append('venue_detail_id', dtsState.selectedRound.venue_detail_id);
                fd.append('reason', reason);
                fd.append('teamsheet_file', file);
                const saved = await dtsApi('upload_teamsheet', fd, 'POST');
                showDtsAlert('Teamsheet document uploaded and shared with the clubs in this gala.', 'success');
                input.value = '';
                await loadDigitalTeamsheets(false);
                const index = dtsState.rounds.findIndex(round => round.round_key === fd.get('round_key') && String(round.venue_detail_id) === String(fd.get('venue_detail_id')));
                if (index >= 0) {
                    document.getElementById('dts-round-select').value = String(index);
                    await selectDigitalRound();
                }
                return saved;
            } catch (err) {
                showDtsAlert(err.message || 'Could not upload teamsheet document.', 'error');
                throw err;
            }
        }

        function renderAudit(audit) {
            const el = document.getElementById('dts-audit-list');
            if (!el) return;
            if (!audit.length) {
                el.classList.add('hidden');
                el.innerHTML = '';
                return;
            }
            el.innerHTML = `<div class="font-bold mb-2">Recent teamsheet audit</div>` + audit.map(item =>
                `<div class="py-1 border-t border-amber-500/10">${dtsEscape(item.created_at)} · ${dtsEscape(item.changed_by || 'Unknown')} · ${dtsEscape(item.change_summary || '')}${item.reason ? ` · ${dtsEscape(item.reason)}` : ''}</div>`
            ).join('');
            el.classList.remove('hidden');
        }

        function renderSharedSheets() {
            const el = document.getElementById('dts-shared-list');
            if (!el) return;
            if (!dtsState.shared.length) {
                el.innerHTML = '<div class="text-sm text-slate-500 border border-dashed border-slate-700 rounded-xl p-4 text-center">No submitted shared teamsheets yet.</div>';
                return;
            }
            el.innerHTML = dtsState.shared.map(sheet => `
                <div class="bg-slate-900/70 border border-white/5 rounded-xl p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-bold text-white">${dtsEscape(sheet.club_name)}${sheet.is_mine ? ' <span class="text-cyan-300">(you)</span>' : ''}</div>
                            <div class="text-[11px] text-slate-500">${dtsEscape(sheet.round_key.replace('_', ' ').toUpperCase())} · ${dtsEscape(sheet.submitted_at || 'Submitted')}</div>
                            ${sheet.submission_type === 'upload' ? `<div class="text-[11px] text-cyan-300 mt-1">${dtsEscape(sheet.upload_original_name || 'Uploaded document')}${sheet.upload_file_size ? ` · ${dtsEscape(formatFileSize(sheet.upload_file_size))}` : ''}</div>` : ''}
                        </div>
                        ${sheet.submission_type === 'upload'
                            ? `<a href="${dtsEscape(sheet.upload_url)}" target="_blank" class="bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-slate-700 px-2.5 py-1.5 rounded-lg text-xs font-bold">Download</a>`
                            : `<button type="button" onclick="viewSharedTeamsheet(${sheet.id})" class="bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-slate-700 px-2.5 py-1.5 rounded-lg text-xs font-bold">View</button>`}
                    </div>
                </div>
            `).join('');
        }

        async function viewSharedTeamsheet(id) {
            try {
                const payload = await dtsApi('teamsheet', { id });
                if (payload.teamsheet?.submission_type === 'upload' && payload.teamsheet.upload_url) {
                    window.open(payload.teamsheet.upload_url, '_blank');
                    return;
                }
                const lines = payload.entries.map(entry => `${entry.event_number}. ${entry.event_name}: ${entry.selected_swimmers.join(', ') || '-'}`);
                alert(`${payload.teamsheet.club_name}\n${payload.teamsheet.round_key}\n\n${lines.join('\n')}`);
            } catch (err) {
                showDtsAlert(err.message || 'Could not open shared teamsheet.', 'error');
            }
        }

        function updateTeamsheetLinks() {
            const id = dtsState.activeTeamsheet?.id;
            const isUpload = dtsState.activeTeamsheet?.submission_type === 'upload';
            const exportLink = document.getElementById('dts-export-link');
            const programmeLink = document.getElementById('dts-programme-link');
            [exportLink, programmeLink].forEach(link => {
                if (!link) return;
                link.classList.toggle('hidden', !id || isUpload);
                link.classList.toggle('inline-flex', !!id && !isUpload);
            });
            if (id && !isUpload) {
                exportLink.href = `digital_teamsheet_export.php?id=${id}`;
                programmeLink.href = `smartprogrammenew.php?digital_teamsheet_id=${id}`;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            switchDtsTab('swimmers');
            updateImportProviderHelp();
            loadDigitalTeamsheets(false);
            const swimmerBody = document.getElementById('dts-swimmers-body');
            if (swimmerBody) {
                swimmerBody.addEventListener('input', event => {
                    if (event.target.matches('.dts-swimmer-field')) scheduleSwimmerAutosave();
                });
                swimmerBody.addEventListener('change', event => {
                    if (event.target.matches('.dts-swimmer-field, [data-availability]')) scheduleSwimmerAutosave();
                });
            }
            const teamsheetBody = document.getElementById('dts-events-body');
            if (teamsheetBody) {
                teamsheetBody.addEventListener('input', event => {
                    if (event.target.matches('.dts-event-pb, .dts-event-notes')) scheduleTeamsheetAutosave();
                });
                teamsheetBody.addEventListener('change', event => {
                    if (event.target.matches('.dts-event-swimmers')) {
                        updateTeamsheetRow(event.target.closest('tr'));
                        scheduleTeamsheetAutosave();
                    }
                });
            }
            window.addEventListener('beforeunload', event => {
                if (dtsAutosave.swimmerDirty || dtsAutosave.teamsheetDirty || dtsAutosave.swimmerSaving || dtsAutosave.teamsheetSaving) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
        });

        // Checkbox Logic for Directory
        function isRowVisible(row) {
            return row && row.style.display !== 'none';
        }

        function syncRowCheckbox(row) {
            if (!row) return;
            const rowCheckbox = row.querySelector('.row-checkbox');
            const emailCheckboxes = [...row.querySelectorAll('.email-checkbox')];
            if (!rowCheckbox) return;

            const activeEmailCheckboxes = emailCheckboxes.filter(cb => !cb.disabled);
            rowCheckbox.checked = activeEmailCheckboxes.length > 0 && activeEmailCheckboxes.every(cb => cb.checked);
        }

        function syncContactHeader(slot) {
            const headerCheckbox = document.querySelector(`.contact-header-checkbox[data-contact-header="${slot}"]`);
            if (!headerCheckbox) return;

            const visibleCheckboxes = [...document.querySelectorAll(`.dir-row .email-checkbox[data-contact-slot="${slot}"]`)]
                .filter(cb => isRowVisible(cb.closest('.dir-row')));

            headerCheckbox.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(cb => cb.checked);
        }

        function syncDirectoryCheckboxes() {
            document.querySelectorAll('.dir-row').forEach(syncRowCheckbox);
            ['1', '2', '3'].forEach(syncContactHeader);
        }

        function toggleRow(source) {
            const row = source.closest('tr');
            const emailCheckboxes = row.querySelectorAll('.email-checkbox');
            emailCheckboxes.forEach(cb => cb.checked = source.checked);
            ['1', '2', '3'].forEach(syncContactHeader);
        }

        function toggleContactColumn(source) {
            const slot = source.dataset.contactHeader;
            if (!slot) return;

            document.querySelectorAll(`.dir-row .email-checkbox[data-contact-slot="${slot}"]`).forEach(cb => {
                if (isRowVisible(cb.closest('.dir-row'))) {
                    cb.checked = source.checked;
                    syncRowCheckbox(cb.closest('.dir-row'));
                }
            });
        }

        document.querySelectorAll('.contact-header-checkbox').forEach(cb => {
            cb.addEventListener('change', (e) => toggleContactColumn(e.target));
        });

        document.querySelectorAll('.email-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                const row = cb.closest('.dir-row');
                syncRowCheckbox(row);
                syncContactHeader(cb.dataset.contactSlot);
            });
        });

        syncDirectoryCheckboxes();

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

                syncDirectoryCheckboxes();
            }
        <?php endif; ?>
    </script>
</body>

</html>
