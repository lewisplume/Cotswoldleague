<?php
include 'db.php';
include 'season_data.php';
include_once 'finals_sync.php';

$active_season_year = $current_season_year ?? 2026;

// Fetch Round 1, Round 2, Round 3, and Round 4 points for Season Draw page
$completed_points = [];
$sql = "SELECT c.name, r.round_1, r.round_2, r.round_3, r.round_4 FROM results r JOIN clubs c ON r.club_id = c.id WHERE r.season_year = $active_season_year";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $completed_points[1][$row['name']] = $row['round_1'];
        $completed_points[2][$row['name']] = $row['round_2'];
        $completed_points[3][$row['name']] = $row['round_3'];
        $completed_points[4][$row['name']] = $row['round_4'];
    }
}

// Name mapping no longer needed with JOIN

// Build Finals team lists using the same ordering logic as the table page
$a_final = [];
$b_final = [];
$c_final = [];

$finals_sql = "SELECT c.name, r.round_1, r.round_2, r.round_3, r.round_4,
              (r.round_1 + r.round_2 + r.round_3 + r.round_4) as total
              FROM results r
              JOIN clubs c ON r.club_id = c.id
              WHERE r.season_year = $active_season_year
              ORDER BY total DESC, c.name ASC";
$finals_result = $conn->query($finals_sql);

if ($finals_result && $finals_result->num_rows > 0) {
    $pos = 1;
    while ($row = $finals_result->fetch_assoc()) {
        if ($pos <= 8) {
            $a_final[] = $row;
        }
        elseif ($pos <= 14) {
            $b_final[] = $row;
        }
        else {
            $c_final[] = $row;
        }
        $pos++;
    }
}


// Fetch Venue Details from DB
$venue_db = [];
$final_venue_db = [];
$v_sql = "SELECT vd.*, c.name AS host_club_name FROM venue_details vd JOIN clubs c ON vd.club_id = c.id WHERE vd.season_year = $active_season_year";
$v_res = $conn->query($v_sql);
if ($v_res && $v_res->num_rows > 0) {
    while ($row = $v_res->fetch_assoc()) {
        // Key by round and host for lookup
        $key = $row['host_club_name'] . '_' . $row['round_number'];
        $venue_db[$key] = $row;

        if ((int)$row['round_number'] === 99 || in_array($row['gala_type'] ?? '', ['a_final', 'b_final', 'c_final'], true)) {
            $final_venue_db[$row['gala_type']] = $row;
        }
    }
}

$live_scoresheets = [];
$live_sql = "SELECT gs.id, gs.venue_detail_id, gs.updated_at, c.name AS host_club_name
             FROM gala_scoresheets gs
             JOIN clubs c ON gs.host_club_id = c.id
             WHERE gs.season_year = $active_season_year
               AND gs.live_public_enabled = 1
               AND gs.status = 'in_progress'
               AND gs.venue_detail_id IS NOT NULL";
$live_res = $conn->query($live_sql);
if ($live_res && $live_res->num_rows > 0) {
    while ($row = $live_res->fetch_assoc()) {
        $live_scoresheets[(int)$row['venue_detail_id']] = [
            'id' => (int)$row['id'],
            'updated_at' => $row['updated_at'],
            'host_club_name' => $row['host_club_name'],
        ];
    }
}

$completed_scoresheet_venues = [];
$completed_sql = "SELECT venue_detail_id, status
                  FROM gala_scoresheets
                  WHERE season_year = $active_season_year
                    AND venue_detail_id IS NOT NULL
                  ORDER BY updated_at DESC, id DESC";
$completed_res = $conn->query($completed_sql);
if ($completed_res && $completed_res->num_rows > 0) {
    while ($row = $completed_res->fetch_assoc()) {
        $venue_detail_id = (int)$row['venue_detail_id'];
        if (!isset($completed_scoresheet_venues[$venue_detail_id])) {
            $completed_scoresheet_venues[$venue_detail_id] = in_array($row['status'], ['verified', 'published'], true);
        }
    }
}

