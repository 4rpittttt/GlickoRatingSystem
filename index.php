<?php
// index.php
require_once 'config.php';

// Quick stats
$totalPlayers = $pdo->query("SELECT COUNT(*) AS c FROM players")->fetch()['c'] ?? 0;
$totalMatches = $pdo->query("SELECT COUNT(*) AS c FROM matches")->fetch()['c'] ?? 0;
$pendingMatches = $pdo->query("SELECT COUNT(*) AS c FROM matches WHERE status = 'pending'")->fetch()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rating System Hub</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #1e293b 0, #020617 50%, #000 100%);
            color: #e5e7eb;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .shell {
            width: 100%;
            max-width: 900px;
            padding: 24px;
        }
        .card {
            background: rgba(15,23,42,0.95);
            border-radius: 18px;
            padding: 24px 26px;
            box-shadow: 0 25px 50px -12px rgba(15,23,42,0.9);
            border: 1px solid #1e293b;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 1.7rem;
        }
        .subtitle {
            margin: 0 0 18px;
            font-size: 0.9rem;
            color: #9ca3af;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }
        .btn-card {
            background: #020617;
            border-radius: 14px;
            padding: 14px 14px;
            border: 1px solid #1f2937;
            text-decoration: none;
            color: #e5e7eb;
            display: block;
            transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease;
        }
        .btn-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 30px -16px rgba(15,23,42,1);
            border-color: #4f46e5;
        }
        .btn-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .btn-desc {
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .pill {
            background: rgba(15,23,42,0.9);
            border: 1px solid #1f2937;
            border-radius: 999px;
            padding: 4px 10px;
        }
        .accent {
            color: #a5b4fc;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="card">
        <h1>1v1 Rating System</h1>
        <p class="subtitle">Admin hub for adding players, pairing fair matches, and updating Glicko-style ratings.</p>

        <div class="stats">
            <div class="pill">Players: <span class="accent"><?php echo (int)$totalPlayers; ?></span></div>
            <div class="pill">Matches: <span class="accent"><?php echo (int)$totalMatches; ?></span></div>
            <div class="pill">Pending Matches: <span class="accent"><?php echo (int)$pendingMatches; ?></span></div>
        </div>

        <div class="grid">
            <a href="add_player.php" class="btn-card">
                <div class="btn-title">➕ Add Player</div>
                <div class="btn-desc">Register a new player with name and optional photo. Starts at base rating 400.</div>
            </a>

            <a href="create_match.php" class="btn-card">
                <div class="btn-title">🎯 Manual Match</div>
                <div class="btn-desc">Manually pick two players within your rating band and create a match.</div>
            </a>

            <a href="quick_match.php" class="btn-card">
                <div class="btn-title">🎲 Random Fair Match</div>
                <div class="btn-desc">System auto-selects two players within ±120 rating and jumps to result screen.</div>
            </a>

            <a href="leaderboard.php" class="btn-card">
                <div class="btn-title">🏆 Leaderboard</div>
                <div class="btn-desc">See all players ordered by rating, with RD and games played.</div>
            </a>
        </div>
    </div>
</div>
</body>
</html>
