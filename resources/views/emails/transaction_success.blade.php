<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Transaction Details' }}</title>
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
            <img src="https://geniepay.ng/uploads/img/page/lock.png" alt="Transaction" style="width: 70px;">
        </div>

        <!-- Main Content Section -->
        <div style="padding: 30px 20px; text-align: center; font-family: Arial, sans-serif; background-color: #ffffff;">
            <h1 style="font-size: 24px; color: #222222; margin-bottom: 10px;">{{ $heading ?? '🎉 Transaction Successful' }}</h1>
            <p style="font-size: 16px; color: #555555; margin: 5px 0 15px;">Thank you for choosing <span style="color: #008744; font-weight: bold;">GeniePay</span>!</p>
            <p style="font-size: 15px; color: #666666; margin-bottom: 30px;">Below are the details of your transaction:</p>

            <div style="display: inline-block; background-color: #f9f9f9; padding: 20px 25px; border-radius: 8px; text-align: left; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                @foreach($details as $label => $value)
                    <p style="margin: 8px 0; font-size: 15px; color: #333;">
                        <strong>{{ $label }}:</strong> {{ $value }}
                    </p>
                @endforeach
            </div>

            <p style="font-size: 13px; color:#999999; margin-top: 30px;">If you received this in error, please ignore this message.</p>
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
        <p style="font-size: 12px; color: #888888;">
            Need help? Contact our support team at <a href="mailto:support@geniepay.com" style="color: #ff6600; text-decoration: none;">support@geniepay.com</a>.
        </p>
        <p style="font-size: 12px; color: #888888;">
            Thank you for choosing GeniePay!
        </p>
    </div>
    </div>
</body>
</html>