<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to index page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

// Include database connection file
require_once "includes/db_connect.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        // The role column was removed in a previous migration, this query is now corrected.
        $sql = "SELECT id, username, password, is_verified FROM users WHERE username = ?";

        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if($stmt->execute()){
                $stmt->store_result();

                if($stmt->num_rows == 1){
                    $stmt->bind_result($id, $username, $hashed_password, $is_verified);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, now check if the account is verified
                            if($is_verified == 1){
                                // User is verified, start a new session
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $username;

                                // --- Start Admin Notification ---
                                require_once "includes/send_notification.php";
                                $admin_subject = "User Login Notification";
                                $admin_body = "A user has logged into the website.<br><br>"
                                            . "<strong>Username:</strong> " . htmlspecialchars($username) . "<br>"
                                            . "<strong>Time:</strong> " . date('Y-m-d H:i:s');
                                send_admin_notification($admin_subject, $admin_body);
                                // --- End Admin Notification ---

                                // Check for a redirect URL
                                if(isset($_SESSION['redirect_to_url'])){
                                    $redirect_url = $_SESSION['redirect_to_url'];
                                    unset($_SESSION['redirect_to_url']);
                                    header("location: " . $redirect_url);
                                } else {
                                    // Redirect user to index page
                                    header("location: index.php");
                                }
                                exit;
                            } else {
                                // User is not verified, guide them to the verification page
                                $_SESSION['unverified_user_id'] = $id;
                                $login_err = 'Your account is not verified. Please <a href="verify_otp.php">verify your account</a>.';
                            }
                        } else{
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    // The connection should not be closed here because the header, which needs it, is included below.
    // The connection is closed in the footer.
    // $mysqli->close();
}

// Include the header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>
</div>

<?php
// Include the footer
include 'includes/footer.php';
?>
