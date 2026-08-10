<!-- admin_gala_results.php -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold flex items-center gap-2 text-white">
        <i data-lucide="bar-chart-2" class="w-5 h-5 text-sky-400"></i> Gala Results Verification
    </h2>
    <div class="flex gap-4 items-center">
        <select id="gr-round-select" class="bg-slate-900 border border-slate-700 rounded-lg py-2 px-4 text-white focus:outline-none focus:border-sky-500 transition-colors">
            <option value="1">Round 1</option>
            <option value="2">Round 2</option>
            <option value="3">Round 3</option>
            <option value="4">Round 4</option>
        </select>
        <button onclick="openSwapModal()" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-5 rounded-lg transition-colors shadow-lg flex items-center gap-2">
            <i data-lucide="arrow-left-right" class="w-4 h-4"></i> Swap Teams
        </button>
        <button id="btn-publish-round" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-5 rounded-lg transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" disabled>
            <i data-lucide="upload-cloud" class="w-4 h-4"></i> Publish Round
        </button>
    </div>
</div>

<!-- VENUES LIST -->
<div id="gr-venues-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
    <!-- Populated by JS -->
    <div class="col-span-full text-center py-8 text-slate-400">
        <i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto mb-2 text-sky-500"></i>
        Loading venues...
    </div>
</div>

<!-- ABSENT/IMPORT MODAL -->
<div id="gr-import-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="glass-panel border border-sky-500/30 p-6 rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <i data-lucide="download-cloud" class="w-5 h-5 text-sky-400"></i> Cross-Gala Import
            </h3>
            <button class="text-slate-400 hover:text-white" onclick="document.getElementById('gr-import-modal').classList.add('hidden')">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <p class="text-sm text-slate-300 mb-6" id="import-modal-desc">
            Import results for <strong>[Team Name]</strong> into the <strong>[Venue Name]</strong> scoresheet.
        </p>

        <input type="hidden" id="import-target-scoresheet-id">
        <input type="hidden" id="import-club-id">

        <div class="space-y-4 mb-6">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Source Round</label>
                <select id="import-round-select" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500 mb-2">
                    <option value="1">Round 1</option>
                    <option value="2">Round 2</option>
                    <option value="3">Round 3</option>
                    <option value="4">Round 4</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Source Venue</label>
                <select id="import-source-scoresheet" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-sky-500">
                    <option value="">- Select Venue -</option>
                    <!-- Populated by JS based on Round selection -->
                </select>
            </div>
        </div>

        <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 mb-6 hidden" id="import-preview-box">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Preview (First 3 Events)</h4>
            <div id="import-preview-content" class="text-sm text-slate-300 space-y-1"></div>
        </div>

        <div class="flex gap-3 mt-6">
            <button onclick="document.getElementById('gr-import-modal').classList.add('hidden')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-lg transition-colors">Cancel</button>
            <button id="btn-confirm-import" class="flex-1 bg-sky-600 hover:bg-sky-500 text-white font-bold py-2.5 rounded-lg transition-colors disabled:opacity-50">Confirm Import</button>
        </div>
    </div>
</div>

<!-- VIRTUAL SWAP MODAL -->
<div id="gr-swap-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
    <div class="glass-panel border border-indigo-500/30 p-6 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <i data-lucide="arrow-left-right" class="w-5 h-5 text-indigo-400"></i> Virtual Team Swap
            </h3>
            <button class="text-slate-400 hover:text-white" onclick="document.getElementById('gr-swap-modal').classList.add('hidden')">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <p class="text-sm text-slate-300 mb-6">
            Swap two teams between two different scoresheets. This moves their results to the new venue and automatically triggers a recalculation.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- VENUE A -->
            <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700">
                <h4 class="text-sm font-bold text-indigo-400 mb-3 uppercase tracking-wider">Venue A</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Venue</label>
                        <select id="swap-venue-a" onchange="loadSwapTeams('A')" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-indigo-500">
                            <option value="">- Loading venues -</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Team to Move</label>
                        <select id="swap-team-a" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-indigo-500">
                            <option value="">- Select Venue A first -</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- VENUE B -->
            <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700">
                <h4 class="text-sm font-bold text-indigo-400 mb-3 uppercase tracking-wider">Venue B</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Venue</label>
                        <select id="swap-venue-b" onchange="loadSwapTeams('B')" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-indigo-500">
                            <option value="">- Loading venues -</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Team to Move</label>
                        <select id="swap-team-b" class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2 px-3 text-white focus:outline-none focus:border-indigo-500">
                            <option value="">- Select Venue B first -</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button onclick="document.getElementById('gr-swap-modal').classList.add('hidden')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-lg transition-colors">Cancel</button>
            <button id="btn-confirm-swap" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 rounded-lg transition-colors">Execute Swap</button>
        </div>
    </div>
