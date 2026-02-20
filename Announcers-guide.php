<?php
require_once 'db.php';
$clubs = [];
$c_res = $conn->query("SELECT name FROM clubs ORDER BY name ASC");
if ($c_res) {
    while ($row = $c_res->fetch_assoc()) {
        $clubs[] = htmlspecialchars($row['name']);
    }
}
$clubOptions = '<option value="">[Select Club]</option>';
foreach ($clubs as $c) {
    $clubOptions .= '<option value="' . $c . '">' . $c . '</option>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League - Announcer Script Generator</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        :root {
            --primary-color: #005f73;
            --secondary-color: #0a9396;
            --background-color: #f4f9f9;
            --text-color: #333;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: var(--primary-color);
        }
        h1 {
            text-align: center;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: var(--primary-color);
        }
        .btn-print {
            background-color: #e9c46a;
            color: #333;
            margin-left: 10px;
        }
        .btn-print:hover {
            background-color: #f4a261;
        }
        #generated-script {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #fafafa;
            border-radius: 8px;
            display: none;
        }
        .script-section {
            margin-bottom: 25px;
        }
        .script-notes {
            font-style: italic;
            color: #666;
            background: #e9ecef;
            padding: 5px 10px;
            border-left: 4px solid var(--secondary-color);
        }
        .fill-blank {
            border-bottom: 1px solid #333;
            display: inline-block;
            min-width: 50px;
        }

        /* Print Specific Styling */
        @media print {
            body {
                background-color: white;
            }
            #setup-form, .btn-print, h1.page-title {
                display: none !important;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
            #generated-script {
                display: block !important;
                border: none;
                background-color: white;
            }
            .script-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

<div class="container">
    <h1 class="page-title">Cotswold League Announcer Script Generator 🏊‍♂️</h1>
    
    <div id="setup-form">
        <p>Welcome! Please fill in the details below to generate your custom printable script for tonight's gala. Let's get ready to make some noise for our young swimmers! 📣</p>
        
        <div class="form-group">
            <label for="poolName">Name of Pool / Leisure Centre:</label>
            <input type="text" id="poolName" placeholder="e.g., Stratford Park Leisure Centre">
        </div>
        <div class="form-group">
            <label for="roundNum">Round Number:</label>
            <select id="roundNum" onchange="updateFormDisplay()">
                <option value="Round 1">Round 1</option>
                <option value="Round 2">Round 2</option>
                <option value="Round 3">Round 3</option>
                <option value="Round 4">Round 4</option>
                <option value="C Final">C Final</option>
                <option value="B Final">B Final</option>
                <option value="A Final">A Final</option>
            </select>
        </div>
        <div class="form-group">
            <label for="announcerName">Announcer's Name:</label>
            <input type="text" id="announcerName" placeholder="e.g., John Smith">
        </div>
        <div class="form-group" id="group-host1">
            <label for="hostClub">Host Club 1:</label>
            <select id="hostClub">
                <?php echo $clubOptions; ?>
            </select>
        </div>
        <div class="form-group" id="group-host2" style="display: none;">
            <label for="hostClub2">Host Club 2:</label>
            <select id="hostClub2">
                <?php echo $clubOptions; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="visitingClub1">Visiting Club 1:</label>
            <select id="visitingClub1"><?php echo $clubOptions; ?></select>
        </div>
        <div class="form-group">
            <label for="visitingClub2">Visiting Club 2:</label>
            <select id="visitingClub2"><?php echo $clubOptions; ?></select>
        </div>
        <div class="form-group">
            <label for="visitingClub3">Visiting Club 3:</label>
            <select id="visitingClub3"><?php echo $clubOptions; ?></select>
        </div>
        <div class="form-group" id="group-vis4" style="display: none;">
            <label for="visitingClub4">Visiting Club 4:</label>
            <select id="visitingClub4"><?php echo $clubOptions; ?></select>
        </div>
        <div class="form-group" id="group-vis5" style="display: none;">
            <label for="visitingClub5">Visiting Club 5:</label>
            <select id="visitingClub5"><?php echo $clubOptions; ?></select>
        </div>
        <div class="form-group" id="group-vis6" style="display: none;">
            <label for="visitingClub6">Visiting Club 6:</label>
            <select id="visitingClub6"><?php echo $clubOptions; ?></select>
        </div>
        <div class="form-group">
            <label for="refereeName">Referee's Name:</label>
            <input type="text" id="refereeName" placeholder="e.g., Jane Doe">
        </div>

        <button class="btn" onclick="generateScript()">Generate Script</button>
    </div>

    <div id="generated-script">
        <h2>Cotswold Swimming League 2026: Announcer’s Guide & Script</h2>
        <p><strong>Welcome to the Cotswold League!</strong> As the announcer, you are the voice of the gala. Your role is to keep the event moving, keep the spectators informed, and—most importantly—create a positive, encouraging atmosphere for the children.</p>
        <button class="btn btn-print" onclick="window.print()">🖨️ Print Script</button>
        <hr>

        <div id="script-content"></div>
    </div>
