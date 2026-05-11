<?php
session_start();
include 'db.php';
include_once 'finals_sync.php';

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
$active_admin_tab = 'tab-links';

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    
    // --- CLUBS CRUD ---
    if ($_POST['admin_action'] === 'update_club') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $pool_name = $_POST['pool_name'];
        $postcode = $_POST['postcode'];
        $website = $_POST['website'];
        $logo = trim($_POST['logo']);
        $lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $lng = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'images/Teams/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_info = pathinfo($_FILES['logo_file']['name']);
            $ext = strtolower($file_info['extension']);
            $new_filename = preg_replace('/[^a-zA-Z0-9]/', '', $name) . '_logo.' . $ext;
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $new_filename)) {
                $logo = $new_filename;
            }
        }

        $stmt = $conn->prepare("UPDATE clubs SET name=?, pool_name=?, postcode=?, website=?, logo=?, latitude=?, longitude=? WHERE id=?");
        $stmt->bind_param("ssssssddi", $name, $pool_name, $postcode, $website, $logo, $lat, $lng, $id);
        if ($stmt->execute()) {
            $success_msg = "Club '$name' updated successfully.";
            // Also update club_contacts name just in case sync is needed
            $conn->query("UPDATE club_contacts SET club_name='" . $conn->real_escape_string($name) . "' WHERE club_id=$id");
        } else {
            $error_msg = "Failed to update club: " . $conn->error;
        }
    }

    if ($_POST['admin_action'] === 'add_club') {
        $name = trim($_POST['name']);
        $pool_name = trim($_POST['pool_name']);
        $postcode = trim($_POST['postcode']);
        $website = trim($_POST['website']);
        $logo = trim($_POST['logo']);
        $lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $lng = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
        $initial_pin = trim($_POST['access_pin'] ?? '0000');

        if ($name === '' || $pool_name === '' || $postcode === '') {
            $error_msg = "Club name, pool name and postcode are required.";
        } elseif ($initial_pin !== '' && !preg_match('/^\d{4}$/', $initial_pin)) {
            $error_msg = "Initial PIN must be exactly 4 digits.";
        } else {
            if ($initial_pin === '') {
                $initial_pin = '0000';
            }

            $dup_stmt = $conn->prepare("SELECT id, is_active FROM clubs WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $dup_stmt->bind_param("s", $name);
            $dup_stmt->execute();
            $existing_club = $dup_stmt->get_result()->fetch_assoc();
            $dup_stmt->close();

            if ($existing_club) {
                $error_msg = !empty($existing_club['is_active'])
                    ? "A club named '$name' already exists."
                    : "A retired club named '$name' already exists. Reactivate it instead of adding a duplicate.";
            } else {
                if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'images/Teams/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    $file_info = pathinfo($_FILES['logo_file']['name']);
                    $ext = strtolower($file_info['extension']);
                    $new_filename = preg_replace('/[^a-zA-Z0-9]/', '', $name) . '_logo.' . $ext;
                    if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $new_filename)) {
                        $logo = $new_filename;
                    }
                }

                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare("INSERT INTO clubs (name, pool_name, postcode, website, logo, latitude, longitude, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    if (!$stmt) throw new Exception($conn->error);
                    $stmt->bind_param("sssssdd", $name, $pool_name, $postcode, $website, $logo, $lat, $lng);
                    if (!$stmt->execute()) throw new Exception($stmt->error);

                    $new_club_id = $conn->insert_id;

                    $contact_stmt = $conn->prepare("INSERT INTO club_contacts (club_id, club_name, access_pin) VALUES (?, ?, ?)");
                    if (!$contact_stmt) throw new Exception($conn->error);
                    $contact_stmt->bind_param("iss", $new_club_id, $name, $initial_pin);
                    if (!$contact_stmt->execute()) throw new Exception($contact_stmt->error);

                    $results_stmt = $conn->prepare("INSERT INTO results (club_id, season_year, round_1, round_2, round_3, round_4, total) VALUES (?, ?, 0, 0, 0, 0, 0)");
                    if (!$results_stmt) throw new Exception($conn->error);
                    $results_stmt->bind_param("ii", $new_club_id, $current_season_year);
                    if (!$results_stmt->execute()) throw new Exception($results_stmt->error);

                    $conn->commit();
                    $success_msg = "Club '$name' added successfully.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_msg = "Failed to add club: " . $e->getMessage();
                }
            }
        }
    }

    if ($_POST['admin_action'] === 'deactivate_club' || $_POST['admin_action'] === 'reactivate_club') {
        $id = (int) $_POST['id'];
        $is_active = ($_POST['admin_action'] === 'reactivate_club') ? 1 : 0;

        $stmt = $conn->prepare("UPDATE clubs SET is_active=? WHERE id=?");
        $stmt->bind_param("ii", $is_active, $id);
        if ($stmt->execute()) {
            $success_msg = $is_active ? "Club reactivated successfully." : "Club retired successfully. Historical results and fixtures have been preserved.";
        } else {
            $error_msg = "Failed to update club status: " . $conn->error;
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

    // --- ADD VENUE SLOT ---
    if ($_POST['admin_action'] === 'add_venue') {
        $placeholder_club = cotswold_get_placeholder_club_id($conn);
        $round_num = isset($_POST['new_round']) ? intval($_POST['new_round']) : 1;

        if ($round_num === 99) {
            cotswold_ensure_finals_venue_rows($conn, $current_season_year, cotswold_get_finals_date_for_season($conn, $current_season_year));
            $sync = cotswold_sync_finals_from_standings($conn, $current_season_year);
            $success_msg = "A, B and C Final venue slots are ready for Season $current_season_year.";
            if (!empty($sync['synced'])) {
                $success_msg .= " Team assignments have been synced from the current standings.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO venue_details (club_id, venue_name, round_number, season_year, gala_type) VALUES (?, 'TBC', ?, ?, 'round')");
            $stmt->bind_param("iii", $placeholder_club, $round_num, $current_season_year);
            if ($stmt->execute()) {
                $success_msg = "Blank venue added for Round $round_num (Season $current_season_year).";
            } else {
                $error_msg = "Failed to add venue: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // --- DELETE VENUE SLOT ---
    if ($_POST['admin_action'] === 'delete_venue') {
        $venue_id = intval($_POST['venue_id']);
        $stmt = $conn->prepare("DELETE FROM venue_details WHERE id = ?");
        $stmt->bind_param("i", $venue_id);
        if ($stmt->execute()) {
            $success_msg = "Venue slot removed.";
        } else {
            $error_msg = "Failed to delete venue: " . $conn->error;
        }
    }

    // --- VENUE CRUD ---
    if ($_POST['admin_action'] === 'update_venue') {
        $active_admin_tab = 'tab-venues';
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
        $t5 = !empty($_POST['team_5_id']) ? $_POST['team_5_id'] : null;
        $t6 = !empty($_POST['team_6_id']) ? $_POST['team_6_id'] : null;
        $t7 = !empty($_POST['team_7_id']) ? $_POST['team_7_id'] : null;
        $t8 = !empty($_POST['team_8_id']) ? $_POST['team_8_id'] : null;
        if ($host_id === null || $round_num === null) {
            $error_msg = "Error: Stale data detected. Please refresh the page and try again to prevent data loss.";
        } else {
            if ($r_date !== null) {
                $stmt_date = $conn->prepare("UPDATE venue_details SET round_date=? WHERE round_number=? AND season_year=?");
                $stmt_date->bind_param("sii", $r_date, $round_num, $current_season_year);
                $stmt_date->execute();
                $stmt_date->close();

                if ((int)$round_num === 99) {
                    $setting_key = 'finals_date_' . $current_season_year;
                    $stmt_setting = $conn->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt_setting->bind_param("ss", $setting_key, $r_date);
                    $stmt_setting->execute();
                    $stmt_setting->close();
                }
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
                $stmt = $conn->prepare("UPDATE venue_details SET club_id=?, venue_name=?, address=?, warmup_time=?, start_time=?, payment_info=?, parking_info=?, team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, team_5_id=?, team_6_id=?, team_7_id=?, team_8_id=?, results_file=? WHERE id=?");
                $stmt->bind_param("issssssiiiiiiiisi", $host_id, $v_name, $addr, $warm, $start, $pay, $park, $t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $results_file_name, $id);
            } else {
                $stmt = $conn->prepare("UPDATE venue_details SET club_id=?, venue_name=?, address=?, warmup_time=?, start_time=?, payment_info=?, parking_info=?, team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, team_5_id=?, team_6_id=?, team_7_id=?, team_8_id=? WHERE id=?");
                $stmt->bind_param("issssssiiiiiiiii", $host_id, $v_name, $addr, $warm, $start, $pay, $park, $t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $id);
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
        $active_admin_tab = 'tab-venues';
        $tier = $_POST['final_tier']; // A, B, or C
        $success_msgs = [];

        if (isset($_POST['finals_date']) && trim($_POST['finals_date']) !== '') {
            $finals_date = trim($_POST['finals_date']);
            $setting_key = 'finals_date_' . $current_season_year;
            $stmt_setting = $conn->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt_setting->bind_param("ss", $setting_key, $finals_date);
            if (!$stmt_setting->execute()) {
                $error_msg = "Failed to save finals date.";
            }
            $stmt_setting->close();

            cotswold_ensure_finals_venue_rows($conn, $current_season_year, $finals_date);
            $sync = cotswold_sync_finals_from_standings($conn, $current_season_year);
            if (empty($error_msg)) {
                $success_msgs[] = !empty($sync['synced']) ? "Finals date saved and team assignments synced." : "Finals date saved.";
            } else {
                $error_msg = "Failed to save finals date.";
            }
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
        } elseif (empty($error_msg) && empty($_FILES['results_file']['name']) && empty($_POST['finals_date'])) {
            $error_msg = "No file, link, or finals date provided.";
        }
    }

    // --- RESET STATS ---
    if ($_POST['admin_action'] === 'reset_stats') {
        if ($conn->query("UPDATE tracking_stats SET count = 0 WHERE action_name IN ('programme_generated', 'report_generated')")) {
            $success_msg = "Tracking statistics have been reset to zero.";
        } else {
            $error_msg = "Failed to reset stats: " . $conn->error;
        }
    }

    // --- GALA EVENT CRUD ---
    if ($_POST['admin_action'] === 'update_event') {
        $event_id = intval($_POST['event_id']);
        $event_name = trim($_POST['event_name']);
        $distance = trim($_POST['distance']);
        $age_group = trim($_POST['age_group']);
        $gender = $_POST['gender'];
        $event_type = $_POST['event_type'];
        $cut_off_raw = trim($_POST['cut_off_time']);
        
        $a_final_event_name = !empty($_POST['a_final_event_name']) ? trim($_POST['a_final_event_name']) : null;
        $a_final_distance = !empty($_POST['a_final_distance']) ? trim($_POST['a_final_distance']) : null;
        $a_final_cut_off_raw = !empty($_POST['a_final_cut_off_time']) ? trim($_POST['a_final_cut_off_time']) : null;

        // Helper: parse "M:SS.xx" or "SS.xx" to milliseconds
        function parseTimeToMs($str) {
            if (!$str) return null;
            $str = trim($str);
            // MM:SS.xx
            if (preg_match('/^(\d{1,2}):(\d{1,2})\.?(\d{0,3})$/', $str, $m)) {
                $min = intval($m[1]);
                $sec = intval($m[2]);
                $frac = str_pad(substr($m[3] ?? '0', 0, 3), 3, '0');
                return ($min * 60 + $sec) * 1000 + intval($frac);
            }
            // SS.xx (no colon)
            if (preg_match('/^(\d{1,4})\.?(\d{0,3})$/', $str, $m)) {
                $sec = intval($m[1]);
                $frac = str_pad(substr($m[2] ?? '0', 0, 3), 3, '0');
                $min = floor($sec / 60);
                $sec = $sec % 60;
                return ($min * 60 + $sec) * 1000 + intval($frac);
            }
            return null;
        }

        $cut_off_ms = parseTimeToMs($cut_off_raw);
        $a_final_cut_off_ms = $a_final_cut_off_raw ? parseTimeToMs($a_final_cut_off_raw) : null;

        if (!$event_name || !$distance || !$cut_off_ms) {
            $error_msg = "Event name, distance, and a valid cut-off time are required.";
        } else {
            $stmt = $conn->prepare("UPDATE gala_events SET 
                event_name=?, distance=?, age_group=?, gender=?, event_type=?, cut_off_time_ms=?,
                a_final_event_name=?, a_final_distance=?, a_final_cut_off_time_ms=?
                WHERE id=?");
            $stmt->bind_param("sssssisisi", 
                $event_name, $distance, $age_group, $gender, $event_type, $cut_off_ms,
                $a_final_event_name, $a_final_distance, $a_final_cut_off_ms, $event_id
            );
            if ($stmt->execute()) {
                $success_msg = "Event #" . intval($_POST['event_number']) . " updated successfully.";
            } else {
                $error_msg = "Failed to update event: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // --- ADD NEW GALA EVENT ---
    if ($_POST['admin_action'] === 'add_event') {
        $event_number = intval($_POST['event_number']);
        $event_name = trim($_POST['event_name']);
        $distance = trim($_POST['distance']);
        $age_group = trim($_POST['age_group']);
        $gender = $_POST['gender'];
        $event_type = $_POST['event_type'];
        $cut_off_raw = trim($_POST['cut_off_time']);
        $season_year = $current_season_year;

        $a_final_event_name = !empty($_POST['a_final_event_name']) ? trim($_POST['a_final_event_name']) : null;
        $a_final_distance = !empty($_POST['a_final_distance']) ? trim($_POST['a_final_distance']) : null;
        $a_final_cut_off_raw = !empty($_POST['a_final_cut_off_time']) ? trim($_POST['a_final_cut_off_time']) : null;

        // Reuse the parser (may already be defined from update_event in same request)
        if (!function_exists('parseTimeToMs')) {
            function parseTimeToMs($str) {
                if (!$str) return null;
                $str = trim($str);
                if (preg_match('/^(\d{1,2}):(\d{1,2})\.?(\d{0,3})$/', $str, $m)) {
                    $min = intval($m[1]); $sec = intval($m[2]);
                    $frac = str_pad(substr($m[3] ?? '0', 0, 3), 3, '0');
                    return ($min * 60 + $sec) * 1000 + intval($frac);
                }
                if (preg_match('/^(\d{1,4})\.?(\d{0,3})$/', $str, $m)) {
                    $sec = intval($m[1]);
                    $frac = str_pad(substr($m[2] ?? '0', 0, 3), 3, '0');
                    $min = floor($sec / 60); $sec = $sec % 60;
                    return ($min * 60 + $sec) * 1000 + intval($frac);
                }
                return null;
            }
        }

        $cut_off_ms = parseTimeToMs($cut_off_raw);
        $a_final_cut_off_ms = $a_final_cut_off_raw ? parseTimeToMs($a_final_cut_off_raw) : null;

        if (!$event_number || !$event_name || !$distance || !$cut_off_ms) {
            $error_msg = "Event number, name, distance, and a valid cut-off time are required.";
        } else {
            // Check for duplicate event number
            $dup = $conn->prepare("SELECT id FROM gala_events WHERE event_number = ? AND season_year = ?");
            $dup->bind_param("ii", $event_number, $season_year);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) {
                $error_msg = "Event #$event_number already exists for this season.";
            } else {
                $stmt = $conn->prepare("INSERT INTO gala_events 
                    (event_number, event_name, distance, age_group, gender, event_type, cut_off_time_ms,
                     a_final_event_name, a_final_distance, a_final_cut_off_time_ms, season_year) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssisisii", 
                    $event_number, $event_name, $distance, $age_group, $gender, $event_type, $cut_off_ms,
                    $a_final_event_name, $a_final_distance, $a_final_cut_off_ms, $season_year
                );
                if ($stmt->execute()) {
                    $success_msg = "Event #$event_number added successfully.";
                } else {
                    $error_msg = "Failed to add event: " . $conn->error;
                }
                $stmt->close();
            }
            $dup->close();
        }
    }

    // --- DELETE GALA EVENT ---
    if ($_POST['admin_action'] === 'delete_event') {
        $event_id = intval($_POST['event_id']);
        
        // Safety: check if this event has any recorded results
        $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM gala_results WHERE event_id = ? AND (time_ms IS NOT NULL OR is_dq = 1)");
        $chk->bind_param("i", $event_id);
        $chk->execute();
        $result_count = $chk->get_result()->fetch_assoc()['cnt'];
        $chk->close();

        if ($result_count > 0) {
            $error_msg = "Cannot delete this event — it has $result_count recorded result(s). Remove them first.";
        } else {
            // Delete any empty result rows first (foreign key constraint)
            $conn->query("DELETE FROM gala_results WHERE event_id = $event_id");
            
            $stmt = $conn->prepare("DELETE FROM gala_events WHERE id = ?");
            $stmt->bind_param("i", $event_id);
            if ($stmt->execute()) {
                $success_msg = "Event deleted successfully.";
            } else {
                $error_msg = "Failed to delete event: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // --- DUPLICATE SEASON ---
    if ($_POST['admin_action'] === 'duplicate_season') {
        $source_year = intval($_POST['source_year']);
        
        $targets = [];
        if (!empty($_POST['target_years']) && is_array($_POST['target_years'])) {
            $targets = array_map('intval', $_POST['target_years']);
        } elseif (!empty($_POST['target_year'])) {
            $targets[] = intval($_POST['target_year']);
        }

        if (!$source_year || empty($targets)) {
            $error_msg = "Please select a source season and at least one target season.";
        } else {
            // Check source has events
            $chk_src = $conn->prepare("SELECT COUNT(*) as cnt FROM gala_events WHERE season_year = ?");
            $chk_src->bind_param("i", $source_year);
            $chk_src->execute();
            $source_count = $chk_src->get_result()->fetch_assoc()['cnt'];
            $chk_src->close();

            if ($source_count == 0) {
                $error_msg = "Source Season $source_year has no events to copy.";
            } else {
                $success_years = [];
                $skipped_years = [];
                
                foreach ($targets as $ty) {
                    if ($ty === $source_year) continue;
                    
                    // Check target doesn't already have events
                    $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM gala_events WHERE season_year = ?");
                    $chk->bind_param("i", $ty);
                    $chk->execute();
                    $existing = $chk->get_result()->fetch_assoc()['cnt'];
                    $chk->close();

                    if ($existing > 0) {
                        $skipped_years[] = $ty;
                    } else {
                        $copy_sql = "INSERT INTO gala_events 
                            (event_number, event_name, distance, age_group, gender, event_type, cut_off_time_ms, 
                             a_final_event_name, a_final_distance, a_final_cut_off_time_ms, season_year)
                            SELECT event_number, event_name, distance, age_group, gender, event_type, cut_off_time_ms, 
                                   a_final_event_name, a_final_distance, a_final_cut_off_time_ms, ?
                            FROM gala_events WHERE season_year = ? ORDER BY event_number ASC";
                        $stmt = $conn->prepare($copy_sql);
                        $stmt->bind_param("ii", $ty, $source_year);
                        if ($stmt->execute()) {
                            $success_years[] = $ty;
                        }
                        $stmt->close();
                    }
                }
                
                if (count($success_years) > 0) {
                    $success_msg = "Successfully copied $source_count events to: " . implode(", ", $success_years) . ".";
                    if (count($skipped_years) > 0) {
                        $success_msg .= " Skipped (already had data): " . implode(", ", $skipped_years) . ".";
                    }
                } else {
                    $error_msg = "No seasons were updated. " . (count($skipped_years) > 0 ? "Skipped (already had data): " . implode(", ", $skipped_years) : "");
                }
            }
        }
    }
    
    // --- SET ACTIVE SEASON ---
    if ($_POST['admin_action'] === 'set_active_season') {
        $new_season = intval($_POST['new_season']);
        if ($new_season >= 2020 && $new_season <= 2099) {
            $setting_key = 'current_season_year';
            $new_season_value = (string)$new_season;
            $stmt = $conn->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->bind_param("ss", $setting_key, $new_season_value);
            if ($stmt->execute()) {
                $current_season_year = $new_season;
                $success_msg = "Active Season successfully changed to $new_season.";
            } else {
                $error_msg = "Failed to update active season.";
            }
        } else {
            $error_msg = "Invalid season year selected.";
        }
    }
    
    // --- AUTO-GENERATE FINALS ---
    if ($_POST['admin_action'] === 'generate_finals') {
        $active_admin_tab = 'tab-venues';
        try {
            $sync = cotswold_sync_finals_from_standings($conn, $current_season_year);
            if (!empty($sync['synced'])) {
                $success_msg = "Finals teams synced from the current standings. Existing final dates, venues, uploads and links were preserved.";
            } else {
                $error_msg = $sync['message'];
            }
        } catch (Exception $e) {
            $error_msg = "Error syncing finals: " . $e->getMessage();
        }
    }
}

// Fetch all necessary data
$clubs_data = [];
$contacts_data = [];
$venues_data = [];
$active_clubs_data = [];
$retired_clubs_data = [];

if ($is_logged_in) {
    // Clubs
    $res = $conn->query("SELECT * FROM clubs ORDER BY name ASC");
    if ($res) while($r = $res->fetch_assoc()) {
        $clubs_data[] = $r;
        if (!empty($r['is_active'])) {
            $active_clubs_data[] = $r;
        } else {
            $retired_clubs_data[] = $r;
        }
    }

    // Contacts
    $res = $conn->query("SELECT cc.*, c.name as real_club_name, c.logo, c.is_active FROM club_contacts cc JOIN clubs c ON cc.club_id = c.id ORDER BY c.is_active DESC, c.name ASC");
    if ($res) while($r = $res->fetch_assoc()) $contacts_data[] = $r;

    // Venues (filtered by Active Season)
    $res = $conn->query("SELECT vd.*, c.name as host_club_name FROM venue_details vd JOIN clubs c ON vd.club_id = c.id WHERE vd.season_year = $current_season_year ORDER BY round_number ASC, FIELD(vd.gala_type, 'a_final', 'b_final', 'c_final', 'round'), c.name ASC");
    if ($res) while($r = $res->fetch_assoc()) $venues_data[] = $r;

    // Stats
    $prog_count = 0;
    $rep_count = 0;
    $res = $conn->query("SELECT action_name, count FROM tracking_stats");
    if ($res) {
        while($r = $res->fetch_assoc()) {
            if ($r['action_name'] == 'programme_generated') $prog_count = $r['count'];
            if ($r['action_name'] == 'report_generated') $rep_count = $r['count'];
        }
    }
}

function cotswold_render_admin_club_card($club) {
    ?>
    <div class="glass-panel p-5 rounded-2xl border border-white/5 relative group">
        <form method="POST" enctype="multipart/form-data" class="space-y-3 relative z-10">
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
                <div class="w-full">
                    <div class="flex items-center gap-2">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($club['name']); ?>" class="font-bold bg-transparent border-b border-transparent hover:border-slate-600 focus:border-indigo-500 focus:outline-none w-full text-white pb-1">
                        <?php if (empty($club['is_active'])): ?>
                            <span class="text-[10px] uppercase tracking-wider bg-amber-500/10 text-amber-300 border border-amber-500/30 rounded px-2 py-0.5">Retired</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-[80px_1fr] items-center gap-2 text-xs">
                <span class="text-slate-500">Pool:</span>
                <input type="text" name="pool_name" value="<?php echo htmlspecialchars($club['pool_name']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">

                <span class="text-slate-500">Postcode:</span>
                <input type="text" name="postcode" value="<?php echo htmlspecialchars($club['postcode']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">

                <span class="text-slate-500">Website:</span>
                <input type="text" name="website" value="<?php echo htmlspecialchars($club['website']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">

                <span class="text-slate-500">Logo file:</span>
                <div class="flex gap-2 w-full">
                    <input type="text" name="logo" value="<?php echo htmlspecialchars($club['logo']); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-1/3 text-xs" placeholder="Filename">
                    <input type="file" name="logo_file" accept="image/*" class="bg-slate-900/50 border border-slate-800 rounded py-1 px-1 focus:border-indigo-500 focus:outline-none text-slate-400 w-2/3 text-[10px] file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer">
                </div>

                <span class="text-slate-500">Latitude:</span>
                <input type="number" step="0.000001" name="latitude" value="<?php echo htmlspecialchars($club['latitude'] ?? ''); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">

                <span class="text-slate-500">Longitude:</span>
                <input type="number" step="0.000001" name="longitude" value="<?php echo htmlspecialchars($club['longitude'] ?? ''); ?>" class="bg-slate-900/50 border border-slate-800 rounded px-2 py-1 focus:border-indigo-500 focus:outline-none text-white w-full">
            </div>

            <div class="flex justify-between items-center mt-4 pt-4 border-t border-white/5 opacity-0 group-hover:opacity-100 transition-opacity">
                <button type="submit" class="text-xs bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded-lg font-bold flex items-center gap-1 transition-colors">
                    <i data-lucide="save" class="w-3 h-3"></i> Sync Details
                </button>
        </form>
                <?php if (!empty($club['is_active'])): ?>
                <form method="POST" onsubmit="return confirm('Retire this club from active league workflows? Historical results and fixtures will be preserved.');">
                    <input type="hidden" name="admin_action" value="deactivate_club">
                    <input type="hidden" name="id" value="<?php echo $club['id']; ?>">
                    <button type="submit" class="text-xs bg-amber-600/20 text-amber-300 hover:bg-amber-600 hover:text-white px-3 py-1.5 rounded-lg flex items-center gap-1 transition-colors" title="Retire club">
                        <i data-lucide="archive" class="w-3 h-3"></i> Retire
                    </button>
                </form>
                <?php else: ?>
                <form method="POST" onsubmit="return confirm('Reactivate this club for league workflows and team portal access?');">
                    <input type="hidden" name="admin_action" value="reactivate_club">
                    <input type="hidden" name="id" value="<?php echo $club['id']; ?>">
                    <button type="submit" class="text-xs bg-emerald-600/20 text-emerald-300 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg flex items-center gap-1 transition-colors" title="Reactivate club">
                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Reactivate
                    </button>
                </form>
                <?php endif; ?>
            </div>
    </div>
    <?php
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
                    <button onclick="openTab(event, 'tab-gala-results')" class="tab-btn px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all text-slate-400 hover:bg-white/5 hover:text-white">
                        <i data-lucide="bar-chart-2" class="w-4 h-4"></i> Gala Results
                    </button>
                    <button onclick="openTab(event, 'tab-digital-teamsheets')" class="tab-btn px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all text-slate-400 hover:bg-white/5 hover:text-white">
                        <i data-lucide="clipboard-list" class="w-4 h-4"></i> Digital Teamsheets
                    </button>
                    <button onclick="openTab(event, 'tab-events')" class="tab-btn px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all text-slate-400 hover:bg-white/5 hover:text-white">
                        <i data-lucide="calendar" class="w-4 h-4"></i> Event Management
                    </button>
                </div>

                <!-- TAB: QUICK LINKS -->
                <div id="tab-links" class="tab-content active space-y-6">
                    <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-4">
                        <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="zap" class="w-5 h-5 text-sky-400"></i> Admin Shortcuts</h2>
                        
                        <!-- SEASON CONTROLLER -->
                        <div class="bg-indigo-500/10 border border-indigo-500/30 p-3 rounded-2xl flex items-center gap-4">
                            <span class="text-sm font-bold text-indigo-400">Active Season:</span>
                            <form method="POST" class="flex gap-2">
                                <input type="hidden" name="admin_action" value="set_active_season">
                                <select name="new_season" class="bg-slate-900 border border-slate-700 rounded-lg py-1 px-3 text-sm focus:outline-none focus:border-indigo-500 text-white">
                                    <?php for($y=2020; $y<=2099; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php if($current_season_year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-xs text-white font-bold py-1 px-3 rounded-lg transition-colors">Apply</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                        <div class="glass-panel p-5 rounded-2xl border border-white/5 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-slate-400 text-xs uppercase tracking-wider font-semibold mb-1">Smart Programmes</p>
                                    <p class="text-3xl font-bold text-white"><?php echo number_format($prog_count); ?></p>
                                </div>
                                <div class="bg-emerald-500/20 p-3 rounded-xl">
                                    <i data-lucide="printer" class="w-6 h-6 text-emerald-400"></i>
                                </div>
                            </div>
                        </div>

                        <div class="glass-panel p-5 rounded-2xl border border-white/5 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-slate-400 text-xs uppercase tracking-wider font-semibold mb-1">Results Matcher Reports</p>
                                    <p class="text-3xl font-bold text-white"><?php echo number_format($rep_count); ?></p>
                                </div>
                                <div class="bg-sky-500/20 p-3 rounded-xl">
                                    <i data-lucide="bar-chart-2" class="w-6 h-6 text-sky-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mb-6">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to reset all generation statistics to zero?');">
                            <input type="hidden" name="admin_action" value="reset_stats">
                            <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-xs font-bold transition-colors border border-slate-700 flex items-center gap-2">
                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Reset Statistics
                            </button>
                        </form>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <a href="#" onclick="document.querySelector('button[onclick*=\'tab-gala-results\']').click(); return false;" class="glass-panel p-6 rounded-2xl hover:bg-emerald-900/30 transition-all group border border-emerald-500/20">
                            <div class="h-12 w-12 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="clipboard-list" class="w-6 h-6 text-emerald-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Gala Scoresheets</h3>
                            <p class="text-xs text-slate-400 mt-1">View all team scoresheets.</p>
                        </a>
                        <a href="#" onclick="document.querySelector('button[onclick*=\'tab-digital-teamsheets\']').click(); return false;" class="glass-panel p-6 rounded-2xl hover:bg-cyan-900/30 transition-all group border border-cyan-500/20">
                            <div class="h-12 w-12 bg-cyan-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="clipboard-check" class="w-6 h-6 text-cyan-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Digital Teamsheets</h3>
                            <p class="text-xs text-slate-400 mt-1">Open any club's swimmer list and round teamsheets.</p>
                        </a>
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
                        <a href="gala_scoresheet.php?sandbox=1" class="glass-panel p-6 rounded-2xl hover:bg-amber-900/30 transition-all group border border-amber-500/20">
                            <div class="h-12 w-12 bg-amber-500/20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i data-lucide="test-tube" class="w-6 h-6 text-amber-400"></i>
                            </div>
                            <h3 class="font-bold text-white">Testing Sandbox</h3>
                            <p class="text-xs text-slate-400 mt-1">Test all scoresheet functions without affecting live data.</p>
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
                        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <input type="hidden" name="admin_action" value="add_club">
                            <input type="text" name="name" placeholder="Club Name*" required class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <input type="text" name="pool_name" placeholder="Pool Name*" required class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <input type="text" name="postcode" placeholder="Postcode*" required class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
	                            <input type="text" name="website" placeholder="Website URL" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
	                            <input type="text" name="logo" placeholder="Logo filename (e.g., logo.webp)" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
	                            <input type="file" name="logo_file" accept="image/*" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer text-slate-400">
	                            <input type="text" name="access_pin" placeholder="Initial Team Portal PIN" maxlength="4" pattern="\d{4}" inputmode="numeric" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
	                            <input type="number" step="0.000001" name="latitude" placeholder="Latitude (optional)" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
	                            <input type="number" step="0.000001" name="longitude" placeholder="Longitude (optional)" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg font-bold">Save Club</button>
                        </form>
                    </div>

                    <div class="space-y-10">
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-bold uppercase tracking-widest text-emerald-300 flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i> Active Clubs
                                </h3>
                                <span class="text-xs text-slate-500 font-bold"><?php echo count($active_clubs_data); ?> clubs</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                <?php foreach($active_clubs_data as $club): ?>
                                    <?php cotswold_render_admin_club_card($club); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section>
                            <div class="flex items-center justify-between mb-4 border-t border-white/10 pt-6">
                                <h3 class="text-sm font-bold uppercase tracking-widest text-amber-300 flex items-center gap-2">
                                    <i data-lucide="archive" class="w-4 h-4"></i> Retired Clubs
                                </h3>
                                <span class="text-xs text-slate-500 font-bold"><?php echo count($retired_clubs_data); ?> clubs</span>
                            </div>
                            <?php if (!empty($retired_clubs_data)): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <?php foreach($retired_clubs_data as $club): ?>
                                        <?php cotswold_render_admin_club_card($club); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="border border-dashed border-slate-700 rounded-2xl px-5 py-6 text-sm text-slate-500">
                                    No retired clubs.
                                </div>
                            <?php endif; ?>
                        </section>
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
                                            <h3 class="font-bold text-white flex items-center gap-2">
                                                <?php echo htmlspecialchars($contact['real_club_name']); ?>
                                                <?php if (empty($contact['is_active'])): ?>
                                                    <span class="text-[10px] uppercase tracking-wider bg-amber-500/10 text-amber-300 border border-amber-500/30 rounded px-2 py-0.5">Retired</span>
                                                <?php endif; ?>
                                            </h3>
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
                    <div class="flex flex-col sm:flex-row justify-between items-start mb-4 gap-4">
                        <h2 class="text-xl font-bold flex items-center gap-2 mb-2"><i data-lucide="map-pin" class="w-5 h-5 text-emerald-400"></i> Event Venues Scheduler (Season <?php echo $current_season_year; ?>)</h2>
                        
                        <div class="flex flex-wrap gap-2">
                            <!-- ADD VENUE SLOT FORM -->
                            <form method="POST" class="flex gap-2">
                                <input type="hidden" name="admin_action" value="add_venue">
                                <select name="new_round" class="bg-slate-900 border border-slate-700 px-3 py-2 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500">
                                    <option value="1">Round 1</option>
                                    <option value="2">Round 2</option>
                                    <option value="3">Round 3</option>
                                    <option value="4">Round 4</option>
                                    <option value="99">Finals</option>
                                </select>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-500/20">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Add Venue
                                </button>
                            </form>

                            <form method="POST" onsubmit="return confirm('Resync finals team assignments from the current standings? Existing final dates, venue details, uploads and links will be kept.');">
                                <input type="hidden" name="admin_action" value="generate_finals">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/20">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sync Finals
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php foreach($venues_data as $venue): ?>
                            <?php
                                $is_final_venue = ((int)$venue['round_number'] === 99) || in_array($venue['gala_type'], ['a_final', 'b_final', 'c_final'], true);
                                $venue_label = $is_final_venue ? cotswold_final_gala_label($venue['gala_type']) : 'Round ' . (int)$venue['round_number'];
                            ?>
                            <div class="glass-panel p-5 rounded-2xl border border-white/5 relative group">
                                <form method="POST" class="space-y-4" enctype="multipart/form-data">
                                    <input type="hidden" name="admin_action" value="update_venue">
                                    <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">
                                    <input type="hidden" name="round_number" value="<?php echo $venue['round_number']; ?>">
                                    
                                    <div class="flex justify-between items-start border-b border-white/5 pb-3">
                                        <div>
                                            <div class="bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mb-1 inline-flex items-center gap-2">
                                                <?php echo htmlspecialchars($venue_label); ?> - 
                                                <input type="text" name="round_date" value="<?php echo htmlspecialchars($venue['round_date'] ?? ''); ?>" class="bg-transparent border-b border-emerald-500/50 w-24 focus:outline-none focus:border-emerald-300 text-emerald-300 placeholder-emerald-700/50" placeholder="DD/MM/YYYY">
                                            </div>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Host:</span>
                                                <select name="host_club_id" onchange="const form = this.closest('form'); form.querySelector('input[name=venue_name]').value = ''; form.querySelector('textarea[name=address]').value = ''; form.querySelector('input[name=warmup_time]').value = ''; form.querySelector('input[name=start_time]').value = ''; form.querySelector('input[name=payment_info]').value = ''; form.querySelector('input[name=parking_info]').value = '';" class="bg-transparent text-white text-lg font-bold focus:outline-none appearance-none border-b border-transparent hover:border-emerald-500 focus:border-emerald-500 cursor-pointer">
                                                    <?php foreach($clubs_data as $c): ?>
                                                        <?php
                                                            $is_selected_host = ((int) $venue['club_id'] === (int) $c['id']);
                                                            if (empty($c['is_active']) && !$is_selected_host) continue;
                                                        ?>
                                                        <option value="<?php echo $c['id']; ?>" <?php echo $is_selected_host ? 'selected' : ''; ?> class="text-black bg-white">
                                                            <?php echo htmlspecialchars($c['name'] . (empty($c['is_active']) ? ' (retired)' : '')); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <i data-lucide="chevron-down" class="w-4 h-4 text-emerald-500/50"></i>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white p-2 rounded-lg transition-colors group-hover:scale-105 shadow-lg">
                                                <i data-lucide="save" class="w-4 h-4"></i>
                                            </button>
                                            <button type="submit" name="admin_action" value="delete_venue" onclick="return confirm('Are you sure you want to permanently delete this venue slot?');" class="bg-rose-600 hover:bg-rose-500 text-white p-2 rounded-lg transition-colors shadow-lg">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
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
                                    </div>

                                    <div class="pt-3 border-t border-white/5 space-y-2">
                                        <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block">Competing Teams (Draw)</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php 
                                            // Finals might have up to 8 teams, normally 4 teams
                                            $max_teams = ($venue['round_number'] == 99) ? 8 : 4;
                                            for($t=1; $t<=$max_teams; $t++): 
                                                $team_key = "team_{$t}_id"; 
                                                $selected_id = $venue[$team_key] ?? null;
                                            ?>
                                            <select name="<?php echo $team_key; ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-500 appearance-none">
                                                <option value="">- Select Team <?php echo $t; ?> -</option>
                                                <?php foreach($clubs_data as $c): ?>
                                                    <?php
                                                        $is_selected_team = ((int) $selected_id === (int) $c['id']);
                                                        if (empty($c['is_active']) && !$is_selected_team) continue;
                                                    ?>
                                                    <option value="<?php echo $c['id']; ?>" <?php echo $is_selected_team ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($c['name'] . (empty($c['is_active']) ? ' (retired)' : '')); ?>
                                                    </option>
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
                        <h2 class="text-xl font-bold flex items-center gap-2 mb-6"><i data-lucide="trophy" class="w-5 h-5 text-amber-400"></i> Finals Date & Results Upload</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <?php 
                            $finals_date_value = '';
                            $finals_setting_key = 'finals_date_' . $current_season_year;
                            $finals_setting_stmt = $conn->prepare("SELECT setting_value FROM global_settings WHERE setting_key=? LIMIT 1");
                            $finals_setting_stmt->bind_param("s", $finals_setting_key);
                            $finals_setting_stmt->execute();
                            if ($finals_setting_row = $finals_setting_stmt->get_result()->fetch_assoc()) {
                                $finals_date_value = $finals_setting_row['setting_value'] ?? '';
                            }
                            $finals_setting_stmt->close();
                            $finals_date_stmt = $conn->prepare("SELECT round_date FROM venue_details WHERE season_year=? AND (round_number=99 OR gala_type IN ('a_final','b_final','c_final')) AND round_date IS NOT NULL AND round_date != '' ORDER BY round_date DESC LIMIT 1");
                            $finals_date_stmt->bind_param("i", $current_season_year);
                            $finals_date_stmt->execute();
                            if ($finals_date_value === '' && $finals_date_row = $finals_date_stmt->get_result()->fetch_assoc()) {
                                $finals_date_value = $finals_date_row['round_date'] ?? '';
                            }
                            $finals_date_stmt->close();
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
                                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Finals Date</label>
                                            <input type="text" name="finals_date" placeholder="DD/MM/YYYY" value="<?php echo htmlspecialchars($finals_date_value); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-sky-500">
                                        </div>
                                        
                                        <div>
                                            <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest block mb-1">Results File</label>
                                            <input type="file" name="results_file" accept=".xlsx,.xls,.csv" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-sky-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-sky-600 file:text-white hover:file:bg-sky-500 cursor-pointer">
                                        </div>

                                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2 rounded-lg text-sm border border-slate-700 transition-colors">Save Updates</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB: GALA RESULTS -->
                <div id="tab-gala-results" class="tab-content hidden space-y-6">
                    <?php include 'admin_gala_results.php'; ?>
                </div>

                <!-- TAB: DIGITAL TEAMSHEETS -->
                <div id="tab-digital-teamsheets" class="tab-content hidden space-y-6">
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="clipboard-list" class="w-5 h-5 text-cyan-400"></i> Digital Teamsheets</h2>
                            <p class="text-sm text-slate-400 mt-1">Select a club to edit its swimmer list and current teamsheets using the same portal view club reps use.</p>
                        </div>
                        <div class="bg-slate-900/60 border border-white/10 rounded-2xl p-3 flex flex-col sm:flex-row gap-3 sm:items-center">
                            <label for="adminDigitalClubSelect" class="text-xs font-bold uppercase tracking-widest text-slate-400">Club</label>
                            <select id="adminDigitalClubSelect" onchange="loadAdminDigitalTeamsheets()"
                                class="bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-sm min-w-[260px] focus:outline-none focus:border-cyan-400">
                                <?php foreach($active_clubs_data as $index => $club): ?>
                                    <option value="<?php echo (int)$club['id']; ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($club['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a id="adminDigitalTeamsheetsOpenLink" href="#" target="_blank"
                                class="bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-2 px-4 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm">
                                <i data-lucide="external-link" class="w-4 h-4"></i> Open
                            </a>
                        </div>
                    </div>
                    <div class="glass-panel rounded-2xl border border-cyan-500/20 overflow-hidden">
                        <?php if (empty($active_clubs_data)): ?>
                            <div class="p-8 text-center text-slate-400">No active clubs are available.</div>
                        <?php else: ?>
                            <iframe id="adminDigitalTeamsheetsFrame" title="Selected club digital teamsheets" class="w-full h-[82vh] bg-slate-950" loading="lazy"></iframe>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB: EVENT MANAGEMENT -->
                <div id="tab-events" class="tab-content hidden space-y-6">
                    <?php include 'admin_gala_events.php'; ?>
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
            function loadAdminDigitalTeamsheets() {
                const select = document.getElementById('adminDigitalClubSelect');
                const frame = document.getElementById('adminDigitalTeamsheetsFrame');
                const openLink = document.getElementById('adminDigitalTeamsheetsOpenLink');
                if (!select || !select.value) return;
                const url = `digital-teamsheets.php?admin_club_id=${encodeURIComponent(select.value)}`;
                if (frame && frame.getAttribute('src') !== url) frame.setAttribute('src', url);
                if (openLink) openLink.setAttribute('href', url);
            }

            const initialAdminTab = <?php echo json_encode($active_admin_tab); ?>;
            const initialAdminButton = Array.from(document.querySelectorAll(".tab-btn"))
                .find(button => (button.getAttribute("onclick") || "").includes(`'${initialAdminTab}'`));
            (initialAdminButton || document.getElementById("defaultOpen")).click();
            loadAdminDigitalTeamsheets();
        <?php endif; ?>
    </script>
</body>
</html>
