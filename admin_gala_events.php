<?php
/**
 * admin_gala_events.php
 * Included within league_admin.php "Event Management" tab.
 * Provides full inline editing of gala events via a slide-out modal.
 */
$season = 2027;

// Fetch all events
$e_sql = "SELECT * FROM gala_events WHERE season_year = ? ORDER BY event_number ASC";
$e_stmt = $conn->prepare($e_sql);
$e_stmt->bind_param("i", $season);
$e_stmt->execute();
$events_res = $e_stmt->get_result();
$all_events = [];
while ($row = $events_res->fetch_assoc()) {
    $all_events[] = $row;
}
$e_stmt->close();

function formatMsToTime($ms) {
    if (!$ms) return '';
    $totalSec = floor($ms / 1000);
    $frac = floor(($ms % 1000) / 10);
    $minutes = floor($totalSec / 60);
    $seconds = $totalSec % 60;
    if ($minutes > 0) {
        return sprintf("%d:%02d.%02d", $minutes, $seconds, $frac);
    }
    return sprintf("%d.%02d", $seconds, $frac);
}

// Encode events for JS
$events_json = json_encode(array_map(function($e) {
    return [
        'id' => (int)$e['id'],
        'event_number' => (int)$e['event_number'],
        'event_name' => $e['event_name'],
        'distance' => $e['distance'],
        'age_group' => $e['age_group'],
        'gender' => $e['gender'],
        'event_type' => $e['event_type'],
        'cut_off_time_ms' => (int)$e['cut_off_time_ms'],
        'a_final_event_name' => $e['a_final_event_name'],
        'a_final_distance' => $e['a_final_distance'],
        'a_final_cut_off_time_ms' => $e['a_final_cut_off_time_ms'] ? (int)$e['a_final_cut_off_time_ms'] : null,
    ];
}, $all_events));
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold flex items-center gap-2 text-white">
        <i data-lucide="calendar" class="w-5 h-5 text-emerald-400"></i> Event Management
    </h2>
    <div class="flex gap-3 items-center">
        <span class="text-slate-400 text-sm font-bold bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700">Season <?php echo $season; ?></span>
        <span class="text-slate-500 text-xs"><?php echo count($all_events); ?> events</span>
        <button type="button" onclick="openAddEvent()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm flex items-center gap-2 shadow-lg shadow-emerald-900/20">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Event
        </button>
        <button type="button" onclick="document.getElementById('duplicate-season-modal').classList.remove('hidden'); lucide.createIcons();" class="bg-slate-800 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded-lg transition-colors border border-slate-700 text-sm flex items-center gap-2">
            <i data-lucide="copy" class="w-4 h-4"></i> Duplicate Season
        </button>
    </div>
</div>

<div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300" id="events-table">
            <thead class="bg-slate-900/80 text-slate-400 text-xs uppercase font-bold tracking-wider border-b border-white/5">
                <tr>
                    <th class="px-4 py-3 w-16 text-center">#</th>
                    <th class="px-4 py-3">Event Name</th>
                    <th class="px-4 py-3 w-20">Age</th>
                    <th class="px-4 py-3 w-20">Gender</th>
                    <th class="px-4 py-3 w-24">Dist.</th>
                    <th class="px-4 py-3 w-20">Type</th>
                    <th class="px-4 py-3 w-32">Cut-Off</th>
                    <th class="px-4 py-3 border-l border-white/5 bg-sky-900/10">A Final Override</th>
                    <th class="px-4 py-3 w-20 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($all_events as $e): ?>
                    <tr class="hover:bg-slate-800/50 transition-colors group" id="event-row-<?php echo $e['id']; ?>">
                        <td class="px-4 py-3 text-center font-bold text-slate-500"><?php echo $e['event_number']; ?></td>
                        <td class="px-4 py-3 font-medium text-white"><?php echo htmlspecialchars($e['event_name']); ?></td>
                        <td class="px-4 py-3 text-xs text-slate-400"><?php echo htmlspecialchars($e['age_group']); ?></td>
                        <td class="px-4 py-3 text-xs text-slate-400"><?php echo htmlspecialchars($e['gender']); ?></td>
                        <td class="px-4 py-3">
                            <span class="bg-slate-800 px-2 py-0.5 rounded text-xs border border-slate-700"><?php echo htmlspecialchars($e['distance']); ?></span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400"><?php echo htmlspecialchars($e['event_type']); ?></td>
                        <td class="px-4 py-3 text-red-400 font-mono">
                            <?php echo formatMsToTime($e['cut_off_time_ms']); ?>
                        </td>
                        <td class="px-4 py-3 border-l border-white/5 bg-sky-900/5">
                            <?php if ($e['a_final_event_name']): ?>
                                <div class="text-xs">
                                    <div class="text-sky-400 font-bold mb-1"><?php echo htmlspecialchars($e['a_final_event_name']); ?></div>
                                    <span class="bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700 mr-2"><?php echo htmlspecialchars($e['a_final_distance']); ?></span>
                                    <span class="text-red-400 font-mono"><?php echo formatMsToTime($e['a_final_cut_off_time_ms']); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-600 text-xs italic">- None -</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button class="text-slate-500 hover:text-sky-400 transition-colors p-1.5 rounded-lg hover:bg-sky-500/10" onclick="openEventEditor(<?php echo $e['id']; ?>)" title="Edit Event">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button class="text-slate-500 hover:text-red-400 transition-colors p-1.5 rounded-lg hover:bg-red-500/10" onclick="confirmDeleteEvent(<?php echo $e['id']; ?>, <?php echo $e['event_number']; ?>, '<?php echo addslashes($e['event_name']); ?>')" title="Delete Event">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- EVENT EDITOR MODAL -->
