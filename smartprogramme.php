<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League Coach Programme</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700&display=swap');

        body {
            font-family: 'Noto Sans', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
        }

        /* --- VISUAL STYLES (Screen) --- */
        .sheet-container {
            background: white;
            width: 210mm;
            min-height: 296mm;
            /* A4 height */
            margin: 20px auto;
            padding: 10mm 10mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Enforces strict column widths */
        }

        th {
            background-color: #003366 !important;
            color: white !important;
            padding: 2px;
            text-align: center;
            border: 1px solid #003366;
            font-size: 8pt;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        td {
            border: 1px solid #cbd5e1;
            padding: 0;
            height: 24px;
            /* Fixed row height */
            vertical-align: middle;
        }

        tr:nth-child(even) td {
            background-color: #f1f5f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .section-header {
            background-color: #e2e8f0 !important;
            color: #1e293b;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
            border-top: 1px solid #94a3b8;
            border-bottom: 1px solid #94a3b8;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* --- EDITABLE CELLS --- */
        .editable-cell {
            width: 100%;
            height: 100%;
            min-height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8.5pt;
            line-height: 1.1;
            text-align: center;
            outline: none;
            word-wrap: break-word;
            white-space: pre-wrap;
            padding: 1px 2px;
            box-sizing: border-box;
            cursor: text;
        }

        /* Visual cue on hover/focus */
        .editable-cell:hover {
            background-color: #eff6ff;
        }

        .editable-cell:focus {
            background-color: #fef08a;
            border: 1px solid #ca8a04;
        }

        /* Placeholder logic for Cannon event */
        .editable-cell[data-ph]:empty:before {
            content: attr(data-ph);
            color: #9ca3af;
        }

        /* Column Widths */
        .col-num {
            width: 5%;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }

        .col-event {
            width: 33%;
            font-size: 8pt;
            padding-left: 4px;
            text-align: left;
        }

        .col-limit {
            width: 9%;
            text-align: center;
            font-size: 7.5pt;
            color: #dc2626;
            font-weight: 600;
        }

        .col-swim {
            width: 13.25%;
        }

        /* Headers */
        .main-header {
            text-align: center;
            color: #003366;
            margin-bottom: 8px;
        }

        .main-header h1 {
            font-size: 18pt;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
        }

        .main-header h2 {
            font-size: 12pt;
            font-weight: 600;
            margin: 0;
            color: #1e293b;
        }

        .main-header p {
            font-size: 8pt;
            margin: 0;
            color: #64748b;
        }

        /* --- PRINT STYLES --- */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: white;
                margin: 0;
                padding: 0;
            }

            .no-print,
            #controlPanel {
                display: none !important;
                height: 0 !important;
                visibility: hidden !important;
            }

            .sheet-container {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                page-break-after: always;
                min-height: 0;
            }

            .page-break {
                page-break-before: always;
                margin-top: 0;
            }

            tr:nth-child(even) td {
                background-color: #f3f4f6 !important;
            }

            /* Hide placeholder text when printing */
            .editable-cell[data-ph]:empty:before {
                content: "";
            }
        }
    </style>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div id="controlPanel"
        class="no-print bg-yellow-50 border-b border-yellow-200 p-6 flex flex-col items-center justify-center gap-4 shadow-sm">
        <div class="text-center">
            <h3 class="text-lg font-bold text-yellow-900">Step 1: Upload Data</h3>
            <p class="text-sm text-yellow-800">Upload your Teamsheet Excel (.xlsx) or CSV file.</p>
        </div>

        <input type="file" id="fileInput" accept=".csv, .xlsx, .xls" class="block w-full max-w-sm text-sm text-slate-500
          file:mr-4 file:py-2 file:px-4
          file:rounded-full file:border-0
          file:text-sm file:font-semibold
          file:bg-blue-600 file:text-white
          hover:file:bg-blue-700
          cursor-pointer
        " />

        <div id="sheetSelectContainer" class="hidden flex flex-col items-center gap-2 w-full max-w-sm">
            <label class="font-bold text-blue-900">Step 2: Select Sheet</label>
            <select id="sheetSelector" class="w-full p-2 border border-blue-300 rounded shadow-sm"></select>
        </div>

        <button onclick="printProgramme()"
            class="bg-gray-800 text-white px-6 py-2 rounded-full hover:bg-gray-900 transition font-bold flex items-center gap-2">
            Print Programme
        </button>
    </div>

    <div class="sheet-container">
        <div class="main-header">
            <h1>THE COTSWOLD LEAGUE</h1>
            <h2 id="programmeTitle">Coach & Team Manager Programme 2026</h2>
            <p>Under SE Laws and SE Technical Rules &bull; Sponsored by Wyvern Swimwear</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-event">Event Detail</th>
                    <th class="col-limit">Limit</th>
                    <th class="col-swim">Swimmer 1</th>
                    <th class="col-swim">Swimmer 2</th>
                    <th class="col-swim">Swimmer 3</th>
                    <th class="col-swim">Swimmer 4</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="section-header">EVENTS 1 - 10</td>
                </tr>
                <tr>
                    <td class="col-num">1</td>
                    <td class="col-event">Girls 15/u 4x1 Ind. Medley</td>
                    <td class="col-limit">1.17.75</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">2</td>
                    <td class="col-event">Boys 15/u 4x1 Ind. Medley</td>
                    <td class="col-limit">1.10.39</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">3</td>
                    <td class="col-event">Girls Open 4x1 Ind. Medley</td>
                    <td class="col-limit">1.15.51</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">4</td>
                    <td class="col-event">Boys Open 4x1 Ind. Medley</td>
                    <td class="col-limit">1.06.34</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">5</td>
                    <td class="col-event">Girls 11/u 25m Freestyle</td>
                    <td class="col-limit">14.78</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">6</td>
                    <td class="col-event">Boys 11/u 25m Freestyle</td>
                    <td class="col-limit">14.81</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">7</td>
                    <td class="col-event">Girls 13/u 50m Breaststroke</td>
                    <td class="col-limit">41.70</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">8</td>
                    <td class="col-event">Boys 13/u 50m Breaststroke</td>
                    <td class="col-limit">41.80</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">9</td>
                    <td class="col-event">Girls 15/u 50m Backstroke</td>
                    <td class="col-limit">33.80</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">10</td>
                    <td class="col-event">Boys 15/u 50m Backstroke</td>
                    <td class="col-limit">33.10</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>

                <tr>
                    <td colspan="7" class="section-header">EVENTS 11 - 20</td>
                </tr>
                <tr>
                    <td class="col-num">11</td>
                    <td class="col-event">Girls Open 100m Butterfly</td>
                    <td class="col-limit">1.11.70</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">12</td>
                    <td class="col-event">Boys Open 100m Butterfly</td>
                    <td class="col-limit">1.01.90</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">13</td>
                    <td class="col-event">Girls 11/u 4x1 Medley Relay</td>
                    <td class="col-limit">1.09.33</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">14</td>
                    <td class="col-event">Boys 11/u 4x1 Medley Relay</td>
                    <td class="col-limit">1.08.69</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">15</td>
                    <td class="col-event">Girls 13/u 4x1 Freestyle Relay</td>
                    <td class="col-limit">57.67</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">16</td>
                    <td class="col-event">Boys 13/u 4x1 Freestyle Relay</td>
                    <td class="col-limit">55.07</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">17</td>
                    <td class="col-event">Girls 15/u 50m Breaststroke</td>
                    <td class="col-limit">38.50</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">18</td>
                    <td class="col-event">Boys 15/u 50m Breaststroke</td>
                    <td class="col-limit">37.40</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">19</td>
                    <td class="col-event">Girls Open 100m Backstroke</td>
                    <td class="col-limit">1.09.50</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">20</td>
                    <td class="col-event">Boys Open 100m Backstroke</td>
                    <td class="col-limit">1.03.40</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>

                <tr>
                    <td colspan="7" class="section-header">EVENTS 21 - 26</td>
                </tr>
                <tr>
                    <td class="col-num">21</td>
                    <td class="col-event">Girls 11/u 25m Butterfly</td>
                    <td class="col-limit">16.16</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">22</td>
                    <td class="col-event">Boys 11/u 25m Butterfly</td>
                    <td class="col-limit">16.39</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">23</td>
                    <td class="col-event">Girls 13/u 50m Freestyle</td>
                    <td class="col-limit">30.50</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">24</td>
                    <td class="col-event">Boys 13/u 50m Freestyle</td>
                    <td class="col-limit">30.60</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">25</td>
                    <td class="col-event">Girls 15/u 4x2 Medley Relay</td>
                    <td class="col-limit">2.12.90</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">26</td>
                    <td class="col-event">Boys 15/u 4x2 Medley Relay</td>
                    <td class="col-limit">2.02.60</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="sheet-container page-break">
        <div class="main-header">
            <h2 id="page2Title">Coach & Team Manager Programme (Page 2)</h2>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-event">Event Detail</th>
                    <th class="col-limit">Limit</th>
                    <th class="col-swim">Swimmer 1</th>
                    <th class="col-swim">Swimmer 2</th>
                    <th class="col-swim">Swimmer 3</th>
                    <th class="col-swim">Swimmer 4</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="section-header">EVENTS 27 - 30</td>
                </tr>
                <tr>
                    <td class="col-num">27</td>
                    <td class="col-event">Girls Open 4x2 Medley Relay</td>
                    <td class="col-limit">2.11.07</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">28</td>
                    <td class="col-event">Boys Open 4x2 Medley Relay</td>
                    <td class="col-limit">1.59.44</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">29</td>
                    <td class="col-event">Girls 11/u 25m Backstroke</td>
                    <td class="col-limit">18.12</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">30</td>
                    <td class="col-event">Boys 11/u 25m Backstroke</td>
                    <td class="col-limit">18.22</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>

                <tr>
                    <td colspan="7" class="section-header">EVENTS 31 - 40</td>
                </tr>
                <tr>
                    <td class="col-num">31</td>
                    <td class="col-event">Girls 13/u 50m Butterfly</td>
                    <td class="col-limit">35.00</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">32</td>
                    <td class="col-event">Boys 13/u 50m Butterfly</td>
                    <td class="col-limit">35.50</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">33</td>
                    <td class="col-event">Girls 15/u 50m Freestyle</td>
                    <td class="col-limit">29.30</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">34</td>
                    <td class="col-event">Boys 15/u 50m Freestyle</td>
                    <td class="col-limit">28.10</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">35</td>
                    <td class="col-event">Girls Open 100m Breaststroke</td>
                    <td class="col-limit">1.22.10</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">36</td>
                    <td class="col-event">Boys Open 100m Breaststroke</td>
                    <td class="col-limit">1.13.90</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">37</td>
                    <td class="col-event">Girls 11/u 4x1 Freestyle Relay</td>
                    <td class="col-limit">59.12</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">38</td>
                    <td class="col-event">Boys 11/u 4x1 Freestyle Relay</td>
                    <td class="col-limit">59.24</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">39</td>
                    <td class="col-event">Girls 13/u 4x1 Medley Relay</td>
                    <td class="col-limit">1.04.60</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">40</td>
                    <td class="col-event">Boys 13/u 4x1 Medley Relay</td>
                    <td class="col-limit">1.02.14</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>

                <tr>
                    <td colspan="7" class="section-header">EVENTS 41 - 53</td>
                </tr>
                <tr>
                    <td class="col-num">41</td>
                    <td class="col-event">Girls 15/u 50m Butterfly</td>
                    <td class="col-limit">32.50</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">42</td>
                    <td class="col-event">Boys 15/u 50m Butterfly</td>
                    <td class="col-limit">31.50</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">43</td>
                    <td class="col-event">Girls Open 100m Freestyle</td>
                    <td class="col-limit">1.02.10</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">44</td>
                    <td class="col-event">Boys Open 100m Freestyle</td>
                    <td class="col-limit">55.30</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">45</td>
                    <td class="col-event">Girls 11/u 25m Breaststroke</td>
                    <td class="col-limit">20.27</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">46</td>
                    <td class="col-event">Boys 11/u 25m Breaststroke</td>
                    <td class="col-limit">19.27</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">47</td>
                    <td class="col-event">Girls 13/u 50m Backstroke</td>
                    <td class="col-limit">36.60</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">48</td>
                    <td class="col-event">Boys 13/u 50m Backstroke</td>
                    <td class="col-limit">36.00</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true" style="background:#e5e7eb"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">49</td>
                    <td class="col-event">Girls 15/u 4x2 Freestyle Relay</td>
                    <td class="col-limit">1.58.80</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">50</td>
                    <td class="col-event">Boys 15/u 4x2 Freestyle Relay</td>
                    <td class="col-limit">1.49.20</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">51</td>
                    <td class="col-event">Girls Open 4x2 Freestyle Relay</td>
                    <td class="col-limit">1.57.21</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>
                <tr>
                    <td class="col-num">52</td>
                    <td class="col-event">Boys Open 4x2 Freestyle Relay</td>
                    <td class="col-limit">1.46.98</td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                    <td>
                        <div class="editable-cell" contenteditable="true"></div>
                    </td>
                </tr>

                <tr>
                    <td class="col-num" rowspan="2">53</td>
                    <td class="col-event" rowspan="2">8x25m Mixed Cannon<br><span
                            class="text-xs text-gray-500 font-normal">8 Swimmers (1 of each age/gender)</span></td>
                    <td class="col-limit" rowspan="2">N/A</td>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="G 11/u"></div>
                    </td>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="G 13/u"></div>
                    </td>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="G 15/u"></div>
                    </td>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="G Open"></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="B 11/u"></div>
                    </td>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="B 13/u"></div>
                    </td>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="B 15/u"></div>
                    </td>
                    <td>
                        <div class="editable-cell text-gray-400" contenteditable="true" data-ph="B Open"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const sheetSelectContainer = document.getElementById('sheetSelectContainer');
        const sheetSelector = document.getElementById('sheetSelector');
        const programmeTitle = document.getElementById('programmeTitle');
        const page2Title = document.getElementById('page2Title');

        let workbook = null;

        // 1. File Upload Listener
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const data = await file.arrayBuffer();
            workbook = XLSX.read(data, { type: 'array' });

            sheetSelector.innerHTML = '<option value="">-- Select a Sheet --</option>';
            workbook.SheetNames.forEach(sheetName => {
                const option = document.createElement('option');
                option.value = sheetName;
                option.textContent = sheetName;
                sheetSelector.appendChild(option);
            });

            sheetSelectContainer.classList.remove('hidden');

            if (workbook.SheetNames.length === 1) {
                sheetSelector.value = workbook.SheetNames[0];
                sheetSelector.dispatchEvent(new Event('change'));
            }
        });

        // 2. Sheet Selection Listener
        sheetSelector.addEventListener('change', (e) => {
            const sheetName = e.target.value;
            if (!sheetName || !workbook) return;

            const cleanName = sheetName.replace('.csv', '');
            programmeTitle.textContent = `Coach & Team Manager Programme - ${cleanName}`;
            page2Title.textContent = `Coach & Team Manager Programme - ${cleanName} (Page 2)`;

            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

            populateTable(jsonData);
        });

        // 3. Populate Logic
        function populateTable(rows) {
            // Clear all editable cells
            document.querySelectorAll('.editable-cell').forEach(cell => {
                cell.textContent = '';
                cell.classList.remove('text-gray-400');
            });

            rows.forEach(row => {
                const eventNum = row[0];
                const swimmers = row[2];

                if (!eventNum || isNaN(eventNum)) return;

                const numCells = Array.from(document.querySelectorAll('.col-num'));
                const targetCell = numCells.find(td => td.textContent.trim() == eventNum);

                if (targetCell) {
                    const tableRow = targetCell.parentElement;
                    const inputs = Array.from(tableRow.querySelectorAll('.editable-cell'));

                    if (!swimmers) return;

                    const namesList = swimmers.toString().split(',').map(n => n.trim());

                    if (eventNum == 53) {
                        const nextRow = tableRow.nextElementSibling;
                        const nextInputs = Array.from(nextRow.querySelectorAll('.editable-cell'));
                        const allCannonInputs = inputs.concat(nextInputs);

                        namesList.forEach((name, index) => {
                            if (allCannonInputs[index]) {
                                allCannonInputs[index].textContent = name;
                                allCannonInputs[index].classList.remove('text-gray-400');
                            }
                        });
                    } else {
                        namesList.forEach((name, index) => {
                            if (inputs[index]) inputs[index].textContent = name;
                        });
                    }
                }
            });
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

        function printProgramme() {
            trackAction('programme_generated');
            window.print();
        }
    </script>
</body>

</html>