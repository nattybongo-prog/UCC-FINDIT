<?php
include 'db.php';
session_start();

$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';

$sql = "SELECT * FROM items WHERE (item_name LIKE ? OR location LIKE ? OR category LIKE ?)";
if ($filter_type === 'Lost' || $filter_type === 'Found') {
    $sql .= " AND type = ?";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$searchTerm = "%" . $search . "%";

if ($filter_type === 'Lost' || $filter_type === 'Found') {
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $filter_type);
} else {
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC FindIt - Campus Lost & Found</title>
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
        .btn-ucc-gold {
            background-color: #ffc107;
            color: #0d2346;
            font-weight: bold;
        }
        .btn-ucc-gold:hover {
            background-color: #e0a800;
            color: #0d2346;
        }
        .text-ucc {
            color: #0d2346;
        }
        .item-card {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
    </style>
</head>
<body>

    <!-- Branded Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-ucc shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <img src="img/logo.png" alt="UCC Crest" height="40">
                <span>UCC FindIt</span>
            </a>
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user'])): ?>
                    <span class="text-white me-3 small d-none d-md-inline">Hi, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
                    <a href="report.php" class="btn btn-ucc-gold btn-sm me-2">+ Report Item</a>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php else: ?>
                    <a href="report.php" class="btn btn-ucc-gold btn-sm me-2">+ Report Item</a>
                    <a href="auth.php" class="btn btn-outline-light btn-sm">Login / Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container my-5">
        <div class="text-center mb-4">
            <img src="img/logo.png" alt="UCC Crest" height="90" class="mb-3">
            <h1 class="display-6 fw-bold text-ucc">University of Cape Coast</h1>
            <p class="text-muted fw-bold">Official Campus Lost & Found Platform</p>
        </div>

        <!-- Campus Storage / Direction Notice Box -->
        <div class="alert alert-warning border-0 shadow-sm p-3 mb-4 rounded-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <strong>📍 Campus Recovery Center:</strong> All physical items (IDs, phones, wallets) found on campus are safely kept at the <strong>UCC Security Headquarters / Dean of Students Office</strong>.
            </div>
            <button class="btn btn-sm btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#directionsModal">View Directions</button>
        </div>

        <!-- Grouping Tabs -->
        <ul class="nav nav-pills justify-content-center mb-4">
            <li class="nav-item">
                <a class="nav-link fw-bold <?php if($filter_type == '') echo 'active bg-primary text-white'; else echo 'text-dark'; ?>" href="index.php">All Items</a>
            </li>
            <li class="nav-item mx-2">
                <a class="nav-link fw-bold <?php if($filter_type == 'Lost') echo 'active bg-danger text-white'; else echo 'text-danger border border-danger'; ?>" href="index.php?type=Lost">Lost Items</a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold <?php if($filter_type == 'Found') echo 'active bg-success text-white'; else echo 'text-success border border-success'; ?>" href="index.php?type=Found">Found Items</a>
            </li>
        </ul>

        <!-- Search Bar Form -->
        <form method="GET" action="index.php" class="row g-2 justify-content-center mb-5">
            <?php if($filter_type): ?>
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($filter_type); ?>">
            <?php endif; ?>
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search item name, location, or category..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-ucc-gold w-100">Search</button>
            </div>
            <?php if (!empty($search) || !empty($filter_type)): ?>
                <div class="col-md-1">
                    <a href="index.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            <?php endif; ?>
        </form>

        <h3 class="mb-4 text-ucc border-bottom pb-2 h5 fw-bold">
            <?php 
                if($filter_type == 'Lost') echo 'Lost Items Feed';
                elseif($filter_type == 'Found') echo 'Found Items Feed';
                else echo 'Recent Campus Reports';
            ?>
        </h3>

        <!-- Feed Grid -->
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <!-- Clicking card triggers unique modal popup -->
                        <div class="card h-100 shadow-sm border-0 item-card" data-bs-toggle="modal" data-bs-target="#itemModal<?php echo $row['id']; ?>">
                            <?php if (!empty($row['image'])): ?>
                                <img src="img/<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="Item Image" style="height: 190px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge <?php echo ($row['type'] == 'Lost') ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo htmlspecialchars($row['type']); ?>
                                    </span>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category']); ?></span>
                                </div>
                                <h5 class="card-title fw-bold text-dark"><?php echo htmlspecialchars($row['item_name']); ?></h5>
                                <p class="card-text text-muted small mb-2"><strong>📍 Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                                <p class="card-text flex-grow-1 small text-truncate"><?php echo htmlspecialchars($row['description']); ?></p>
                                <div class="text-primary small fw-bold mt-2">Click to view details & recovery info →</div>
                            </div>
                        </div>
                    </div>

                    <!-- INDIVIDUAL POPUP MODAL FOR EACH ITEM -->
                    <div class="modal fade" id="itemModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <div class="modal-header bg-ucc text-white" style="background-color: #0d2346;">
                                    <h5 class="modal-title fw-bold">
                                        <span class="badge <?php echo ($row['type'] == 'Lost') ? 'bg-danger' : 'bg-success'; ?> me-2">
                                            <?php echo htmlspecialchars($row['type']); ?>
                                        </span>
                                        <?php echo htmlspecialchars($row['item_name']); ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row">
                                        <?php if (!empty($row['image'])): ?>
                                            <div class="col-md-6 mb-3 mb-md-0 text-center">
                                                <img src="img/<?php echo htmlspecialchars($row['image']); ?>" class="img-fluid rounded-3 shadow-sm" alt="Item Image" style="max-height: 300px; width: 100%; object-fit: cover;">
                                            </div>
                                            <div class="col-md-6">
                                        <?php else: ?>
                                            <div class="col-12">
                                        <?php endif; ?>
                                            <p><strong>Category:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category']); ?></span></p>
                                            <p><strong>Last Known Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                                            <p><strong>Detailed Description:</strong><br><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                            
                                            <div class="alert alert-primary p-3 small mb-3">
                                                <strong>📞 Direct Contact:</strong><br><?php echo htmlspecialchars($row['contact_info']); ?>
                                            </div>

                                            <div class="alert alert-warning p-3 small mb-0">
                                                <strong>🏢 Where to claim / drop off:</strong><br>
                                                If this is your item or you want to hand it over, visit the **UCC Security HQ** or the **SRC Office**. Bring your Student ID for verification.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No items found under this category.</p>
                    <a href="index.php" class="btn btn-outline-primary btn-sm">View All Items</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CAMPUS DIRECTIONS MODAL -->
    <div class="modal fade" id="directionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">📍 UCC Campus Recovery Centers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted">To ensure safety and verification, items found around campus are stored at specific designated locations:</p>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>1. Main Security Headquarters:</strong> For electronics, IDs, cash, and high-value belongings.</li>
                        <li class="list-group-item"><strong>2. SRC Office (Old/New Admin):</strong> For students submitting books, certificates, and accessories.</li>
                        <li class="list-group-item"><strong>3. Hall Residences (Casford, Adehye, Kwame Nkrumah, etc.):</strong> Hall porters hold items found specifically within residential quads.</li>
                    </ul>
                    <div class="alert alert-info small mb-0">
                        Always bring your <strong>University of Cape Coast Student ID card</strong> when claiming any item.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark btn-sm" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>