<?php
// job_search.php
session_start();
require 'db.php';

$category = $_GET['category'] ?? '';
$location = $_GET['location'] ?? '';
$job_type = $_GET['job_type'] ?? '';
$salary_min = $_GET['salary_min'] ?? '';

$query = "SELECT * FROM jobs WHERE 1=1";
$params = [];

if ($category) {
    $query .= " AND category LIKE ?";
    $params[] = "%$category%";
}
if ($location) {
    $query .= " AND location LIKE ?";
    $params[] = "%$location%";
}
if ($job_type) {
    $query .= " AND job_type = ?";
    $params[] = $job_type;
}
if ($salary_min) {
    $query .= " AND salary_min >= ?";
    $params[] = $salary_min;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Search - Hiring Cafe</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 1200px;
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
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease-in;
        }
        .form-group {
            margin-bottom: 20px;
            display: inline-block;
            width: 24%;
            margin-right: 1%;
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
        .card:hover {
            transform: translateY(-5px);
        }
        .card h3 {
            font-size: 1.5rem;
            color: #3498db;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @media (max-width: 768px) {
            .form-group {
                width: 100%;
            }
            .card-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Find Your Dream Job</h2>
        <div class="form-container">
            <form method="GET">
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>">
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                <div class="form-group">
                    <label for="job_type">Job Type</label>
                    <select id="job_type" name="job_type">
                        <option value="">Any</option>
                        <option value="full-time" <?php echo $job_type === 'full-time' ? 'selected' : ''; ?>>Full-Time</option>
                        <option value="part-time" <?php echo $job_type === 'part-time' ? 'selected' : ''; ?>>Part-Time</option>
                        <option value="remote" <?php echo $job_type === 'remote' ? 'selected' : ''; ?>>Remote</option>
                        <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="salary_min">Min Salary</label>
                    <input type="number" id="salary_min" name="salary_min" value="<?php echo htmlspecialchars($salary_min); ?>">
                </div>
                <button type="submit">Search</button>
            </form>
        </div>
        <div class="card-grid">
            <?php foreach ($jobs as $job): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                    <p><?php echo htmlspecialchars($job['description']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>
                    <p><strong>Salary:</strong> $<?php echo number_format($job['salary_min']); ?> - $<?php echo number_format($job['salary_max']); ?></p>
                    <a href="#" onclick="redirect('interview.php?job_id=<?php echo $job['id']; ?>')">Apply</a>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="text-align: center; margin-top: 20px;">
            <a href="#" onclick="redirect('index.php')">Back to Home</a>
        </p>
    </div>
    <script>
        function redirect(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
