<?php
// create_match.php
require_once 'config.php';

$success = "";
$error = "";

// Fetch all active players for dropdowns
$stmt = $pdo->query("SELECT id, name, rating FROM players WHERE is_active = 1 ORDER BY name ASC");
$players = $stmt->fetchAll();

// Create a simple array keyed by ID for quick lookup in PHP
$playersById = [];
foreach ($players as $p) {
    $playersById[$p['id']] = $p;
}

// Max allowed rating difference
$MAX_DIFF = 120;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $p1 = (int)($_POST["player1_id"] ?? 0);
    $p2 = (int)($_POST["player2_id"] ?? 0);

    if ($p1 === 0 || $p2 === 0) {
        $error = "Please select both players.";
    } elseif ($p1 === $p2) {
        $error = "Player 1 and Player 2 must be different.";
    } elseif (!isset($playersById[$p1]) || !isset($playersById[$p2])) {
        $error = "Invalid players selected.";
    } else {
        // 🔴 BACKEND RATING CHECK (important)
        $r1 = (float)$playersById[$p1]['rating'];
        $r2 = (float)$playersById[$p2]['rating'];
        $diff = abs($r1 - $r2);

        if ($diff > $MAX_DIFF) {
            $error = "Rating difference too high (" . round($diff) . "). 
                      Players must be within ±$MAX_DIFF rating.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO matches (player1_id, player2_id)
                    VALUES (:p1, :p2)
                ");
                $stmt->execute([
                    ':p1' => $p1,
                    ':p2' => $p2,
                ]);
                $matchId = $pdo->lastInsertId();
                $success = "Match created! ID = $matchId";
            } catch (PDOException $e) {
                $error = "DB error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Match</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #020617;
            color: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #0b1120;
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 25px 50px -12px rgba(15,23,42,0.9);
            width: 100%;
            max-width: 520px;
            border: 1px solid #1f2937;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 1.5rem;
        }
        .subtitle {
            font-size: 0.85rem;
            color: #9ca3af;
            margin-bottom: 16px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
        }
        .field {
            margin-bottom: 14px;
        }
        .btn {
            margin-top: 10px;
            width: 100%;
            padding: 10px 16px;
            border-radius: 999px;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            color: white;
        }
        .btn:hover {
            filter: brightness(1.07);
        }
        .msg {
            margin-bottom: 10px;
            font-size: 0.85rem;
            padding: 8px 10px;
            border-radius: 8px;
        }
        .msg.error {
            background: rgba(248, 113, 113, 0.12);
            border: 1px solid #f87171;
            color: #fecaca;
        }
        .msg.success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid #22c55e;
            color: #bbf7d0;
        }
        .hint {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Create Match</h1>
    <p class="subtitle">
        Choose two players to create a new 1v1 match.  
        Only players within ±<?php echo $MAX_DIFF; ?> rating should be paired.
    </p>

    <?php if ($error): ?>
        <div class="msg error"><?php echo nl2br(htmlspecialchars($error)); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" id="matchForm">
        <div class="field">
            <label for="player1_id">Player 1</label>
            <select name="player1_id" id="player1_id" required>
                <option value="">-- Select Player 1 --</option>
                <?php foreach ($players as $p): ?>
                    <option value="<?php echo $p['id']; ?>"
                            data-rating="<?php echo (float)$p['rating']; ?>">
                        <?php echo htmlspecialchars($p['name']) . " (". round($p['rating']) . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="player2_id">Player 2</label>
            <select name="player2_id" id="player2_id" required>
                <option value="">-- Select Player 2 --</option>
                <?php foreach ($players as $p): ?>
                    <option value="<?php echo $p['id']; ?>"
                            data-rating="<?php echo (float)$p['rating']; ?>">
                        <?php echo htmlspecialchars($p['name']) . " (". round($p['rating']) . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="hint" id="ratingHint"></div>
        </div>

        <button type="submit" class="btn">Create Match</button>
    </form>
</div>

<script>
// 💡 Frontend filtering: when Player 1 is chosen,
// Player 2 options are limited to ±60 rating.

const MAX_DIFF = <?php echo (int)$MAX_DIFF; ?>;
const p1Select = document.getElementById('player1_id');
const p2Select = document.getElementById('player2_id');
const hint = document.getElementById('ratingHint');

// Store original options so we can re-filter
const originalP2Options = Array.from(p2Select.options);

p1Select.addEventListener('change', () => {
    const selected = p1Select.options[p1Select.selectedIndex];
    const rating1 = parseFloat(selected.getAttribute('data-rating'));

    // Reset Player 2 options
    p2Select.innerHTML = "";
    // Always keep first placeholder option
    const placeholder = document.createElement('option');
    placeholder.value = "";
    placeholder.textContent = "-- Select Player 2 --";
    p2Select.appendChild(placeholder);

    if (isNaN(rating1)) {
        hint.textContent = "";
        originalP2Options.forEach(opt => {
            if (opt.value !== "") p2Select.appendChild(opt.cloneNode(true));
        });
        return;
    }

    const minRating = rating1 - MAX_DIFF;
    const maxRating = rating1 + MAX_DIFF;

    let count = 0;
    originalP2Options.forEach(opt => {
        const val = opt.value;
        if (!val) return; // skip placeholder
        const r = parseFloat(opt.getAttribute('data-rating'));
        if (!isNaN(r) && r >= minRating && r <= maxRating && val !== selected.value) {
            p2Select.appendChild(opt.cloneNode(true));
            count++;
        }
    });

    if (count === 0) {
        hint.textContent = "No players within ±" + MAX_DIFF + " rating of " + rating1 + ".";
    } else {
        hint.textContent = "Showing " + count + " players within ±" + MAX_DIFF + " rating of " + rating1 + ".";
    }
});
</script>

</body>
</html>
