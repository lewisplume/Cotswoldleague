<?php
include_once 'db.php';
include_once 'document_event_helpers.php';

$programme_events = cotswold_load_document_events($conn, $current_season_year);
$programme_events_json = json_encode($programme_events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spectator Programme | Cotswold League</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">

    <!-- Scripts -->
    <script src="assets/vendor/tailwindcss-3.4.17.js"></script>
    <script src="assets/vendor/lucide-1.31.0.min.js"></script>
    <script src="assets/vendor/html2pdf-0.10.1.bundle.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

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
                size: A4 portrait;
                margin: 5mm;
            }

            html,
            body {
                height: initial !important;
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            /* Hide UI elements */
            .no-print,
            nav,
            button,
            .controls-container {
                display: none !important;
            }

            .sheet-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: white !important;
                border: none !important;
            }

            /* Ensure background colors print */
            .print-bg-slate-50 {
                background-color: #f8fafc !important;
            }

            .print-bg-slate-100 {
                background-color: #f1f5f9 !important;
            }

            .print-bg-slate-200 {
                background-color: #e2e8f0 !important;
            }

            .print-bg-sky-50 {
                background-color: #f0f9ff !important;
            }

            .print-bg-sky-100 {
                background-color: #e0f2fe !important;
            }

            .print-text-sky-800 {
                color: #075985 !important;
            }

            .page-break {
                clear: both;
                page-break-before: always;
                break-before: page;
            }
        }

        /* Prevent rows and specific blocks from splitting across pages */
        tr,
        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Table Base Styles - Tightened for A4 fitting */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            /* Reduced slightly to ensure 30 rows fit A4 */
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 2px 4px;
            /* Tighter padding to save vertical space */
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .limit-col {
            color: #dc2626;
            font-weight: 700;
            text-align: center;
        }

        /* Write-in lines for parents */
        .write-in-col {
            border-bottom: 1px dashed #cbd5e1 !important;
            background-color: white !important;
        }

        .block-divider {
            border-bottom: 2px solid #94a3b8;
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-900 min-h-screen">
    <?php include 'nav.php'; ?>

    <?php
    $printable_doc_title = 'Spectator Programme';
    $printable_doc_print_label = 'Print';
    $printable_doc_back_href = (isset($_GET['from']) && $_GET['from'] === 'spectators')
        ? 'spectators.php'
        : 'teamportal.php#documents';
    ob_start();
    ?>
    <div class="flex items-center gap-3">
        <label class="text-slate-400 text-xs font-bold uppercase tracking-wider">Programme Type:</label>
        <select id="galaType" onchange="generateProgramme()"
            class="bg-slate-900 text-white text-base font-bold py-2 px-3 rounded-md border border-slate-600 focus:ring-2 focus:ring-sky-500 outline-none cursor-pointer">
            <option value="round">Rounds Programme</option>
            <option value="final_c">C Final Programme</option>
            <option value="final_b">B Final Programme</option>
            <option value="final_a">A Final Programme</option>
        </select>
    </div>
    <?php
    $printable_doc_controls = ob_get_clean();
    ob_start();
    ?>
    <button type="button" onclick="downloadPDF()" id="downloadBtn"
        class="bg-slate-700 hover:bg-slate-600 text-white px-5 py-2.5 rounded-full font-bold flex-1 md:flex-none flex items-center justify-center gap-2 transition-all text-sm uppercase tracking-wider">
        <i data-lucide="download" class="w-4 h-4"></i> Download PDF
    </button>
    <?php
    $printable_doc_extra_actions = ob_get_clean();
    include 'printable_doc_toolbar.php';
    ?>

    <!-- PRINTABLE SHEET -->
    <main class="flex justify-center p-4 md:p-8 transition-all print:block print:p-0 print:m-0">
        <!-- Added wrapper to isolate PDF sizing from screen padding -->
        <div id="programmeSheet"
            class="sheet-container bg-white w-[210mm] p-[10mm] print:p-0 shadow-2xl rounded-sm mx-auto overflow-hidden">

            <!-- Header -->
            <div class="text-center mb-2 block-divider pb-2 relative">
                <h1 class="text-3xl font-black uppercase tracking-tight text-slate-800">The Cotswold League</h1>
                <h2 id="programmeSubtitle" class="text-lg font-bold text-slate-500 mt-0.5 uppercase tracking-widest">
                    Rounds Programme - <?php echo $current_season_year; ?></h2>
                <div
                    class="mt-1.5 inline-block bg-sky-50 print-bg-sky-50 text-sky-800 print-text-sky-800 font-bold px-4 py-1 rounded-full text-xs border border-sky-200">
                    Official Programme Sponsored by Wyvern Swimwear
                </div>
            </div>

            <!-- Page 1 Events Container -->
            <div id="emptyProgrammeMessage" class="hidden border border-amber-200 bg-amber-50 text-amber-900 rounded-xl p-4 text-sm font-semibold">
                No event list has been configured for the active season yet.
            </div>
            <div id="programmePage1"></div>

            <div class="text-center text-[10px] text-slate-400 mt-1 print:mt-auto pb-1">--- PAGE 1 ---</div>

            <!-- IMPORTANT: html2pdf__page-break is required for the html2pdf library to respect breaks -->
            <div class="html2pdf__page-break page-break"></div>

            <!-- Header Page 2 -->
            <div class="hidden print:block text-center mb-1 pt-1 block-divider pb-1">
                <h2 class="text-xl font-black uppercase tracking-tight text-slate-800">The Cotswold League</h2>
                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Programme Continuation</h3>
            </div>

            <!-- Page 2 Events Container -->
            <div id="programmePage2"></div>

            <!-- Spectator Guide Footer -->
            <div
                class="mt-2 border border-slate-300 print-bg-slate-50 rounded-xl overflow-hidden bg-slate-50 avoid-break">
                <div class="bg-slate-200 border-b border-slate-300 p-1">
                    <h3
                        class="font-bold text-slate-700 text-center uppercase tracking-widest text-[11px] flex items-center justify-center gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> Spectator Information Guide
                    </h3>
                </div>
                <div class="p-2 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-[11px] leading-tight">
                    <div>
                        <h4 class="font-bold text-slate-800 border-b border-slate-300 pb-0.5 mb-1">Relay Events
                            Explained</h4>
                        <p class="mb-1"><strong class="text-sky-700">The Medley Relay:</strong> Four swimmers each swim
                            a different stroke in a specific order: Backstroke &rarr; Breaststroke &rarr; Butterfly
                            &rarr; Freestyle.</p>
                        <p><strong class="text-sky-700">The Cannon:</strong> The loudest event of the night! Event 53 is
                            a mixed 8-person freestyle relay featuring one boy and one girl from each age group (11/u,
                            13/u, 15/u, Open).</p>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 border-b border-slate-300 pb-0.5 mb-1">Rules & Scoring</h4>
                        <p class="mb-1"><strong class="text-red-600">Speeding Tickets (Time Limits):</strong> In this
                            league, swimming too fast can actually disqualify you! If a swimmer beats the "Time Limit"
                            shown, they receive no points.</p>
                        <p><strong class="text-sky-700">Race Distances:</strong> 25m = 1 Length | 50m = 2 Lengths | 100m
                            = 4 Lengths</p>
                    </div>
                    <div
                        class="md:col-span-2 border-t border-slate-300 pt-1 mt-0.5 text-center bg-white print-bg-white p-1 rounded shadow-sm">
                        <p class="font-bold text-slate-700 text-[11px]">Join the community on Facebook! Search <span
                                class="text-sky-600">"Cotswold Swimming League"</span> to share your gala photos and
                            celebrate our swimmers. 📸🏊‍♂️</p>
                    </div>
                </div>
            </div>

            <div class="text-center text-[10px] text-slate-400 mt-1 mb-1">--- PAGE 2 ---</div>
        </div>
    </main>

    <script>
        // Initialize Icons
        lucide.createIcons();

        const baseEvents = <?php echo $programme_events_json ?: '[]'; ?>;

        function generateProgramme() {
            const type = document.getElementById('galaType').value;
            const emptyMessage = document.getElementById('emptyProgrammeMessage');

            const activeSeasonYear = <?php echo (int)$current_season_year; ?>;
            let subtitle = "";
            let teamCount = 4; // Default to Rounds

            if (type === 'round') {
                subtitle = `Rounds Programme - ${activeSeasonYear} Season`;
                teamCount = 4;
            } else if (type === 'final_a') {
                subtitle = `A Final Programme - ${activeSeasonYear} Season`;
                teamCount = 8;
            } else if (type === 'final_b') {
                subtitle = `B Final Programme - ${activeSeasonYear} Season`;
                teamCount = 6;
            } else if (type === 'final_c') {
                subtitle = `C Final Programme - ${activeSeasonYear} Season`;
                teamCount = 6;
            }

            document.getElementById('programmeSubtitle').textContent = subtitle;
            emptyMessage.classList.toggle('hidden', baseEvents.length > 0);

            // Split events into chunks of 10 for score updates
            const chunksPage1 = [
                baseEvents.slice(0, 10),
                baseEvents.slice(10, 20),
                baseEvents.slice(20, 30)
            ];

            const chunksPage2 = [
                baseEvents.slice(30, 40),
                baseEvents.slice(40, 53) // Last block has 13 events
            ];

            document.getElementById('programmePage1').innerHTML = buildChunksHtml(chunksPage1, teamCount, type);
            document.getElementById('programmePage2').innerHTML = buildChunksHtml(chunksPage2, teamCount, type);
        }

        function buildChunksHtml(chunks, teamCount, type) {
            let html = '';

            chunks.forEach((chunk) => {
                if (chunk.length === 0) return;

                const firstEvt = chunk[0].id;
                const lastEvt = chunk[chunk.length - 1].id;

                html += `
                    <div class="mb-1.5 avoid-break">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="w-[6%] py-1">#</th>
                                    <th class="w-[44%] py-1">Event Detail</th>
                                    <th class="w-[15%] py-1 text-slate-500">Time Limit</th>
                                    <th class="w-[35%] py-1 text-slate-400 font-medium text-[9px] tracking-normal">Notes / My Swimmers</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                chunk.forEach((evt, index) => {
                    const desc = type === 'final_a' ? evt.a_final_desc : evt.round_desc;
                    const limit = type === 'final_a' ? evt.a_final_limit : evt.round_limit;

                    const rowBg = index % 2 === 0 ? 'print-bg-slate-50 bg-slate-50' : 'bg-white';

                    html += `
                        <tr class="${rowBg}">
                            <td class="text-center font-bold text-slate-700">${evt.id}</td>
                            <td class="font-bold text-slate-800">${desc}</td>
                            <td class="limit-col">${limit || '-'}</td>
                            <td class="write-in-col"></td>
                        </tr>
                    `;
                });

                // Add the Score Update Box specific to this chunk
                let scoreBoxes = '';
                for (let i = 1; i <= teamCount; i++) {
                    scoreBoxes += `
                        <div class="flex items-center gap-0.5">
                            <span class="text-[8px] text-slate-500 font-bold uppercase">T${i}</span>
                            <div class="w-6 h-4 bg-white border border-slate-300 rounded-sm"></div>
                        </div>
                    `;
                }

                html += `
                                <tr class="bg-slate-100 print-bg-slate-100 border-t-2 border-slate-300">
                                    <td colspan="4" class="py-1 px-2">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-slate-600 text-[9px] uppercase tracking-wider">Score Update (Evt ${firstEvt}-${lastEvt})</span>
                                            <div class="flex gap-1.5">
                                                ${scoreBoxes}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
            });

            return html;
        }

        function downloadPDF() {
            const btn = document.getElementById('downloadBtn');
            const originalContent = btn.innerHTML;

            // Show loading state
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Generating...';
            lucide.createIcons();

            const element = document.getElementById('programmeSheet');
            const mainContainer = document.querySelector('main');

            // Fix for html2pdf offset bug: temporarily remove the flexbox centering 
            // and margin/padding so it captures securely from the (0,0) coordinate.
            mainContainer.classList.remove('flex', 'justify-center', 'p-4', 'md:p-8');
            element.classList.remove('mx-auto', 'shadow-2xl');

            const opt = {
                margin: 0,
                filename: 'Cotswold_League_Spectator_Programme.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            // Generate and Download
            html2pdf().set(opt).from(element).save().then(() => {
                // Restore styling back to normal after generation
                mainContainer.classList.add('flex', 'justify-center', 'p-4', 'md:p-8');
                element.classList.add('mx-auto', 'shadow-2xl');

                // Restore button state
                btn.innerHTML = originalContent;
                lucide.createIcons();
            });
        }

        // Generate on load
        document.addEventListener('DOMContentLoaded', generateProgramme);

    </script>
</body>

</html>