<div id="event-editor-modal" class="fixed inset-0 bg-slate-950/80 backdrop-filter backdrop-blur-sm z-[100] hidden flex items-start justify-end">
    <!-- Backdrop click to close -->
    <div class="absolute inset-0" onclick="closeEventEditor()"></div>
    
    <!-- Slide-in Panel -->
    <div id="event-editor-panel" class="relative w-full max-w-xl h-full bg-slate-900 border-l border-white/10 shadow-2xl overflow-y-auto transform translate-x-full transition-transform duration-300 ease-out">
        <form method="POST" id="event-edit-form">
            <input type="hidden" name="admin_action" value="update_event">
            <input type="hidden" name="event_id" id="ee-event-id">
            <input type="hidden" name="event_number" id="ee-event-number-hidden">

            <!-- Header -->
            <div class="sticky top-0 bg-slate-900/95 backdrop-blur-sm border-b border-white/5 p-6 flex items-center justify-between z-10">
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i data-lucide="edit-2" class="w-5 h-5 text-sky-400"></i> Edit Event
                    </h3>
                    <p class="text-xs text-slate-500 mt-1" id="ee-subtitle">Event #1</p>
                </div>
                <button type="button" onclick="closeEventEditor()" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- Core Details -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> Core Details
                    </h4>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1.5">Event Name</label>
                        <input type="text" name="event_name" id="ee-event-name" required
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Distance</label>
                            <input type="text" name="distance" id="ee-distance" required placeholder="e.g. 50m, 100m, 200m"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Age Group</label>
                            <input type="text" name="age_group" id="ee-age-group" required placeholder="e.g. 11/u, 13/u, Open"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Gender</label>
                            <select name="gender" id="ee-gender" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-sky-500 transition-all text-sm appearance-none cursor-pointer">
                                <option value="Girls">Girls</option>
                                <option value="Boys">Boys</option>
                                <option value="Mixed">Mixed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Event Type</label>
                            <select name="event_type" id="ee-event-type" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-sky-500 transition-all text-sm appearance-none cursor-pointer">
                                <option value="Individual">Individual</option>
                                <option value="Relay">Relay</option>
                                <option value="Cannon">Cannon</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1.5">Cut-Off Time <span class="text-slate-600 font-normal">(MM:SS.xx or SS.xx)</span></label>
                        <input type="text" name="cut_off_time" id="ee-cut-off" required placeholder="e.g. 1:17.75 or 41.70"
                            class="w-full bg-slate-950 border border-red-500/30 rounded-xl py-2.5 px-4 text-red-400 font-mono focus:outline-none focus:border-red-500 transition-all placeholder-slate-600 text-sm tracking-wider">
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-white/5"></div>

                <!-- A Final Override -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-sky-400 uppercase tracking-widest flex items-center gap-2">
                            <i data-lucide="trophy" class="w-3.5 h-3.5"></i> A Final Override
                        </h4>
                        <div class="flex items-center gap-2">
                            <label class="text-[10px] text-slate-500 uppercase font-bold">Enable</label>
                            <input type="checkbox" id="ee-a-final-toggle" onchange="toggleAFinalFields()" class="w-4 h-4 rounded accent-sky-500 cursor-pointer">
                        </div>
                    </div>
                    
                    <p class="text-xs text-slate-500 leading-relaxed">If this event changes for the A Final (e.g. 11/u events switching from 25m to 50m), set the override values below.</p>

                    <div id="ee-a-final-fields" class="space-y-4 opacity-30 pointer-events-none transition-opacity">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">A Final Event Name</label>
                            <input type="text" name="a_final_event_name" id="ee-af-event-name" placeholder="e.g. Girls 11/u 50m Freestyle"
                                class="w-full bg-slate-950 border border-sky-500/30 rounded-xl py-2.5 px-4 text-sky-400 focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1.5">A Final Distance</label>
                                <input type="text" name="a_final_distance" id="ee-af-distance" placeholder="e.g. 50m"
                                    class="w-full bg-slate-950 border border-sky-500/30 rounded-xl py-2.5 px-4 text-sky-400 focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1.5">A Final Cut-Off</label>
                                <input type="text" name="a_final_cut_off_time" id="ee-af-cut-off" placeholder="e.g. 32.29"
                                    class="w-full bg-slate-950 border border-sky-500/30 rounded-xl py-2.5 px-4 text-red-400 font-mono focus:outline-none focus:border-red-500 transition-all placeholder-slate-600 text-sm tracking-wider">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer (Sticky) -->
            <div class="sticky bottom-0 bg-slate-900/95 backdrop-blur-sm border-t border-white/5 p-6 flex gap-3">
                <button type="button" onclick="closeEventEditor()" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-3 rounded-xl transition-colors text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-sky-900/30 text-sm flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE EVENT FORM (hidden) -->
