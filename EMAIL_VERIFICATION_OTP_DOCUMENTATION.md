# Email Verification with OTP System

## Overview
The CO-Z Co-Workspace application uses a secure One-Time Password (OTP) email verification system to prevent spam registrations and ensure valid user accounts.

## Features

### Security Features
- ✅ 6-digit numeric OTP codes
- ✅ 10-minute expiration time
- ✅ One-time use only (cannot be reused)
- ✅ Rate limiting (6 verification attempts, 3 resend attempts per minute)
- ✅ Password verification before OTP generation during login
- ✅ Old OTPs automatically deleted when new one is generated
- ✅ Auto-logout for unverified users trying to access protected routes

### User Experience
- ✅ Professional branded email template with CO-Z logo
- ✅ Large, easy-to-read OTP display in email
- ✅ Clean verification page with centered input field
- ✅ Numeric keyboard on mobile devices
- ✅ Real-time input validation (must be exactly 6 digits)
- ✅ One-click "Resend Code" button
- ✅ Clear error messages and success notifications

## User Flows

### 1. New User Registration
```
User Registration → OTP Generated & Sent → Verification Page → Enter OTP → Account Activated → Login Successful
```

**Steps:**
1. User fills out registration form (name, email, phone, password)
2. System creates user account with `is_active = false`
3. Customer record created with `status = 'pending'`
4. OTP generated and saved to database (valid for 10 minutes)
5. Email sent with 6-digit OTP code
6. User logged in temporarily (can only access verification page)
7. User enters OTP on verification page
8. System validates OTP:
   - Checks if OTP exists
   - Checks if OTP matches
   - Checks if OTP is not expired
   - Checks if OTP hasn't been used
9. If valid:
   - Email marked as verified
   - User account activated (`is_active = true`)
   - Customer status updated to `active`
   - OTP marked as used
   - Redirect to customer dashboard

### 2. Login with Unverified Account
```
User Login Attempt → Password Verified → OTP Sent → Verification Page → Enter OTP → Account Activated → Access Granted
```

**Steps:**
1. User enters email and password on login page
2. System checks if account exists and password is correct
3. System detects account is not verified (`is_active = false`)
4. New OTP generated and sent to email
5. User redirected to verification page
6. User enters OTP to complete verification
7. Account activated and user gains full access

### 3. Deactivated Account Reactivation
```
Admin Deactivates Account → User Login → OTP Sent → Verification Page (Reactivation Notice) → Enter OTP → Account Reactivated
```

**Steps:**
1. Admin deactivates user account from admin panel
2. User attempts to log in with correct credentials
3. System detects account is deactivated but was previously verified
4. OTP generated and sent with reactivation message
5. User sees amber warning on verification page
6. User enters OTP
7. Account reactivated automatically

### 4. User Navigates Away from Verification Page
```
Unverified User → Tries to Access Protected Route → Auto Logout → Redirect to Login → Message Displayed
```

**Steps:**
1. Unverified user tries to navigate away from verification page
2. Middleware detects unverified status
3. User automatically logged out
4. Session invalidated
5. Redirect to login page with message: "Please verify your email address to continue."

## Technical Implementation

### Database Schema

**Table: `email_verification_otps`**
```sql
CREATE TABLE email_verification_otps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_otp_used (user_id, otp, used)
);
```

### Key Files

**Models:**
- `app/Models/EmailVerificationOtp.php` - OTP model with generation and verification methods
- `app/Models/User.php` - Implements `MustVerifyEmail` interface

**Controllers:**
- `app/Http/Controllers/Auth/RegisteredUserController.php` - Handles registration and OTP sending
- `app/Http/Controllers/Auth/OtpVerificationController.php` - Handles OTP verification and resending
- `app/Http/Controllers/Auth/VerifyEmailController.php` - Handles email verification events

**Middleware:**
- `app/Http/Middleware/EnsureEmailIsVerified.php` - Protects routes, auto-logouts unverified users

**Mail:**
- `app/Mail/VerificationOtpMail.php` - Mailable class for OTP emails
- `resources/views/emails/verification-otp.blade.php` - Email template

**Views:**
- `resources/js/Pages/Auth/VerifyEmail.vue` - OTP input page
- `resources/js/Pages/Auth/Register.vue` - Registration form

**Routes:**
- `routes/auth.php` - Authentication routes including OTP verification

### API Endpoints

**POST `/verify-otp`**
- Verifies the OTP code
- Rate limit: 6 attempts per minute
- Request body: `{ otp: "123456" }`
- Response: Redirect to dashboard or customer view

**POST `/resend-otp`**
- Sends a new OTP code
- Rate limit: 3 attempts per minute
- Response: Redirect back with success message

### OTP Generation Logic

```php
// Generate 6-digit OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Create OTP record
EmailVerificationOtp::create([
    'user_id' => $user->id,
    'otp' => $otp,
    'expires_at' => now()->addMinutes(10),
    'used' => false,
]);

// Send email
Mail::to($user->email)->send(new VerificationOtpMail($user, $otp));
```

### OTP Verification Logic

```php
// Find valid OTP
$otpRecord = EmailVerificationOtp::where('user_id', $user->id)
    ->where('otp', $otp)
    ->where('used', false)
    ->where('expires_at', '>', now())
    ->first();

if ($otpRecord) {
    // Mark as used
    $otpRecord->update(['used' => true]);
    
    // Activate account
    $user->markEmailAsVerified();
    $user->is_active = true;
    $user->save();
    
    return true;
}

return false;
```

