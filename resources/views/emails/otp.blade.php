<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e6e6e6;
        }
        .email-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .email-header h1 {
            font-size: 24px;
            color: #007BFF;
        }
        .email-body {
            line-height: 1.6;
            font-size: 16px;
        }
        .otp-code {
            display: inline-block;
            margin: 10px 0;
            padding: 10px 15px;
            background-color: #007BFF;
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            border-radius: 5px;
            letter-spacing: 2px;
        }
        .email-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #999;
        }
        @media (max-width: 600px) {
            .email-container {
                padding: 15px;
            }
            .email-header h1 {
                font-size: 20px;
            }
            .otp-code {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Email Verification</h1>
        </div>
        <div class="email-body">
            <p>Dear User,</p>
            <p>Thank you for signing up. To verify your email address, please use the OTP code below:</p>
            <div class="otp-code">{{ $otp }}</div>
            <p>This code is valid for <strong>10 minutes</strong>. If you did not request this, please ignore this email.</p>
        </div>
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} Your Company Name. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