<form method="POST" id="delete-event-form" class="hidden">
    <input type="hidden" name="admin_action" value="delete_event">
    <input type="hidden" name="event_id" id="del-event-id">
</form>

<!-- DELETE CONFIRM MODAL -->
<div id="delete-event-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[110] hidden flex items-center justify-center p-4">
    <div class="glass-panel border border-red-500/30 p-6 rounded-2xl max-w-sm w-full">
        <h3 class="text-lg font-bold text-red-400 mb-2 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i> Delete Event
        </h3>
        <p class="text-sm text-slate-300 mb-1">Are you sure you want to delete:</p>
        <p class="text-white font-bold mb-4" id="del-event-label">Event #1 — Girls 15/u 4x1 Ind. Medley</p>
        <p class="text-xs text-slate-500 mb-6">This cannot be undone. Events with recorded results cannot be deleted.</p>
        <div class="flex gap-3">
            <button type="button" onclick="document.getElementById('delete-event-modal').classList.add('hidden')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-lg transition-colors text-sm">Cancel</button>
            <button type="button" onclick="submitDeleteEvent()" class="flex-1 bg-red-600 hover:bg-red-500 text-white font-bold py-2.5 rounded-lg transition-colors text-sm flex items-center justify-center gap-2">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
            </button>
        </div>
    </div>
</div>

