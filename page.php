<?php
// Include header
include 'includes/header.php';
require_once 'includes/db_connect.php';

// Check if slug is provided
if (!isset($_GET['slug'])) {
    // If no slug, maybe redirect to home or show a generic message
    header("Location: index.php");
    exit;
}

$slug = $_GET['slug'];

// Fetch the page from the database
// We only want to fetch it if it's published
$stmt = $mysqli->prepare("SELECT title, content FROM pages WHERE slug = ? AND is_published = 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$stmt->store_result();

$page_found = false;
if ($stmt->num_rows > 0) {
    $stmt->bind_result($title, $content);
    $stmt->fetch();
    $page_found = true;
}
$stmt->close();

?>

<div class="container my-5">
    <?php if ($page_found): ?>
        <h1 class="mb-4"><?php echo htmlspecialchars($title); ?></h1>
        <div class="page-content">
            <?php echo $content; // Content is from TinyMCE, so it's expected to be HTML ?>
        </div>
    <?php else: ?>
        <div class="text-center">
            <h1>404 - Page Not Found</h1>
            <p>Sorry, the page you are looking for does not exist or has been moved.</p>
            <a href="index.php" class="btn btn-primary">Go to Homepage</a>
        </div>
    <?php endif; ?>
</div>


<?php
// Include footer
include 'includes/footer.php';
?>