## Protected Routes

Routes protected by the `verified` middleware:
- `/my-transactions` - User transaction history
- `/password/change/request` - Password change requests
- `/public/reservations` (POST) - Making reservations
- All admin routes (automatically verified)

## Email Configuration

**Required `.env` settings:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=jdndiaz@addu.edu.ph
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=jdndiaz@addu.edu.ph
MAIL_FROM_NAME="CO-Z Co-Workspace"
```

## Error Handling

### Common Error Messages

**Invalid or Expired OTP:**
```
"Invalid or expired OTP. Please try again or request a new code."
```

**Unverified Login Attempt:**
```
"Please verify your email address to continue."
```

**Auto-Logout:**
```
"Please verify your email address to continue."
```

**Reactivation Required:**
```
"Your account has been deactivated. We've sent a verification code to reactivate it."
```

## Security Considerations

1. **Rate Limiting:** Prevents brute force attacks on OTP codes
2. **Expiration:** 10-minute window limits vulnerability timeframe
3. **One-Time Use:** OTP becomes invalid after successful verification
4. **Auto-Deletion:** Old OTPs deleted when new one is generated
5. **Password Verification:** Must enter correct password before OTP is sent during login
6. **Session Management:** Unverified users auto-logged out from protected routes

## Testing

### Manual Testing Checklist

**Registration Flow:**
- [ ] Register new account
- [ ] Verify email is received with OTP
- [ ] Enter OTP on verification page
- [ ] Confirm account is activated
- [ ] Confirm access to customer dashboard

**Login with Unverified Account:**
- [ ] Create account but don't verify
- [ ] Logout
- [ ] Try to login
- [ ] Verify OTP is sent
- [ ] Verify redirect to verification page
- [ ] Enter OTP and confirm activation

**Expired OTP:**
- [ ] Wait 11 minutes after OTP generation
- [ ] Try to verify with expired OTP
- [ ] Confirm error message appears
- [ ] Request new OTP
- [ ] Verify new OTP works

**Invalid OTP:**
- [ ] Enter incorrect 6-digit code
- [ ] Confirm error message appears
- [ ] Enter correct code
- [ ] Confirm successful verification

**Resend OTP:**
- [ ] Click "Resend Code" button
- [ ] Verify new email is received
- [ ] Enter new OTP
- [ ] Confirm successful verification

**Auto-Logout:**
- [ ] Start registration process
- [ ] On verification page, navigate to homepage
- [ ] Verify auto-logout occurs
- [ ] Verify redirect to login page

**Reactivation:**
- [ ] Admin deactivates account
- [ ] User tries to login
- [ ] Verify OTP is sent
- [ ] Verify amber reactivation message
- [ ] Enter OTP
- [ ] Confirm account reactivated

## Troubleshooting

### OTP Email Not Received

**Check:**
1. Spam/junk folder
2. Email configuration in `.env`
3. SMTP credentials are valid
4. Port 465 is not blocked
5. Check Laravel logs: `storage/logs/laravel.log`

**Solution:**
```bash
# Clear config cache
php artisan config:clear

# Test email configuration
php artisan tinker
Mail::raw('Test', function($msg) { $msg->to('test@example.com'); });
```

### OTP Always Shows as Invalid

**Check:**
1. Database connection
2. `email_verification_otps` table exists
3. System time is correct (affects expiration)
4. No typos in OTP entry

**Debug:**
```bash
# Check if OTP exists in database
php artisan tinker
EmailVerificationOtp::where('user_id', 1)->get();
```

### User Gets Logged Out Immediately

**Check:**
1. User email is verified: `$user->hasVerifiedEmail()`
2. User account is active: `$user->is_active`
3. Middleware configuration

**Solution:**
```bash
# Manually verify user in database
php artisan tinker
$user = User::find(1);
$user->email_verified_at = now();
$user->is_active = true;
$user->save();
```

## Maintenance

### Clean Up Expired OTPs

Create a scheduled task to clean up expired OTPs:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        EmailVerificationOtp::where('expires_at', '<', now())
            ->orWhere('used', true)
            ->delete();
    })->daily();
}
```

### Monitor OTP Usage

Query to check OTP statistics:

```sql
-- OTPs generated today
SELECT COUNT(*) FROM email_verification_otps 
WHERE DATE(created_at) = CURDATE();

-- Verification success rate
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN used = 1 THEN 1 ELSE 0 END) as used,
    (SUM(CASE WHEN used = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100) as success_rate
FROM email_verification_otps 
WHERE DATE(created_at) = CURDATE();
```

## Future Enhancements

Potential improvements:
- [ ] SMS OTP as alternative to email
- [ ] Adjustable OTP expiration time in admin settings
- [ ] OTP verification attempt logging
- [ ] 2FA (Two-Factor Authentication) for sensitive operations
- [ ] Biometric verification on mobile apps
- [ ] QR code verification as alternative

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Review email queue: `php artisan queue:work`
- Contact system administrator

---

**Last Updated:** November 10, 2025  
**Version:** 1.0  
**System:** CO-Z Co-Workspace Management System
