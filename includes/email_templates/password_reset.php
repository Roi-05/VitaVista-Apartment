<?php
function getPasswordResetTemplate($data) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #00006e;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
                background-color: #f9f9f9;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #d4af37;
                color: black;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>VitaVista Apartments</h1>
            </div>
            <div class='content'>
                <h2>Password Reset Request</h2>
                <p>Hello {$data['name']},</p>
                <p>We received a request to reset your password for your VitaVista Apartments account. Click the button below to reset your password:</p>
                <p style='text-align: center;'>
                    <a href='{$data['reset_link']}' class='button'>Reset Password</a>
                </p>
                <p>This link will expire in 1 hour for security reasons.</p>
                <p>If you didn't request this password reset, you can safely ignore this email. Your password will remain unchanged.</p>
                <p>Best regards,<br>VitaVista Apartments Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
} 