</div>

<script>
    const API_URL = 'gala_admin_api.php';
    let currentRound = 1;
    let currentVenues = [];

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('gr-round-select').addEventListener('change', (e) => {
            currentRound = e.target.value;
            loadVenues();
        });
        
        document.getElementById('btn-publish-round').addEventListener('click', publishRound);
        
        document.getElementById('import-round-select').addEventListener('change', loadSourceVenues);
        document.getElementById('import-source-scoresheet').addEventListener('change', fetchImportPreview);
        document.getElementById('btn-confirm-import').addEventListener('click', executeImport);

        document.getElementById('btn-confirm-swap').addEventListener('click', executeSwap);

        // Auto load tab data if it becomes visible (or just load it now)
        loadVenues();
    });

    async function loadVenues() {
        const container = document.getElementById('gr-venues-container');
        container.innerHTML = '<div class="col-span-full text-center py-8 text-slate-400"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto mb-2 text-sky-500"></i>Loading...</div>';
        lucide.createIcons();

        try {
            const resp = await fetch(`${API_URL}?action=list_scoresheets&round=${currentRound}`);
            const data = await resp.json();
            
            if(data.error) throw new Error(data.error);

            currentVenues = data.venues;
            renderVenues(data.venues);
        } catch (e) {
            container.textContent = `Error loading venues: ${e.message}`;
            container.className = 'col-span-full text-red-400 text-center py-8';
        }
    }

    function grEscape(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[character]);
    }

    function grLogo(value) {
        return encodeURIComponent(String(value ?? '').split(/[\\/]/).pop().replace(/[^A-Za-z0-9._-]/g, ''));
    }

    function importButton(scoresheetId, clubId, clubName, venueName) {
        if (!clubId) return '';
        const safeClub = grEscape(clubName);
        return `<button class="bg-slate-800 hover:bg-sky-900 border border-slate-700 hover:border-sky-500 text-slate-300 hover:text-sky-400 px-2.5 py-1 rounded-md transition-colors" onclick="openImportModal(${Number(scoresheetId) || 0}, ${Number(clubId) || 0}, decodeURIComponent('${encodeURIComponent(String(clubName ?? ''))}'), decodeURIComponent('${encodeURIComponent(String(venueName ?? ''))}'))" title="Import results for ${safeClub}">${safeClub}</button>`;
    }

    function renderVenues(venues) {
        const container = document.getElementById('gr-venues-container');
        const publishBtn = document.getElementById('btn-publish-round');
        
        let allVerified = true;
        let html = '';

        if (venues.length === 0) {
            container.innerHTML = '<div class="col-span-full text-slate-400 text-center py-8">No venues found for this round.</div>';
            publishBtn.disabled = true;
            return;
        }

        venues.forEach(v => {
            if (v.status !== 'verified' && v.status !== 'published') {
                allVerified = false;
            }

            const statusConfig = getStatusConfig(v.status);
            
            html += `
                <div class="glass-panel p-4 rounded-xl border border-white/5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="${statusConfig.bgClass} ${statusConfig.textClass} text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">${statusConfig.label}</span>
                        </div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-white rounded p-1"><img src="images/Teams/${grLogo(v.host_club_logo)}" alt="" class="w-full h-full object-contain"></div>
                            <div>
                                <h3 class="font-bold text-white text-sm">${grEscape(v.host_club_name)}</h3>
                                <p class="text-xs text-slate-400">${grEscape(v.recorder_name || 'No recorder yet')}</p>
                            </div>
                        </div>
                        
                        <div class="text-[11px] text-slate-500 mb-4 space-y-1">
                            <p class="mb-1 uppercase tracking-widest font-bold">Import Team Results:</p>
                            <div class="flex flex-wrap gap-2">
                                ${importButton(v.scoresheet_id, v.team_1_id, v.team_1_name, v.host_club_name)}
                                ${importButton(v.scoresheet_id, v.team_2_id, v.team_2_name, v.host_club_name)}
                                ${importButton(v.scoresheet_id, v.team_3_id, v.team_3_name, v.host_club_name)}
                                ${importButton(v.scoresheet_id, v.team_4_id, v.team_4_name, v.host_club_name)}
                            </div>
                            ${v.updated_at ? `<p class="mt-2 pt-1 text-[10px]">Updated: ${new Date(v.updated_at).toLocaleString()}</p>` : ''}
                        </div>
                    </div>
                    
                    <div class="space-y-2 mt-2 pt-3 border-t border-white/5">
                        ${getVenueActions(v)}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        lucide.createIcons();
        publishBtn.disabled = !allVerified || venues.length === 0;
    }

    function getStatusConfig(status) {
        const config = {
            'not_started': { label: 'Not Started', bgClass: 'bg-slate-500/20', textClass: 'text-slate-400' },
            'draft': { label: 'Draft / Offline', bgClass: 'bg-amber-500/20', textClass: 'text-amber-400' },
            'in_progress': { label: 'In Progress', bgClass: 'bg-sky-500/20', textClass: 'text-sky-400' },
            'submitted': { label: 'Submitted', bgClass: 'bg-orange-500/20', textClass: 'text-orange-400' },
            'verified': { label: 'Verified', bgClass: 'bg-emerald-500/20', textClass: 'text-emerald-400' },
            'published': { label: 'Published', bgClass: 'bg-purple-500/20', textClass: 'text-purple-400' },
        };
        return config[status] || config['not_started'];
    }

    function getVenueActions(v) {
        if (!v.scoresheet_id) {
            return `<div class="text-xs text-center text-slate-500 italic">Scoresheet not created yet</div>`;
        }

        let btns = `<a href="gala_scoresheet.php?id=${v.scoresheet_id}" target="_blank" class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 py-1.5 rounded-lg text-xs font-bold transition-all block text-center mb-2"><i data-lucide="eye" class="w-3.5 h-3.5 inline"></i> View Scoresheet</a>`;

        if (v.status === 'submitted') {
            btns += `
                <div class="flex gap-2">
                    <button onclick="actionScoresheet(${v.scoresheet_id}, 'reject')" class="flex-1 bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/30 py-1.5 rounded-lg text-xs font-bold transition-all">Reject</button>
                    <button onclick="actionScoresheet(${v.scoresheet_id}, 'verify')" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white py-1.5 rounded-lg text-xs font-bold transition-all shadow-lg shadow-emerald-900/20 flex items-center justify-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5"></i> Verify</button>
                </div>
            `;
        } else if (v.status === 'verified') {
            btns += `<button onclick="actionScoresheet(${v.scoresheet_id}, 'reject')" class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 py-1.5 rounded-lg text-xs font-bold transition-all border border-slate-700">Un-verify</button>`;
        }
        
        return btns;
    }

    async function actionScoresheet(id, actionStr) {
        try {
            const fd = new FormData();
            fd.append('action', actionStr);
            fd.append('scoresheet_id', id);
            
            const resp = await fetch(API_URL, { method: 'POST', body: fd });
            const data = await resp.json();
            
            if(data.success) {
                loadVenues();
            } else {
                alert(data.error || 'Action failed');
            }
        } catch(e) {
            alert('Error communicating with server');
        }
    }

    async function publishRound() {
        if(!confirm(`Are you sure you want to publish Round ${currentRound} to the main league table?`)) return;
        
        try {
            const fd = new FormData();
            fd.append('action', 'publish_round');
            fd.append('round', currentRound);
            
            const resp = await fetch(API_URL, { method: 'POST', body: fd });
            const data = await resp.json();
            
            if(data.success) {
                alert('Success: ' + data.message);
                loadVenues();
            } else {
                alert('Error: ' + data.error);
            }
        } catch(e) {
            alert('Error communicating with server');
        }
    }

    // ================== CROSS GALA IMPORT ==================
    function openImportModal(targetScoresheetId, clubId, clubName, venueName) {
        if(!targetScoresheetId) return alert('Scoresheet not created yet. Team needs to open it first.');
        if(!clubId) return alert('No club assigned to this slot.');
        
        document.getElementById('import-target-scoresheet-id').value = targetScoresheetId;
        document.getElementById('import-club-id').value = clubId;
        document.getElementById('import-modal-desc').innerHTML = `Import results for <strong>${grEscape(clubName)}</strong> into the <strong>${grEscape(venueName)}</strong> scoresheet.`;
        
        document.getElementById('import-round-select').value = currentRound;
        loadSourceVenues();
        
        document.getElementById('import-preview-box').classList.add('hidden');
        document.getElementById('gr-import-modal').classList.remove('hidden');
    }

    async function loadSourceVenues() {
        const round = document.getElementById('import-round-select').value;
        const sel = document.getElementById('import-source-scoresheet');
        sel.innerHTML = '<option value="">Loading...</option>';
        
        try {
            const resp = await fetch(`${API_URL}?action=list_scoresheets&round=${round}`);
            const data = await resp.json();
            
            if(data.venues) {
                sel.innerHTML = '<option value="">- Select Venue -</option>';
                data.venues.forEach(v => {
                    if(v.scoresheet_id) {
                        sel.innerHTML += `<option value="${v.scoresheet_id}">${v.host_club_name} (${v.status})</option>`;
                    }
                });
            }
        } catch(e) {
            sel.innerHTML = '<option value="">Error loading</option>';
        }
    }

    async function fetchImportPreview() {
        const srcId = document.getElementById('import-source-scoresheet').value;
        const box = document.getElementById('import-preview-box');
        
        if(!srcId) {
            box.classList.add('hidden');
            return;
        }
        
        // This would ideally hit an endpoint to get just that team's results. 
        // For simplicity, we just enable the confirm button.
        box.classList.remove('hidden');
        document.getElementById('import-preview-content').innerHTML = `<em>Preview not fully implemented in API yet. Confirming will copy all 53 events.</em>`;
    }

    async function executeImport() {
        const targetId = document.getElementById('import-target-scoresheet-id').value;
        const sourceId = document.getElementById('import-source-scoresheet').value;
        const clubId = document.getElementById('import-club-id').value;
        
        if(!targetId || !sourceId || !clubId) return alert('Missing info');
        
        try {
            const btn = document.getElementById('btn-confirm-import');
            btn.innerHTML = 'Importing...';
            btn.disabled = true;
            
            const fd = new FormData();
            fd.append('action', 'import_results');
            fd.append('target_scoresheet_id', targetId);
            fd.append('source_scoresheet_id', sourceId);
            fd.append('club_id', clubId);
            
            const resp = await fetch(API_URL, { method: 'POST', body: fd });
            const data = await resp.json();
            
            if(data.success) {
                alert(data.message);
                document.getElementById('gr-import-modal').classList.add('hidden');
                loadVenues(); // Refresh UI
            } else {
                alert('Error: ' + data.error);
            }
        } catch(e) {
            alert('Error communicating with server');
        } finally {
            const btn = document.getElementById('btn-confirm-import');
            btn.innerHTML = 'Confirm Import';
            btn.disabled = false;
        }
    }

    // ================== VIRTUAL TEAM SWAP ==================
    function openSwapModal() {
        populateSwapVenues('A');
        populateSwapVenues('B');
        document.getElementById('gr-swap-modal').classList.remove('hidden');
    }

    function populateSwapVenues(side) {
        const sel = document.getElementById(`swap-venue-${side.toLowerCase()}`);
        sel.innerHTML = '<option value="">- Select Venue -</option>';
        currentVenues.forEach(v => {
            if(v.scoresheet_id) {
                sel.innerHTML += `<option value="${v.scoresheet_id}">${v.host_club_name}</option>`;
            }
        });
    }

    function loadSwapTeams(side) {
        const venueId = document.getElementById(`swap-venue-${side.toLowerCase()}`).value;
        const sel = document.getElementById(`swap-team-${side.toLowerCase()}`);
        
        sel.innerHTML = '<option value="">- Select Team -</option>';
        
        if(!venueId) return;
        
        const venue = currentVenues.find(v => v.scoresheet_id == venueId);
        if(venue) {
            if(venue.team_1_id) sel.innerHTML += `<option value="${venue.team_1_id}">${venue.team_1_name}</option>`;
            if(venue.team_2_id) sel.innerHTML += `<option value="${venue.team_2_id}">${venue.team_2_name}</option>`;
            if(venue.team_3_id) sel.innerHTML += `<option value="${venue.team_3_id}">${venue.team_3_name}</option>`;
            if(venue.team_4_id) sel.innerHTML += `<option value="${venue.team_4_id}">${venue.team_4_name}</option>`;
        }
    }

    async function executeSwap() {
        const venueA = document.getElementById('swap-venue-a').value;
        const teamA = document.getElementById('swap-team-a').value;
        const venueB = document.getElementById('swap-venue-b').value;
        const teamB = document.getElementById('swap-team-b').value;

        if(!venueA || !teamA || !venueB || !teamB) {
            return alert('Please select all fields for both Venue A and Venue B.');
        }

        if(venueA === venueB) {
            return alert('Cannot swap teams within the same venue. Select two different venues.');
        }

        if(!confirm('Are you sure you want to swap these teams and all their results between these two venues? Both scoresheets will be reverted to "In Progress" so they can recalculate.')) {
            return;
        }

        try {
            const btn = document.getElementById('btn-confirm-swap');
            btn.innerHTML = 'Swapping...';
            btn.disabled = true;
            
            const fd = new FormData();
            fd.append('action', 'swap_teams');
            fd.append('scoresheet_a', venueA);
            fd.append('team_a', teamA);
            fd.append('scoresheet_b', venueB);
            fd.append('team_b', teamB);
            
            const resp = await fetch(API_URL, { method: 'POST', body: fd });
            const data = await resp.json();
            
            if(data.success) {
                alert('Swap completed successfully!');
                document.getElementById('gr-swap-modal').classList.add('hidden');
                loadVenues(); // Refresh UI
            } else {
                alert('Error: ' + data.error);
            }
        } catch(e) {
            alert('Error communicating with server');
        } finally {
            const btn = document.getElementById('btn-confirm-swap');
            btn.innerHTML = 'Execute Swap';
            btn.disabled = false;
        }
    }
</script>
