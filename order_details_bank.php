<?php
// We need to start the session on all pages to access session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Check if an order ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])){
    header("location: my_orders.php");
    exit;
}
$order_id = $_GET['id'];
$user_id = $_SESSION['id'];

// Fetch order details and verify ownership
$sql_order = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$order = null;
if($stmt_order = $mysqli->prepare($sql_order)){
    $stmt_order->bind_param("ii", $order_id, $user_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    if($result_order->num_rows == 1){
        $order = $result_order->fetch_assoc();
    } else {
        header("location: my_orders.php");
        exit;
    }
    $stmt_order->close();
}

// Fetch bank details from settings
$settings_sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'bank_%'";
$result = $mysqli->query($settings_sql);
$bank_settings = [];
while($row = $result->fetch_assoc()){
    $bank_settings[$row['setting_key']] = $row['setting_value'];
}


// Include the header
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="alert alert-info">
        <h4 class="alert-heading">Order Placed! Awaiting Payment</h4>
        <p>Your order with ID <strong>#<?php echo htmlspecialchars($order_id); ?></strong> has been placed successfully. Please make a payment to the bank account below to complete your order.</p>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Bank Account Details
                </div>
                <div class="card-body">
                    <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank_settings['bank_name'] ?? 'N/A'); ?></p>
                    <p><strong>Account Name:</strong> <?php echo htmlspecialchars($bank_settings['bank_account_name'] ?? 'N/A'); ?></p>
                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($bank_settings['bank_account_number'] ?? 'N/A'); ?></p>
                    <hr>
                    <h5>Instructions:</h5>
                    <p><?php echo nl2br(htmlspecialchars($bank_settings['bank_payment_instructions'] ?? 'Please use your Order ID as the payment reference.')); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <p>After making the payment, please go to your "My Orders" page to upload the proof of payment.</p>
            <a href="my_orders.php" class="btn btn-primary">Go to My Orders</a>
        </div>
    </div>
</div>

<?php
// Include the footer
include 'includes/footer.php';
?>
