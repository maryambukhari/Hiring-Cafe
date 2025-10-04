<?php
// interview.php (updated)
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$job_id = filter_var($_GET['job_id'] ?? null, FILTER_VALIDATE_INT);
$job_exists = false;
$job_title = '';

if ($job_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, user_id FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            $job_exists = true;
            $job_title = $job['title'];
            $recruiter_id = $job['user_id'];
        } else {
            $error = "The selected job does not exist.";
        }
    } catch (PDOException $e) {
        $error = "Error checking job: " . $e->getMessage();
    }
} else {
    $error = "No job ID provided. Please select a job from the homepage.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule']) && $job_exists && $user_type === 'candidate') {
    $interview_date = $_POST['interview_date'];
    $interview_type = $_POST['interview_type'];
    try {
        // Validate interview date (not in the past)
        $interview_datetime = new DateTime($interview_date);
        $now = new DateTime();
        if ($interview_datetime < $now) {
            throw new Exception("Interview date cannot be in the past.");
        }

        // Check if interview already exists for this job and candidate
        $stmt = $pdo->prepare("SELECT id FROM interviews WHERE job_id = ? AND candidate_id = ?");
        $stmt->execute([$job_id, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("You have already scheduled an interview for this job.");
        }

        $stmt = $pdo->prepare("INSERT INTO interviews (job_id, candidate_id, recruiter_id, interview_date, interview_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$job_id, $user_id, $recruiter_id, $interview_date, $interview_type]);

        // Notify recruiter
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$recruiter_id, "A candidate scheduled an interview for your job: " . htmlspecialchars($job_title) . "!"]);
        
        $success = "Interview scheduled successfully!";
    } catch (Exception $e) {
        $error = "Error scheduling interview: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Interview - Hiring Cafe</title>
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
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        body.dark-mode .form-group input, body.dark-mode .form-group select {
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
        <h2>Schedule Interview</h2>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif ($user_type !== 'candidate'): ?>
            <p class="no-data">Only candidates can schedule interviews. Please log in as a candidate.</p>
        <?php elseif (!$job_id): ?>
            <p class="no-data">No job ID provided. Please select a job from the homepage.</p>
        <?php elseif (!$job_exists): ?>
            <p class="no-data">The selected job does not exist. Please choose a valid job.</p>
        <?php else: ?>
            <div class="form-container">
                <h3>Schedule Interview for <?php echo htmlspecialchars($job_title); ?></h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="interview_date">Interview Date & Time</label>
                        <input type="datetime-local" id="interview_date" name="interview_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="interview_type">Interview Type</label>
                        <select id="interview_type" name="interview_type" required>
                            <option value="video">Video</option>
                            <option value="in-person">In-Person</option>
                        </select>
                    </div>
                    <button type="submit" name="schedule">Schedule</button>
                </form>
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
