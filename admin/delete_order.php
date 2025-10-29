<?php
include 'includes/admin_header.php';
require_permission('manage_orders');

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: manage_orders.php");
    exit();
}

$order_id = $_GET['id'];

// First, check if the order has a status of 'Cancelled' or 'Pending'
$stmt = $mysqli->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0){
    $_SESSION['error_message'] = "Order not found.";
    header("Location: manage_orders.php");
    exit();
}
$order = $result->fetch_assoc();
$stmt->close();

if($order['status'] !== 'Cancelled' && $order['status'] !== 'Pending'){
    $_SESSION['error_message'] = "Only orders with 'Cancelled' or 'Pending' status can be deleted.";
    header("Location: manage_orders.php");
    exit();
}

// Proceed with deletion
$mysqli->begin_transaction();
try {
    // Delete from order_items first to maintain referential integrity
    $stmt_items = $mysqli->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $stmt_items->close();

    // Delete from the orders table
    $stmt_order = $mysqli->prepare("DELETE FROM orders WHERE id = ?");
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $stmt_order->close();

    $mysqli->commit();
    $_SESSION['success_message'] = "Order #" . $order_id . " has been deleted successfully.";

} catch (mysqli_sql_exception $exception) {
    $mysqli->rollback();
    $_SESSION['error_message'] = "Error deleting order: " . $exception->getMessage();
}

header("Location: manage_orders.php");
exit();

// The admin footer is not included because the script redirects
// include 'includes/admin_footer.php';
?>