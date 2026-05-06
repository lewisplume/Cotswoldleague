<?php include_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League - Host Team Officials Sign-In</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        /* Restored Original Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f0f2f5;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background-color: white;
            /* White sheet effect */
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }

        h1 {
            margin: 0;
            color: #0056b3;
            text-transform: uppercase;
            font-size: 24px;
            letter-spacing: 1px;
        }

        h2 {
            margin: 5px 0 0 0;
            font-weight: 400;
            font-size: 18px;
            color: #555;
        }

        .gala-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: bold;
        }

        .gala-info div {
            border-bottom: 1px solid #999;
            padding-bottom: 5px;
            min-width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
            font-size: 14px;
        }

        th {
            background-color: #f0f4f8;
            font-weight: bold;
            color: #0056b3;
            text-align: center;
            white-space: nowrap;
        }

        .role-col {
            width: 16%;
            font-weight: bold;
        }

        .name-col {
            width: 22%;
        }

        .club-col {
            width: 16%;
        }

        .se-col {
            width: 16%;
        }

        /* New column for SE Number */
        .checkbox-col {
            width: 10%;
            text-align: center;
            font-size: 11px;
            color: #555;
        }

        .checkbox-box {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #333;
            vertical-align: middle;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            font-style: italic;
        }

        .notes {
            margin-top: 15px;
            font-size: 12px;
        }

        .hidden-row {
            display: none;
        }

        /* Print Styles - Crucial for fitting on one page */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0.5cm;
            }

            body {
                padding: 0;
                margin: 0;
                background-color: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .container {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }

            th {
                background-color: #eee !important;
            }

            /* Hiding the Nav bar in print */
            nav.no-print,
            .no-print {
                display: none !important;
            }

            /* Scale Header */
            h1 {
                font-size: 22pt;
                margin-bottom: 5px;
            }

            h2 {
                font-size: 16pt;
                margin-top: 0;
                margin-bottom: 15px;
            }

            header {
                margin-bottom: 15px;
                padding-bottom: 10px;
            }

            .gala-info {
                margin-bottom: 20px;
                font-size: 12pt;
            }

            /* Scale Table */
            table {
                width: 100%;
                font-size: 12pt;
                /* Increased font size to fill width */
            }

            th,
            td {
                padding: 4px 4px;
                height: 27px;
                /* Reduced to fit 8-lane finals on one page */
                border: 1px solid #000;
            }

            .checkbox-col {
                font-size: 10pt;
            }

            .checkbox-box {
                width: 16px;
                height: 16px;
                border: 1.5px solid #000;
            }

            .footer {
                margin-top: 20px;
                font-size: 10pt;
                position: fixed;
                bottom: 10mm;
                left: 0;
                right: 0;
            }

            .notes {
                margin-top: 5px;
                font-size: 11pt;
            }

            /* Ensure columns sums to 100% */
            .role-col {
                width: 17%;
            }

            .name-col {
                width: 23%;
            }

            .club-col {
                width: 20%;
            }

            .se-col {
                width: 15%;
            }

            /* Checkbox cols take remaining 25% */
        }
    </style>
</head>

