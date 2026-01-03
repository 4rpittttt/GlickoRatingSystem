<?php
// add_player.php
require_once 'config.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");

    if ($name === "") {
        $error = "Name is required.";
    } else {
        // Handle photo upload (optional)
        $photoPath = null;

        if (!empty($_FILES["photo"]["name"])) {
            $uploadDir = __DIR__ . "/uploads/";
            // Ensure directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmp  = $_FILES["photo"]["tmp_name"];
            $fileName = basename($_FILES["photo"]["name"]);
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Basic validation
            $allowed = ["jpg", "jpeg", "png", "gif", "webp"];
            if (!in_array($ext, $allowed)) {
                $error = "Invalid file type. Allowed: jpg, jpeg, png, gif, webp.";
            } else {
                // Unique name to avoid clashes
                $newName   = uniqid("player_", true) . "." . $ext;
                $targetRel = "uploads/" . $newName;       // path to store in DB
                $targetAbs = $uploadDir . $newName;       // physical path

                if (move_uploaded_file($fileTmp, $targetAbs)) {
                    $photoPath = $targetRel;
                } else {
                    $error = "Failed to upload file.";
                }
            }
        }

        // Insert if no error so far
        if ($error === "") {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO players (name, photo_url)
                    VALUES (:name, :photo_url)
                ");
                $stmt->execute([
                    ":name"      => $name,
                    ":photo_url" => $photoPath,
                ]);

                $success = "Player added successfully!";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Player</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #020617;
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 25px 50px -12px rgba(15,23,42,0.9);
            width: 100%;
            max-width: 420px;
            border: 1px solid #1f2937;
        }
        h1 {
            margin-top: 0;
            font-size: 1.6rem;
            margin-bottom: 0.75rem;
        }
        p.subtitle {
            font-size: 0.85rem;
            color: #9ca3af;
            margin-top: 0;
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 1px #4f46e5;
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
            background: linear-gradient(135deg, #4f46e5, #22c55e);
            color: white;
        }
        .btn:hover {
            filter: brightness(1.1);
        }
        .msg {
            margin-bottom: 12px;
            font-size: 0.85rem;
            padding: 8px 10px;
            border-radius: 8px;
        }
        .msg.error {
            background: rgba(248, 113, 113, 0.15);
            border: 1px solid #f87171;
            color: #fecaca;
        }
        .msg.success {
            background: rgba(34, 197, 94, 0.15);
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
    <h1>Add New Player</h1>
    <p class="subtitle">Fill in the details to register a new player into the rating system.</p>

    <?php if ($error): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="field">
            <label for="name">Player Name</label>
            <input type="text" name="name" id="name" required placeholder="e.g. Magnus, Smriti, Player123">
        </div>

        <div class="field">
            <label for="photo">Player Photo (optional)</label>
            <input type="file" name="photo" id="photo" accept="image/*">
            <div class="hint">If you skip this, the player will just have no photo initially.</div>
        </div>

        <button type="submit" class="btn">Save Player</button>
    </form>
</div>
</body>
</html>
