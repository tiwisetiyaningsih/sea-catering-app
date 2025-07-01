<?php
include '../includes/header.php';
include '../includes/db_connect.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $review_message = filter_input(INPUT_POST, 'review_message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

    if (empty($customer_name) || empty($review_message) || $rating === false) {
        $error_message = 'Please fill in all required fields and provide a valid rating.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO testimonials (customer_name, review_message, rating) VALUES (?, ?, ?)");
            $stmt->execute([$customer_name, $review_message, $rating]);
            $success_message = 'Your testimonial has been submitted successfully and will be reviewed!';
            $_POST = array(); 
        } catch (PDOException $e) {
            error_log("Error submitting testimonial: " . $e->getMessage());
            $error_message = 'There was an error submitting your testimonial. Please try again later.';
        }
    }
}

$testimonials = [];
try {
    $stmt = $pdo->query("SELECT customer_name, review_message, rating FROM testimonials WHERE approved = TRUE ORDER BY submission_date DESC");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching testimonials: " . $e->getMessage());
    // Display a user-friendly message
    echo "<div class='alert alert-danger text-center'>Could not load testimonials.</div>";
}
?>

<main class="container my-5">
    <h2 class="text-center mb-5 display-5 fw-bold">What Our Customers Say</h2>

    <section class="mb-5">
        <h3 class="text-center mb-4">Share Your Experience!</h3>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm p-4">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="testimonials.php">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customerName" name="customer_name" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="reviewMessage" class="form-label">Your Review <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reviewMessage" name="review_message" rows="4" placeholder="Share your thoughts about SEA Catering..." required></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="rating" class="form-label">Rating <span class="text-danger">*</span></label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="" selected disabled>Choose...</option>
                                <option value="5">5 Stars - Excellent!</option>
                                <option value="4">4 Stars - Very Good</option>
                                <option value="3">3 Stars - Good</option>
                                <option value="2">2 Stars - Fair</option>
                                <option value="1">1 Star - Poor</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Submit Testimonial</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <hr class="my-5">

    <section>
        <h3 class="text-center mb-4">Customer Reviews</h3>
        <?php if (!empty($testimonials)): ?>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php for ($i = 0; $i < count($testimonials); $i++): ?>
                        <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="<?php echo $i; ?>" class="<?php echo ($i == 0) ? 'active' : ''; ?>" aria-current="<?php echo ($i == 0) ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $i + 1; ?>"></button>
                    <?php endfor; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($testimonials as $index => $testimonial): ?>
                        <div class="carousel-item <?php echo ($index == 0) ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-center">
                                <div class="card text-center p-4 shadow-sm w-75">
                                    <div class="card-body">
                                        <p class="lead font-italic mb-3">"<?php echo htmlspecialchars($testimonial['review_message']); ?>"</p>
                                        <footer class="blockquote-footer"><?php echo htmlspecialchars($testimonial['customer_name']); ?></footer>
                                        <div class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $testimonial['rating']): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        <?php else: ?>
            <div class="text-center">
                <p>No testimonials available yet. Be the first to share your experience!</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>