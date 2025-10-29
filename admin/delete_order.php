<?php
// Include the new admin header
include 'includes/admin_header.php';
require_permission('manage_orders');

// Check if Order ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])){
    header("Location: manage_orders.php");
    exit;
}
$order_id = $_GET['id'];

// Fetch the order to ensure it exists and has a cancellable status
$stmt = $mysqli->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0){
    $_SESSION['error_message'] = "Order not found.";
    header("Location: manage_orders.php");
    exit;
}
$order = $result->fetch_assoc();
$stmt->close();

// Only allow deletion for specific statuses
$allowed_statuses = ['Cancelled', 'Awaiting Payment', 'Pending'];
if (!in_array($order['status'], $allowed_statuses)) {
    $_SESSION['error_message'] = "Only orders with status 'Cancelled', 'Awaiting Payment', or 'Pending' can be deleted.";
    header("Location: manage_orders.php");
    exit;
}

// Proceed with deletion
$stmt_delete = $mysqli->prepare("DELETE FROM orders WHERE id = ?");
$stmt_delete->bind_param("i", $order_id);
if($stmt_delete->execute()){
    $_SESSION['success_message'] = "Order #" . $order_id . " has been permanently deleted.";
} else {
    $_SESSION['error_message'] = "Failed to delete the order. Please try again.";
}
$stmt_delete->close();

header("Location: manage_orders.php");
exit;
?>