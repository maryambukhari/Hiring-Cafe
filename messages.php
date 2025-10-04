<?php
// messages.php (new)
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $receiver_id = filter_var($_POST['receiver_id'], FILTER_VALIDATE_INT);
    $message = $_POST['message'];
    try {
        if (!$receiver_id) {
            throw new Exception("Invalid recipient ID");
        }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$receiver_id]);
        if (!$stmt->fetchColumn()) {
            throw new Exception("Recipient not found");
        }
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);

        // Notify receiver
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$receiver_id, "You have a new message!"]);
        
        $success = "Message sent successfully!";
    } catch (Exception $e) {
        $error = "Error sending message: " . $e->getMessage();
    }
}

// Fetch messages
try {
    $stmt = $pdo->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id = u.id WHERE (m.sender_id = ? OR m.receiver_id = ?) ORDER BY m.sent_at DESC");
    $stmt->execute([$user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages = [];
    $error = "Error fetching messages: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Hiring Cafe</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        body.dark-mode {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h2, h3 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        body.dark-mode h2, body.dark-mode h3 {
            color: #ecf0f1;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            animation: slideIn 0.5s ease-in;
        }
        body.dark-mode .form-container {
            background: #34495e;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        body.dark-mode .form-group input, body.dark-mode .form-group textarea {
            background: #2c3e50;
            color: #ecf0f1;
            border-color: #4a6278;
        }
        button {
            padding: 10px 20px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .message-box {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: background 0.3s;
        }
        .message-box:hover {
            background: #e0e0e0;
        }
        body.dark-mode .message-box {
            background: #2c3e50;
            border-bottom: 1px solid #4a6278;
        }
        body.dark-mode .message-box:hover {
            background: #3e5a74;
        }
        .success {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        body.dark-mode .error {
            color: #e74c3c;
        }
        .no-data {
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }
        body.dark-mode .no-data {
            color: #bdc3c7;
        }
        .toggle-container {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        .toggle-container button {
            padding: 10px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-container button:hover {
            background: #2980b9;
        }
        @keyframes slideIn {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="toggle-container">
        <button onclick="toggleDarkMode()">Toggle Dark Mode</button>
    </div>
    <div class="container">
        <h2>Messages</h2>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <div class="form-container">
            <h3>Send Message</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="receiver_id">Recipient (User ID)</label>
                    <input type="number" id="receiver_id" name="receiver_id" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" name="message">Send</button>
            </form>
        </div>
        <div class="form-container">
            <h3>Messages</h3>
            <?php if (empty($messages)): ?>
                <p class="no-data">No messages found.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-box">
                        <p><strong><?php echo htmlspecialchars($msg['username']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?></p>
                        <p><small><?php echo $msg['sent_at']; ?></small></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <p style="text-align: center; margin-top: 20px;">
            <a href="#" onclick="redirect('index.php')">Back to Home</a>
        </p>
    </div>
    <script>
        function redirect(page) {
            window.location.href = page;
        }
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>