// Helper to get points
function getPoints($round, $team, $completed_points)
{
    if (isset($completed_points[$round][$team])) {
        return $completed_points[$round][$team];
    }
    return null;
}

function cotswold_venue_value($venue_info, $field, $fallback = "Check with host")
{
    $value = trim((string)($venue_info[$field] ?? ''));
    return $value !== '' ? $value : $fallback;
}

function cotswold_render_venue_details($venue_info)
{
    $v_name = trim((string)($venue_info['venue_name'] ?? ''));
    $v_addr = trim((string)($venue_info['address'] ?? ''));
    $v_doors = cotswold_venue_value($venue_info, 'start_time');
    $v_wu = cotswold_venue_value($venue_info, 'warmup_time');
    $v_pay = cotswold_venue_value($venue_info, 'payment_info');
    $v_park = cotswold_venue_value($venue_info, 'parking_info');
    $v_other = trim((string)($venue_info['other_info'] ?? ''));
    $has_card = stripos((string)($venue_info['payment_info'] ?? ''), 'card') !== false;
    ?>
    <div class="text-xs text-slate-300">
        <?php if ($v_name || $v_addr): ?>
        <div class="mb-3">
            <?php if ($v_name): ?>
            <div class="font-bold text-white text-sm mb-0.5">
                <?php echo htmlspecialchars($v_name); ?>
            </div>
            <?php endif; ?>
            <?php if ($v_addr): ?>
            <div class="text-slate-400 leading-snug">
                <?php echo htmlspecialchars($v_addr); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-2 bg-slate-900/40 p-3 rounded-lg border border-white/5">
            <div>
                <span class="block text-[10px] uppercase text-sky-500/80 font-bold mb-0.5">Doors Open</span>
                <span class="font-medium text-white"><?php echo htmlspecialchars($v_doors); ?></span>
            </div>
            <div>
                <span class="block text-[10px] uppercase text-sky-500/80 font-bold mb-0.5">Warm Up</span>
                <span class="font-medium text-white"><?php echo htmlspecialchars($v_wu); ?></span>
            </div>
            <div>
                <span class="block text-[10px] uppercase text-sky-500/80 font-bold mb-0.5">Payment Details</span>
                <span class="font-medium text-white flex items-center gap-2"><?php echo htmlspecialchars($v_pay); ?></span>
            </div>
            <div>
                <span class="block text-[10px] uppercase text-sky-500/80 font-bold mb-0.5">Parking Details</span>
                <span class="font-medium text-white"><?php echo htmlspecialchars($v_park); ?></span>
            </div>
            <?php if ($v_other !== ''): ?>
            <div class="sm:col-span-2">
                <span class="block text-[10px] uppercase text-sky-500/80 font-bold mb-0.5">Any Other Information</span>
                <span class="font-medium text-white"><?php echo htmlspecialchars($v_other); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($has_card): ?>
        <div class="mt-2">
            <span class="inline-flex items-center gap-1 text-[10px] bg-emerald-500/10 text-emerald-400 px-2 py-1 rounded border border-emerald-500/20 font-bold">
                <i data-lucide="credit-card" class="w-3 h-3"></i> Card Accepted
            </span>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Season Draw</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="assets/vendor/tailwindcss-3.4.17.js"></script>
    <script src="assets/vendor/lucide-1.31.0.min.js"></script>
    <script src="gala_scoresheet.js?v=20260514-public-live"></script>
    <style>
        body {
            background-color: #0f172a;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="text-white font-sans min-h-screen">

    <?php include 'nav.php'; ?>

    <!-- HEADER -->
    <div class="py-12 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            Season <span class="text-sky-500">Draw</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto">
            Full round-by-round gala fixtures, venues, and live links for the <?php echo $active_season_year; ?> season.
        </p>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="space-y-8">
            <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                <div>
                    <h2 class="text-3xl font-bold">Season <span class="text-sky-500">Draw</span></h2>
                    <p class="text-slate-400">All preliminary rounds for the <?php echo $active_season_year; ?> season.</p>
                </div>
                <div class="flex gap-2 bg-slate-800/50 p-1 rounded-xl border border-slate-700">
                    <button onclick="filterDraw(1)" id="btnR1"
                        class="px-4 py-2 rounded-lg text-sm font-bold transition-all text-slate-400 hover:text-white">R1</button>
                    <button onclick="filterDraw(2)" id="btnR2"
                        class="px-4 py-2 rounded-lg text-sm font-bold transition-all text-slate-400 hover:text-white">R2</button>
                    <button onclick="filterDraw(3)" id="btnR3"
                        class="px-4 py-2 rounded-lg text-sm font-bold transition-all text-slate-400 hover:text-white">R3</button>
                    <button onclick="filterDraw(4)" id="btnR4"
                        class="px-4 py-2 rounded-lg text-sm font-bold transition-all text-slate-400 hover:text-white">R4</button>
                    <button onclick="filterDraw('Finals')" id="btnRFinals"
                        class="px-4 py-2 rounded-lg text-sm font-bold transition-all bg-sky-600 text-white">Finals</button>
                </div>
            </div>

            <!-- DRAW CONTAINER -->
            <div id="drawWrapper">
                <?php foreach ($season_draw as $round_data):
    $round_num = $round_data['round'];
?>
                <div id="round-<?php echo $round_num; ?>"
                    class="round-cards grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 hidden">

                    <?php foreach ($round_data['galas'] as $index => $gala):
        $host = $gala['host'];
        $teams = $gala['teams'];

        // Use the native club name, which matches clubs.name and host_club_name
        $lookup_key = $host . '_' . $round_num;

        $venue_info = $venue_db[$lookup_key] ?? null;

        // Construct display Details
        $payment_info_raw = '';
        $v_name = '';
        $v_addr = '';
        $v_wu = '';
        $v_st = '';
        $v_pay = '';
        $v_park = '';
        $is_db_venue = false; // Flag to track if we have DB data

        if ($venue_info) {
            $is_db_venue = true;
            $v_name = $venue_info['venue_name'] ?? '';
            $v_addr = $venue_info['address'] ?? '';
            $v_wu = !empty($venue_info['warmup_time']) ? $venue_info['warmup_time'] : "Check with host";
            $v_st = !empty($venue_info['start_time']) ? $venue_info['start_time'] : "Check with host";
            $v_pay = !empty($venue_info['payment_info']) ? $venue_info['payment_info'] : "Check with host";
            $v_park = !empty($venue_info['parking_info']) ? $venue_info['parking_info'] : "Check with host";

            $payment_info_raw = $venue_info['payment_info'] ?? '';
        }
        else {
            $display_details = $gala['details']; // Fallback
            $payment_info_raw = $gala['details'];
        }

        $has_card = stripos($payment_info_raw, 'Card') !== false;
        $live_info = ($venue_info && isset($live_scoresheets[(int)$venue_info['id']])) ? $live_scoresheets[(int)$venue_info['id']] : null;
        $is_completed = $venue_info && !empty($completed_scoresheet_venues[(int)$venue_info['id']]);
?>

                    <div
                        class="glass-panel rounded-2xl overflow-hidden border border-white/5 hover:border-sky-500/30 transition-all group">
                        <div class="bg-sky-500/10 px-5 py-3 border-b border-white/5 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-tighter text-sky-400">Host
                                    Club</span>
                                <?php if ($is_completed): ?>
                                <span
                                    class="bg-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider border border-emerald-500/30">Completed</span>
                                <?php
        endif; ?>
                            </div>
                            <span class="text-xs text-slate-500 font-medium">
                                <?php echo $round_data['date']; ?>
                            </span>
                        </div>
                        <div class="p-5">
                            <h3
                                class="text-xl font-bold mb-4 group-hover:text-sky-400 transition-colors flex items-center justify-between">
                                <?php echo htmlspecialchars($host); ?>
                            </h3>

                            <div class="space-y-2 mb-4">
                                <?php foreach ($teams as $team):
            $pts = getPoints($round_num, $team, $completed_points);
            $is_host = ($team === $host);
?>
                                <div
                                    class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                                    <div class="flex items-center gap-3 text-sm">
                                        <div
                                            class="w-1.5 h-1.5 rounded-full <?php echo $is_host ? 'bg-sky-500' : 'bg-slate-600'; ?>">
                                        </div>
                                        <span
                                            class="<?php echo $is_host ? 'text-white font-bold' : 'text-slate-400'; ?>">
                                            <?php echo htmlspecialchars($team); ?>
                                        </span>
                                        <?php if ($is_host): ?>
                                        <span
                                            class="text-[10px] bg-sky-500/20 text-sky-400 px-2 rounded-full font-black uppercase">Host</span>
                                        <?php
            endif; ?>
                                    </div>
                                    <?php if ($is_completed && $pts !== null): ?>
                                    <span
                                        class="text-sm font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded">
                                        <?php echo $pts; ?> pts
                                    </span>
                                    <?php
            endif; ?>
                                </div>
                                <?php
        endforeach; ?>
                            </div>

                            <!-- VENUE DETAILS -->
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <p
                                    class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-3 flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i> Venue Info
                                </p>

                                <?php if ($is_db_venue): ?>
                                <?php cotswold_render_venue_details($venue_info); ?>
                                <?php else: ?>
                                <!-- Fallback View (Raw Text) -->
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    <?php echo $display_details; ?>
                                </p>
                                <?php
        endif; ?>
                            </div>

                            <?php if ($live_info): ?>
                            <div class="mt-4 pt-4 border-t border-white/5">
                                <button onclick="toggleLive(<?php echo (int)$live_info['id']; ?>, <?php echo $round_num; ?>, <?php echo $index; ?>)"
                                    class="w-full py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-xs font-bold uppercase rounded-lg transition-all flex items-center justify-center gap-2 border border-red-500/20 hover:border-red-500/50">
                                    <i data-lucide="radio" class="w-4 h-4 animate-pulse"></i> <span>Live Results</span>
                                </button>
                            </div>
                            <?php
        endif; ?>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                </div>
                <?php
endforeach; ?>

                <!-- FINALS SECTION -->
                <?php
                $a_final_venue = $final_venue_db['a_final'] ?? [
                    'venue_name' => 'Hutton Moore Leisure Centre',
                    'address' => 'Weston-Super-Mare',
                    'start_time' => '5.45PM',
                    'warmup_time' => '6.15PM',
                    'payment_info' => '£5 adults, £3 for children',
                    'parking_info' => 'Free, must register with reception',
                    'other_info' => '',
                ];
                $b_final_venue = $final_venue_db['b_final'] ?? [
                    'venue_name' => 'Pontypool Leisure Centre',
                    'address' => 'Pontypool',
                    'start_time' => '4.45PM',
                    'warmup_time' => '5.15PM',
                    'payment_info' => '£5 adults, £3 for children',
                    'parking_info' => 'Free parking',
                    'other_info' => '',
                ];
                $c_final_venue = $final_venue_db['c_final'] ?? [
                    'venue_name' => 'Easton Leisure Centre',
                    'address' => 'Bristol',
                    'start_time' => '5.45PM',
                    'warmup_time' => '6.15PM',
                    'payment_info' => '£5 adults, £3 for children',
                    'parking_info' => 'Paid parking',
                    'other_info' => '',
                ];
                $finals_date_label = '';
                foreach ([$a_final_venue, $b_final_venue, $c_final_venue] as $final_venue_row) {
                    $candidate = trim((string)($final_venue_row['round_date'] ?? ''));
                    if ($candidate !== '') {
                        $finals_date_label = $candidate;
                        break;
                    }
                }
                if ($finals_date_label === '') {
                    $finals_date_label = trim((string)cotswold_get_finals_date_for_season($conn, $active_season_year));
                }
                if ($finals_date_label === '') {
                    $finals_date_label = $active_season_year . ' Finals';
                }
                ?>
                <div id="round-Finals" class="round-cards grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <!-- A FINAL -->
                    <div class="glass-panel rounded-2xl overflow-hidden border border-white/5 hover:border-sky-500/30 transition-all group border-sky-500/30 relative">
                        <div class="absolute inset-x-0 -top-px h-px bg-gradient-to-r from-transparent via-sky-500 to-transparent opacity-50"></div>
                        <div class="bg-sky-500/10 px-5 py-3 border-b border-white/5 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-tighter text-sky-400">A Final Location</span>
                            </div>
                            <span class="text-xs text-slate-500 font-medium"><?php echo htmlspecialchars($finals_date_label); ?></span>
                        </div>
                        <div class="p-5">
                            <h3 class="text-xl font-bold mb-4 group-hover:text-sky-400 transition-colors flex items-center justify-between">
                                <?php echo htmlspecialchars(cotswold_venue_value($a_final_venue, 'venue_name', 'A Final Venue')); ?>
                            </h3>
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-3 flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i> Venue Info
                                </p>
                                <?php cotswold_render_venue_details($a_final_venue); ?>
                                <div class="text-xs text-slate-300">
                                    <div class="mt-4 pt-4 border-t border-white/10">
                                        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-3">Qualified Teams</p>
                                        <ul class="space-y-1.5">
                                            <?php foreach ($a_final as $team_row): ?>
                                            <li class="flex items-center justify-between text-xs border-b border-white/5 pb-1 last:border-0">
                                                <span class="text-slate-300"><?php echo htmlspecialchars($team_row['name']); ?></span>
                                                <span class="font-bold text-emerald-400"><?php echo (int) $team_row['total']; ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- B FINAL (Pontypool) -->
                    <div class="glass-panel rounded-2xl overflow-hidden border border-white/5 hover:border-sky-500/30 transition-all group">
                        <div class="bg-sky-500/10 px-5 py-3 border-b border-white/5 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-tighter text-sky-400">B Final Location</span>
                            </div>
                            <span class="text-xs text-slate-500 font-medium"><?php echo htmlspecialchars($finals_date_label); ?></span>
                        </div>
                        <div class="p-5">
                            <h3 class="text-xl font-bold mb-4 group-hover:text-sky-400 transition-colors flex items-center justify-between">
                                <?php echo htmlspecialchars(cotswold_venue_value($b_final_venue, 'venue_name', 'B Final Venue')); ?>
                            </h3>
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-3 flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i> Venue Info
                                </p>
                                <?php cotswold_render_venue_details($b_final_venue); ?>
                                <div class="text-xs text-slate-300">
                                    <div class="mt-4 pt-4 border-t border-white/10">
                                        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-3">Qualified Teams</p>
                                        <ul class="space-y-1.5">
                                            <?php foreach ($b_final as $team_row): ?>
                                            <li class="flex items-center justify-between text-xs border-b border-white/5 pb-1 last:border-0">
                                                <span class="text-slate-300"><?php echo htmlspecialchars($team_row['name']); ?></span>
                                                <span class="font-bold text-amber-400"><?php echo (int) $team_row['total']; ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- C FINAL (Easton) -->
                    <div class="glass-panel rounded-2xl overflow-hidden border border-white/5 hover:border-sky-500/30 transition-all group">
                        <div class="bg-sky-500/10 px-5 py-3 border-b border-white/5 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-tighter text-sky-400">C Final Location</span>
                            </div>
                            <span class="text-xs text-slate-500 font-medium"><?php echo htmlspecialchars($finals_date_label); ?></span>
                        </div>
                        <div class="p-5">
                            <h3 class="text-xl font-bold mb-4 group-hover:text-sky-400 transition-colors flex items-center justify-between">
                                <?php echo htmlspecialchars(cotswold_venue_value($c_final_venue, 'venue_name', 'C Final Venue')); ?>
                            </h3>
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-3 flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i> Venue Info
                                </p>
                                <?php cotswold_render_venue_details($c_final_venue); ?>
                                <div class="text-xs text-slate-300">
                                    <div class="mt-4 pt-4 border-t border-white/10">
                                        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-bold mb-3">Qualified Teams</p>
                                        <ul class="space-y-1.5">
                                            <?php foreach ($c_final as $team_row): ?>
                                            <li class="flex items-center justify-between text-xs border-b border-white/5 pb-1 last:border-0">
                                                <span class="text-slate-300"><?php echo htmlspecialchars($team_row['name']); ?></span>
                                                <span class="font-bold text-rose-400"><?php echo (int) $team_row['total']; ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- LIVE RESULTS VIEWER (Full Width) -->
            <div id="liveViewer"
                class="hidden mt-8 glass-panel overflow-hidden rounded-2xl border border-sky-500/30 shadow-[0_0_50px_rgba(14,165,233,0.15)] transition-all duration-500">
            </div>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
            &copy; <?php echo $active_season_year; ?> The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();
        const drawData = <?php echo json_encode($season_draw); ?>;

        let currentActiveGala = null;
        let liveRefreshInterval = null;

        function filterDraw(roundNum) {
            const rounds = [1, 2, 3, 4, 'Finals'];
            for (let i of rounds) {
                const btn = document.getElementById(`btnR${i}`);
                const section = document.getElementById(`round-${i}`);
                if (!btn) continue;

                if (i === roundNum) {
                    btn.classList.add('bg-sky-600', 'text-white');
                    btn.classList.remove('text-slate-400');
                    if (section) section.classList.remove('hidden');
                } else {
                    btn.classList.remove('bg-sky-600', 'text-white');
                    btn.classList.add('text-slate-400');
                    if (section) section.classList.add('hidden');
                }
            }
            closeLive();
        }

        async function toggleLive(scoresheetId, roundNum, galaIndex) {
            const viewer = document.getElementById('liveViewer');
            const round = drawData.find(r => r.round === roundNum);
            const gala = round.galas[galaIndex];
            const galaId = `${roundNum}-${galaIndex}-${scoresheetId}`;

            if (currentActiveGala === galaId && !viewer.classList.contains('hidden')) {
                closeLive();
                return;
            }

            currentActiveGala = galaId;
            viewer.classList.remove('hidden');
            viewer.innerHTML = renderLiveShell(gala.host, roundNum, '<div class="p-6 text-slate-400">Loading live results...</div>');
            lucide.createIcons();

            await loadLiveScoresheet(scoresheetId, gala.host, roundNum);

            if (liveRefreshInterval) clearInterval(liveRefreshInterval);
            liveRefreshInterval = setInterval(() => loadLiveScoresheet(scoresheetId, gala.host, roundNum, false), 30000);

            setTimeout(() => {
                viewer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 50);
        }

        function renderLiveShell(host, roundNum, bodyHtml) {
            return `
                <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-white/5">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="bg-red-500/20 p-2 rounded-lg border border-red-500/30 shrink-0">
                            <i data-lucide="radio" class="w-6 h-6 text-red-500 animate-pulse"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xl font-bold text-white leading-none">Live Results</h3>
                            <p class="text-xs text-sky-400 font-bold uppercase tracking-wider mt-1 truncate">${host} Gala - Round ${roundNum}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 flex items-center gap-1"><i data-lucide="refresh-cw" class="w-3 h-3"></i> Auto-refreshing every 30s</p>
                        </div>
                    </div>
                    <button onclick="closeLive()" class="group bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white p-2 rounded-lg transition-all border border-slate-700 hover:border-slate-500 shrink-0">
                        <span class="sr-only">Close</span>
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div id="liveResultsBody">${bodyHtml}</div>
            `;
        }

        async function loadLiveScoresheet(scoresheetId, host, roundNum, showErrors = true) {
            try {
                const resp = await fetch(`gala_live_public_api.php?scoresheet_id=${scoresheetId}&t=${Date.now()}`);
                const data = await resp.json();
                if (data.error) throw new Error(data.error);

                const body = document.getElementById('liveResultsBody');
                if (body) body.innerHTML = renderLiveScoresheet(data);
                lucide.createIcons();
            } catch (err) {
                if (!showErrors) return;
                const body = document.getElementById('liveResultsBody');
                if (body) {
                    body.innerHTML = `<div class="p-6 text-red-300 bg-red-500/10 border-t border-red-500/20">${escapeHtml(err.message)}</div>`;
                }
            }
        }

        function renderLiveScoresheet(data) {
            const resultsMap = {};
            data.results.forEach(r => {
                resultsMap[`${r.event_id}_${r.club_id}`] = r;
            });

            const calc = GalaEngine.calculateFullScoresheet(data.events, data.teams, resultsMap);
            const activeTeams = data.teams.filter(t => !t.is_absent);
            const filledEvents = new Set();
            data.results.forEach(r => {
                if (r.time_ms !== null || r.is_dq) filledEvents.add(r.event_id);
            });

            const leaderboard = calc.leaderboard.map((team, idx) => `
                <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-900/70 border border-white/5 px-3 py-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-6 h-6 rounded-full bg-sky-500/15 text-sky-300 border border-sky-500/20 flex items-center justify-center text-xs font-black shrink-0">${idx + 1}</span>
                        <span class="text-sm font-bold text-white truncate">${escapeHtml(team.club_name)}</span>
                    </div>
                    <span class="text-lg font-black text-emerald-400">${team.total_points}</span>
                </div>
            `).join('');

            const rows = data.events.map(event => {
                const cells = activeTeams.map(team => {
                    const key = `${event.id}_${team.club_id}`;
                    const raw = resultsMap[key] || {};
                    const scored = calc.scored[key] || {};
                    let value = '-';
                    let meta = '';

                    if (raw.is_dq) {
                        value = 'DQ';
                        meta = '0 pts';
                    } else if (raw.time_ms !== null && raw.time_ms !== undefined) {
                        value = GalaEngine.formatTime(raw.time_ms);
                        meta = scored.status === 'too_fast' ? 'Too fast' : `${ordinalSuffix(scored.place)} - ${scored.points} pts`;
                    }

                    return `<td class="px-3 py-2 text-center border-l border-white/5">
                        <div class="font-bold ${raw.is_dq ? 'text-red-300' : 'text-white'}">${escapeHtml(value)}</div>
                        <div class="text-[10px] uppercase tracking-wide text-slate-500">${escapeHtml(meta)}</div>
                    </td>`;
                }).join('');

                return `<tr class="border-t border-white/5">
                    <td class="px-3 py-2 min-w-[220px]">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded bg-slate-950 border border-slate-700 text-slate-400 text-xs font-bold flex items-center justify-center shrink-0">${event.event_number}</span>
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-white truncate">${escapeHtml(event.event_name)}</div>
                                <div class="text-[10px] uppercase tracking-wide text-slate-500">${escapeHtml(event.distance)}</div>
                            </div>
                        </div>
                    </td>
                    ${cells}
                </tr>`;
            }).join('');

            const teamHeads = activeTeams.map(team => `
                <th class="px-3 py-3 text-center border-l border-white/5 min-w-[130px]">
                    <div class="text-[10px] text-sky-400 uppercase tracking-wider">Lane ${team.lane_number || '-'}</div>
                    <div class="text-xs text-white truncate">${escapeHtml(team.club_name)}</div>
                </th>
            `).join('');

            return `
                <div class="p-4 sm:p-6 bg-slate-950/40">
                    <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-5">
                        <aside>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-black uppercase tracking-wider text-slate-400">Current Standings</h4>
                                <span class="text-xs text-slate-500">${filledEvents.size}/${data.events.length} events</span>
                            </div>
                            <div class="space-y-2">${leaderboard}</div>
                            <p class="text-[10px] text-slate-500 mt-3">Last update: ${escapeHtml(data.scoresheet.updated_at || '')}</p>
                        </aside>
                        <div class="overflow-auto rounded-xl border border-white/5 bg-slate-950/50">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-900 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-3 py-3 text-xs uppercase tracking-wider text-slate-400 min-w-[220px]">Event</th>
                                        ${teamHeads}
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        function ordinalSuffix(i) {
            if (!i) return '-';
            const j = i % 10, k = i % 100;
            if (j === 1 && k !== 11) return i + 'st';
            if (j === 2 && k !== 12) return i + 'nd';
            if (j === 3 && k !== 13) return i + 'rd';
            return i + 'th';
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
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
