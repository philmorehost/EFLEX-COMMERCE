<?php
// Include the new admin header
include 'includes/admin_header.php';
require_permission('manage_products');

$message = "";

// Handle Delete Product
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $sql_img = "SELECT image FROM products WHERE id = ?";
    if($stmt_img = $mysqli->prepare($sql_img)){
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $stmt_img->bind_result($image_filename);
        $stmt_img->fetch();
        $stmt_img->close();
        if($image_filename && $image_filename != 'default.jpg' && file_exists("../uploads/" . $image_filename)){
            unlink("../uploads/" . $image_filename);
        }
    }
    $sql = "DELETE FROM products WHERE id = ?";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
             header("location: manage_products.php");
             exit();
        } else {
            $message = '<div class="alert alert-danger">Error deleting product.</div>';
        }
        $stmt->close();
    }
}

// Pagination variables
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10; // Show 10 products per page for admin
$offset = ($page - 1) * $records_per_page;

// Get total number of products
$total_records_result = $mysqli->query("SELECT COUNT(*) FROM products");
$total_records = $total_records_result->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);


// Fetch products for the current page
$sql = "SELECT p.id, p.name, p.price, p.image, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.name ASC
        LIMIT ? OFFSET ?";
if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $products = [];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Products</h2>
        <a href="add_product.php" class="btn btn-success">Add New Product</a>
    </div>

    <?php echo $message; ?>

    <div class="card shadow">
        <div class="card-header">Existing Products</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <!-- Table header -->
                    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php if(count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($_SESSION['currency_symbol']); ?><?php echo htmlspecialchars($product['price']); ?></td>
                                <td class="text-end">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="manage_products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No products found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center mt-4">
        <?php if($page > 1): ?><li class="page-item"><a class="page-link" href="manage_products.php?page=<?php echo $page-1; ?>">Previous</a></li><?php endif; ?>
        <?php for($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?php if($page == $i) echo 'active'; ?>"><a class="page-link" href="manage_products.php?page=<?php echo $i; ?>"><?php echo $i; ?></a></li><?php endfor; ?>
        <?php if($page < $total_pages): ?><li class="page-item"><a class="page-link" href="manage_products.php?page=<?php echo $page+1; ?>">Next</a></li><?php endif; ?>
      </ul>
    </nav>
</div>
<?php
// Include the new admin footer
include 'includes/admin_footer.php';
?>
