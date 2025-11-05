# Cash Booking Validation - Abuse Prevention

## Overview
Implemented validation to prevent users from mass-booking and cancelling "Pay with Cash" appointments.

## Validation Rules

### 1. Active Cash Bookings Limit
- **Rule**: Maximum 2 active cash bookings per customer
- **Trigger**: When trying to create a new cash booking
- **Message**: "You cannot book more than 2 appointments with 'Pay with Cash' at a time."

### 2. Cancellation Rate Check
- **Rule**: If cancellation rate > 50% AND total bookings >= 5, block cash bookings
- **Calculation**: (cancelled_count / total_count) * 100
- **Trigger**: When trying to create a new cash booking
- **Message**: "Your cancellation rate is too high (XX%). You must use online payment."

## Implementation Details

### Customer Model Methods

#### `validateCashBooking(): array`
```php
public function validateCashBooking(): array
{
    // Check active cash bookings (max 2)
    $activeCashBookings = $this->reservations()
        ->where('payment_method', 'cash')
        ->active()
        ->count();
        
    if ($activeCashBookings >= 2) {
        return [
            'valid' => false,
            'message' => 'You cannot book more than 2 appointments with "Pay with Cash" at a time.'
        ];
    }
    
    // Check cancellation rate (>50% with 5+ bookings)
    $stats = $this->getBookingStats();
    
    if ($stats['total_bookings'] >= 5) {
        $cancellationRate = ($stats['cancelled_count'] / $stats['total_bookings']) * 100;
        
        if ($cancellationRate > 50) {
            return [
                'valid' => false,
                'message' => sprintf('Your cancellation rate is too high (%.0f%%). You must use online payment.', $cancellationRate)
            ];
        }
    }
    
    return ['valid' => true, 'message' => ''];
}
```

#### `getBookingStats(): array`
```php
public function getBookingStats(): array
{
    $totalBookings = $this->reservations()->count();
    $cancelledCount = $this->reservations()->cancelled()->count();
    $activeCount = $this->reservations()->active()->count();
    
    return [
        'total_bookings' => $totalBookings,
        'cancelled_count' => $cancelledCount,
        'active_count' => $activeCount,
    ];
}
```

### Controller Integration

**File**: `app/Http/Controllers/PublicReservationController.php`

**Flow**:
1. **Create/Update Customer** - Find or create customer record before validation
2. **Validate Cash Booking** - If payment method is 'cash', run validation
3. **Handle Rejection** - Redirect with error message if validation fails
4. **Process Reservation** - Only proceed if validation passes

```php
// 1. Create/update customer
$customer = Customer::firstOrCreate(
    ['email' => $validated['customer_email']],
    [/* customer data */]
);

// 2. Validate cash booking
if ($validated['payment_method'] === 'cash') {
    $validation = $customer->validateCashBooking();
    
    if (!$validation['valid']) {
        return redirect()
            ->back()
            ->withErrors([
                'payment_method' => $validation['message'],
            ])
            ->withInput();
    }
}

// 3. Process reservation
$reservation = DB::transaction(function () use (...) {
    // Create reservation
});
```

## User Experience

### Scenario 1: Too Many Active Cash Bookings
**User Action**: Customer with 2 active cash bookings tries to book a 3rd
**System Response**: Form validation error displayed
**Message**: "You cannot book more than 2 appointments with 'Pay with Cash' at a time."
**Resolution**: User must complete/cancel existing bookings OR use online payment

### Scenario 2: High Cancellation Rate
**User Action**: Customer with 60% cancellation rate (6 cancelled out of 10 total) tries to book with cash
**System Response**: Form validation error displayed
**Message**: "Your cancellation rate is too high (60%). You must use online payment."
**Resolution**: User must use online payment method instead

### Scenario 3: Valid Cash Booking
**User Action**: New customer or customer with good history books with cash
**System Response**: Reservation created successfully
**Message**: Standard confirmation

## Testing Scenarios

### Test 1: Active Booking Limit
1. Create 2 active cash reservations for a customer
2. Try to create a 3rd cash reservation
3. Should be rejected with appropriate message

### Test 2: Cancellation Rate Threshold
1. Create customer with 10 total bookings
2. Cancel 6 of them (60% rate)
3. Try to create new cash booking
4. Should be rejected with cancellation rate message

### Test 3: Below Threshold
1. Create customer with 10 total bookings
2. Cancel 4 of them (40% rate)
3. Try to create new cash booking
4. Should succeed

### Test 4: Insufficient History
1. Create customer with only 3 total bookings
2. Cancel 2 of them (66% rate)
3. Try to create new cash booking
4. Should succeed (minimum 5 bookings required for rate check)

### Test 5: Online Payment Bypass
1. Create customer who would be blocked from cash booking
2. Create reservation with online payment
3. Should succeed (validation only applies to cash)

## Future Enhancements

### Potential Additions:
1. **Admin Override**: Allow admins to bypass validation for trusted customers
2. **User Dashboard**: Show booking stats to users so they understand their status
3. **Grace Period**: Reset cancellation rate after X months of good behavior
4. **Configurable Limits**: Make thresholds configurable in admin settings
5. **Notification System**: Warn users when approaching limits

## Related Features
- **Cancellation System**: Works with existing refund and cancellation flow
- **Transaction Logging**: All cancelled bookings are logged in transaction_logs
- **Refund Policy**: Time-based refund percentages still apply
- **Customer History**: Relies on accurate reservation status tracking

## Files Modified
- `app/Models/Customer.php` - Added validation methods
- `app/Http/Controllers/PublicReservationController.php` - Integrated validation
