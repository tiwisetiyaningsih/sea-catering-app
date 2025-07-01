<?php
include '../includes/header.php';
include '../includes/db_connect.php';

$meal_plans = [];
try {
    $stmt = $pdo->query("SELECT id, plan_name, price, description FROM meal_plans ORDER BY id ASC");
    $meal_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching meal plans for menu: " . $e->getMessage());
    echo "<div class='alert alert-danger text-center'>Could not load meal plans. Please try again later.</div>";
}
?>

<main class="container my-5">
    <h2 class="text-center mb-5 display-5 fw-bold">Our Delicious Meal Plans</h2>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (!empty($meal_plans)): ?>
            <?php foreach ($meal_plans as $plan): ?>
                <div class="col">
    <div class="card h-100 shadow-sm">
        <img src="https://i0.wp.com/ciputrahospital.com/wp-content/uploads/2025/02/makanan-sehat-dan-bergizi-1.jpeg?resize=1536%2C864&ssl=1"
             class="card-img-top"
             alt="<?php echo htmlspecialchars($plan['plan_name']); ?>">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($plan['plan_name']); ?></h5>
            <p class="card-text text-success fw-bold fs-4">Rp<?php echo number_format($plan['price'], 0, ',', '.'); ?> / meal</p>
            <p class="card-text"><?php echo htmlspecialchars($plan['description']); ?></p>
            <button type="button" class="btn btn-primary mt-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#planModal<?php echo $plan['id']; ?>">
                See More Details
            </button>
        </div>
    </div>
</div>


                <div class="modal fade" id="planModal<?php echo $plan['id']; ?>" tabindex="-1" aria-labelledby="planModalLabel<?php echo $plan['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="planModalLabel<?php echo $plan['id']; ?>"><?php echo htmlspecialchars($plan['plan_name']); ?> Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Price:</strong> Rp<?php echo number_format($plan['price'], 0, ',', '.'); ?> per meal</p>
                                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                                <?php if ($plan['plan_name'] == 'Diet Plan'): ?>
                                    <p><strong>Key Benefits:</strong> Calorie-controlled portions, Rich in fiber, Supports healthy weight loss.</p>
                                    <p><strong>Sample Meals:</strong> Grilled Chicken with Steamed Broccoli, Salmon with Quinoa, Vegetable Stir-fry.</p>
                                <?php elseif ($plan['plan_name'] == 'Protein Plan'): ?>
                                    <p><strong>Key Benefits:</strong> High protein content, Supports energy levels, Aids in recovery.</p>
                                    <p><strong>Sample Meals:</strong> Beef Rendang (lean cut), Chicken Breast with Sweet Potato, Tofu & Tempeh Curry.</p>
                                <?php elseif ($plan['plan_name'] == 'Royal Plan'): ?>
                                    <p><strong>Key Benefits:</strong> Premium ingredients, Gourmet recipes, Luxurious dining experience.</p>
                                    <p><strong>Sample Meals:</strong> Pan-seared Duck Breast with Berry Reduction, Truffle Pasta, Salmon en Papillote.</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <a href="subscription.php?plan_id=<?php echo $plan['id']; ?>" class="btn btn-primary">Subscribe Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p>No meal plans found. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>