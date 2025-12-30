<?php
include 'db.php';

// Fetch Results sorted by Total Points (Descending)
// This SQL ensures the league table is already sorted by position before the page loads
$sql = "SELECT c.name, r.round_1, r.round_2, r.round_3, r.round_4, 
       (r.round_1 + r.round_2 + r.round_3 + r.round_4) as total 
       FROM results r 
       JOIN clubs c ON r.club_id = c.id 
       ORDER BY total DESC, c.name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024">
    <title>Cotswold League | League Table</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.05); }
        .table-row-hover:hover { background-color: rgba(255, 255, 255, 0.03); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="py-12 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            League <span class="text-sky-500">Table</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto">
            Season standings and progression towards the Finals.
        </p>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 flex-grow">
        <div class="glass-panel backdrop-blur-md p-4 rounded-2xl mb-8 flex items-start gap-4 border-l-4 border-l-sky-500">
            <div class="bg-sky-500/10 p-2 rounded-lg">
                <i data-lucide="info" class="text-sky-500 w-5 h-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-sm">Update Schedule</h3>
                <p class="text-xs text-slate-400 leading-relaxed mt-1">
                    The league table is updated following each of the four rounds. Standings refresh on the <span class="text-sky-400 font-semibold">Sunday following the Saturday gala</span>.
                </p>
            </div>
        </div>

        <div class="glass-panel backdrop-blur-md rounded-3xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-900/50 border-b border-white/5">
                            <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500">Pos</th>
                            <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500">Club Name</th>
                            <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R1</th>
                            <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R2</th>
                            <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R3</th>
                            <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R4</th>
                            <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-sky-500 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php 
                        $pos = 1;
                        $a_final = [];
                        $b_final = [];
                        $c_final = [];

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                if ($pos <= 8) {
                                    $a_final[] = $row;
                                } elseif ($pos <= 14) {
                                    $b_final[] = $row;
                                } else {
                                    $c_final[] = $row;
                                }
                                echo '<tr class="table-row-hover transition-colors">';
                                echo '<td class="px-6 py-4 text-sm font-medium text-slate-500 italic">' . $pos++ . '</td>';
                                echo '<td class="px-6 py-4 text-sm font-bold text-white">' . $row["name"] . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-slate-600 text-center">' . ($row["round_1"] > 0 ? $row["round_1"] : '-') . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-slate-600 text-center">' . ($row["round_2"] > 0 ? $row["round_2"] : '-') . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-slate-600 text-center">' . ($row["round_3"] > 0 ? $row["round_3"] : '-') . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-slate-600 text-center">' . ($row["round_4"] > 0 ? $row["round_4"] : '-') . '</td>';
                                // MODIFIED LINE: Added count-up class, data-target, and set initial text to 0
                                echo '<td class="px-6 py-4 text-sm font-black text-sky-500 text-center count-up" data-target="' . $row["total"] . '">0</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50"></div>
                    <h4 class="font-bold text-sm">A Final</h4>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach($a_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span><?php echo $t['name']; ?></span>
                        <span class="font-bold text-emerald-400"><?php echo $t['total']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-2 h-2 rounded-full bg-amber-500 shadow-lg shadow-amber-500/50"></div>
                    <h4 class="font-bold text-sm">B Final</h4>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach($b_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span><?php echo $t['name']; ?></span>
                        <span class="font-bold text-amber-400"><?php echo $t['total']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-2 h-2 rounded-full bg-rose-500 shadow-lg shadow-rose-500/50"></div>
                    <h4 class="font-bold text-sm">C Final</h4>
                </div>
                <ul class="space-y-2 mt-4">
                    <?php foreach($c_final as $t): ?>
                    <li class="flex justify-between text-xs text-slate-300 border-b border-white/5 pb-1 last:border-0">
                        <span><?php echo $t['name']; ?></span>
                        <span class="font-bold text-rose-400"><?php echo $t['total']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em] py-8">
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        lucide.createIcons();
        
        // Navigation Menu Logic
        const menuBtn = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        // Check if elements exist before adding event listener to avoid errors
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                const isHidden = mobileMenu.classList.contains('hidden');
                if (isHidden) {
                    mobileMenu.classList.remove('hidden');
                    if(menuIcon) menuIcon.classList.add('hidden');
                    if(closeIcon) closeIcon.classList.remove('hidden');
                } else {
                    mobileMenu.classList.add('hidden');
                    if(menuIcon) menuIcon.classList.remove('hidden');
                    if(closeIcon) closeIcon.classList.add('hidden');
                }
            });
        }

        // NEW: Number Count-Up Animation
        document.addEventListener('DOMContentLoaded', () => {
            const counters = document.querySelectorAll('.count-up');
            // speed determines how fast the count happens (lower = faster)
            const speed = 200; 

            counters.forEach(counter => {
                const updateCount = () => {
                    // Get the target number from data attribute
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;
                    
                    // Calculate increment 
                    const inc = Math.max(1, Math.ceil(target / speed)); 

                    if (count < target) {
                        // Add increment and run function again
                        counter.innerText = count + inc > target ? target : count + inc;
                        setTimeout(updateCount, 15);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            });
        });
    </script>
</body>
</html>