<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Fetch Log
$logs = [];
$sql = "SELECT * FROM audit_log ORDER BY timestamp DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | Audit Log</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { background: rgba(15, 23, 42, 0.8); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="max-w-6xl mx-auto w-full px-4 py-8 flex-grow">
        
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <a href="admin.php" class="p-2 bg-slate-800 rounded-lg hover:bg-slate-700 transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-slate-400"></i>
                </a>
                System <span class="text-sky-500">Audit Log</span>
            </h1>
            <div class="text-end">
                 <p class="text-xs text-slate-500 uppercase tracking-widest">Logged in as Admin</p>
            </div>
        </div>

        <div class="glass-panel rounded-2xl overflow-hidden border border-white/5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-400">
                    <thead class="bg-slate-900/50 text-xs uppercase text-slate-300 font-bold border-b border-white/5">
                        <tr>
                            <th class="px-6 py-4">Date/Time</th>
                            <th class="px-6 py-4">User / Club</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">No activity recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-sky-400">
                                        <?php echo date('d M Y H:i', strtotime($log['timestamp'])); ?>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-white">
                                        <?php echo htmlspecialchars($log['club_name']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-sky-500/10 text-sky-400 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider border border-sky-500/20">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-300 text-xs leading-relaxed max-w-md">
                                        <?php echo htmlspecialchars($log['change_details']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
