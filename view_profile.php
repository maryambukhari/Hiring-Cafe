<?php
// view_profile.php (updated)
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$candidate_id = $_GET['user_id'] ?? null;
$viewer_id = $_SESSION['user_id'];

if ($candidate_id && $viewer_id != $candidate_id) {
    try {
        // Log profile view
        $stmt = $pdo->prepare("INSERT INTO profile_views (candidate_id, viewer_id) VALUES (?, ?)");
        $stmt->execute([$candidate_id, $viewer_id]);
        
        // Notify candidate
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$candidate_id, "Your profile was viewed by a recruiter!"]);
    } catch (PDOException $e) {
        $error = "Error logging profile view: " . $e->getMessage();
    }
}

// Fetch candidate profile
try {
    $stmt = $pdo->prepare("SELECT u.id, u.username, cp.full_name, cp.skills, cp.experience, cp.resume, cp.video_intro 
                           FROM users u 
                           LEFT JOIN candidate_profiles cp ON u.id = cp.user_id 
                           WHERE u.id = ? AND u.user_type = 'candidate'");
    $stmt->execute([$candidate_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching profile: " . $e->getMessage();
    $profile = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - Hiring Cafe</title>
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
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        body.dark-mode h2 {
            color: #ecf0f1;
        }
        .profile-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s ease-in;
        }
        body.dark-mode .profile-card {
            background: #34495e;
        }
        .profile-card h3 {
            font-size: 1.5rem;
            color: #3498db;
            margin-bottom: 10px;
        }
        .profile-card p {
            margin-bottom: 10px;
        }
        .error, .no-data {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        body.dark-mode .error, body.dark-mode .no-data {
            color: #e74c3c;
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
        <h2>Candidate Profile</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif (!$profile): ?>
            <p class="no-data">Profile not found or user is not a candidate.</p>
        <?php else: ?>
            <div class="profile-card">
                <h3><?php echo htmlspecialchars($profile['full_name'] ?? $profile['username']); ?></h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($profile['username']); ?></p>
                <p><strong>Skills:</strong> <?php echo htmlspecialchars($profile['skills'] ?? 'Not specified'); ?></p>
                <p><strong>Experience:</strong> <?php echo htmlspecialchars($profile['experience'] ?? 0); ?> years</p>
                <?php if ($profile['resume']): ?>
                    <p><strong>Resume:</strong> <a href="uploads/<?php echo htmlspecialchars($profile['resume']); ?>" target="_blank">Download</a></p>
                <?php endif; ?>
                <?php if ($profile['video_intro']): ?>
                    <p><strong>Video Intro:</strong> <a href="uploads/<?php echo htmlspecialchars($profile['video_intro']); ?>" target="_blank">Watch</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
