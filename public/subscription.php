<?php
include '../includes/header.php';
include '../includes/db_connect.php';

$plan_data = [];
try {
    $stmt = $pdo->query("SELECT id, plan_name, price FROM meal_plans ORDER BY price ASC");
    $plan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching meal plans: " . $e->getMessage());
    $plan_data = []; 
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $plan_id = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
    $meal_types = isset($_POST['meal_type']) ? implode(',', array_map('htmlspecialchars', $_POST['meal_type'])) : '';
    $delivery_days = isset($_POST['delivery_days']) ? implode(',', array_map('htmlspecialchars', $_POST['delivery_days'])) : '';
    $allergies = filter_input(INPUT_POST, 'allergies', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $total_price = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT);

    
    if (empty($name) || empty($phone_number) || !$plan_id || empty($meal_types) || empty($delivery_days) || $total_price === false) {
        $error_message = 'Please fill in all required fields and ensure price is calculated correctly.';
    } else {
        try {
            
            $stmt_plan = $pdo->prepare("SELECT price FROM meal_plans WHERE id = ?");
            $stmt_plan->execute([$plan_id]);
            $selected_plan = $stmt_plan->fetch(PDO::FETCH_ASSOC);

            if (!$selected_plan) {
                $error_message = 'Selected meal plan is invalid.';
            } else {
                
                $plan_price_db = $selected_plan['price'];
                $num_meal_types = count(explode(',', $meal_types));
                $num_delivery_days = count(explode(',', $delivery_days));
                $calculated_price = $plan_price_db * $num_meal_types * $num_delivery_days * 4.3;

                if (abs($calculated_price - $total_price) > 0.01) { 
                    $error_message = 'Calculated price mismatch. Please try again.';
                    error_log("Price mismatch for user " . $name . ": Client submitted " . $total_price . ", Server calculated " . $calculated_price);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO subscriptions (name, phone_number, plan_id, meal_types, delivery_days, allergies, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $phone_number, $plan_id, $meal_types, $delivery_days, $allergies, $calculated_price]);
                    $success_message = 'Your subscription has been successfully placed!';
                }
            }
        } catch (PDOException $e) {
            error_log("Error saving subscription: " . $e->getMessage());
            $error_message = 'There was an error processing your subscription. Please try again later.';
        }
    }
}
?>

