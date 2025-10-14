<?php
// We need to start the session on all pages to access session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'includes/db_connect.php';

// Initialize the cart session if it doesn't exist
if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = array();
}

// Handle Add to Cart from product cards (for non-variant products)
if(isset($_POST['add_to_cart']) && isset($_POST['product_id'])){
    $product_id = (int)$_POST['product_id'];
    $variant_id = (int)$_POST['variant_id'] ?? 0;
    $quantity = (int)$_POST['quantity'] ?? 1;

    $cart_key = $variant_id > 0 ? 'v_' . $variant_id : 'p_' . $product_id;

    if($quantity > 0 && $product_id > 0) {
        if(isset($_SESSION['cart'][$cart_key])){
            $_SESSION['cart'][$cart_key] += $quantity;
        } else {
            $_SESSION['cart'][$cart_key] = $quantity;
        }
    }
    // Redirect to the cart page to show the updated cart and prevent form resubmission
    header('Location: cart.php');
    exit();
}


// Handle Remove from Cart action
if(isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['cart_key'])){
    $cart_key = $_GET['cart_key'];
    unset($_SESSION['cart'][$cart_key]);
    header('location: cart.php');
    exit();
}

// Handle Update Quantity action
if(isset($_POST['action']) && $_POST['action'] == 'update'){
    $cart_key = $_POST['cart_key'];
    $quantity = (int)$_POST['quantity'];

    if($quantity > 0){
        $_SESSION['cart'][$cart_key] = $quantity;
    } else {
        // Remove item if quantity is 0 or less
        unset($_SESSION['cart'][$cart_key]);
    }
    header('location: cart.php');
    exit();
}

// The logic below is for displaying the cart contents.

// --- Logic to display the cart ---
$cart_items = [];
$total_price = 0;
if(!empty($_SESSION['cart'])){
    $product_ids = [];
    $variant_ids = [];
    foreach($_SESSION['cart'] as $key => $qty) {
        if (strpos($key, 'p_') === 0) {
            $product_ids[] = (int)substr($key, 2);
        } elseif (strpos($key, 'v_') === 0) {
            $variant_ids[] = (int)substr($key, 2);
        }
    }

    // Fetch simple products
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $sql = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
        if($stmt = $mysqli->prepare($sql)){
            $types = str_repeat('i', count($product_ids));
            $stmt->bind_param($types, ...$product_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()){
                $cart_key = 'p_' . $row['id'];
                $quantity = $_SESSION['cart'][$cart_key];
                $subtotal = $row['price'] * $quantity;
                $total_price += $subtotal;
                $cart_items[] = [
                    'cart_key' => $cart_key,
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'image' => $row['image'],
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'is_variant' => false
                ];
            }
            $stmt->close();
        }
    }

    // Fetch variant products
    if (!empty($variant_ids)) {
        $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
        $sql = "
            SELECT v.id as variant_id, p.id as product_id, p.name, v.price, p.image,
                   (SELECT p_base.price FROM products p_base WHERE p_base.id = v.product_id) as base_price,
                   GROUP_CONCAT(CONCAT(pa.name, ': ', av.value) SEPARATOR ', ') as options
            FROM product_variants v
            JOIN products p ON v.product_id = p.id
            JOIN product_variant_options pvo ON v.id = pvo.variant_id
            JOIN attribute_values av ON pvo.value_id = av.id
            JOIN product_attributes pa ON pvo.attribute_id = pa.id
            WHERE v.id IN ($placeholders)
            GROUP BY v.id
        ";
        if($stmt = $mysqli->prepare($sql)){
            $types = str_repeat('i', count($variant_ids));
            $stmt->bind_param($types, ...$variant_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()){
                $cart_key = 'v_' . $row['variant_id'];
                $quantity = $_SESSION['cart'][$cart_key];
                $price = $row['price'] ?? $row['base_price'];
                $subtotal = $price * $quantity;
                $total_price += $subtotal;
                $cart_items[] = [
                    'cart_key' => $cart_key,
                    'id' => $row['variant_id'],
                    'name' => $row['name'] . ' (' . $row['options'] . ')',
                    'price' => $price,
                    'image' => $row['image'],
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'is_variant' => true
                ];
            }
            $stmt->close();
        }
    }
}

// Include the header
include 'includes/header.php';
?>

<h2>Shopping Cart</h2>

<?php if(!empty($cart_items)): ?>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th scope="col" colspan="2">Product</th>
                <th scope="col">Price</th>
                <th scope="col" style="width: 150px;">Quantity</th>
                <th scope="col" class="text-end">Subtotal</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cart_items as $item): ?>
            <tr>
                <td style="width: 100px;"><img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($item['name']); ?>"></td>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($_SESSION['currency_symbol']); ?><?php echo number_format($item['price'], 2); ?></td>
                <td>
                    <form action="cart.php" method="post" class="d-flex">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="cart_key" value="<?php echo $item['cart_key']; ?>">
                        <input type="number" name="quantity" class="form-control form-control-sm" value="<?php echo $item['quantity']; ?>" min="1">
                        <button type="submit" class="btn btn-sm btn-primary ms-2">Update</button>
                    </form>
                </td>
                <td class="text-end"><?php echo htmlspecialchars($_SESSION['currency_symbol']); ?><?php echo number_format($item['subtotal'], 2); ?></td>
                <td class="text-end"><a href="cart.php?action=remove&cart_key=<?php echo $item['cart_key']; ?>" class="btn btn-sm btn-danger">&times;</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong><?php echo htmlspecialchars($_SESSION['currency_symbol']); ?><?php echo number_format($total_price, 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>
<div class="d-flex justify-content-end">
    <a href="products.php" class="btn btn-secondary me-2">Continue Shopping</a>
    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
</div>
<?php else: ?>
    <div class="alert alert-info">Your shopping cart is empty.</div>
    <a href="products.php" class="btn btn-primary">Start Shopping</a>
<?php endif; ?>

<?php
// Include the footer
include 'includes/footer.php';
?>
