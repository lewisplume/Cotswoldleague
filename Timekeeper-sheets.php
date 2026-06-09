<?php
include_once 'db.php';
include_once 'document_event_helpers.php';

$timekeeper_events = cotswold_load_document_events($conn, $current_season_year);
$timekeeper_events_json = json_encode($timekeeper_events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timekeeper Sheet | Cotswold League</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700;800&display=swap');

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
            }

            html,
            body {
                height: auto;
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            /* Hide UI elements */
            .no-print,
            nav,
            button,
            select,
            label,
            .controls-container {
                display: none !important;
            }

            /* Main Container adjustments for print */
            .sheet-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: white !important;
                border: none !important;
                min-height: 0 !important;
            }

            /* Stronger Borders for Print */
            table {
                width: 100%;
                border-collapse: collapse;
                border: 2px solid #000;
                font-size: 9pt;
            }

            th {
                background-color: #d1d5db !important;
                color: black !important;
                border: 1px solid #000;
                padding: 4px;
                text-align: center;
                font-weight: 800;
            }

            td {
                border: 1px solid #000;
                padding: 1px 6px;
                height: 22px;
            }

            tr {
                border-bottom: 1px solid #000;
                page-break-inside: avoid;
            }

            h1 {
                font-size: 18pt !important;
                margin-bottom: 10px !important;
            }

            .info-grid {
                font-size: 10pt !important;
                padding: 5px !important;
                margin-bottom: 10px !important;
            }

            .page-break {
                clear: both;
                page-break-before: always;
                break-before: page;
                display: block;
                height: 0;
                visibility: hidden;
            }

            .page-footer {
                margin-top: 5px;
            }

            /* Ensure prefilled text prints clearly */
            #roundOutput {
                color: black !important;
                font-weight: 800 !important;
            }
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-900 min-h-screen">
    <?php include 'nav.php'; ?>

    <?php
    $printable_doc_title = 'Timekeeper Sheet';
    $printable_doc_print_label = 'Print Sheet';
    ob_start();
    ?>
    <div class="flex items-center">
        <label for="galaType" class="text-slate-400 text-xs font-bold px-2 uppercase">Gala:</label>
        <select id="galaType" onchange="updateProgramme()"
            class="bg-slate-900 text-white text-sm font-bold py-1.5 px-2 rounded-md border-none focus:ring-2 focus:ring-sky-500 outline-none cursor-pointer">
            <option value="round">League Round</option>
            <option value="final_a">A Final</option>
            <option value="final_b">B Final</option>
            <option value="final_c">C Final</option>
        </select>
    </div>
    <div id="roundSelectorContainer" class="flex items-center border-l border-slate-600 ml-1 pl-1">
        <label for="roundNum" class="text-slate-400 text-xs font-bold px-2 uppercase">Round:</label>
        <select id="roundNum" onchange="updateProgramme()"
            class="bg-slate-900 text-white text-sm font-bold py-1.5 px-2 rounded-md border-none focus:ring-2 focus:ring-sky-500 outline-none cursor-pointer w-16">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
        </select>
    </div>
    <?php
    $printable_doc_controls = ob_get_clean();
    include 'printable_doc_toolbar.php';
    ?>

    <main class="flex justify-center p-4 md:p-8">

        <div
            class="sheet-container glass-panel w-[210mm] min-h-[297mm] mx-auto p-10 md:p-[10mm] shadow-2xl rounded-sm bg-white print:block">

            <div class="mb-6">
                <h1 class="text-3xl font-black uppercase text-center tracking-tight text-slate-900 mb-6 print:mb-2">
                    Cotswold Swimming League
                    <span class="text-slate-500 font-bold text-xl block mt-1 print:text-base">
                        <?php echo $current_season_year; ?> Season - Official Timekeeper Record
                    </span>
                </h1>

                <div
                    class="info-grid grid grid-cols-2 gap-x-8 gap-y-4 border-2 border-slate-800 p-4 bg-slate-50 print:bg-white print:border-black">
                    <div class="flex items-end">
                        <span class="font-bold text-sm w-32 shrink-0">ROUND/FINAL:</span>
                        <div id="roundOutput"
                            class="border-b-2 border-slate-400 flex-grow h-6 text-lg leading-none font-bold text-slate-800 uppercase pl-2">
                        </div>
                    </div>
                    <div class="flex items-end">
                        <span class="font-bold text-sm w-32 shrink-0">DATE:</span>
                        <div class="border-b-2 border-slate-400 flex-grow h-6"></div>
                    </div>
                    <div class="flex items-end">
                        <span class="font-bold text-sm w-32 shrink-0">HOST TEAM:</span>
                        <div class="border-b-2 border-slate-400 flex-grow h-6"></div>
                    </div>
                    <div class="flex items-end">
                        <span class="font-bold text-sm w-32 shrink-0">LANE NUMBER:</span>
                        <div class="border-b-2 border-slate-400 flex-grow h-6"></div>
                    </div>
                    <div class="col-span-2 flex items-end">
                        <span class="font-bold text-sm w-32 shrink-0">OFFICIAL'S NAME:</span>
                        <div class="border-b-2 border-slate-400 flex-grow h-6"></div>
                    </div>
                </div>
            </div>

            <table class="w-full text-xs md:text-sm border-2 border-black mb-0">
                <thead>
                    <tr class="bg-slate-200 border-b-2 border-black">
                        <th class="w-[10%] border border-slate-400 p-2">Evt</th>
                        <th class="w-[20%] border border-slate-400 p-2">Age Group</th>
                        <th class="w-[45%] border border-slate-400 p-2">Event Description</th>
                        <th class="w-[25%] border border-slate-400 p-2">Time Recorded</th>
                    </tr>
                </thead>
                <tbody id="tableBody1" class="divide-y divide-black">
                </tbody>
            </table>
            <div id="emptyEventsMessage" class="hidden mt-4 border border-amber-300 bg-amber-50 text-amber-900 rounded-lg p-4 text-sm font-semibold">
                No event list has been configured for the active season yet.
            </div>

            <div class="page-footer text-center text-[10px] text-slate-400 mt-1">--- PAGE 1 ---</div>

            <div class="page-break"></div>

            <div class="print-header hidden print:block text-center mb-4 pt-4">
                <h2 class="text-lg font-bold uppercase">Official Timekeeper Record - Page 2</h2>
            </div>

            <table class="w-full text-xs md:text-sm mt-8 print:mt-0 border-2 border-black">
                <thead>
                    <tr class="bg-slate-200 border-b-2 border-black">
                        <th class="w-[10%] border border-slate-400 p-2">Evt</th>
                        <th class="w-[20%] border border-slate-400 p-2">Age Group</th>
                        <th class="w-[45%] border border-slate-400 p-2">Event Description</th>
                        <th class="w-[25%] border border-slate-400 p-2">Time Recorded</th>
                    </tr>
                </thead>
                <tbody id="tableBody2" class="divide-y divide-black">
                </tbody>
            </table>

            <div class="mt-8 border-2 border-slate-300 p-4 bg-slate-50 print:bg-white print:border-black rounded-lg">
                <h3 class="font-bold text-center underline mb-2">IMPORTANT TIMEKEEPER NOTES</h3>
                <ul class="list-disc pl-5 text-sm space-y-1">
                    <li><span class="font-bold text-red-600 print:text-black">NO DIVING</span> at the non-start end
                        during Rounds. All starts from that end must be in the water.</li>
                    <li>Please record the time clearly in the box provided.</li>
                    <li>If a mistake is made, cross it out and write clearly next to it.</li>
                    <li>Ensure manual slips (if used) are handed to the Chief Timekeeper promptly.</li>
                </ul>
            </div>

            <div class="text-center text-[10px] text-slate-400 mt-4">--- PAGE 2 ---</div>

        </div>
    </main>

    <script>
        lucide.createIcons();

        const events = <?php echo $timekeeper_events_json ?: '[]'; ?>;

        function updateProgramme() {
            const type = document.getElementById('galaType').value;
            const roundNum = document.getElementById('roundNum').value;
            const roundSelector = document.getElementById('roundSelectorContainer');
            const roundOutput = document.getElementById('roundOutput');
            const tb1 = document.getElementById('tableBody1');
            const tb2 = document.getElementById('tableBody2');
            const emptyMessage = document.getElementById('emptyEventsMessage');

            let displayText = "";

            // Logic to handle Type selection
            if (type === 'round') {
                // Show Round Selector
                roundSelector.style.display = 'flex';
                displayText = "Round " + roundNum;
            } else {
                // Hide Round Selector
                roundSelector.style.display = 'none';

                if (type === 'final_a') {
                    displayText = "A Final";
                } else if (type === 'final_b') {
                    displayText = "B Final";
                } else if (type === 'final_c') {
                    displayText = "C Final";
                }
            }

            // Update the box on the sheet
            roundOutput.textContent = displayText;

            // Clear tables
            tb1.innerHTML = '';
            tb2.innerHTML = '';
            emptyMessage.classList.toggle('hidden', events.length > 0);

            // Generate Table Rows
            events.forEach((evt) => {
                const fullDesc = type === 'final_a' ? evt.a_final_detail : evt.round_detail;

                // Styling logic
                const isEven = evt.id % 2 === 0;
                const bgClass = isEven ? 'bg-slate-50 print:bg-white' : '';

                const rowHtml = `
                    <tr class="${bgClass}">
                        <td class="text-center font-bold border-r border-black">${evt.id}</td>
                        <td class="text-center border-r border-black">${evt.age}</td>
                        <td class="border-r border-black pl-2">${fullDesc}</td>
                        <td></td>
                    </tr>
                `;

                if (evt.id <= 30) {
                    tb1.insertAdjacentHTML('beforeend', rowHtml);
                } else {
                    tb2.insertAdjacentHTML('beforeend', rowHtml);
                }
            });
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', updateProgramme);
    </script>
</body>

</html>
