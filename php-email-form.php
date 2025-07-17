<?php

/**
 * Class PHP_Email_Form
 *
 * A simple class to handle email form submissions.
 */
class PHP_Email_Form
{
    protected $to = '';
    protected $from = '';
    protected $subject = '';
    protected $body = '';
    protected $headers = [];
    protected $recaptcha_secret_key = '';

    /**
     * Constructor for PHP_Email_Form.
     *
     * @param string $to_email The recipient email address.
     * @param string $subject_prefix A prefix for the email subject.
     */
    public function __construct($to_email = '', $subject_prefix = 'New Form Submission')
    {
        $this->to = $to_email;
        $this->subject = $subject_prefix;
        $this->headers[] = 'MIME-Version: 1.0';
        $this->headers[] = 'Content-type: text/html; charset=UTF-8';
    }

    /**
     * Sets the reCAPTCHA secret key for validation.
     *
     * @param string $secret_key The reCAPTCHA secret key.
     */
    public function set_recaptcha_secret_key($secret_key)
    {
        $this->recaptcha_secret_key = $secret_key;
    }

    /**
     * Processes the form data and sends the email.
     *
     * @param array $form_data An associative array of form data (e.g., $_POST).
     * @return bool True on success, false on failure.
     */
    public function send($form_data)
    {
        // Basic reCAPTCHA validation (if key is set)
        if (!empty($this->recaptcha_secret_key) && isset($form_data['g-recaptcha-response'])) {
            $recaptcha_response = $form_data['g-recaptcha-response'];
            $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
            $response = file_get_contents($verify_url . '?secret=' . $this->recaptcha_secret_key . '&response=' . $recaptcha_response);
            $responseData = json_decode($response);

            if (!$responseData->success) {
                error_log('reCAPTCHA verification failed.');
                return false;
            }
        } elseif (!empty($this->recaptcha_secret_key) && !isset($form_data['g-recaptcha-response'])) {
            error_log('reCAPTCHA response not found.');
            return false;
        }

        // Sanitize and validate input
        $name = isset($form_data['name']) ? htmlspecialchars(strip_tags(trim($form_data['name']))) : 'N/A';
        $email = isset($form_data['email']) ? htmlspecialchars(strip_tags(trim($form_data['email']))) : 'N/A';
        $message = isset($form_data['message']) ? htmlspecialchars(strip_tags(trim($form_data['message']))) : 'N/A';
        $subject_suffix = isset($form_data['subject']) ? htmlspecialchars(strip_tags(trim($form_data['subject']))) : 'No Subject';

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid email format: ' . $email);
            return false;
        }

        // Set 'From' header
        $this->headers[] = 'From: ' . $name . ' <' . $email . '>';
        $this->headers[] = 'Reply-To: ' . $email;

        // Construct email body
        $this->body = "
            <h2>" . $this->subject . "</h2>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Subject:</strong> {$subject_suffix}</p>
            <p><strong>Message:</strong><br>{$message}</p>
        ";

        // Send email
        $mail_sent = mail($this->to, $this->subject . ' - ' . $subject_suffix, $this->body, implode("
", $this->headers));

        if (!$mail_sent) {
            error_log('Email sending failed to: ' . $this->to . ' from: ' . $email);
        }

        return $mail_sent;
    }
}
