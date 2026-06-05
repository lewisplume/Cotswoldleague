/**
 * Gala Scoresheet Engine
 * =====================
 * Core scoring algorithm + time utilities + IndexedDB persistence + sync manager.
 * This mirrors the Excel formula logic exactly.
 */

const GalaEngine = (() => {
    // =========================================================
    // TIME UTILITIES
    // =========================================================

    /**
     * Parse a time string (MM:SS.ms or SS.ms) into milliseconds.
     * Accepts: "1:17.75", "77.75", "01:17.75", "1:17.7", "77", "DQ"
     * Returns: integer ms or null if invalid, or 'DQ' string
     */
    function parseTime(input) {
        if (!input || typeof input !== 'string') return null;
        const trimmed = input.trim().toUpperCase();
        if (trimmed === '' ) return null;
        if (trimmed === 'DQ' || trimmed === 'D.Q' || trimmed === 'D.Q.') return 'DQ';

        let minutes = 0, seconds = 0, ms = 0;

        // Format: M:SS.xx or MM:SS.xx
        const colonMatch = trimmed.match(/^(\d{1,2}):(\d{1,2})\.?(\d{0,3})?$/);
        if (colonMatch) {
            minutes = parseInt(colonMatch[1], 10);
            seconds = parseInt(colonMatch[2], 10);
            const frac = colonMatch[3] || '0';
            ms = parseInt(frac.padEnd(3, '0').substring(0, 3), 10);
            return (minutes * 60 + seconds) * 1000 + ms;
        }

        // Format: SS.xx (no colon, seconds only)
        const secMatch = trimmed.match(/^(\d{1,4})\.?(\d{0,3})?$/);
        if (secMatch) {
            seconds = parseInt(secMatch[1], 10);
            const frac = secMatch[2] || '0';
            ms = parseInt(frac.padEnd(3, '0').substring(0, 3), 10);
            // If seconds >= 60, treat as total seconds
            if (seconds >= 60) {
                minutes = Math.floor(seconds / 60);
                seconds = seconds % 60;
            }
            return (minutes * 60 + seconds) * 1000 + ms;
        }

        return null; // Unparseable
    }

    /**
     * Format milliseconds into display string MM:SS.xx
     */
    function formatTime(ms) {
        if (ms === null || ms === undefined) return '';
        if (ms === 0) return '0.00';
        const totalSec = Math.floor(ms / 1000);
        const frac = Math.floor((ms % 1000) / 10); // centiseconds
        const minutes = Math.floor(totalSec / 60);
        const seconds = totalSec % 60;
        if (minutes > 0) {
            return `${minutes}:${seconds.toString().padStart(2, '0')}.${frac.toString().padStart(2, '0')}`;
        }
        return `${seconds}.${frac.toString().padStart(2, '0')}`;
    }

    /**
     * Format ms for input field (always show full precision)
     */
    function formatTimeForInput(ms) {
        if (ms === null || ms === undefined) return '';
        const totalSec = Math.floor(ms / 1000);
        const frac = Math.floor((ms % 1000) / 10);
        const minutes = Math.floor(totalSec / 60);
        const seconds = totalSec % 60;
        if (minutes > 0) {
            return `${minutes}:${seconds.toString().padStart(2, '0')}.${frac.toString().padStart(2, '0')}`;
        }
        return `${seconds}.${frac.toString().padStart(2, '0')}`;
    }

    // =========================================================
    // SCORING ENGINE (mirrors Excel formula exactly)
    // =========================================================

    /**
     * Calculate points and places for a single event across all teams.
     *
     * @param {Array} entries - [{club_id, time_ms, is_dq}] for each team
     * @param {number} cutOffMs - The event's cut-off time in ms
     * @returns {Array} [{club_id, points, place, status}]
     *
     * Algorithm (from Excel):
     * 1. DQ → 0 points, status='dq'
     * 2. time < cutOff → 0 points, status='too_fast'
     * 3. Valid time → points = (DQ count) + (TooFast count) + (count of teams with time >= this time)
     * 4. Place = rank by valid time, with dead heats sharing the same place
     */
    function calculateEventScores(entries, cutOffMs) {
        const teamCount = entries.length;
        if (teamCount === 0) return [];

        // Step 1: Classify each entry
        const classified = entries.map(e => {
            if (e.is_dq || e.time_ms === null || e.time_ms === undefined) {
                // DQ or no time entered
                if (e.is_dq) {
                    return { ...e, points: 0, place: null, status: 'dq' };
                }
                return { ...e, points: 0, place: null, status: 'pending' };
            }
            if (e.time_ms < cutOffMs) {
                return { ...e, points: 0, place: null, status: 'too_fast' };
            }
            return { ...e, points: 0, place: null, status: 'valid' };
        });

        const dqCount = classified.filter(c => c.status === 'dq').length;
        const tooFastCount = classified.filter(c => c.status === 'too_fast').length;
        const validEntries = classified.filter(c => c.status === 'valid');

        // Step 2: For each valid entry, calculate points
        // Points = dqCount + tooFastCount + (number of valid entries with time >= this entry's time)
        validEntries.forEach(entry => {
            const sameOrSlower = validEntries.filter(other => other.time_ms >= entry.time_ms).length;
            entry.points = dqCount + tooFastCount + sameOrSlower;
        });

        // Step 3: Assign places by time, with ties sharing the same place.
        // Example: 1st, 2nd, 2nd, 4th.
        const sorted = [...validEntries].sort((a, b) => a.time_ms - b.time_ms);
        sorted.forEach((entry, idx) => {
            if (idx > 0 && entry.time_ms === sorted[idx - 1].time_ms) {
                entry.place = sorted[idx - 1].place;
            } else {
                entry.place = idx + 1;
            }
        });

        return classified;
    }

    /**
     * Calculate full scoresheet - all events, all teams.
     * Returns an enriched results object with points, places, totals.
     *
     * @param {Array} events - [{id, event_number, cut_off_time_ms, ...}]
     * @param {Array} teams - [{club_id, is_absent, ...}]
     * @param {Object} results - Map of "eventId_clubId" → {time_ms, is_dq, ...}
     * @returns {Object} {
     *   scored: { "eventId_clubId": {points, place, status} },
     *   totals: { clubId: {total_points, firsts, seconds, thirds, fourths, dqs, too_fasts} },
     *   leaderboard: [{club_id, total_points, firsts, ...}] sorted by total desc,
     *   checkpoints: { 10: {clubId: runningTotal}, 20: {...}, 30: {...}, 40: {...} }
     * }
     */
    function calculateFullScoresheet(events, teams, results) {
        const scored = {};
        const totals = {};
        const checkpoints = { 10: {}, 20: {}, 30: {}, 40: {} };

        // Initialize totals for each team
        teams.forEach(t => {
            totals[t.club_id] = {
                club_id: t.club_id,
                total_points: 0,
                firsts: 0, seconds: 0, thirds: 0, fourths: 0,
                dqs: 0, too_fasts: 0,
                club_name: t.club_name || '',
                logo: t.logo || ''
            };
            Object.keys(checkpoints).forEach(cp => {
                checkpoints[cp][t.club_id] = 0;
            });
        });

        // Process each event
        events.forEach(event => {
            // Gather entries for this event
            const entries = teams
                .filter(t => !t.is_absent)
                .map(t => {
                    const key = `${event.id}_${t.club_id}`;
                    const r = results[key] || {};
                    return {
                        club_id: t.club_id,
                        time_ms: r.time_ms !== undefined ? r.time_ms : null,
                        is_dq: r.is_dq || false,
                    };
                });

            const eventScores = calculateEventScores(entries, event.cut_off_time_ms);

            // Store scored results
            eventScores.forEach(s => {
                const key = `${event.id}_${s.club_id}`;
                scored[key] = {
                    points: s.points,
                    place: s.place,
                    status: s.status,
                };

                // Accumulate totals
                if (totals[s.club_id]) {
                    totals[s.club_id].total_points += s.points;
                    if (s.place === 1) totals[s.club_id].firsts++;
                    if (s.place === 2) totals[s.club_id].seconds++;
                    if (s.place === 3) totals[s.club_id].thirds++;
                    if (s.place === 4) totals[s.club_id].fourths++;
                    if (s.status === 'dq') totals[s.club_id].dqs++;
                    if (s.status === 'too_fast') totals[s.club_id].too_fasts++;
                }
            });

            // Update checkpoint running totals
            const evNum = event.event_number;
            Object.keys(checkpoints).forEach(cp => {
                if (evNum <= parseInt(cp)) {
                    eventScores.forEach(s => {
                        if (checkpoints[cp][s.club_id] !== undefined) {
                            checkpoints[cp][s.club_id] += s.points;
                        }
                    });
                }
            });
        });

        // Build leaderboard (sorted by total_points desc, then firsts desc)
        const leaderboard = Object.values(totals).sort((a, b) => {
            if (b.total_points !== a.total_points) return b.total_points - a.total_points;
            if (b.firsts !== a.firsts) return b.firsts - a.firsts;
            if (b.seconds !== a.seconds) return b.seconds - a.seconds;
            return b.thirds - a.thirds;
        });

        return { scored, totals, leaderboard, checkpoints };
    }

    // =========================================================
    // INDEXEDDB PERSISTENCE
    // =========================================================

    const DB_NAME = 'GalaScoresheets';
    const DB_VERSION = 1;
    let db = null;

    function openDB() {
        return new Promise((resolve, reject) => {
            if (db) return resolve(db);
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            request.onupgradeneeded = (e) => {
                const d = e.target.result;
                if (!d.objectStoreNames.contains('scoresheets')) {
                    d.createObjectStore('scoresheets', { keyPath: 'id' });
                }
                if (!d.objectStoreNames.contains('pendingSync')) {
                    const store = d.createObjectStore('pendingSync', { keyPath: 'id', autoIncrement: true });
                    store.createIndex('scoresheet_id', 'scoresheet_id', { unique: false });
                }
            };
            request.onsuccess = (e) => {
                db = e.target.result;
                resolve(db);
            };
            request.onerror = (e) => reject(e.target.error);
        });
    }

    async function saveToLocal(scoresheetId, data) {
        const d = await openDB();
        return new Promise((resolve, reject) => {
            const tx = d.transaction('scoresheets', 'readwrite');
            tx.objectStore('scoresheets').put({ id: scoresheetId, data, updatedAt: Date.now() });
            tx.oncomplete = () => resolve();
            tx.onerror = (e) => reject(e.target.error);
        });
    }

    async function loadFromLocal(scoresheetId) {
        const d = await openDB();
        return new Promise((resolve, reject) => {
            const tx = d.transaction('scoresheets', 'readonly');
            const req = tx.objectStore('scoresheets').get(scoresheetId);
            req.onsuccess = () => resolve(req.result?.data || null);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async function queueForSync(scoresheetId, resultData) {
        const d = await openDB();
        return new Promise((resolve, reject) => {
            const tx = d.transaction('pendingSync', 'readwrite');
            tx.objectStore('pendingSync').put({
                scoresheet_id: scoresheetId,
                result: resultData,
                timestamp: Date.now()
            });
            tx.oncomplete = () => resolve();
            tx.onerror = (e) => reject(e.target.error);
        });
    }

    async function getPendingSync(scoresheetId) {
        const d = await openDB();
        return new Promise((resolve, reject) => {
            const tx = d.transaction('pendingSync', 'readonly');
            const idx = tx.objectStore('pendingSync').index('scoresheet_id');
            const req = idx.getAll(scoresheetId);
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async function clearSynced(ids) {
        const d = await openDB();
        return new Promise((resolve, reject) => {
            const tx = d.transaction('pendingSync', 'readwrite');
            const store = tx.objectStore('pendingSync');
            ids.forEach(id => store.delete(id));
            tx.oncomplete = () => resolve();
            tx.onerror = (e) => reject(e.target.error);
        });
    }

    // =========================================================
    // SYNC MANAGER
    // =========================================================

    let syncInProgress = false;

    async function syncToServer(scoresheetId) {
        if (syncInProgress || !navigator.onLine) return;
        syncInProgress = true;

        try {
            const pending = await getPendingSync(scoresheetId);
            if (pending.length === 0) { syncInProgress = false; return; }

            const results = pending.map(p => p.result);
            const formData = new FormData();
            formData.append('action', 'save_batch');
            formData.append('scoresheet_id', scoresheetId);
            formData.append('results', JSON.stringify(results));

            const resp = await fetch('gala_scoresheet_api.php', { method: 'POST', body: formData });
            if (resp.ok) {
                const data = await resp.json();
                if (data.success) {
                    await clearSynced(pending.map(p => p.id));
                    console.log(`Synced ${data.saved} results to server`);
                }
            }
        } catch (err) {
            console.warn('Sync failed, will retry:', err);
        } finally {
            syncInProgress = false;
        }
    }

    // =========================================================
    // EXPORT UTILITIES
    // =========================================================

    function exportToCSV(appState) {
        try {
            if (!appState || !appState.events || !appState.teams) {
                throw new Error("AppState not fully initialized.");
            }

            const rows = [
                ['Event #', 'Event Name', 'Age Group', 'Gender', 'Team', 'Lane', 'Time', 'DQ', 'Points', 'Place']
            ];

            appState.events.forEach(event => {
                appState.teams.forEach(team => {
                    const key = `${event.id}_${team.club_id}`;
                    const result = appState.results[key] || {};
                    
                    // Fallback for team name property
                    const teamName = team.club_name || team.name || 'Unknown Team';
                    const lane = team.is_absent ? 'ABS' : (team.lane_number || '-');
                    const timeStr = result.time_ms ? formatTime(result.time_ms) : (result.is_dq ? 'DQ' : '-');
                    const dqStr = result.is_dq ? (result.dq_reason || 'Yes') : 'No';

                    rows.push([
                        event.event_number,
                        event.event_name,
                        event.age_group,
                        event.gender,
                        teamName,
                        lane,
                        timeStr,
                        dqStr,
                        result.points || 0,
                        result.place ? ordinalSuffix(result.place) : '-'
                    ]);
                });
            });

            const csvString = rows.map(e => e.map(val => {
                const s = String(val === null || val === undefined ? '' : val);
                return `"${s.replace(/"/g, '""')}"`;
            }).join(",")).join("\n");

            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement("a");
            link.setAttribute("href", url);
            
            const hostName = (appState.scoresheet && appState.scoresheet.host_club_name) ? appState.scoresheet.host_club_name : 'Gala';
            const roundNum = (appState.scoresheet && appState.scoresheet.round_number) ? appState.scoresheet.round_number : 'X';
            const filename = `GalaResults_${hostName}_Round${roundNum}.csv`;
            
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } catch (err) {
            console.error("Export failed:", err);
            alert("Export failed: " + err.message);
        }
    }

    function ordinalSuffix(i) {
        var j = i % 10, k = i % 100;
        if (j == 1 && k != 11) return i + "st";
        if (j == 2 && k != 12) return i + "nd";
        if (j == 3 && k != 13) return i + "rd";
        return i + "th";
    }

    // =========================================================
    // PUBLIC API
    // =========================================================

    return {
        parseTime,
        formatTime,
        formatTimeForInput,
        calculateEventScores,
        calculateFullScoresheet,
        saveToLocal,
        loadFromLocal,
        queueForSync,
        syncToServer,
        openDB,
        exportToCSV
    };
})();
