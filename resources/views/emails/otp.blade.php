<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login OTP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-weight: 500;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #f3f4f6;
            overflow: hidden;
            box-shadow: 0 2px 15px -3px rgba(0, 111, 120, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .email-header {
            background-color: #ffffff;
            padding: 32px 32px 24px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
        }

        .email-title {
            color: #006F78;
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .email-subtitle {
            color: #374151;
            font-size: 18px;
            font-weight: 500;
            margin: 0;
        }

        .email-body {
            padding: 32px;
        }

        .email-content {
            color: #4b5563;
            font-size: 16px;
            margin-bottom: 24px;
            line-height: 1.6;
            font-weight: 500;
        }

        .otp-container {
            text-align: center;
            margin: 32px 0;
        }

        .otp-box {
            display: inline-block;
            background-color: #f8fafc;
            border: 2px solid #006F78;
            border-radius: 12px;
            padding: 24px 48px;
        }

        .otp-code {
            font-size: 36px;
            font-weight: 600;
            letter-spacing: 8px;
            color: #006F78;
            font-family: 'Courier New', monospace;
            margin: 0;
        }

        .info-text {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            margin-top: 24px;
        }

        .email-footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #f3f4f6;
        }

        .footer-text {
            color: #9ca3af;
            font-size: 12px;
            font-weight: 500;
            margin: 0;
        }
    </style>
</head>

<body>
    <div style="padding: 20px;">
        <div class="email-container">
            <div class="email-header">
                <h1 class="email-title">Stock Management</h1>
                <h2 class="email-subtitle">Your Login OTP</h2>
            </div>
            <div class="email-body">
                <p class="email-content">Hello,</p>
                <p class="email-content">
                    You requested a one-time password (OTP) to login to your account. Use the code below:
                </p>
                <div class="otp-container">
                    <div class="otp-box">
                        <p class="otp-code">{{ $otp }}</p>
                    </div>
                </div>
                <p class="email-content">
                    Enter this code on the login page to complete your authentication.
                </p>
                <p class="info-text">
                    This OTP will expire in {{ $expiresInMinutes }} minutes.
                </p>
                <p class="info-text" style="margin-top: 24px;">
                    If you didn't request this OTP, please ignore this email or contact support if you have concerns.
                </p>
            </div>
            <div class="email-footer">
                <p class="footer-text">© {{ date('Y') }} Stock Management System. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>
