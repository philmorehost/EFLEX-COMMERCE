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

// Include the header and database connection
require_once 'includes/db_connect.php';

// Check if token is provided
if(!isset($_GET['token']) || empty($_GET['token'])){
    die("Invalid download link.");
}

$token = $_GET['token'];
$user_id = $_SESSION['id'];

// Fetch download details
$sql = "
    SELECT cd.id, cd.downloads_remaining, pd.file_path
    FROM customer_downloads cd
    JOIN product_downloads pd ON cd.product_download_id = pd.id
    WHERE cd.download_token = ? AND cd.user_id = ?
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("si", $token, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 1){
    $download = $result->fetch_assoc();
    if($download['downloads_remaining'] > 0){
        // Decrement download count
        $new_downloads_remaining = $download['downloads_remaining'] - 1;
        $sql_update = "UPDATE customer_downloads SET downloads_remaining = ? WHERE id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("ii", $new_downloads_remaining, $download['id']);
        $stmt_update->execute();
        $stmt_update->close();

        // Serve the file
        $file_path = '../downloads/' . $download['file_path'];
        if(file_exists($file_path)){
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            die("File not found.");
        }
    } else {
        die("Download limit reached.");
    }
} else {
    die("Invalid download link.");
}
$stmt->close();
?>