<!-- ADD EVENT MODAL -->
<div id="add-event-modal" class="fixed inset-0 bg-slate-950/80 backdrop-filter backdrop-blur-sm z-[100] hidden flex items-start justify-end">
    <div class="absolute inset-0" onclick="closeAddEvent()"></div>
    <div id="add-event-panel" class="relative w-full max-w-xl h-full bg-slate-900 border-l border-white/10 shadow-2xl overflow-y-auto transform translate-x-full transition-transform duration-300 ease-out">
        <form method="POST" id="add-event-form">
            <input type="hidden" name="admin_action" value="add_event">

            <div class="sticky top-0 bg-slate-900/95 backdrop-blur-sm border-b border-white/5 p-6 flex items-center justify-between z-10">
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-emerald-400"></i> Add New Event
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Season 2027</p>
                </div>
                <button type="button" onclick="closeAddEvent()" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> Core Details
                    </h4>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1.5">Event Number <span class="text-red-400">*</span></label>
                        <input type="number" name="event_number" required min="1" max="99" placeholder="e.g. 54"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1.5">Event Name <span class="text-red-400">*</span></label>
                        <input type="text" name="event_name" required placeholder="e.g. Girls Open 200m Freestyle"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Distance <span class="text-red-400">*</span></label>
                            <input type="text" name="distance" required placeholder="e.g. 50m"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Age Group <span class="text-red-400">*</span></label>
                            <input type="text" name="age_group" required placeholder="e.g. 11/u, 13/u, Open"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Gender</label>
                            <select name="gender" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-emerald-500 transition-all text-sm appearance-none cursor-pointer">
                                <option value="Girls">Girls</option>
                                <option value="Boys">Boys</option>
                                <option value="Mixed">Mixed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">Event Type</label>
                            <select name="event_type" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-emerald-500 transition-all text-sm appearance-none cursor-pointer">
                                <option value="Individual">Individual</option>
                                <option value="Relay">Relay</option>
                                <option value="Cannon">Cannon</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1.5">Cut-Off Time <span class="text-red-400">*</span> <span class="text-slate-600 font-normal">(MM:SS.xx or SS.xx)</span></label>
                        <input type="text" name="cut_off_time" required placeholder="e.g. 1:17.75 or 41.70"
                            class="w-full bg-slate-950 border border-red-500/30 rounded-xl py-2.5 px-4 text-red-400 font-mono focus:outline-none focus:border-red-500 transition-all placeholder-slate-600 text-sm tracking-wider">
                    </div>
                </div>

                <div class="border-t border-white/5"></div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-sky-400 uppercase tracking-widest flex items-center gap-2">
                            <i data-lucide="trophy" class="w-3.5 h-3.5"></i> A Final Override
                        </h4>
                        <div class="flex items-center gap-2">
                            <label class="text-[10px] text-slate-500 uppercase font-bold">Enable</label>
                            <input type="checkbox" id="add-a-final-toggle" onchange="toggleAddAFinalFields()" class="w-4 h-4 rounded accent-sky-500 cursor-pointer">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Optional: set A Final override values if this event changes for the A Final.</p>
                    <div id="add-a-final-fields" class="space-y-4 opacity-30 pointer-events-none transition-opacity">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1.5">A Final Event Name</label>
                            <input type="text" name="a_final_event_name" placeholder="e.g. Girls 11/u 50m Freestyle"
                                class="w-full bg-slate-950 border border-sky-500/30 rounded-xl py-2.5 px-4 text-sky-400 focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1.5">A Final Distance</label>
                                <input type="text" name="a_final_distance" placeholder="e.g. 50m"
                                    class="w-full bg-slate-950 border border-sky-500/30 rounded-xl py-2.5 px-4 text-sky-400 focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1.5">A Final Cut-Off</label>
                                <input type="text" name="a_final_cut_off_time" placeholder="e.g. 32.29"
                                    class="w-full bg-slate-950 border border-sky-500/30 rounded-xl py-2.5 px-4 text-red-400 font-mono focus:outline-none focus:border-red-500 transition-all placeholder-slate-600 text-sm tracking-wider">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 bg-slate-900/95 backdrop-blur-sm border-t border-white/5 p-6 flex gap-3">
                <button type="button" onclick="closeAddEvent()" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-3 rounded-xl transition-colors text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-emerald-900/30 text-sm flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Event
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Event data from PHP
    const eventsData = <?php echo $events_json; ?>;

    function formatMsForInput(ms) {
        if (!ms) return '';
        const totalSec = Math.floor(ms / 1000);
        const frac = Math.floor((ms % 1000) / 10);
        const minutes = Math.floor(totalSec / 60);
        const seconds = totalSec % 60;
        if (minutes > 0) {
            return `${minutes}:${seconds.toString().padStart(2, '0')}.${frac.toString().padStart(2, '0')}`;
        }
        return `${seconds}.${frac.toString().padStart(2, '0')}`;
    }

    // ===== EDIT EVENT =====
    function openEventEditor(eventId) {
        const ev = eventsData.find(e => e.id === eventId);
        if (!ev) return;

        document.getElementById('ee-event-id').value = ev.id;
        document.getElementById('ee-event-number-hidden').value = ev.event_number;
        document.getElementById('ee-subtitle').innerText = `Event #${ev.event_number} — ${ev.event_name}`;
        document.getElementById('ee-event-name').value = ev.event_name;
        document.getElementById('ee-distance').value = ev.distance;
        document.getElementById('ee-age-group').value = ev.age_group;
        document.getElementById('ee-gender').value = ev.gender;
        document.getElementById('ee-event-type').value = ev.event_type;
        document.getElementById('ee-cut-off').value = formatMsForInput(ev.cut_off_time_ms);

        const hasAFinal = !!ev.a_final_event_name;
        document.getElementById('ee-a-final-toggle').checked = hasAFinal;
        document.getElementById('ee-af-event-name').value = ev.a_final_event_name || '';
        document.getElementById('ee-af-distance').value = ev.a_final_distance || '';
        document.getElementById('ee-af-cut-off').value = formatMsForInput(ev.a_final_cut_off_time_ms);
        toggleAFinalFields();

        document.querySelectorAll('#events-table tbody tr').forEach(r => r.classList.remove('bg-sky-900/20'));
        const row = document.getElementById(`event-row-${eventId}`);
        if (row) row.classList.add('bg-sky-900/20');

        openSlidePanel('event-editor-modal', 'event-editor-panel');
    }

    function closeEventEditor() {
        closeSlidePanel('event-editor-modal', 'event-editor-panel');
        document.querySelectorAll('#events-table tbody tr').forEach(r => r.classList.remove('bg-sky-900/20'));
    }

    function toggleAFinalFields() {
        const enabled = document.getElementById('ee-a-final-toggle').checked;
        const container = document.getElementById('ee-a-final-fields');
        toggleFieldset(container, enabled);
        if (!enabled) {
            document.getElementById('ee-af-event-name').value = '';
            document.getElementById('ee-af-distance').value = '';
            document.getElementById('ee-af-cut-off').value = '';
        }
    }

    // ===== ADD EVENT =====
    function openAddEvent() {
        document.getElementById('add-event-form').reset();
        document.getElementById('add-a-final-toggle').checked = false;
        toggleAddAFinalFields();
        openSlidePanel('add-event-modal', 'add-event-panel');
    }

    function closeAddEvent() {
        closeSlidePanel('add-event-modal', 'add-event-panel');
    }

    function toggleAddAFinalFields() {
        const enabled = document.getElementById('add-a-final-toggle').checked;
        const container = document.getElementById('add-a-final-fields');
        toggleFieldset(container, enabled);
    }

    // ===== DELETE EVENT =====
    function confirmDeleteEvent(eventId, eventNumber, eventName) {
        document.getElementById('del-event-id').value = eventId;
        document.getElementById('del-event-label').innerText = `Event #${eventNumber} — ${eventName}`;
        document.getElementById('delete-event-modal').classList.remove('hidden');
        lucide.createIcons();
    }

    function submitDeleteEvent() {
        document.getElementById('delete-event-form').submit();
    }

    // ===== SHARED HELPERS =====
    function openSlidePanel(modalId, panelId) {
        const modal = document.getElementById(modalId);
        const panel = document.getElementById(panelId);
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            panel.classList.remove('translate-x-full');
            panel.classList.add('translate-x-0');
        });
        lucide.createIcons();
    }

    function closeSlidePanel(modalId, panelId) {
        const panel = document.getElementById(panelId);
        panel.classList.remove('translate-x-0');
        panel.classList.add('translate-x-full');
        setTimeout(() => {
            document.getElementById(modalId).classList.add('hidden');
        }, 300);
    }

    function toggleFieldset(container, enabled) {
        if (enabled) {
            container.classList.remove('opacity-30', 'pointer-events-none');
            container.classList.add('opacity-100');
        } else {
            container.classList.add('opacity-30', 'pointer-events-none');
            container.classList.remove('opacity-100');
        }
    }

    // Close modals on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (!document.getElementById('duplicate-season-modal').classList.contains('hidden')) {
                document.getElementById('duplicate-season-modal').classList.add('hidden');
            } else if (!document.getElementById('delete-event-modal').classList.contains('hidden')) {
                document.getElementById('delete-event-modal').classList.add('hidden');
            } else if (!document.getElementById('event-editor-modal').classList.contains('hidden')) {
                closeEventEditor();
            } else if (!document.getElementById('add-event-modal').classList.contains('hidden')) {
                closeAddEvent();
            }
        }
    });
