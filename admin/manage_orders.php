<?php
// Include the new admin header
include 'includes/admin_header.php';
require_permission('manage_orders');

// Pagination variables
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total number of orders
$total_records_result = $mysqli->query("SELECT COUNT(*) FROM orders");
$total_records = $total_records_result->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);

// Fetch orders for the current page
$sql = "SELECT o.id, u.username, o.total_amount, o.status, o.created_at
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";
if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $orders = [];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Manage Orders</h2>
</div>

<div class="card shadow">
    <div class="card-header">All Orders</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <!-- Table header -->
                <thead><tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if(count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo $order['created_at']; ?></td>
                            <td><?php echo htmlspecialchars($_SESSION['currency_symbol']); ?><?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($order['status']); ?></span></td>
                            <td class="text-end">
                                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation">
  <ul class="pagination justify-content-center mt-4">
    <?php if($page > 1): ?><li class="page-item"><a class="page-link" href="manage_orders.php?page=<?php echo $page-1; ?>">Previous</a></li><?php endif; ?>
    <?php for($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?php if($page == $i) echo 'active'; ?>"><a class="page-link" href="manage_orders.php?page=<?php echo $i; ?>"><?php echo $i; ?></a></li><?php endfor; ?>
    <?php if($page < $total_pages): ?><li class="page-item"><a class="page-link" href="manage_orders.php?page=<?php echo $page+1; ?>">Next</a></li><?php endif; ?>
  </ul>
</nav>
<?php
// Include the new admin footer
include 'includes/admin_footer.php';
?>
