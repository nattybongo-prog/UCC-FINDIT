<?php
include 'db.php';

if (isset($_POST['submit'])) {
    $name = $_POST['item_name'];
    $category = $_POST['category'];
    $type = $_POST['type'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $contact = $_POST['contact_info'];

    $image_name = $_FILES['image']['name'];
    $target_dir = "img/" . basename($image_name);
    move_uploaded_file($_FILES['image']['tmp_name'], $target_dir);

    $stmt = $conn->prepare("INSERT INTO items (item_name, category, type, location, description, contact_info, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $category, $type, $location, $description, $contact, $image_name);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Item - UCC FindIt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
        }
        body::before {
            content: "";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 450px;
            height: 450px;
            background-image: url('img/logo.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.06;
            pointer-events: none;
            z-index: -1;
        }
        .navbar-ucc {
            background-color: #0d2346;
            border-bottom: 4px solid #ffc107;
        }
        .btn-ucc-navy {
            background-color: #0d2346;
            color: #ffffff;
            font-weight: bold;
        }
        .btn-ucc-navy:hover {
            background-color: #153463;
            color: #ffffff;
        }
        .text-ucc {
            color: #0d2346;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-ucc shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <img src="img/logo.png" alt="UCC Crest" height="40">
                <span>UCC FindIt</span>
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">Back to Feed</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card shadow border-0 p-4 rounded-4">
                    <div class="text-center mb-3">
                        <img src="img/logo.png" alt="UCC Crest" height="60">
                        <h3 class="fw-bold text-ucc mt-2">Report Lost / Found Item</h3>
                        <p class="text-muted small">Post details to help return missing items on campus.</p>
                    </div>
                    
                    <form action="report.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Item Name</label>
                            <input type="text" name="item_name" class="form-control" placeholder="e.g. Student ID Card, Phone" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Status</label>
                                <select name="type" class="form-select">
                                    <option value="Lost">Lost Item</option>
                                    <option value="Found">Found Item</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Category</label>
                                <input type="text" name="category" class="form-control" placeholder="e.g. ID Card, Keys" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Location (Lost or Found)</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Science Complex, Casford Hall" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Description / Details</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Provide key details..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Your Contact Info</label>
                            <input type="text" name="contact_info" class="form-control" placeholder="e.g. 0551234567 or student email" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Upload Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <button type="submit" name="submit" class="btn btn-ucc-navy w-100 py-2 shadow-sm">Submit Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>