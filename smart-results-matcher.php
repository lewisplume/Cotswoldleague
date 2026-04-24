<?php
session_start();
include 'db.php';
$sheetId = $_GET['sheet_id'] ?? null;
$current_club_id = $_SESSION['club_id'] ?? null;

$available_results = [];
if ($current_club_id) {
    $draws_sql = "SELECT vd.*, c_host.name AS host_name
                  FROM venue_details vd
                  LEFT JOIN clubs c_host ON vd.club_id = c_host.id
                  WHERE (vd.club_id = ? OR vd.team_1_id = ? OR vd.team_2_id = ? OR vd.team_3_id = ? OR vd.team_4_id = ?)
                  AND vd.results_file IS NOT NULL
                  ORDER BY vd.round_number ASC";
    $d_stmt = $conn->prepare($draws_sql);
    if ($d_stmt) {
        $d_stmt->bind_param("iiiii", $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id);
        $d_stmt->execute();
        $d_res = $d_stmt->get_result();
        while ($row = $d_res->fetch_assoc()) {
            $available_results[] = [
                'name' => 'Round ' . $row['round_number'] . ' - ' . $row['host_name'],
                'file' => $row['results_file']
            ];
        }
        $d_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League Smart Results Matcher</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700&display=swap');

        body {
            font-family: 'Noto Sans', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding-bottom: 40px;
        }

        /* --- VISUAL STYLES (Screen) --- */
        .sheet-container {
            background: white;
            width: 210mm;
            min-height: 296mm;
            /* A4 height */
            margin: 20px auto;
            padding: 10mm 15mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        th {
            background-color: #003366 !important;
            color: white !important;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #003366;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8pt;
        }

        td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            vertical-align: middle;
            color: #1e293b;
        }

        tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Column Specifics */
        .col-num {
            width: 40px;
            text-align: center;
            font-weight: bold;
        }

        .col-event {
            text-align: left;
            font-weight: 600;
        }

        .col-swimmer {
            text-align: left;
            color: #003366;
        }

        .col-time {
            width: 80px;
            text-align: center;
            font-family: monospace;
            font-size: 10pt;
        }

        .col-cut {
            width: 70px;
            text-align: center;
            color: #dc2626;
            font-size: 8pt;
        }

        .col-place {
            width: 50px;
            text-align: center;
            font-weight: bold;
        }

        /* Place Colors */
        .place-1 {
            background-color: #fef9c3 !important;
            color: #854d0e;
            border-color: #facc15;
        }

        /* Gold */
        .place-2 {
            background-color: #f3f4f6 !important;
            color: #475569;
            border-color: #cbd5e1;
        }

        /* Silverish */
        .place-3 {
            background-color: #ffedd5 !important;
            color: #9a3412;
            border-color: #fdba74;
        }

        /* Bronze */

        /* DQ / Speeding */
        .status-dq {
            color: red;
            font-weight: bold;
        }

        .status-fast {
            color: #9333ea;
            font-weight: bold;
        }

        /* Purple for Speeding Ticket */

        /* Header Section */
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #003366;
            padding-bottom: 10px;
        }

        .report-header h1 {
            font-size: 20pt;
            font-weight: 800;
            color: #003366;
            margin: 0;
        }

        .report-header h2 {
            font-size: 14pt;
            margin: 5px 0 0;
            color: #334155;
        }

        .report-header p {
            font-size: 10pt;
            color: #64748b;
            margin: 2px 0;
        }

        /* Controls */
        .control-group {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
        }

        /* --- PRINT STYLES --- */
        @media print {
            body {
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .sheet-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: 0;
                padding: 0;
            }

            th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            tr:nth-child(even) td {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .place-1,
            .place-2,
            .place-3 {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="no-print max-w-4xl mx-auto mt-8 p-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Cotswold League Smart Results Matcher</h1>

        <div class="control-group">
            <?php if ($sheetId): ?>
                <div id="loadingStatus" class="text-center py-4">
                    <h3 class="text-lg font-bold text-blue-900 mb-2">Step 1: Importing Teamsheet...</h3>
                    <p class="text-sm text-gray-600">Please wait while your Teamsheet is loaded from Google Sheets.</p>
                </div>
                <div id="loadedStatus" class="hidden text-center py-4">
                    <h3 class="text-lg font-bold text-green-700 mb-2">Step 1: Import Complete</h3>
                    <p class="text-sm text-green-600 mb-3">Your teamsheet was successfully loaded.</p>
                </div>
            <?php else: ?>
                <h3 class="text-lg font-bold text-blue-900 mb-2">Step 1: Upload Teamsheet</h3>
                <p class="text-sm text-gray-600 mb-3">Paste your Google Sheet URL or upload your Excel file containing swimmer names.</p>
                <div class="flex flex-col gap-4">
                    <div class="flex gap-4 items-center">
                        <input type="text" id="manualUrl" placeholder="Paste Google Sheet URL here" class="block w-full p-2 border border-slate-300 rounded shadow-sm text-slate-800 text-sm" />
                        <button onclick="importManualUrl()" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition font-bold whitespace-nowrap text-sm" id="loadUrlBtn">Load URL</button>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-400 font-bold uppercase tracking-wider relative">
                        <div class="flex-grow border-t border-gray-200"></div>
                        <span>or upload file</span>
                        <div class="flex-grow border-t border-gray-200"></div>
                    </div>
                    <div class="flex gap-4 items-center">
                        <input type="file" id="teamsheetInput" accept=".xlsx, .xls, .csv"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    </div>
                </div>
            <?php endif; ?>
            <div class="flex gap-4 items-center mt-4 border-t border-gray-100 pt-4 hidden" id="sheetSelectContainer">
                <div id="workbookNameDisplay" class="bg-blue-50 text-blue-800 text-xs font-bold px-3 py-1 rounded-full border border-blue-200 hidden"></div>
                <select id="teamSheetSelector" class="border border-gray-300 rounded px-3 py-2 text-sm min-w-[200px]"></select>
                <span class="text-sm text-gray-600 font-semibold" id="sheetSelectLabel">Select Sheet tab</span>
            </div>
        </div>

        <div class="control-group opacity-50 pointer-events-none transition-opacity" id="step2Container">
            <h3 class="text-lg font-bold text-green-900 mb-2">Step 2: Upload Results File</h3>
            
            <?php if (!empty($available_results)): ?>
                <p class="text-sm text-gray-600 mb-3">Select an available Gala Results file from your draws, or upload one manually.</p>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <label class="block text-sm font-bold text-green-800 mb-2">Select Available Results:</label>
                    <div class="flex gap-3">
                        <select id="serverResultsSelector" class="flex-grow border border-green-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:outline-none focus:border-green-500 shadow-sm">
                            <option value="">-- Select a Results File --</option>
                            <?php foreach ($available_results as $res): ?>
                                <option value="<?php echo htmlspecialchars('uploads/results/' . $res['file']); ?>">
                                    <?php echo htmlspecialchars($res['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="loadServerResults()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition font-bold whitespace-nowrap text-sm shadow-sm" id="loadServerBtn">Load Selected</button>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-600 mb-3">Upload the Gala Results Calculator file (Excel or CSV).</p>
            <?php endif; ?>

            <div id="resultsSheetSelectorContainer" class="hidden mb-4 p-4 bg-green-100 border border-green-300 rounded-lg flex items-center gap-3 shadow-inner">
                <span class="text-sm font-bold text-green-800">Select Sheet Tab:</span>
                <select id="resultsSheetSelector" class="flex-grow border border-green-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white"></select>
            </div>

            <?php if (!empty($available_results)): ?>
                <div class="flex items-center gap-4 text-xs text-gray-400 font-bold uppercase tracking-wider relative mb-4">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <span>or upload manually</span>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>
            <?php endif; ?>

            <div class="flex gap-4 items-center">
                <input type="file" id="resultsInput" accept=".xlsx, .xls, .csv"
                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" />
            </div>
        </div>

        <div class="control-group hidden" id="step3Container">
            <h3 class="text-lg font-bold text-purple-900 mb-2">Step 3: Select Your Team</h3>
            <p class="text-sm text-gray-600 mb-3">We found these teams in the results file. Which one is yours?</p>
            <div class="flex gap-4 items-center">
                <select id="teamSelector"
                    class="border border-purple-300 rounded px-4 py-2 w-full max-w-md font-semibold text-gray-700 shadow-sm"></select>
                <button onclick="generateReport()"
                    class="bg-purple-700 text-white px-6 py-2 rounded hover:bg-purple-800 font-bold transition shadow">Generate
                    Report</button>
            </div>
        </div>

        <div class="text-center mt-6">
            <button onclick="window.print()"
                class="hidden bg-gray-800 text-white px-8 py-3 rounded-full hover:bg-gray-900 transition font-bold shadow-lg flex items-center gap-2 mx-auto"
                id="printBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print Report
            </button>
        </div>
    </div>

    <div id="reportContainer" class="hidden sheet-container">
        <div class="report-header">
            <h1>GALA RESULTS SUMMARY</h1>
            <h2 id="reportTeamName">Team Name</h2>
            <p id="reportDate">Date: --/--/----</p>
        </div>

        <table id="resultsTable">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-event">Event Detail</th>
                    <th class="col-swimmer">Swimmer(s)</th>
                    <th class="col-cut">Limit</th>
                    <th class="col-time">Time</th>
                    <th class="col-place">Place</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

    <script>
        // --- STATE ---
        let teamsheetData = {}; // { EventNum: "Name1, Name2" }
        let resultsData = [];   // Array of arrays (rows)
        let teamMapping = [];   // [{ name: "Dursley", colIndex: 4 }, ...]

        // --- DOM ELEMENTS ---
        const step2 = document.getElementById('step2Container');
        const step3 = document.getElementById('step3Container');
        const printBtn = document.getElementById('printBtn');
        const reportContainer = document.getElementById('reportContainer');

        // --- STEP 1: TEAMSHEET UPLOAD ---
        const teamsheetInput = document.getElementById('teamsheetInput');
        if (teamsheetInput) {
            teamsheetInput.addEventListener('change', handleTeamsheetUpload);
        }
        document.getElementById('teamSheetSelector').addEventListener('change', (e) => processTeamsheet(e.target.workbook, e.target.value));

        // Auto-load if sheetId supplied
        <?php if ($sheetId): ?>
        window.addEventListener('load', () => {
            fetchAndProcessSheet('<?php echo htmlspecialchars($sheetId); ?>').then(() => {
                document.getElementById('loadingStatus').classList.add('hidden');
                document.getElementById('loadedStatus').classList.remove('hidden');
            });
        });
        <?php endif; ?>

        async function fetchAndProcessSheet(sheetId) {
            try {
                const response = await fetch('fetch_sheet.php?id=' + sheetId);
                if (!response.ok) throw new Error('Network response was not ok');
                
                let filename = 'Google Sheet Teamsheet';
                const cd = response.headers.get('Content-Disposition');
                if (cd) {
                    const match = cd.match(/filename="?([^";]+)"?/);
                    if (match && match[1]) filename = match[1];
                }
                
                const data = await response.arrayBuffer();
                const workbook = XLSX.read(data, { type: 'array' });

                document.getElementById('sheetSelectContainer').classList.remove('hidden');
                const titleEl = document.getElementById('workbookNameDisplay');
                if(titleEl) {
                    titleEl.textContent = filename;
                    titleEl.classList.remove('hidden');
                }

                setupSheetSelector(workbook, 'teamSheetSelector', (wb, sheet) => processTeamsheet(wb, sheet));

                step2.classList.remove('opacity-50', 'pointer-events-none');
            } catch (error) {
                console.error('Error fetching sheet:', error);
                alert('Failed to load Teamsheet data.');
            }
        }

        function importManualUrl() {
            const url = document.getElementById('manualUrl').value;
            const match = url.match(/\/d\/([a-zA-Z0-9-_]+)/);
            if (match && match[1]) {
                const btn = document.getElementById('loadUrlBtn');
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Loading...';
                setTimeout(() => {
                    fetchAndProcessSheet(match[1]).then(() => {
                        btn.innerHTML = originalText;
                    });
                }, 100);
            } else {
                alert('Invalid Google Sheet URL');
            }
        }

        async function handleTeamsheetUpload(e) {
            const file = e.target.files[0];
            if (!file) return;

            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });

            setupSheetSelector(workbook, 'teamSheetSelector', (wb, sheet) => processTeamsheet(wb, sheet));
            
            document.getElementById('sheetSelectContainer').classList.remove('hidden');
            step2.classList.remove('opacity-50', 'pointer-events-none');
        }

        function processTeamsheet(workbook, sheetName) {
            const sheet = workbook.Sheets[sheetName];
            const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

            teamsheetData = {};

            rows.forEach(row => {
                const eventNum = row[0];
                const swimmers = row[2];
                if (eventNum && !isNaN(eventNum)) {
                    teamsheetData[eventNum] = swimmers || "";
                }
            });
            console.log("Teamsheet Loaded:", Object.keys(teamsheetData).length + " events found.");
        }

        // --- STEP 2: RESULTS UPLOAD ---
        document.getElementById('resultsInput').addEventListener('change', handleResultsUpload);
        const resultsSheetSelector = document.getElementById('resultsSheetSelector');
        if (resultsSheetSelector) {
            resultsSheetSelector.addEventListener('change', (e) => processResults(e.target.workbook, e.target.value));
        }

        async function loadServerResults() {
            const url = document.getElementById('serverResultsSelector').value;
            if (!url) {
                alert('Please select a results file.');
                return;
            }
            
            const btn = document.getElementById('loadServerBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Loading...';
            btn.disabled = true;
            
            try {
                // Add cache-busting query parameter to ensure we get the latest file
                const cacheBuster = '?t=' + new Date().getTime();
                const response = await fetch(url + cacheBuster);
                if (!response.ok) throw new Error('File not found');
                
                const data = await response.arrayBuffer();
                const workbook = XLSX.read(data, { type: 'array' });
                
                setupSheetSelector(workbook, 'resultsSheetSelector', (wb, sheet) => processResults(wb, sheet));
            } catch (error) {
                console.error(error);
                alert('Error loading the results file from the server.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function handleResultsUpload(e) {
            const file = e.target.files[0];
            if (!file) return;

            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });

            setupSheetSelector(workbook, 'resultsSheetSelector', (wb, sheet) => processResults(wb, sheet));
        }

        function processResults(workbook, sheetName) {
            const sheet = workbook.Sheets[sheetName];
            resultsData = XLSX.utils.sheet_to_json(sheet, { header: 1 });

            let laneRowIndex = -1;
            // Search first 20 rows for the lane headers
            for (let i = 0; i < Math.min(20, resultsData.length); i++) {
                const row = resultsData[i];
                let laneCount = 0;

                // Count how many cells in this row start with "Lane"
                if (Array.isArray(row)) {
                    row.forEach(cell => {
                        if (cell && String(cell).toLowerCase().trim().startsWith("lane")) {
                            laneCount++;
                        }
                    });
                }

                // If we found at least 2 "Lane" cells, assume this is the header row
                // This allows for "Lane 3, Lane 4, Lane 5, Lane 6" configurations
                if (laneCount >= 2) {
                    laneRowIndex = i;
                    break;
                }
            }

            if (laneRowIndex === -1) {
                step3.classList.add('hidden');
                if (workbook.SheetNames.length === 1) {
                    alert("Could not find a row with multiple 'Lane' headers (e.g. Lane 1, Lane 2, or Lane 3, Lane 4 etc.) in the results file. Please check the file.");
                }
                return;
            }

            const laneRow = resultsData[laneRowIndex];
            const teamRow = resultsData[laneRowIndex + 1];
            teamMapping = [];

            laneRow.forEach((cell, index) => {
                if (typeof cell === 'string' && cell.toLowerCase().trim().startsWith('lane')) {
                    let teamName = "Unknown Team";
                    if (teamRow && teamRow[index]) {
                        teamName = teamRow[index];
                    }
                    teamMapping.push({
                        lane: cell,
                        team: teamName,
                        colIndex: index
                    });
                }
            });

            const teamSelect = document.getElementById('teamSelector');
            teamSelect.innerHTML = "";
            teamMapping.forEach(tm => {
                const opt = document.createElement('option');
                opt.value = tm.colIndex;
                opt.textContent = `${tm.team} (${tm.lane})`;
                teamSelect.appendChild(opt);
            });

            step3.classList.remove('hidden');
        }

        // --- STEP 3: GENERATE REPORT ---
        function generateReport() {
            trackAction('report_generated');

            const selectedColIndex = parseInt(document.getElementById('teamSelector').value);
            const selectedTeamObj = teamMapping.find(t => t.colIndex === selectedColIndex);

            document.getElementById('reportTeamName').textContent = selectedTeamObj ? selectedTeamObj.team : "Team Results";
            document.getElementById('reportDate').textContent = "Date: " + new Date().toLocaleDateString('en-GB');

            const tbody = document.querySelector('#resultsTable tbody');
            tbody.innerHTML = "";

            let headerRowIndex = -1;
            for (let i = 0; i < resultsData.length; i++) {
                if (resultsData[i][0] && String(resultsData[i][0]).includes("Event No.")) {
                    headerRowIndex = i;
                    break;
                }
            }

            if (headerRowIndex === -1) {
                alert("Could not locate data table (looking for 'Event No.').");
                return;
            }

            for (let i = headerRowIndex + 1; i < resultsData.length; i++) {
                const row = resultsData[i];
                const eventNum = row[0];

                if (!eventNum) continue;
                if (String(eventNum).toLowerCase().includes("total")) continue;

                const eventName = row[1];
                let cutOffRaw = row[3];
                let time = row[selectedColIndex];

                // Check for empty rows/times
                if (!time) time = "-";

                // Format the "Cut Off" if it is a raw number too
                let displayCutOff = cutOffRaw;
                if (typeof cutOffRaw === 'number') {
                    displayCutOff = formatExcelTime(cutOffRaw);
                }

                let swimmers = teamsheetData[eventNum] || "";

                // --- PLACE CALCULATION ---
                let place = "-";
                let rawTime = parseTime(time);

                if (rawTime !== null) {
                    const timeStr = String(time).toLowerCase();
                    if (timeStr.includes("dq")) {
                        place = "DQ";
                    } else if (timeStr.includes("fast")) {
                        place = "TF";
                    } else {
                        let allTimes = [];

                        teamMapping.forEach(tm => {
                            const tVal = row[tm.colIndex];
                            const pVal = parseTime(tVal);
                            // Only compare against valid times
                            if (pVal !== null && !String(tVal).toLowerCase().includes("dq") && !String(tVal).toLowerCase().includes("fast")) {
                                allTimes.push(pVal);
                            }
                        });

                        allTimes.sort((a, b) => a - b);
                        const rank = allTimes.indexOf(rawTime) + 1;
                        if (rank > 0) place = rank;
                    }
                } else {
                    if (String(time).toLowerCase().includes("dq")) place = "DQ";
                    if (String(time).toLowerCase().includes("fast")) place = "TF";
                }

                // --- TIME FORMATTING (The Fix) ---
                let displayTime = time;

                if (typeof time === 'number') {
                    // It is a raw Excel fraction (e.g. 0.00097...)
                    displayTime = formatExcelTime(time);
                } else if (typeof time === 'string') {
                    // Just enforce standard separators if needed, or leave as is
                    if (time.includes(':')) {
                        // Optional: Convert HH:MM:SS to MM:SS.mm if you prefer
                        // But if text is "00:01:24", we usually just leave it.
                        // Let's strip the leading "00:" if it exists for cleaner look?
                        // displayTime = displayTime.replace(/^00:/, ''); 
                    }
                }

                // --- RENDER ---
                const tr = document.createElement('tr');

                let placeClass = "";
                if (place === 1) placeClass = "place-1";
                if (place === 2) placeClass = "place-2";
                if (place === 3) placeClass = "place-3";

                let placeDisplay = place;
                if (place === "DQ") placeDisplay = "<span class='status-dq'>DQ</span>";
                if (place === "TF") placeDisplay = "<span class='status-fast'>TF</span>";

                tr.innerHTML = `
                    <td class="col-num">${eventNum}</td>
                    <td class="col-event">${eventName}</td>
                    <td class="col-swimmer">${swimmers}</td>
                    <td class="col-cut">${displayCutOff || "-"}</td>
                    <td class="col-time">${displayTime}</td>
                    <td class="col-place ${placeClass}">${placeDisplay}</td>
                `;
                tbody.appendChild(tr);
            }

            reportContainer.classList.remove('hidden');
            printBtn.classList.remove('hidden');
            reportContainer.scrollIntoView({ behavior: 'smooth' });
        }

        // --- HELPER: Convert Excel Serial Date to MM:SS.mm ---
        function formatExcelTime(serial) {
            // Excel time is fraction of a day. 
            // 1.0 = 24 hours = 86400 seconds
            const totalSeconds = serial * 86400;

            // Extract minutes, seconds, milliseconds
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = Math.floor(totalSeconds % 60);

            // Get remainder for milliseconds (e.g., 0.18s)
            // We want .18, so we multiply the fraction by 100 and round
            const fraction = totalSeconds - Math.floor(totalSeconds);
            const hundreths = Math.round(fraction * 100);

            // Pad with zeros
            const mm = String(minutes).padStart(2, '0');
            const ss = String(seconds).padStart(2, '0');
            const ms = String(hundreths).padStart(2, '0');

            return `${mm}:${ss}.${ms}`;
        }

        // --- HELPER: Parse Time for Sorting ---
        function parseTime(timeStr) {
            if (!timeStr) return null;

            // If it's already a number (Excel serial), return seconds directly
            if (typeof timeStr === 'number') {
                return timeStr * 86400;
            }

            timeStr = String(timeStr).trim();

            // Handle standard string "00:01:24"
            const parts = timeStr.replace(/\./g, ':').split(':');
            if (parts.length === 3) {
                return (+parts[0] * 3600) + (+parts[1] * 60) + (+parts[2]);
            } else if (parts.length === 2) {
                return (+parts[0] * 60) + (+parts[1]);
            }
            return null;
        }

        // --- HELPER: Sheet Selector ---
        function setupSheetSelector(workbook, selectorId, callback) {
            const selector = document.getElementById(selectorId);
            const container = document.getElementById(selectorId + 'Container');
            selector.innerHTML = "";

            if (workbook.SheetNames.length > 1) {
                selector.classList.remove('hidden');
                if (container) container.classList.remove('hidden');
                workbook.SheetNames.forEach(name => {
                    const opt = document.createElement('option');
                    opt.value = name;
                    opt.textContent = name;
                    selector.appendChild(opt);
                });
                selector.workbook = workbook;
                callback(workbook, workbook.SheetNames[0]);
            } else {
                selector.classList.add('hidden');
                if (container) container.classList.add('hidden');
                callback(workbook, workbook.SheetNames[0]);
            }
        }

        async function trackAction(actionName) {
            try {
                await fetch('track_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: actionName }),
                });
            } catch (error) {
                console.error('Error tracking action:', error);
            }
        }
    </script>
</body>

</html>