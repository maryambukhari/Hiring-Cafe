<?php
// profile.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user_type === 'candidate') {
        $full_name = $_POST['full_name'];
        $skills = $_POST['skills'];
        $experience = $_POST['experience'];
        
        // Handle file uploads
        $resume = $_FILES['resume']['name'];
        $video = $_FILES['video']['name'];
        if ($resume) {
            move_uploaded_file($_FILES['resume']['tmp_name'], "uploads/$resume");
        }
        if ($video) {
            move_uploaded_file($_FILES['video']['tmp_name'], "uploads/$video");
        }

        $stmt = $pdo->prepare("INSERT INTO candidate_profiles (user_id, full_name, skills, resume, video_intro, experience) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = ?, skills = ?, resume = ?, video_intro = ?, experience = ?");
        $stmt->execute([$user_id, $full_name, $skills, $resume, $video, $experience, $full_name, $skills, $resume, $video, $experience]);
    } else {
        $company_name = $_POST['company_name'];
        $description = $_POST['description'];
        $website = $_POST['website'];
        
        $logo = $_FILES['logo']['name'];
        if ($logo) {
            move_uploaded_file($_FILES['logo']['tmp_name'], "uploads/$logo");
        }

        $stmt = $pdo->prepare("INSERT INTO company_profiles (user_id, company_name, description, logo, website) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE company_name = ?, description = ?, logo = ?, website = ?");
        $stmt->execute([$user_id, $company_name, $description, $logo, $website, $company_name, $description, $logo, $website]);
    }
    $success = "Profile updated successfully!";
}

// Fetch profile data
if ($user_type === 'candidate') {
    $stmt = $pdo->prepare("SELECT * FROM candidate_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Hiring Cafe</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Poppins', sans-serif;
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
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 10px;
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
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $user_type === 'candidate' ? 'Candidate Profile' : 'Company Profile'; ?></h2>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($user_type === 'candidate'): ?>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo isset($profile['full_name']) ? htmlspecialchars($profile['full_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="skills">Skills (comma-separated)</label>
                        <input type="text" id="skills" name="skills" value="<?php echo isset($profile['skills']) ? htmlspecialchars($profile['skills']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="experience">Years of Experience</label>
                        <input type="number" id="experience" name="experience" value="<?php echo isset($profile['experience']) ? htmlspecialchars($profile['experience']) : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label for="resume">Upload Resume (PDF)</label>
                        <input type="file" id="resume" name="resume" accept=".pdf">
                    </div>
                    <div class="form-group">
                        <label for="video">Upload Video Intro</label>
                        <input type="file" id="video" name="video" accept="video/*">
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo isset($profile['company_name']) ? htmlspecialchars($profile['company_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Company Description</label>
                        <textarea id="description" name="description"><?php echo isset($profile['description']) ? htmlspecialchars($profile['description']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" value="<?php echo isset($profile['website']) ? htmlspecialchars($profile['website']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="logo">Upload Company Logo</label>
                        <input type="file" id="logo" name="logo" accept="image/*">
                    </div>
                <?php endif; ?>
                <button type="submit">Update Profile</button>
            </form>
            <p style="text-align: center; margin-top: 20px;">
                <a href="#" onclick="redirect('index.php')">Back to Home</a>
            </p>
        </div>
    </div>
    <script>
        function redirect(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
