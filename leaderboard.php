<?php
// leaderboard.php
require_once 'config.php';

$stmt = $pdo->query("
    SELECT id, name, photo_url, rating, rd, games_played, created_at
    FROM players
    WHERE is_active = 1
    ORDER BY rating DESC
");
$players = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #020617;
            color: #e5e7eb;
            margin: 0;
            padding: 24px;
        }
        .wrap {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 1.6rem;
        }
        .subtitle {
            margin: 0 0 18px;
            font-size: 0.9rem;
            color: #9ca3af;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 12px;
            font-size: 0.85rem;
            color: #a5b4fc;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #020617;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #1f2937;
        }
        thead {
            background: #020617;
        }
        th, td {
            padding: 10px 12px;
            font-size: 0.85rem;
            text-align: left;
            border-bottom: 1px solid #111827;
        }
        th {
            font-size: 0.8rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        tbody tr:nth-child(odd) {
            background: #020617;
        }
        tbody tr:nth-child(even) {
            background: #020617;
        }
        tbody tr:hover {
            background: #030712;
        }
        .rank {
            font-weight: 600;
            color: #a5b4fc;
        }
        .name-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #020617;
            border: 1px solid #1f2937;
            object-fit: cover;
        }
        .empty-msg {
            margin-top: 18px;
            font-size: 0.9rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
<div class="wrap">
    <a href="index.php" class="back-link">← Back to Home</a>
    <h1>Leaderboard</h1>
    <p class="subtitle">Players ordered by current rating. RD shows how “uncertain” the rating still is.</p>

    <?php if (empty($players)): ?>
        <div class="empty-msg">
            No players found. Go to <a href="add_player.php" style="color:#a5b4fc;">Add Player</a> and create some first.
        </div>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Player</th>
                <th>Rating</th>
                <th>RD</th>
                <th>Games</th>
                <th>Joined</th>
            </tr>
            </thead>
            <tbody>
            <?php $rank = 1; ?>
            <?php foreach ($players as $p): ?>
                <tr>
                    <td class="rank"><?php echo $rank++; ?></td>
                    <td>
                        <div class="name-cell">
                            <?php if (!empty($p['photo_url'])): ?>
                                <img src="<?php echo htmlspecialchars($p['photo_url']); ?>" class="avatar" alt="">
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($p['name']); ?></span>
                        </div>
                    </td>
                    <td><?php echo round($p['rating']); ?></td>
                    <td><?php echo round($p['rd']); ?></td>
                    <td><?php echo (int)$p['games_played']; ?></td>
                    <td><?php echo htmlspecialchars(substr($p['created_at'], 0, 10)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
