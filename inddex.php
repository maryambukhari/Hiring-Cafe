<?php
// index.php (updated)
session_start();
require 'db.php';

// Validate session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    $_SESSION['user_id'] = null;
    $_SESSION['user_type'] = null;
}

// Fetch trending jobs
try {
    $stmt = $pdo->query("SELECT * FROM jobs ORDER BY posted_at DESC LIMIT 6");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jobs = [];
    $job_error = "Error fetching jobs: " . $e->getMessage();
}

// Fetch top candidates
try {
    $stmt = $pdo->query("SELECT u.id AS user_id, u.username, cp.full_name, cp.skills 
                         FROM users u 
                         LEFT JOIN candidate_profiles cp ON u.id = cp.user_id 
                         WHERE u.user_type = 'candidate' 
                         LIMIT 6");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $candidates = [];
    $candidate_error = "Error fetching candidates: " . $e->getMessage();
}

// Fetch notifications for logged-in user
$notifications = [];
$notification_debug = '';
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($notifications)) {
            $notification_debug = "No notifications found in the database for user_id: " . $_SESSION['user_id'] . ". Try scheduling an interview or sending a message.";
        }
    } catch (PDOException $e) {
        $notifications = [];
        $notification_error = "Error fetching notifications: " . $e->getMessage();
    }
} else {
    $notification_debug = "User not logged in. Please log in to view notifications.";
}

