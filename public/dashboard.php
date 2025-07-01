<?php
include '../includes/auth_check.php';
include '../includes/db_connect.php';
include '../includes/csrf_token.php'; 

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['user_role'];

$errors = [];
$success_message = '';


if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $subscription_id = filter_input(INPUT_POST, 'subscription_id', FILTER_VALIDATE_INT);

        if (!$subscription_id) {
            $errors[] = 'Invalid subscription ID.';
        } else {
            try {
                
                $check_stmt = $pdo->prepare("SELECT user_id FROM subscriptions WHERE id = ?");
                $check_stmt->execute([$subscription_id]);
                if ($check_stmt->fetchColumn() !== $user_id) {
                    $errors[] = 'You do not have permission to modify this subscription.';
                } else {
                    if ($action === 'pause') {
                        $pause_start = filter_input(INPUT_POST, 'pause_start_date');
                        $pause_end = filter_input(INPUT_POST, 'pause_end_date');

                        if (empty($pause_start) || empty($pause_end) || $pause_start > $pause_end) {
                            $errors[] = 'Invalid pause date range. Start date must be before or equal to end date.';
                        } else {
                            
                            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'paused', pause_start_date = ?, pause_end_date = ? WHERE id = ?");
                            $stmt->execute([$pause_start, $pause_end, $subscription_id]);
                            $success_message = 'Subscription paused successfully!';
                            
                            header('Location: dashboard.php?msg=' . urlencode($success_message));
                            exit();
                        }
                    } elseif ($action === 'cancel') {
                        
                        $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled', end_date = CURDATE(), pause_start_date = NULL, pause_end_date = NULL WHERE id = ?");
                        $stmt->execute([$subscription_id]);
                        $success_message = 'Subscription cancelled successfully!';
                        
                        header('Location: dashboard.php?msg=' . urlencode($success_message));
                        exit();
                    } else {
                        $errors[] = 'Invalid action.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Dashboard action error: " . $e->getMessage());
                $errors[] = 'An error occurred. Please try again.';
            }
        }
    }
}

$subscriptions = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.id, mp.name AS plan_name, s.meal_types, s.delivery_days, s.total_price, s.status, s.start_date, s.end_date, s.pause_start_date, s.pause_end_date
        FROM subscriptions s
        JOIN meal_plans mp ON s.meal_plan_id = mp.id
        WHERE s.user_id = ? ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching subscriptions: " . $e->getMessage());
    $errors[] = 'Could not load your subscriptions. Please try again later.';
}

$csrf_token = generate_csrf_token();
?>

<?php include '../includes/header.php';  ?>

<main class="container my-5">
    <h2>Welcome, <?php echo htmlspecialchars($full_name); ?>! Your Dashboard</h2>

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

    <?php if ($user_role === 'admin'): ?>
        <div class="alert alert-info">
            You are logged in as an Admin. <a href="<?php echo $admin_url; ?>admin_dashboard.php">Go to Admin Dashboard</a>
        </div>
    <?php endif; ?>

    <h3>Your Subscriptions</h3>
    <?php if (empty($subscriptions)): ?>
        <p>You currently have no subscriptions. <a href="<?php echo $pages_url; ?>subscription.php">Subscribe to a plan now!</a></p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($subscriptions as $sub): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($sub['plan_name']); ?></h5>
                            <p class="card-text">Status:
                                <strong>
                                    <?php
                                    $status_class = '';
                                    switch ($sub['status']) {
                                        case 'active': $status_class = 'text-success'; break;
                                        case 'paused': $status_class = 'text-warning'; break;
                                        case 'cancelled': $status_class = 'text-danger'; break;
                                    }
                                    echo '<span class="' . $status_class . '">' . htmlspecialchars(ucfirst($sub['status'])) . '</span>';
                                    ?>
                                </strong>
                            </p>
                            <p class="card-text">Meal Types: <?php echo htmlspecialchars(implode(', ', json_decode($sub['meal_types']))); ?></p>
                            <p class="card-text">Delivery Days: <?php echo htmlspecialchars(implode(', ', json_decode($sub['delivery_days']))); ?></p>
                            <p class="card-text">Total Price: Rp<?php echo number_format($sub['total_price'], 2, ',', '.'); ?></p>
                            <p class="card-text">Subscribed From: <?php echo htmlspecialchars(date('d M Y', strtotime($sub['start_date']))); ?></p>
                            <?php if ($sub['end_date']): ?>
                                <p class="card-text">Ended On: <?php echo htmlspecialchars(date('d M Y', strtotime($sub['end_date']))); ?></p>
                            <?php endif; ?>
                            <?php if ($sub['status'] === 'paused' && $sub['pause_start_date'] && $sub['pause_end_date']): ?>
                                <p class="card-text text-warning">Paused from <?php echo htmlspecialchars(date('d M Y', strtotime($sub['pause_start_date']))); ?> to <?php echo htmlspecialchars(date('d M Y', strtotime($sub['pause_end_date']))); ?></p>
                            <?php endif; ?>

                            <?php if ($sub['status'] === 'active'): ?>
                                <button class="btn btn-sm btn-warning me-2" data-bs-toggle="modal" data-bs-target="#pauseModal<?php echo $sub['id']; ?>">Pause</button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $sub['id']; ?>">Cancel</button>

                                <div class="modal fade" id="pauseModal<?php echo $sub['id']; ?>" tabindex="-1" aria-labelledby="pauseModalLabel<?php echo $sub['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="dashboard.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="action" value="pause">
                                                <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="pauseModalLabel<?php echo $sub['id']; ?>">Pause Subscription: <?php echo htmlspecialchars($sub['plan_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="pause_start_date_<?php echo $sub['id']; ?>" class="form-label">Pause Start Date</label>
                                                        <input type="date" class="form-control" id="pause_start_date_<?php echo $sub['id']; ?>" name="pause_start_date" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="pause_end_date_<?php echo $sub['id']; ?>" class="form-label">Pause End Date</label>
                                                        <input type="date" class="form-control" id="pause_end_date_<?php echo $sub['id']; ?>" name="pause_end_date" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-warning">Confirm Pause</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="cancelModal<?php echo $sub['id']; ?>" tabindex="-1" aria-labelledby="cancelModalLabel<?php echo $sub['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="dashboard.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="cancelModalLabel<?php echo $sub['id']; ?>">Cancel Subscription: <?php echo htmlspecialchars($sub['plan_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to permanently cancel your <strong><?php echo htmlspecialchars($sub['plan_name']); ?></strong> subscription?</p>
                                                    <p class="text-danger">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, go back</button>
                                                    <button type="submit" class="btn btn-danger">Yes, Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>