<body>
    <?php include 'nav.php'; ?>

    <!-- New Navigation Bar (Tailwind Styled) -->
    <div
        class="no-print border-b border-slate-800 bg-slate-900/95 backdrop-blur-md sticky top-16 left-0 right-0 z-40 min-h-[70px]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
            <div class="flex flex-col gap-3 py-3 md:flex-row md:items-center md:justify-between md:min-h-[70px]">
                <!-- Left: Home Link and Title -->
                <div class="flex items-center gap-4">
                    <a href="admin.php" class="text-white hover:text-sky-400 transition-colors">
                        <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </a>
                    <span class="text-white font-bold text-lg hidden md:block">Officials Sign-In</span>
                </div>

                <!-- Center: Controls -->
                <div
                    class="controls-container flex flex-wrap items-center gap-2 bg-slate-800 rounded-lg p-1 border border-slate-700">
                    <div class="flex items-center">
                        <label for="galaType" class="text-slate-400 text-xs font-bold px-2 uppercase">Gala:</label>
                        <select id="galaType" onchange="updateSheet()"
                            class="bg-slate-900 text-white text-sm font-bold py-1.5 px-2 rounded-md border-none focus:ring-2 focus:ring-sky-500 outline-none cursor-pointer">
                            <option value="round">League Round</option>
                            <option value="final_a">A Final (8 Lanes)</option>
                            <option value="final_b">B Final (6 Lanes)</option>
                            <option value="final_c">C Final (6 Lanes)</option>
                        </select>
                    </div>

                    <div id="roundSelectorContainer" class="flex items-center border-l border-slate-600 ml-1 pl-1">
                        <label for="roundNum" class="text-slate-400 text-xs font-bold px-2 uppercase">Round:</label>
                        <select id="roundNum" onchange="updateSheet()"
                            class="bg-slate-900 text-white text-sm font-bold py-1.5 px-2 rounded-md border-none focus:ring-2 focus:ring-sky-500 outline-none cursor-pointer w-16">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                </div>

                <!-- Right: Print Button -->
                <button onclick="window.print()"
                    class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded-full font-bold shadow-lg shadow-sky-500/30 flex items-center gap-2 transition-all">
                    <i data-lucide="printer" class="w-4 h-4"></i> <span class="hidden sm:inline">Print Form</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Restored Content Structure -->
    <div class="container">
        <!-- Original Controls Removed (Use Nav Bar above) -->

        <header>
            <h1>Cotswold Swimming League <?php echo $current_season_year; ?></h1>
            <h2>Host Team Officials Sign-In List</h2>
        </header>

        <div class="gala-info">
            <div id="roundLabel">Round Number: <span id="roundValue">1</span></div>
            <div>Host Team: </div>
            <div>Date: </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="role-col">Role</th>
                    <th class="name-col">Name (Please Print)</th>
                    <th class="club-col">Club Representing</th>
                    <th class="se-col">SE Number<br><span style="font-weight:normal; font-size: 0.8em">(If
                            Qualified/Trainee)</span></th>
                    <th class="checkbox-col">Qualified</th>
                    <th class="checkbox-col">Trainee</th>
                    <th class="checkbox-col">Non Qual</th>
                </tr>
            </thead>
            <tbody>
                <!-- Key Officials -->
                <tr>
                    <td>Referee</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr>
                    <td>Starter</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr>
                    <td>Chief Timekeeper</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>

                <!-- Timekeepers -->
                <tr class="tk-row">
                    <td>Timekeeper 1</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="tk-row">
                    <td>Timekeeper 2</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="tk-row">
                    <td>Timekeeper 3</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="tk-row">
                    <td>Timekeeper 4</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="tk-row hidden-row">
                    <td>Timekeeper 5</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="tk-row hidden-row">
                    <td>Timekeeper 6</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="tk-row hidden-row">
                    <td>Timekeeper 7</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="tk-row hidden-row">
                    <td>Timekeeper 8</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>

                <!-- Stroke Judges -->
                <tr>
                    <td>Stroke Judge 1</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr>
                    <td>Stroke Judge 2</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>

                <!-- Turn Judges -->
                <tr>
                    <td>Turn Judge 1</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr>
                    <td>Turn Judge 2</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr>
                    <td>Turn Judge 3</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr>
                    <td>Turn Judge 4</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>

                <!-- Others -->
                <tr class="other-row">
                    <td>Other / Reserve 1</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="other-row">
                    <td>Other / Reserve 2</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="other-row">
                    <td>Other / Reserve 3</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="other-row">
                    <td>Other / Reserve 4</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="other-row hidden-row">
                    <td>Other / Reserve 5</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="other-row hidden-row">
                    <td>Other / Reserve 6</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="other-row hidden-row">
                    <td>Other / Reserve 7</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
                <tr class="other-row hidden-row">
                    <td>Other / Reserve 8</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                    <td class="checkbox-col"><span class="checkbox-box"></span></td>
                </tr>
            </tbody>
        </table>

        <div class="notes">
            <strong>Note to Host Team:</strong> Please ensure all officials sign in before the start of the warm-up.
            This list must be retained by the Referee or Promoter for insurance purposes.
        </div>

        <div class="footer">
            Thank you for volunteering! Your support makes the Cotswold League possible for our swimmers.
        </div>
    </div>

    <script>
        lucide.createIcons();

        function updateSheet() {
            const galaType = document.getElementById('galaType').value;
            const roundNum = document.getElementById('roundNum').value;
            const roundSelector = document.getElementById('roundSelectorContainer');
            const roundLabel = document.getElementById('roundLabel');

            const tkRows = document.querySelectorAll('.tk-row');
            const otherRows = document.querySelectorAll('.other-row');

            let tkCount = 4;
            let otherCount = 4;
            let labelText = "Round Number: <span id='roundValue'>" + roundNum + "</span>";

            if (galaType === 'round') {
                roundSelector.style.display = 'flex'; // Adjusted to flex for Tailwind
                tkCount = 4;
                otherCount = 4;
                labelText = "Round Number: <span id='roundValue'>" + roundNum + "</span>";
            } else {
                roundSelector.style.display = 'none';
                if (galaType === 'final_a') {
                    tkCount = 8;
                    otherCount = 8;
                    labelText = "Final: A Final";
                } else if (galaType === 'final_b') {
                    tkCount = 6;
                    otherCount = 6;
                    labelText = "Final: B Final";
                } else if (galaType === 'final_c') {
                    tkCount = 6;
                    otherCount = 6;
                    labelText = "Final: C Final";
                }
            }

            roundLabel.innerHTML = labelText;

            // Update Timekeeper Rows
            tkRows.forEach((row, index) => {
                if (index < tkCount) {
                    row.classList.remove('hidden-row');
                } else {
                    row.classList.add('hidden-row');
                }
            });

            // Update Other Rows
            otherRows.forEach((row, index) => {
                if (index < otherCount) {
                    row.classList.remove('hidden-row');
                } else {
                    row.classList.add('hidden-row');
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', updateSheet);
    </script>

</body>

</html>
