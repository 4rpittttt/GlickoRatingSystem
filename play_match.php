<?php
// play_match.php
require_once 'config.php';
require_once 'glicko.php';

$matchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($matchId <= 0) {
    die("No match ID given.");
}

// Fetch match + players
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        p1.name  AS p1_name, p1.rating AS p1_rating, p1.rd AS p1_rd, p1.games_played AS p1_games,
        p2.name  AS p2_name, p2.rating AS p2_rating, p2.rd AS p2_rd, p2.games_played AS p2_games
    FROM matches m
    JOIN players p1 ON m.player1_id = p1.id
    JOIN players p2 ON m.player2_id = p2.id
    WHERE m.id = :id
");
$stmt->execute([':id' => $matchId]);
$match = $stmt->fetch();

if (!$match) {
    die("Match not found.");
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Who won? We expect winner_id = player1_id or player2_id
    $winnerId = isset($_POST['winner_id']) ? (int)$_POST['winner_id'] : 0;

    if ($match['status'] === 'completed') {
        $error = "This match is already completed.";
    } elseif ($winnerId !== (int)$match['player1_id'] && $winnerId !== (int)$match['player2_id']) {
        $error = "Invalid winner selection.";
    } else {
        // Determine scores from each perspective
        $p1_id = (int)$match['player1_id'];
        $p2_id = (int)$match['player2_id'];

        if ($winnerId === $p1_id) {
            $s1 = 1.0;
            $s2 = 0.0;
        } elseif ($winnerId === $p2_id) {
            $s1 = 0.0;
            $s2 = 1.0;
        } else {
            $s1 = $s2 = 0.5; // in case you add draw option later
        }

        // Current ratings / RDs / games
        $r1  = (float)$match['p1_rating'];
        $rd1 = (float)$match['p1_rd'];
        $g1  = (int)$match['p1_games'];

        $r2  = (float)$match['p2_rating'];
        $rd2 = (float)$match['p2_rd'];
        $g2  = (int)$match['p2_games'];

        // Update ratings (Glicko)
        [$new_r1, $new_rd1] = glicko_update($r1, $rd1, $r2, $rd2, $s1, $g1);
        [$new_r2, $new_rd2] = glicko_update($r2, $rd2, $r1, $rd1, $s2, $g2);

        try {
            $pdo->beginTransaction();

            // Update players
            $up = $pdo->prepare("
                UPDATE players
                SET rating = :rating, rd = :rd, games_played = games_played + 1
                WHERE id = :id
            ");

            $up->execute([
                ':rating' => $new_r1,
                ':rd'     => $new_rd1,
                ':id'     => $p1_id,
            ]);

            $up->execute([
                ':rating' => $new_r2,
                ':rd'     => $new_rd2,
                ':id'     => $p2_id,
            ]);

            // Update match
            $um = $pdo->prepare("
                UPDATE matches
                SET winner_id = :winner_id, status = 'completed', completed_at = NOW()
                WHERE id = :id
            ");
            $um->execute([
                ':winner_id' => $winnerId,
                ':id'        => $matchId,
            ]);

            // Insert rating history for both players
            $ins = $pdo->prepare("
                INSERT INTO rating_history (player_id, match_id, old_rating, new_rating, old_rd, new_rd)
                VALUES (:player_id, :match_id, :old_rating, :new_rating, :old_rd, :new_rd)
            ");

            $ins->execute([
                ':player_id'  => $p1_id,
                ':match_id'   => $matchId,
                ':old_rating' => $r1,
                ':new_rating' => $new_r1,
                ':old_rd'     => $rd1,
                ':new_rd'     => $new_rd1,
            ]);

            $ins->execute([
                ':player_id'  => $p2_id,
                ':match_id'   => $matchId,
                ':old_rating' => $r2,
                ':new_rating' => $new_r2,
                ':old_rd'     => $rd2,
                ':new_rd'     => $new_rd2,
            ]);

            $pdo->commit();

            $success = "Ratings updated! Winner: " . htmlspecialchars(
                $winnerId === $p1_id ? $match['p1_name'] : $match['p2_name']
            );
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "DB error during rating update: " . $e->getMessage();
        }
    }

    // Refresh match data after update
    $stmt->execute([':id' => $matchId]);
    $match = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Play Match</title>
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
            background: #020617;
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 25px 50px -12px rgba(15,23,42,0.9);
            width: 100%;
            max-width: 600px;
            border: 1px solid #1f2937;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 1.4rem;
        }
        .subtitle {
            font-size: 0.85rem;
            color: #9ca3af;
            margin-bottom: 16px;
        }
        .players {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }
        .player-card {
            flex: 1;
            border-radius: 14px;
            padding: 14px;
            border: 1px solid #1f2937;
            background: #020617;
        }
        .player-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .player-rating {
            font-size: 0.9rem;
            color: #9ca3af;
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
        .winner-form {
            margin-top: 10px;
        }
        .winner-options {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
        }
        label.radio {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }
        .btn {
            width: 100%;
            padding: 10px 16px;
            border-radius: 999px;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #22c55e, #4ade80);
            color: #022c22;
        }
        .btn[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .status-pill {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            margin-left: 8px;
        }
        .status-pending {
            background: rgba(234, 179, 8, 0.1);
            color: #facc15;
        }
        .status-completed {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Play Match #<?php echo $matchId; ?>
        <?php if ($match['status'] === 'pending'): ?>
            <span class="status-pill status-pending">Pending</span>
        <?php elseif ($match['status'] === 'completed'): ?>
            <span class="status-pill status-completed">Completed</span>
        <?php endif; ?>
    </h1>
    <p class="subtitle">Select the winner to update their ratings (Glicko-style).</p>

    <?php if ($error): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="players">
        <div class="player-card">
            <div class="player-name"><?php echo htmlspecialchars($match['p1_name']); ?></div>
            <div class="player-rating">
                Rating: <?php echo round($match['p1_rating']); ?>
                &nbsp;&middot;&nbsp; RD: <?php echo round($match['p1_rd']); ?>
                &nbsp;&middot;&nbsp; Games: <?php echo (int)$match['p1_games']; ?>
            </div>
        </div>
        <div class="player-card">
            <div class="player-name"><?php echo htmlspecialchars($match['p2_name']); ?></div>
            <div class="player-rating">
                Rating: <?php echo round($match['p2_rating']); ?>
                &nbsp;&middot;&nbsp; RD: <?php echo round($match['p2_rd']); ?>
                &nbsp;&middot;&nbsp; Games: <?php echo (int)$match['p2_games']; ?>
            </div>
        </div>
    </div>

    <form method="POST" class="winner-form">
        <div class="winner-options">
            <label class="radio">
                <input type="radio" name="winner_id" value="<?php echo (int)$match['player1_id']; ?>" required>
                Winner: <?php echo htmlspecialchars($match['p1_name']); ?>
            </label>
            <label class="radio">
                <input type="radio" name="winner_id" value="<?php echo (int)$match['player2_id']; ?>" required>
                Winner: <?php echo htmlspecialchars($match['p2_name']); ?>
            </label>
        </div>
        <button type="submit" class="btn" <?php echo $match['status'] === 'completed' ? 'disabled' : ''; ?>>
            <?php echo $match['status'] === 'completed' ? 'Match Completed' : 'Submit Result & Update Ratings'; ?>
        </button>
    </form>
</div>
</body>
</html>
