<?php
function getPasswordResetSuccessTemplate($data) {
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
                <h2>Password Reset Successful</h2>
                <p>Hello {$data['name']},</p>
                <p>Your password has been successfully reset. You can now log in to your VitaVista Apartments account using your new password.</p>
                <p>If you did not make this change, please contact our support team immediately.</p>
                <p>Best regards,<br>VitaVista Apartments Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
} 