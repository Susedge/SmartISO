<?php

namespace App\Libraries;

use Config\Email as EmailConfig;

class EmailService
{
    protected $email;
    protected $config;

    public function __construct()
    {
        $this->config = new EmailConfig();
        $this->email = \Config\Services::email();
    }

    /**
     * Send notification email to a user
     * 
     * @param string $toEmail Recipient email address
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $message Email message (plain text)
     * @return bool Success status
     */
    public function sendNotificationEmail($toEmail, $toName, $subject, $message)
    {
        try {
            // Clear any previous email settings
            $this->email->clear();

            // Set email parameters
            $this->email->setFrom($this->config->fromEmail, $this->config->fromName);
            $this->email->setTo($toEmail);
            $this->email->setSubject($subject);

            // Create HTML email body
            $htmlMessage = $this->createEmailTemplate($subject, $message, $toName);
            $this->email->setMessage($htmlMessage);

            // Send email
            $result = $this->email->send();

            if (!$result) {
                // Log error for debugging
                $error = $this->email->printDebugger(['headers', 'subject', 'body']);
                log_message('error', 'Email sending failed: ' . $error);
                return false;
            }

            log_message('info', "Email sent successfully to: {$toEmail}");
            return true;

        } catch (\Exception $e) {
            log_message('error', 'Email sending exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create HTML email template
     * 
     * @param string $title Email title
     * @param string $content Email content
     * @param string $recipientName Recipient name
     * @return string HTML email template
     */
    protected function createEmailTemplate($title, $content, $recipientName = '')
    {
        $greeting = !empty($recipientName) ? "Hello {$recipientName}," : "Hello,";
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color: #0d6efd;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-body {
            padding: 30px;
        }
        .email-body p {
            margin: 0 0 15px 0;
        }
        .message-box {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin: 20px 0;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0d6efd;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>SmartISO System</h1>
        </div>
        <div class="email-body">
            <p>{$greeting}</p>
            <div class="message-box">
                <strong>{$title}</strong>
            </div>
            <p>{$content}</p>
            <p>Please log in to the SmartISO system to view more details and take action if required.</p>
            <a href="<?= base_url() ?>" class="button">Go to SmartISO</a>
        </div>
        <div class="email-footer">
            <p>This is an automated email from SmartISO System. Please do not reply to this email.</p>
            <p>&copy; 2025 SmartISO System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Send email to multiple recipients
     * 
     * @param array $recipients Array of email addresses
     * @param string $subject Email subject
     * @param string $message Email message
     * @return array Results for each recipient
     */
    public function sendBulkEmails($recipients, $subject, $message)
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';
            
            $results[$email] = $this->sendNotificationEmail($email, $name, $subject, $message);
        }
        
        return $results;
    }

    /**
     * Test email configuration
     * 
     * @param string $testEmail Email to send test message to
     * @return bool Success status
     */
    public function sendTestEmail($testEmail)
    {
        $subject = 'SmartISO Email Test';
        $message = 'This is a test email from SmartISO system. If you received this, email notifications are working correctly!';
        
        return $this->sendNotificationEmail($testEmail, 'Test User', $subject, $message);
    }
}
