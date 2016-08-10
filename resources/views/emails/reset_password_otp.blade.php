<!DOCTYPE html>
<html>

<head>
    <title>Password Reset OTP</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div
        style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2 style="color: #333;">Reset Password</h2>
        <p>Hello {{ $user->name }},</p>
        <p>You have requested to reset your password. Please use the following OTP code to proceed:</p>
        <div style="font-size: 24px; font-weight: bold; color: #007bff; text-align: center; margin: 20px 0;">
            {{ $otp }}
        </div>
        <p>This code is valid for 15 minutes.</p>
        <p>If you did not request this, please ignore this email.</p>
        <p>Thank you,<br>Biji Coffee Team</p>
    </div>
</body>

</html>