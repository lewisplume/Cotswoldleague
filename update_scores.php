<?php
include 'db.php';

// Simple Password Protection
$pass = "Cotswold2026Galas";
$authenticated = false;

if (isset($_POST['password']) && $_POST['password'] === $pass) {
    $authenticated = true;
}

// Handle Form Submission
if ($authenticated && isset($_POST['update_scores'])) {
    foreach ($_POST['scores'] as $club_id => $rounds) {
        $r1 = intval($rounds[1]);
        $r2 = intval($rounds[2]);
        $r3 = intval($rounds[3]);
        $r4 = intval($rounds[4]);
        
        $stmt = $conn->prepare("UPDATE results SET round_1=?, round_2=?, round_3=?, round_4=? WHERE club_id=?");
        $stmt->bind_param("iiiii", $r1, $r2, $r3, $r4, $club_id);
        $stmt->execute();
    }
    $message = "Scores Updated Successfully!";
}

// Fetch Current Data
$sql = "SELECT c.id, c.name, r.round_1, r.round_2, r.round_3, r.round_4 
        FROM results r JOIN clubs c ON r.club_id = c.id 
        ORDER BY c.name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Scores | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white p-8">

<?php if (!$authenticated): ?>
    <div class="max-w-md mx-auto bg-slate-800 p-8 rounded-xl shadow-lg mt-20 text-center">
        <h1 class="text-2xl font-bold mb-4">Score Entry Login</h1>
        <form method="POST">
            <input type="password" name="password" class="w-full bg-slate-700 p-3 rounded mb-4 text-white" placeholder="Password">
            <button type="submit" class="w-full bg-sky-600 p-3 rounded font-bold hover:bg-sky-500">Enter</button>
        </form>
    </div>
<?php else: ?>

    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Update Scores</h1>
            <a href="index.php" class="text-slate-400 hover:text-white">Back to Site</a>
        </div>

        <?php if (isset($message)): ?>
            <div class="bg-green-500/20 text-green-400 p-4 rounded mb-6 text-center font-bold">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="password" value="<?php echo $pass; ?>">
            <div class="bg-slate-800 rounded-xl overflow-hidden border border-slate-700">
                <table class="w-full text-left">
                    <thead class="bg-slate-900 text-slate-400 text-sm uppercase">
                        <tr>
                            <th class="p-4">Club Name</th>
                            <th class="p-4 w-24 text-center">R1</th>
                            <th class="p-4 w-24 text-center">R2</th>
                            <th class="p-4 w-24 text-center">R3</th>
                            <th class="p-4 w-24 text-center">R4</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-700/50">
                            <td class="p-4 font-bold"><?php echo $row['name']; ?></td>
                            <td class="p-2"><input type="number" name="scores[<?php echo $row['id']; ?>][1]" value="<?php echo $row['round_1']; ?>" class="w-full bg-slate-900 p-2 rounded text-center"></td>
                            <td class="p-2"><input type="number" name="scores[<?php echo $row['id']; ?>][2]" value="<?php echo $row['round_2']; ?>" class="w-full bg-slate-900 p-2 rounded text-center"></td>
                            <td class="p-2"><input type="number" name="scores[<?php echo $row['id']; ?>][3]" value="<?php echo $row['round_3']; ?>" class="w-full bg-slate-900 p-2 rounded text-center"></td>
                            <td class="p-2"><input type="number" name="scores[<?php echo $row['id']; ?>][4]" value="<?php echo $row['round_4']; ?>" class="w-full bg-slate-900 p-2 rounded text-center"></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <button type="submit" name="update_scores" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 rounded-xl shadow-lg transition-colors mt-6">
                Save All Scores
            </button>
        </form>
    </div>

<?php endif; ?>

</body>
</html>