<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendTestEmail extends BaseCommand
{
    protected $group       = 'Test';
    protected $name        = 'email:test';
    protected $description = 'Sends a test email to verify email configuration';

    public function run(array $params)
    {
        CLI::write('=== SmartISO Email Test ===', 'yellow');
        CLI::newLine();

        try {
            $email = \Config\Services::email();
            
            $email->setFrom('chesspiece901@gmail.com', 'SmartISO System');
            $email->setTo('chesspiece901@gmail.com');
            $email->setSubject('SmartISO Email Test - ' . date('Y-m-d H:i:s'));
            
            $message = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
                    .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; }
                    .success { color: #28a745; font-weight: bold; }
                    .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
                    .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>📧 SmartISO Email Test</h1>
                    </div>
                    
                    <div class="content">
                        <h2 class="success">✅ Email System Working!</h2>
                        
                        <p>This is a test email from the SmartISO system to verify that email notifications are configured correctly.</p>
                        
                        <div class="info-box">
                            <strong>Test Details:</strong><br>
                            📅 Date: ' . date('F j, Y') . '<br>
                            🕐 Time: ' . date('g:i:s A') . '<br>
                            🖥️ Server: localhost<br>
                            📧 SMTP: Gmail (smtp.gmail.com:587)<br>
                            🔐 Protocol: TLS
                        </div>
                        
                        <p><strong>Email Configuration:</strong></p>
                        <ul>
                            <li>From: chesspiece901@gmail.com</li>
                            <li>To: chesspiece901@gmail.com</li>
                            <li>Mail Type: HTML</li>
                            <li>Charset: UTF-8</li>
                        </ul>
                        
                        <div class="info-box" style="border-left-color: #28a745;">
                            <strong>✅ What This Means:</strong><br>
                            Your SmartISO email notifications are working correctly! Users will receive emails for:
                            <ul style="margin: 10px 0;">
                                <li>New service request submissions</li>
                                <li>Request approvals and rejections</li>
                                <li>Service staff assignments</li>
                                <li>Service completions</li>
                                <li>Schedule reminders</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="footer">
                        <p>This is an automated test message from SmartISO System<br>
                        Powered by CodeIgniter 4 | Gmail SMTP</p>
                    </div>
                </div>
            </body>
            </html>
            ';
            
            $email->setMessage($message);
            
            CLI::write('📧 Sending test email...', 'blue');
            CLI::write('From: chesspiece901@gmail.com', 'white');
            CLI::write('To: chesspiece901@gmail.com', 'white');
            CLI::write('Subject: SmartISO Email Test', 'white');
            CLI::newLine();
            
            if ($email->send()) {
                CLI::write('✅ SUCCESS! Test email sent successfully!', 'green');
                CLI::newLine();
                CLI::write('📬 Check your inbox at: chesspiece901@gmail.com', 'yellow');
                CLI::write('   (Also check spam folder if not in inbox)', 'yellow');
                CLI::newLine(2);
                
                CLI::write('✅ Email system is working correctly!', 'green');
                CLI::write('Users will receive notifications for:', 'white');
                CLI::write('  • New submissions', 'white');
                CLI::write('  • Approvals/rejections', 'white');
                CLI::write('  • Staff assignments', 'white');
                CLI::write('  • Service completions', 'white');
                
                return 0;
            } else {
                CLI::error('❌ FAILED! Could not send test email.');
                CLI::newLine();
                CLI::write('=== Error Details ===', 'red');
                CLI::write($email->printDebugger());
                return 1;
            }
            
        } catch (\Exception $e) {
            CLI::error('❌ Error: ' . $e->getMessage());
            CLI::newLine();
            CLI::write('Stack trace:', 'red');
            CLI::write($e->getTraceAsString());
            return 1;
        }
    }
}
