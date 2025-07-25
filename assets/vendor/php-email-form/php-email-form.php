<?php

class PHP_Email_Form
{
    protected $to = '';
    protected $from = '';
    protected $subject = '';
    protected $body = '';
    protected $headers = [];
    protected $recaptcha_secret_key = '';

    public function __construct($to_email = '', $subject_prefix = 'New Form Submission')
    {
        $this->to = $to_email;
        $this->subject = $subject_prefix;
        $this->headers[] = 'MIME-Version: 1.0';
        $this->headers[] = 'Content-type: text/html; charset=UTF-8';
    }

    public function set_recaptcha_secret_key($secret_key)
    {
        $this->recaptcha_secret_key = $secret_key;
    }

    public function send($form_data)
    {
        // ✅ reCAPTCHA validation
        if (!empty($this->recaptcha_secret_key)) {
            $recaptcha_response = $form_data['g-recaptcha-response'] ?? '';
            if (empty($recaptcha_response)) {
                error_log('Missing reCAPTCHA response.');
                return false;
            }

            $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
            $response = file_get_contents($verify_url . '?secret=' . $this->recaptcha_secret_key . '&response=' . $recaptcha_response);
            $responseData = json_decode($response);

            if (empty($responseData->success)) {
                error_log('reCAPTCHA validation failed.');
                return false;
            }
        }

        // ✅ Sanitize and validate input
        $name = htmlspecialchars(strip_tags(trim($form_data['name'] ?? 'N/A')));
        $email = filter_var(trim($form_data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $subject_suffix = htmlspecialchars(strip_tags(trim($form_data['subject'] ?? 'No Subject')));
        $message = htmlspecialchars(strip_tags(trim($form_data['message'] ?? '')));

        if (!$email) {
            error_log('Invalid email format.');
            return false;
        }

        // ✅ Optional override of $from
        $this->from = $email;

        $this->headers[] = 'From: ' . $name . ' <' . $email . '>';
        $this->headers[] = 'Reply-To: ' . $email;

        // ✅ Construct HTML email
        $this->body = "
            <h2>{$this->subject}</h2>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Subject:</strong> {$subject_suffix}</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
        ";

        // ✅ Send the email
        $mail_sent = mail(
            $this->to,
            "{$this->subject} - {$subject_suffix}",
            $this->body,
            implode("\r\n", $this->headers)
        );

        if (!$mail_sent) {
            error_log("Email sending failed to {$this->to} from {$email}");
        }

        return $mail_sent;
    }
}