// Handle profile view
$profile = false;
if (isset($_GET['view_profile']) && $_GET['view_profile']) {
    $candidate_id = filter_var($_GET['view_profile'], FILTER_VALIDATE_INT);
    $viewer_id = $_SESSION['user_id'] ?? null;
    
    if ($candidate_id && $viewer_id && $candidate_id != $viewer_id) {
        try {
            // Log profile view
            $stmt = $pdo->prepare("INSERT INTO profile_views (candidate_id, viewer_id) VALUES (?, ?)");
            $stmt->execute([$candidate_id, $viewer_id]);
            
            // Notify candidate
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->execute([$candidate_id, "Your profile was viewed by a recruiter!"]);
        } catch (PDOException $e) {
            $profile_error = "Error logging profile view: " . $e->getMessage();
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
        $profile_error = "Error fetching profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiring Cafe Clone</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            text-align: center;
            padding: 50px 0;
            animation: fadeIn 1s ease-in;
        }
        header h1 {
            font-size: 3rem;
            font-weight: 800;
            color: #2c3e50;
        }
        body.dark-mode header h1 {
            color: #ecf0f1;
        }
        .nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        .nav a {
            text-decoration: none;
            color: #fff;
            background: #3498db;
            padding: 10px 20px;
            border-radius: 5px;
            transition: transform 0.3s, background 0.3s;
        }
        body.dark-mode .nav a {
            background: #2980b9;
        }
        .nav a:hover {
            transform: scale(1.1);
            background: #2980b9;
        }
        .section {
            margin: 40px 0;
        }
        .section h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        body.dark-mode .section h2 {
            color: #ecf0f1;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        body.dark-mode .card {
            background: #34495e;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h3 {
            font-size: 1.5rem;
            color: #3498db;
        }
        .error, .no-data {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        body.dark-mode .error, body.dark-mode .no-data {
            color: #e74c3c;
        }
        .debug {
            color: orange;
            text-align: center;
            margin-bottom: 20px;
        }
        body.dark-mode .debug {
            color: #f1c40f;
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
        .notifications {
            margin: 20px 0;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s ease-in;
        }
        body.dark-mode .notifications {
            background: #34495e;
        }
        .notifications h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .notification {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            transition: background 0.3s;
        }
        .notification:hover {
            background: #f0f0f0;
        }
        body.dark-mode .notification {
            border-bottom: 1px solid #4a6278;
        }
        body.dark-mode .notification:hover {
            background: #3e5a74;
        }
        .profile-section {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            animation: slideIn 0.5s ease-in;
        }
        body.dark-mode .profile-section {
            background: #34495e;
        }
        .profile-section h3 {
            font-size: 1.5rem;
            color: #3498db;
            margin-bottom: 10px;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            header h1 {
                font-size: 2rem;
            }
            .card-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="toggle-container">
        <button onclick="toggleDarkMode()">Toggle Dark Mode</button>
    </div>
    <header>
        <h1>Welcome to Hiring Cafe</h1>
        <div class="nav">
            <a href="#" onclick="redirect('signup.php')">Signup</a>
            <a href="#" onclick="redirect('login.php')">Login</a>
            <a href="#" onclick="redirect('job_search.php')">Find Jobs</a>
            <a href="#" onclick="redirect('profile.php')">Profile</a>
            <a href="#" onclick="redirect('messages.php')">Messages</a>
        </div>
    </header>
    <div class="container">
        <?php if (isset($notification_error)): ?>
            <p class="error"><?php echo $notification_error; ?></p>
        <?php elseif (!empty($notifications)): ?>
            <div class="notifications">
                <h3>Notifications</h3>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification">
                        <p><?php echo htmlspecialchars($notification['message']); ?> <small>(<?php echo $notification['created_at']; ?>)</small></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-data">No notifications available.</p>
            <?php if ($notification_debug): ?>
                <p class="debug"><?php echo htmlspecialchars($notification_debug); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($profile) && $profile): ?>
            <div class="profile-section">
                <h2>Candidate Profile</h2>
                <h3><?php echo htmlspecialchars($profile['full_name'] ?? $profile['username']); ?></h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($profile['username']); ?></p>
                <p><strong>Skills:</strong> <?php echo htmlspecialchars($profile['skills'] ?? 'Not specified'); ?></p>
                <p><strong>Experience:</strong> <?php echo htmlspecialchars($profile['experience'] ?? 0); ?> years</p>
                <?php if ($profile['resume']): ?>
                    <p><strong>Resume:</strong> <a href="uploads/<?php echo htmlspecialchars($profile['resume']); ?>" target="_blank">Download</a></p>
                <?php endif; ?>
                <?php if ($profile['video_intro']): ?>
                    <p><strong>Video Intro:</strong> <a href="Uploads/<?php echo htmlspecialchars($profile['video_intro']); ?>" target="_blank">Watch</a></p>
                <?php endif; ?>
                <p><a href="#" onclick="redirect('index.php')">Back to Home</a></p>
            </div>
        <?php elseif (isset($profile_error)): ?>
            <p class="error"><?php echo $profile_error; ?></p>
        <?php elseif (isset($_GET['view_profile'])): ?>
            <p class="no-data">Profile not found or user is not a candidate.</p>
        <?php endif; ?>
        <div class="section">
            <h2>Trending Jobs</h2>
            <?php if (isset($job_error)): ?>
                <p class="error"><?php echo $job_error; ?></p>
            <?php elseif (empty($jobs)): ?>
                <p class="no-data">No jobs available at the moment. Check back later!</p>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($jobs as $job): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                            <p><?php echo htmlspecialchars($job['description']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location'] ?? 'Not specified'); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>
                            <p><strong>Salary:</strong> $<?php echo number_format($job['salary_min'] ?? 0); ?> - $<?php echo number_format($job['salary_max'] ?? 0); ?></p>
                            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'candidate'): ?>
                                <a href="#" onclick="redirect('interview.php?job_id=<?php echo $job['id']; ?>')">Apply</a>
                            <?php else: ?>
                                <p class="no-data">Log in as a candidate to apply.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="section">
            <h2>Top Candidates</h2>
            <?php if (isset($candidate_error)): ?>
                <p class="error"><?php echo $candidate_error; ?></p>
            <?php elseif (empty($candidates)): ?>
                <p class="no-data">No candidates available at the moment. Check back later!</p>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($candidate['full_name'] ?? $candidate['username']); ?></h3>
                            <p><strong>Skills:</strong> <?php echo htmlspecialchars($candidate['skills'] ?? 'Not specified'); ?></p>
                            <a href="#" onclick="redirect('index.php?view_profile=<?php echo $candidate['user_id']; ?>')">View Profile</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
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
