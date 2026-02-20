<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Chief Timekeeper Slips | Cotswold League</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700;800&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI&display=swap');

        body {
            font-family: 'Noto Sans', sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* --- PRINT STYLES --- */
        @media print {
            @page {
                size: A4;
                margin: 5mm;
                /* Minimum margins for maximum print area */
            }

            /* Hide the UI Interface */
            .no-print,
            nav,
            button,
            select,
            label,
            .controls-container {
                display: none !important;
            }

            /* Reset Body for Print */
            body {
                background-color: white !important;
                margin: 0;
                padding: 0;
            }

            /* Main Container adjustments for print */
            .sheet-container {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                overflow: hidden;
                /* Prevent spillover pages */
            }
        }

        /* --- SLIP GRID STYLES --- */
        .sheet-container {
            display: grid;
            /* Grid columns and rows are set via JS */
            gap: 4px;
            box-sizing: border-box;
            background-color: white;
        }

        .slip {
            border: 1px dashed #999;
            /* Cutting guide */
            padding: 4px 8px;
            /* Slightly more padding now that we have space */
            display: flex;
            flex-direction: column;
            justify-content: start;
            overflow: hidden;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .slip-header {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 4px;
            white-space: nowrap;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }

        .slip-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            /* Increased font size for better readability */
            margin-top: 2px;
            flex-grow: 1;
        }

        .slip-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            font-weight: bold;
            height: 16px;
        }

        .slip-table td {
            border: 1px solid #000;
            text-align: center;
        }

        /* Column Widths */
        .col-lane {
            width: 15%;
            font-weight: bold;
        }

        .col-time {
            width: 60%;
        }

        .col-pos {
            width: 25%;
        }

        /* --- Lane Specific Adjustments --- */
        /* 4 Lanes - Very Roomy */
        .slip.lanes-4 td {
            height: 26px;
            font-size: 12pt;
        }

        .slip.lanes-4 th {
            font-size: 11pt;
        }

        /* 6 Lanes - Standard Comfort */
        .slip.lanes-6 td {
            height: 20px;
            font-size: 11pt;
        }

        /* 8 Lanes - Readable */
        .slip.lanes-8 td {
            height: 19px;
            font-size: 10.5pt;
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-900 min-h-screen">
    <?php include 'nav.php'; ?>

    <nav class="no-print border-b border-slate-800 bg-slate-900/50 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <div class="flex items-center gap-4">
                    <a href="admin.php" class="text-white hover:text-sky-400 transition-colors">
                        <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </a>
                    <span class="text-white font-bold text-lg hidden md:block">Chief Timekeeper Slips</span>
                </div>

                <div
                    class="controls-container flex items-center gap-2 bg-slate-800 rounded-lg p-1 border border-slate-700">
                    <div class="flex items-center">
                        <label for="eventSelector" class="text-slate-400 text-xs font-bold px-2 uppercase">Select
                            Lanes:</label>
                        <select id="eventSelector" onchange="renderSlips()"
                            class="bg-slate-900 text-white text-sm font-bold py-1.5 px-2 rounded-md border-none focus:ring-2 focus:ring-sky-500 outline-none cursor-pointer">
                            <option value="4">Rounds (4 Lanes)</option>
                            <option value="6" selected>Finals B & C (6 Lanes)</option>
                            <option value="8">Finals A (8 Lanes)</option>
                        </select>
                    </div>
                </div>

                <button onclick="window.print()"
                    class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded-full font-bold shadow-lg shadow-sky-500/30 flex items-center gap-2 transition-all">
                    <i data-lucide="printer" class="w-4 h-4"></i> <span class="hidden sm:inline">Print Slips</span>
                </button>
            </div>
        </div>
    </nav>

    <main class="flex justify-center p-4 md:p-8">

        <div id="sheet" class="sheet-container w-[210mm] h-[296mm] mx-auto bg-white shadow-2xl p-[5mm]">
        </div>

    </main>

    <script>
        // Initialize Icons
        lucide.createIcons();

        function renderSlips() {
            const selector = document.getElementById('eventSelector');
            const sheet = document.getElementById('sheet');
            const lanes = parseInt(selector.value);

            // Configuration Logic for 3 Columns
            let totalSlips = 15; // 3 cols x 5 rows
            let gridRows = 5;

            // If 8 lanes, we reduce rows to fit height comfortably
            if (lanes === 8) {
                totalSlips = 12; // 3 cols x 4 rows
                gridRows = 4;
            }

            // Apply Grid CSS - Changed to 3 columns
            sheet.style.gridTemplateColumns = 'repeat(3, 1fr)';
            sheet.style.gridTemplateRows = `repeat(${gridRows}, 1fr)`;

            // Clear current content
            sheet.innerHTML = '';

            // Generate Slips
            for (let i = 0; i < totalSlips; i++) {
                const slip = document.createElement('div');
                slip.className = `slip lanes-${lanes}`;

                // Build Table Rows
                let rowsHtml = '';
                for (let l = 1; l <= lanes; l++) {
                    rowsHtml += `<tr>
                        <td class="col-lane">${l}</td>
                        <td class="col-time"></td>
                        <td class="col-pos"></td>
                    </tr>`;
                }

                slip.innerHTML = `
                    <div class="slip-header">Event: ______________</div>
                    <table class="slip-table">
                        <thead>
                            <tr>
                                <th class="col-lane">Ln</th>
                                <th class="col-time">Time</th>
                                <th class="col-pos">Pos</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                `;
                sheet.appendChild(slip);
            }
        }

        // Initial Render
        document.addEventListener('DOMContentLoaded', renderSlips);
    </script>

</body>

</html>