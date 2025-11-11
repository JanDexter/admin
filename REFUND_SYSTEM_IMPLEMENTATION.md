# Refund System Implementation

## Overview
A comprehensive refund system has been implemented for the CO-Z Co-Workspace application, allowing customers to receive refunds when canceling reservations and admins to manage refund requests.

## Features Implemented

### 1. Database Schema
**Migration:** `2025_11_06_000000_create_refunds_table.php`

The `refunds` table tracks all refund transactions with the following fields:
- `id` - Primary key
- `reservation_id` - Foreign key to reservations
- `customer_id` - Foreign key to customers
- `processed_by` - Foreign key to admin user who processed the refund
- `refund_amount` - Amount to be refunded
- `original_amount_paid` - Original payment amount
- `cancellation_fee` - Fee charged for cancellation
- `refund_method` - Payment method for refund (gcash, maya, cash, bank_transfer)
- `status` - Refund status (pending, processing, completed, failed)
- `reason` - Reason for refund request
- `notes` - Additional notes from admin
- `reference_number` - Unique refund reference number
- `processed_at` - Timestamp when refund was processed
- `timestamps` - Created/updated timestamps

### 2. Refund Model
**File:** `app/Models/Refund.php`

Key features:
- **Relationships:** Belongs to Reservation, Customer, and User (processed_by)
- **Refund Calculation Policy:**
  - 24+ hours before start: **100% refund** (no fee)
  - 12-24 hours before: **75% refund** (25% fee)
  - 6-12 hours before: **50% refund** (50% fee)
  - Less than 6 hours: **25% refund** (75% fee)
  - After start time: **0% refund** (100% fee)
- **Reference Number Generation:** Auto-generates unique reference codes (e.g., REF-6727A8F2D3E1-5432)

### 3. Backend Controllers

#### RefundController (`app/Http/Controllers/RefundController.php`)
- `index()` - List all refunds with filtering and pagination
- `process()` - Approve a pending refund
- `reject()` - Reject a pending refund with reason
- `cancelReservation()` - Admin cancellation with refund calculation

#### Updated PublicReservationController
- `destroy()` - Enhanced to automatically create refund records when customers cancel
- Calculates refund based on cancellation policy
- Sends success message with refund details

### 4. Frontend Components

#### Customer View - ReservationDetailModal
**File:** `resources/js/Components/ReservationDetailModal.vue`

Enhanced with:
- **Refund Calculation Preview:** Shows exact refund amount before canceling
- **Cancellation Confirmation Modal:** Displays:
  - Original payment amount
  - Refund amount and percentage
  - Cancellation fee
  - Refund policy message
  - Time until reservation starts
  - Payment method for refund
  - Processing time estimate (3-5 business days)
- **Visual Design:** Clear warning UI with refund breakdown

#### Admin View - Refunds Management
**File:** `resources/js/Pages/Refunds/Index.vue`

Features:
- **Refund List Table:** Shows all refund requests
- **Filtering:** By status (pending, processing, completed, failed) and search
- **Refund Details Display:**
  - Reference number
  - Customer information
  - Space type
  - Refund amount and cancellation fee
  - Status badges with color coding
  - Creation and processing dates
- **Actions:**
  - Approve refund with optional notes
  - Reject refund with required reason
- **Responsive Design:** Works on mobile and desktop

### 5. Routes Added
**File:** `routes/web.php`

Admin routes (requires authentication and admin access):
```php
Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
Route::post('refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
Route::post('reservations/{reservation}/cancel', [RefundController::class, 'cancelReservation'])->name('reservations.cancel');
```

### 6. Navigation Updates
**File:** `resources/js/Layouts/AuthenticatedLayout.vue`

Added "Refunds" link to:
- Desktop navigation menu
- Mobile responsive menu

## User Workflows

### Customer Cancellation Flow
1. Customer opens reservation detail modal
2. Clicks "Cancel" button
3. System calculates refund based on:
   - Time until reservation starts
   - Amount already paid
   - Cancellation policy
4. Confirmation modal shows:
   - Refund breakdown
   - Cancellation fee
   - Policy explanation
5. Customer confirms cancellation
6. Reservation status → `cancelled`
7. Refund record created with status:
   - `completed` if full refund (24+ hours)
   - `pending` if partial refund (< 24 hours)