</script>

<!-- DUPLICATE SEASON MODAL -->
<div id="duplicate-season-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[110] hidden flex items-center justify-center p-4">
    <div class="glass-panel border border-white/10 p-6 rounded-2xl max-w-sm w-full">
        <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
            <i data-lucide="copy" class="w-5 h-5 text-sky-400"></i> Duplicate Season
        </h3>
        <p class="text-sm text-slate-400 mb-6">Copy all <?php echo count($all_events); ?> events from <strong class="text-white">Season <?php echo $season; ?></strong> to a new season.</p>
        
        <form method="POST">
            <input type="hidden" name="admin_action" value="duplicate_season">
            <input type="hidden" name="source_year" value="<?php echo $season; ?>">
            
            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-400 mb-1.5">Target Season Year</label>
                <input type="number" name="target_year" required min="2025" max="2040" value="<?php echo $season + 1; ?>"
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2.5 px-4 text-white focus:outline-none focus:border-sky-500 transition-all text-sm text-center text-lg font-bold tracking-wider">
            </div>
            
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 mb-6">
                <p class="text-xs text-amber-400 flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <span>This will not affect the current season. You can edit the new season's events independently after duplicating.</span>
                </p>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('duplicate-season-modal').classList.add('hidden')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-lg transition-colors text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-sky-600 hover:bg-sky-500 text-white font-bold py-2.5 rounded-lg transition-colors text-sm flex items-center justify-center gap-2">
                    <i data-lucide="copy" class="w-4 h-4"></i> Duplicate
                </button>
            </div>
        </form>
    </div>
</div>