<main class="container my-5">
    <h2 class="text-center mb-5 display-5 fw-bold">Subscribe to a Meal Plan</h2>

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

    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-lg p-4">
                <form id="subscriptionForm" method="POST" action="subscription.php">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                    </div>

                    <div class="mb-3">
                        <label for="phoneNumber" class="form-label">Active Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phoneNumber" name="phone_number" placeholder="e.g., 08123456789" required pattern="^08[0-9]{8,11}$">
                        <small class="form-text text-muted">Format: 08xxxxxxxxxx (8-11 digits after 08)</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Plan Selection <span class="text-danger">*</span></label>
                        <?php if (empty($plan_data)): ?>
                            <p class="text-danger">No meal plans available. Please contact support.</p>
                        <?php else: ?>
                            <?php foreach ($plan_data as $plan): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="plan_id" id="plan<?php echo $plan['id']; ?>" value="<?php echo $plan['id']; ?>" data-price="<?php echo $plan['price']; ?>" required>
                                    <label class="form-check-label" for="plan<?php echo $plan['id']; ?>"><?php echo $plan['plan_name']; ?> (Rp<?php echo number_format($plan['price'], 0, ',', '.'); ?>/meal)</label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Meal Type(s) <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input meal-type" type="checkbox" id="breakfast" name="meal_type[]" value="Breakfast">
                            <label class="form-check-label" for="breakfast">Breakfast</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input meal-type" type="checkbox" id="lunch" name="meal_type[]" value="Lunch">
                            <label class="form-check-label" for="lunch">Lunch</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input meal-type" type="checkbox" id="dinner" name="meal_type[]" value="Dinner">
                            <label class="form-check-label" for="dinner">Dinner</label>
                        </div>
                        <div class="invalid-feedback d-none" id="mealTypeError">
                            Please select at least one meal type.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Delivery Day(s) <span class="text-danger">*</span></label>
                        <?php
                        $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days_of_week as $day) {
                            echo '<div class="form-check form-check-inline">';
                            echo '<input class="form-check-input delivery-day" type="checkbox" id="' . strtolower($day) . '" name="delivery_days[]" value="' . $day . '">';
                            echo '<label class="form-check-label" for="' . strtolower($day) . '">' . $day . '</label>';
                            echo '</div>';
                        }
                        ?>
                        <div class="invalid-feedback d-none" id="deliveryDaysError">
                            Please select at least one delivery day.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="allergies" class="form-label">Allergies/Dietary Restrictions (Optional)</label>
                        <textarea class="form-control" id="allergies" name="allergies" rows="3" placeholder="e.g., Peanut allergy, Gluten-free, Vegetarian"></textarea>
                    </div>

                    <div class="mb-4">
                        <h4 class="text-center">Total Price: <span id="totalPriceDisplay" class="text-success fw-bold">Rp0</span></h4>
                        <input type="hidden" id="totalPriceInput" name="total_price" value="0">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Subscribe Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const subscriptionForm = document.getElementById('subscriptionForm');
        const planRadios = subscriptionForm.querySelectorAll('input[name="plan_id"]');
        const mealTypeCheckboxes = subscriptionForm.querySelectorAll('.meal-type');
        const deliveryDayCheckboxes = subscriptionForm.querySelectorAll('.delivery-day');
        const totalPriceDisplay = document.getElementById('totalPriceDisplay');
        const totalPriceInput = document.getElementById('totalPriceInput');
        const mealTypeError = document.getElementById('mealTypeError');
        const deliveryDaysError = document.getElementById('deliveryDaysError');

        function calculateTotalPrice() {
            let selectedPlanPrice = 0;
            planRadios.forEach(radio => {
                if (radio.checked) {
                    selectedPlanPrice = parseFloat(radio.dataset.price);
                }
            });

            let numMealTypes = 0;
            mealTypeCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    numMealTypes++;
                }
            });

            let numDeliveryDays = 0;
            deliveryDayCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    numDeliveryDays++;
                }
            });

            if (selectedPlanPrice > 0 && numMealTypes > 0 && numDeliveryDays > 0) {
                const totalPrice = selectedPlanPrice * numMealTypes * numDeliveryDays * 4.3;
                totalPriceDisplay.textContent = 'Rp' + totalPrice.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                totalPriceInput.value = totalPrice.toFixed(2); // Store with 2 decimal places for backend
            } else {
                totalPriceDisplay.textContent = 'Rp0';
                totalPriceInput.value = '0';
            }
        }

        planRadios.forEach(radio => radio.addEventListener('change', calculateTotalPrice));
        mealTypeCheckboxes.forEach(checkbox => checkbox.addEventListener('change', calculateTotalPrice));
        deliveryDayCheckboxes.forEach(checkbox => checkbox.addEventListener('change', calculateTotalPrice));

        calculateTotalPrice();

        subscriptionForm.addEventListener('submit', function(event) {
            let isMealTypeSelected = false;
            mealTypeCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    isMealTypeSelected = true;
                }
            });

            if (!isMealTypeSelected) {
                mealTypeError.classList.remove('d-none');
                event.preventDefault();
            } else {
                mealTypeError.classList.add('d-none');
            }

            let isDeliveryDaySelected = false;
            deliveryDayCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    isDeliveryDaySelected = true;
                }
            });

            if (!isDeliveryDaySelected) {
                deliveryDaysError.classList.remove('d-none');
                event.preventDefault();
            } else {
                deliveryDaysError.classList.add('d-none');
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>