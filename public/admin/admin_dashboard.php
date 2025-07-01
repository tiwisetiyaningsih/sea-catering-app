<?php

include '../includes/db_connect.php';
include '../includes/csrf_token.php'; 

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../dashboard.php'); 
    exit();
}

$errors = [];
$success_message = '';

$start_date = $_GET['start_date'] ?? date('Y-m-01'); 
$end_date = $_GET['end_date'] ?? date('Y-m-t');     

$new_subscriptions = 0;
$mrr = 0; 
$reactivations = 0;
$subscription_growth = 0; 

try {
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $new_subscriptions = $stmt->fetchColumn();

    
    $stmt = $pdo->prepare("
        SELECT SUM(total_price)
        FROM subscriptions
        WHERE status = 'active'
        AND start_date <= ?
        AND (end_date IS NULL OR end_date >= ?)
    ");
    $stmt->execute([$end_date, $start_date]);
    $mrr = $stmt->fetchColumn() ?: 0; 
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND reactivated_at BETWEEN ? AND ?");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $reactivations = $stmt->fetchColumn() ?: 0;

    
    $stmt = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
    $subscription_growth = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Admin Dashboard error: " . $e->getMessage());
    $errors[] = 'An error occurred while fetching dashboard data.';
}


$csrf_token = generate_csrf_token();
?>

<?php include '../includes/header.php';  ?>

<main class="container my-5">
    <h2>Admin Dashboard</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <form class="mb-4" action="admin_dashboard.php" method="GET">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">New Subscriptions (<?php echo date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)); ?>)</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $new_subscriptions; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Monthly Recurring Revenue (MRR)</div>
                <div class="card-body">
                    <h5 class="card-title">Rp<?php echo number_format($mrr, 2, ',', '.'); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Reactivations (<?php echo date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)); ?>)</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $reactivations; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white bg-dark mb-3">
                <div class="card-header">Subscription Growth (Overall Active)</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $subscription_growth; ?></h5>
                </div>
            </div>
        </div>
    </div>

    </main>

<?php include '../includes/footer.php'; ?>