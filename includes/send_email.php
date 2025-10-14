<?php
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

require_once __DIR__ . '/email_template.php';

function send_email($to, $subject, $body_content) {
    // We need the database connection to fetch settings
    global $mysqli;

    // Fetch all site settings from the database for branding and SMTP
    $settings_sql = "SELECT setting_key, setting_value FROM settings";
    $result = $mysqli->query($settings_sql);
    $settings = [];
    while($row = $result->fetch_assoc()){
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Check if essential SMTP settings are present
    if (empty($settings['smtp_host']) || empty($settings['from_email'])) {
        error_log("SMTP settings are not configured in the admin panel.");
        return false;
    }

    // Generate the full HTML email using the modern template
    $html_body = get_modern_email_html($subject, $body_content, $settings);

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_user'];
        $mail->Password   = $settings['smtp_pass'] ?? '';
        $mail->SMTPSecure = ($settings['smtp_encryption'] ?? 'tls') === 'none' ? '' : ($settings['smtp_encryption'] ?? 'tls');
        $mail->Port       = $settings['smtp_port'] ?? 587;
        // $mail->SMTPDebug = 2; // Enable for detailed debug output

        // Recipients
        $mail->setFrom($settings['from_email'], $settings['from_name'] ?? 'Eflex');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags($body_content);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