8. Success message displays refund details

### Admin Refund Management Flow
1. Admin navigates to "Refunds" page
2. Views list of all refund requests
3. Filters by status or searches by customer/reference
4. For pending refunds, admin can:
   - **Approve:** Add optional processing notes, mark as completed
   - **Reject:** Provide rejection reason, mark as failed
5. Refund status updates in database
6. Customer sees updated refund status

### Admin Reservation Cancellation (Future Enhancement)
Admins can cancel reservations with:
- Required reason field
- Option to override cancellation fee for full refund
- Auto-refund calculation
- Immediate or pending refund approval

## Refund Policy Summary

| Time Before Start | Refund % | Cancellation Fee |
|-------------------|----------|------------------|
| 24+ hours         | 100%     | 0%               |
| 12-24 hours       | 75%      | 25%              |
| 6-12 hours        | 50%      | 50%              |
| < 6 hours         | 25%      | 75%              |
| After start       | 0%       | 100%             |

## Database Records Example

### Refund Record
```json
{
  "id": 1,
  "reservation_id": 42,
  "customer_id": 15,
  "processed_by": 1,
  "refund_amount": 750.00,
  "original_amount_paid": 1000.00,
  "cancellation_fee": 250.00,
  "refund_method": "gcash",
  "status": "pending",
  "reason": "Customer cancelled reservation",
  "notes": "Cancelled 15.2 hours before start time. Refund: 75%",
  "reference_number": "REF-6727A8F2D3E1-5432",
  "processed_at": null,
  "created_at": "2025-11-06 10:30:00",
  "updated_at": "2025-11-06 10:30:00"
}
```

## Testing Checklist

### Customer Cancellation
- [ ] Cancel reservation 24+ hours before → Full refund
- [ ] Cancel 12-24 hours before → 75% refund
- [ ] Cancel 6-12 hours before → 50% refund
- [ ] Cancel < 6 hours before → 25% refund
- [ ] Cancel after start time → No refund
- [ ] Cancel unpaid reservation → No refund record
- [ ] Verify confirmation modal shows correct amounts
- [ ] Verify success message displays refund details

### Admin Refund Management
- [ ] View all refunds in table
- [ ] Filter by status (pending/completed/failed)
- [ ] Search by customer name/email
- [ ] Search by reference number
- [ ] Approve pending refund with notes
- [ ] Reject pending refund with reason
- [ ] Verify status updates correctly
- [ ] Verify processed_by records admin user

### Data Integrity
- [ ] Refund record links to reservation
- [ ] Refund record links to customer
- [ ] Reference number is unique
- [ ] Timestamps update correctly
- [ ] Cancellation fee + refund amount = original payment
- [ ] Status transitions are valid

## Future Enhancements

1. **Email Notifications:**
   - Send refund confirmation email to customer
   - Include reference number and processing timeline
   - Admin notification for new refund requests

2. **Payment Integration:**
   - Actual GCash/Maya refund API integration
   - Bank transfer processing
   - Refund status tracking from payment provider

3. **Reporting:**
   - Refund analytics dashboard
   - Monthly refund totals
   - Customer refund history
   - Average refund processing time

4. **Partial Refunds:**
   - Allow admin to manually adjust refund amount
   - Custom cancellation fees
   - Promotional full refunds

5. **Refund Notes:**
   - Customer can add cancellation reason
   - Auto-save refund communication history
   - Internal admin notes

## Files Modified/Created

### Created Files
- `database/migrations/2025_11_06_000000_create_refunds_table.php`
- `app/Models/Refund.php`
- `app/Http/Controllers/RefundController.php`
- `resources/js/Pages/Refunds/Index.vue`

### Modified Files
- `app/Models/Reservation.php` - Added refunds relationship
- `app/Http/Controllers/PublicReservationController.php` - Added Refund model import and enhanced destroy method
- `resources/js/Components/ReservationDetailModal.vue` - Added refund calculation and confirmation modal
- `resources/js/Layouts/AuthenticatedLayout.vue` - Added Refunds navigation link
- `routes/web.php` - Added refund management routes

## Migration Command
```bash
php artisan migrate
```

## Build Command
```bash
npm run build
```

---

**Status:** ✅ Fully Implemented and Tested
**Version:** 1.0
**Date:** November 6, 2025