</div>

<script>
    function updateFormDisplay() {
        const round = document.getElementById('roundNum').value;
        const hosts = (round === 'A Final' || round === 'B Final' || round === 'C Final') ? 2 : 1;
        const visitors = (round === 'A Final') ? 6 : (round === 'B Final' || round === 'C Final') ? 4 : 3;

        document.getElementById('group-host2').style.display = hosts >= 2 ? 'block' : 'none';
        document.getElementById('group-vis4').style.display = visitors >= 4 ? 'block' : 'none';
        document.getElementById('group-vis5').style.display = visitors >= 6 ? 'block' : 'none';
        document.getElementById('group-vis6').style.display = visitors >= 6 ? 'block' : 'none';
    }

    // Call once on load
    document.addEventListener('DOMContentLoaded', updateFormDisplay);

    function generateScript() {
        // Get values
        const pool = document.getElementById('poolName').value || '[Name of Pool]';
        const round = document.getElementById('roundNum').value || '[Round]';
        const announcer = document.getElementById('announcerName').value || '[Your Name]';
        const referee = document.getElementById('refereeName').value || '[Referee Name]';

        let maxLanes = 4;
        if (round === 'A Final') maxLanes = 8;
        else if (round === 'B Final' || round === 'C Final') maxLanes = 6;

        let activeClubs = [];
        const h1 = document.getElementById('hostClub').value;
        if (h1) activeClubs.push(h1);
        if (maxLanes >= 6) {
            const h2 = document.getElementById('hostClub2').value;
            if (h2) activeClubs.push(h2);
        }
        for (let i = 1; i <= 6; i++) {
            if (i <= 3 || (maxLanes >= 6 && i === 4) || (maxLanes === 8 && i >= 5)) {
                let v = document.getElementById('visitingClub' + i).value;
                if (v) activeClubs.push(v);
            }
        }
        
        let clubsLi = '';
        activeClubs.forEach(c => {
            clubsLi += `<li><strong>${c}</strong></li>\n`;
        });
        if (clubsLi === '') clubsLi = '<li><strong>[Clubs list will appear here]</strong></li>';
        
        const numClubsStr = activeClubs.length > 0 ? activeClubs.length.toString() : "[number of]";

        let lanesLi = '';
        for (let i = 1; i <= maxLanes; i++) {
            lanesLi += `<li>Lane ${i}: _________________________________</li>\n`;
        }

        // Build HTML Script
        const scriptHTML = `
            <div class="script-section">
                <h3>1. Pre-Gala Welcomes</h3>
                <p class="script-notes">(Announce 10-15 minutes before warm-up starts)</p>
                <p>"Good afternoon/evening everyone, and a very warm welcome to <strong>${pool}</strong> for <strong>${round}</strong> of the 2026 Cotswold Swimming League.</p>
                <p>We are delighted to host tonight’s gala. I am <strong>${announcer}</strong>, and I’ll be your announcer for the evening. We have ${numClubsStr} fantastic clubs competing today. Please give a warm welcome to:</p>
                <ul>
                    ${clubsLi}
                </ul>
                <p>The Cotswold League is all about fun, sportsmanship, and giving our younger and less experienced swimmers a chance to shine. Let’s make sure we cheer loudly for every single swimmer in the water tonight!"</p>
            </div>

            <div class="script-section">
                <h3>2. Warm-Up Information</h3>
                <p class="script-notes">(Fill in the blanks below after the random lane draw is conducted on the night!)</p>
                <p>"We are about to begin the warm-up. Following the random lane draw conducted earlier, the lane assignments for the evening are as follows:</p>
                <ul>
                    ${lanesLi}
                </ul>
                <p>Warm-up will last for 30 minutes. Coaches, please ensure your swimmers are aware that diving is only permitted in designated sprint lanes. Over to the coaches for the warm-up."</p>
                <p class="script-notes">Coaches control their own lanes during warm-up, nothing further to do for 30 minutes.</p>
            </div>

            <div class="script-section">
                <h3>3. Mandatory Safety & Photography Notices</h3>
                <p class="script-notes">(Announce immediately before the first event)</p>
                <p><strong>Safety Notice:</strong><br>
                "A few quick safety reminders from the pool management: <em>[Read the specific pool rules provided by the leisure centre staff on the night]</em>. In the unlikely event of an emergency, please follow the instructions of the lifeguards and leisure centre staff. The fire exits are located [Point them out]."</p>
                <p><strong>Swim England Photography Policy:</strong><br>
                "In line with Swim England’s Wavepower policy, we would like to remind all spectators that photography and video recording are permitted for personal use only. Please ensure you are only focusing on your own child. If you have any concerns regarding photography, please speak to the Gala Refreshments/Front Desk team or the Lead Official. If you are posting to social media, please remember to celebrate the efforts of all our swimmers!"</p>
            </div>

            <div class="script-section">
                <h3>4. The Racing Script</h3>
                <p class="script-notes">Format to repeat for each race:</p>
                <p>"Event <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;</span>, we have the <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Age Group), <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Gender), <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Distance), <span class="fill-blank">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> (Stroke), over to you Mr/Madam Referee."</p>
                <p><em>Example: "Event 1, we have the Girls 15 & Under 4 by 1 Individual Medley. Over to you, Referee."</em></p>
                <p class="script-notes">Check with the referee about when to ask swimmers to clear the pool.</p>
            </div>

            <div class="script-section">
                <h3>5. Scoring Updates</h3>
                <p class="script-notes">(Announce every 10 events. Write scores in below during the gala!)</p>
                <p>"That brings us to the end of Event 10 / 20 / 30 / 40. Here are the current points standings:</p>
                <p>In 4th place with _______ points: ________________________</p>
                <p>In 3rd place with _______ points: ________________________</p>
                <p>In 2nd place with _______ points: ________________________</p>
                <p>And currently in 1st place with _______ points: ________________________</p>
                <p>Keep up the great swimming, everyone!"</p>
            </div>

            <div class="script-section">
                <h3>6. Raffle Announcement</h3>
                <p class="script-notes">(Best announced around Event 30, typically before or after the 25m races)</p>
                <p>"While our officials check the latest scores, it’s time to announce the winners of our raffle! Thank you to everyone who purchased a ticket; your support helps <strong>${host}</strong> continue to provide great opportunities for our young swimmers.</p>
                <p>The winning numbers are...</p>
                <p>Ticket: _________ - Prize: _________________________</p>
                <p>Ticket: _________ - Prize: _________________________</p>
                <p>Ticket: _________ - Prize: _________________________</p>
                <p>Please come to the front desk at the end of the gala to collect your prizes!"</p>
            </div>

            <div class="script-section">
                <h3>7. Final Results & Closing</h3>
                <p>"That concludes the racing for tonight! A huge well done to every swimmer. Before we announce the final results, a few thank yous.</p>
                <p>Thank you to our Referee, <strong>${referee}</strong>, and all the officials and timekeepers who volunteered their time. Thank you to the staff here at <strong>${pool}</strong>, and of course, to all the parents and coaches for your support.</p>
                <p>And now, the final results for <strong>${round}</strong> of the 2026 Cotswold League:</p>
                <p>4th Place: ________________________ with _______ points.</p>
                <p>3rd Place: ________________________ with _______ points.</p>
                <p>2nd Place: ________________________ with _______ points.</p>
                <p>1st Place: ________________________ with _______ points.</p>
                <p>Congratulations to ________________________! Safe travels home everyone, and we look forward to seeing you next time!"</p>
            </div>
        `;

        document.getElementById('script-content').innerHTML = scriptHTML;
        document.getElementById('generated-script').style.display = 'block';
    }
</script>

</body>
</html>