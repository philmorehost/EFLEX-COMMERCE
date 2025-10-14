<?php
// Include the new admin header
include 'includes/admin_header.php';
require_permission('manage_users');

$message = "";

// Handle Delete User
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])){
    $user_id_to_delete = $_GET['id'];

    // Safety check: do not allow an admin to delete their own account
    if($user_id_to_delete == $_SESSION['id']){
        $message = '<div class="alert alert-danger">You cannot delete your own account.</div>';
    } else {
        // Check if the user has any orders
        $sql_check = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
        if($stmt_check = $mysqli->prepare($sql_check)){
            $stmt_check->bind_param("i", $user_id_to_delete);
            $stmt_check->execute();
            $order_count = $stmt_check->get_result()->fetch_row()[0];
            $stmt_check->close();

            if($order_count > 0){
                $message = '<div class="alert alert-warning">Cannot delete user. This user has existing orders. Consider disabling their account instead.</div>';
            } else {
                // No orders, proceed with deletion
                $sql_delete = "DELETE FROM users WHERE id = ?";
                if($stmt_delete = $mysqli->prepare($sql_delete)){
                    $stmt_delete->bind_param("i", $user_id_to_delete);
                    if($stmt_delete->execute()){
                        header("location: manage_users.php");
                        exit;
                    } else {
                        $message = '<div class="alert alert-danger">Error deleting user.</div>';
                    }
                    $stmt_delete->close();
                }
            }
        }
    }
}

// Pagination variables
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total number of customers
$total_records_result = $mysqli->query("SELECT COUNT(*) FROM users WHERE role_id IS NULL");
$total_records = $total_records_result->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);

// Fetch customers for the current page (users with no role_id)
$sql = "SELECT id, username, email, created_at FROM users WHERE role_id IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?";
if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $users = [];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Manage Customers</h2>
</div>

<?php echo $message; ?>

<div class="card shadow">
    <div class="card-header">All Customer Accounts</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <!-- Table Header -->
                <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Registered On</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if(count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['created_at']; ?></td>
                            <td class="text-end">
                                <!-- Actions for customers could be view orders, etc. For now, just delete. -->
                                <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation">
  <ul class="pagination justify-content-center mt-4">
    <?php if($page > 1): ?><li class="page-item"><a class="page-link" href="manage_users.php?page=<?php echo $page-1; ?>">Previous</a></li><?php endif; ?>
    <?php for($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?php if($page == $i) echo 'active'; ?>"><a class="page-link" href="manage_users.php?page=<?php echo $i; ?>"><?php echo $i; ?></a></li><?php endfor; ?>
    <?php if($page < $total_pages): ?><li class="page-item"><a class="page-link" href="manage_users.php?page=<?php echo $page+1; ?>">Next</a></li><?php endif; ?>
  </ul>
</nav>
<?php
// Include the new admin footer
include 'includes/admin_footer.php';
?>
