<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; background-color: #f2f3f8; margin: 0 auto; padding: 40px 0px 20px 0px; max-width: 650px;">
        <!-- Header with Logo -->
        <div style="margin-bottom: 20px; text-align: center;">
            <img src="https://geniepay.ng/uploads/img/page/logo.png" alt="GeniePay Logo" width="170" style="border-radius: 8px;">
        </div>
        <div style="max-width: 550px; background-color: #ffffff; border-radius: 8px; margin: 0 auto; overflow: hidden; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">

            <!-- Banner Section with Image -->
            <div style="padding: 20px; background-color: #008744; text-align: center;">
                <img src="https://geniepay.ng/uploads/img/page/lock.png" alt="Verification Code" style="width: 70px;">
            </div>

            <!-- Main Content Section -->
            <div style="padding: 20px; text-align: center;">
                <h1 style="font-size: 22px; color: #333333;">Your One-Time Password (OTP) is:</h1>

                <!-- Verification Code -->
                <div style="background-color: #f5f5f5; color: #008744; padding: 15px; font-size: 24px; font-weight: bold; margin: 20px auto; border-radius: 5px; width: fit-content;">
                    {{$otp}}
                </div>
                <p style="font-size: 16px; font-weight: bold;">This code will expire in 5 minutes. If you didn't request this, you can ignore this email.</p>

                <!-- Device, IP, and Location Information -->
                <p style="margin-top: 20px; font-size: 14px; color: #555555;">
                    <strong>Device:</strong> {{ $device }}<br>
                    <strong>IP Address:</strong> {{ $ip }}<br>
                    <strong>Location:</strong> {{ $location }}
                </p>
            </div>
        </div>

        <!-- Footer Section with Social Icons -->
        <div style="padding-top: 70px; padding-left: 20px; padding-right: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
            <a href="https://facebook.com/geniepay.ng" style="text-decoration: none;" target="_blank">
                <img src="https://geniepay.ng/uploads/img/page/Facebook_f_logo_(2019).svg" alt="Facebook" width="24" height="24" style="vertical-align: middle;"/>
            </a>
            <a href="https://instagram.com/geniepay.ng" style="margin-left: 10px; text-decoration: none;" target="_blank">
                <img src="https://geniepay.ng/uploads/img/page/Instagram_icon.png" alt="Instagram" width="24" height="24" style="vertical-align: middle;"/>
            </a>
            <a href="https://twitter.com/geniepayng" style="margin-left: 10px;" target="_blank">
                <img src="https://geniepay.ng/uploads/img/page/X_logo_2023.svg" alt="X (Twitter)" width="24" style="vertical-align: middle;">
            </a>
            <p style="margin-top: 20px; color: #888888;">&copy; {{ date('Y') }} GeniePay. All rights reserved.</p>
        </div>
    </div>
</body>
</html>