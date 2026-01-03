<?php
// quick_match.php
require_once 'config.php';

$MAX_DIFF = 120; // same as in create_match.php

// 1. Check we have at least 2 active players
$stmt = $pdo->query("SELECT COUNT(*) AS c FROM players WHERE is_active = 1");
$countRow = $stmt->fetch();
if (!$countRow || (int)$countRow['c'] < 2) {
    die("Not enough active players to create a match.");
}

// Helper: get a random active player
function getRandomPlayer($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, rating 
        FROM players 
        WHERE is_active = 1 
        ORDER BY RAND() 
        LIMIT 1
    ");
    return $stmt->fetch();
}

$pairFound = false;
$attempts = 0;
$maxAttempts = 25; // Try up to 25 times to find a fair pair

while (!$pairFound && $attempts < $maxAttempts) {
    $attempts++;

    // 2. Randomly pick Player 1
    $p1 = getRandomPlayer($pdo);
    if (!$p1) {
        break;
    }

    $p1_id = (int)$p1['id'];
    $p1_rating = (float)$p1['rating'];

    // 3. Find a random Player 2 within ±MAX_DIFF
    $minRating = $p1_rating - $MAX_DIFF;
    $maxRating = $p1_rating + $MAX_DIFF;

    $stmt = $pdo->prepare("
        SELECT id, name, rating 
        FROM players
        WHERE is_active = 1
          AND id != :p1_id
          AND rating BETWEEN :minR AND :maxR
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute([
        ':p1_id' => $p1_id,
        ':minR'  => $minRating,
        ':maxR'  => $maxRating,
    ]);
    $p2 = $stmt->fetch();

    if ($p2) {
        $pairFound = true;
        $p2_id = (int)$p2['id'];
    }
}

if (!$pairFound) {
    // No fair pair found within attempts
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>No Fair Match Available</title>
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
                max-width: 480px;
                border: 1px solid #1f2937;
                text-align: center;
            }
            h1 {
                margin: 0 0 10px;
                font-size: 1.4rem;
            }
            p {
                font-size: 0.9rem;
                color: #9ca3af;
            }
            a.btn {
                display: inline-block;
                margin-top: 16px;
                padding: 10px 18px;
                border-radius: 999px;
                text-decoration: none;
                background: linear-gradient(135deg, #4f46e5, #3b82f6);
                color: white;
                font-size: 0.9rem;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
    <div class="card">
        <h1>No Fair Match Found</h1>
        <p>
            The system couldn't find two players within ±<?php echo $MAX_DIFF; ?> rating of each other.<br>
            Try adding more players or adjusting the rating limit.
        </p>
        <a href="create_match.php" class="btn">Back to Manual Match Creation</a>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. Create the match in DB
try {
    $stmt = $pdo->prepare("
        INSERT INTO matches (player1_id, player2_id)
        VALUES (:p1, :p2)
    ");
    $stmt->execute([
        ':p1' => $p1_id,
        ':p2' => $p2_id,
    ]);
    $matchId = $pdo->lastInsertId();
} catch (PDOException $e) {
    die("Failed to create match: " . $e->getMessage());
}

// 5. Redirect straight to play_match for this random match
header("Location: play_match.php?id=" . $matchId);
exit;
