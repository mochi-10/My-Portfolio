<?php
// Load Composer's autoloader if you used Composer
// require '../vendor/autoload.php';

// Or load PHPMailer classes manually if you downloaded manually:
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$receiving_email_address = 'mochibrian10@gmail.com';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = strip_tags(trim($_POST["name"]));
    $email   = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $subject = strip_tags(trim($_POST["subject"]));
    $message = trim($_POST["message"]);

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo "Please fill in all fields.";
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        exit;
    }
    if (preg_match("/[\r\n]/", $name) || preg_match("/[\r\n]/", $email)) {
        echo "Invalid input.";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mochibrian10@gmail.com'; // Your Gmail address
        $mail->Password   = 'yzbbrrkwltmztltn';    // Your Gmail App Password (not your Gmail password)
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom($email, $name);
        $mail->addAddress($receiving_email_address);

        // Content
        $mail->Subject = $subject;
        $mail->Body    = "Name: $name\nEmail: $email\nMessage:\n$message";

        $mail->send();
        echo 'OK';
    } catch (Exception $e) {
        echo "Error sending email: {$mail->ErrorInfo}";
    }
}
?>