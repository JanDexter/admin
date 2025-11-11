# Create/Extend Space Type - Pricing Type Feature Update

## Overview
Added pricing type field to the "Create or Extend Space Type" form in Space Management, allowing administrators to set the pricing type when creating new space types or extending existing ones.

## What Was Added

### Backend Changes

#### SpaceManagementController (`app/Http/Controllers/SpaceManagementController.php`)

**Method:** `storeSpaceType()`

**Added Validation:**
```php
'pricing_type' => 'nullable|in:per_person,per_reservation',
```

**Added to Space Type Creation:**
```php
$spaceType = SpaceType::create([
    'name' => strtoupper($validated['name']),
    'default_price' => $validated['hourly_rate'],
    'hourly_rate' => $validated['hourly_rate'],
    'pricing_type' => $validated['pricing_type'] ?? 'per_person', // ✅ NEW
    // ...other fields
]);
```

### Frontend Changes

#### SpaceManagement Index (`resources/js/Pages/SpaceManagement/Index.vue`)

**1. Updated Form State:**
```javascript
const createTypeForm = useForm({
    name: '',
    description: '',
    hourly_rate: '',
    pricing_type: 'per_person', // ✅ NEW - defaults to per_person
    default_discount_hours: '',
    default_discount_percentage: '',
    initial_slots: 1,
});
```

**2. Reset Logic Updated:**
```javascript
onSuccess: () => {
    createTypeForm.reset();
    createTypeForm.initial_slots = 1;
    createTypeForm.pricing_type = 'per_person'; // ✅ NEW - reset to default
    showCreateType.value = false;
}
```

**3. Added UI Field:**
Added a new dropdown field in the form after the "Rate" field:
```html
<div class="md:col-span-1">
    <label class="block text-xs font-medium text-gray-700">Pricing Type</label>
    <select v-model="createTypeForm.pricing_type" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <option value="per_person">Per Person</option>
        <option value="per_reservation">Per Reservation</option>
    </select>
</div>
```

**4. Updated savePricingModal (for Edit Pricing):**
```javascript
const payload = {
    hourly_rate: Number(val.hourly_rate),
    pricing_type: val.pricing_type || 'per_person', // ✅ NEW
    // ...other fields
};
```

## Form Layout

The updated "Create or Extend Space Type" form now includes:

| Field | Description | Type | Required |
|-------|-------------|------|----------|
| **Type Name** | Name of the space type | Text input (2 cols) | Yes |
| **Rate (₱/h)** | Hourly rate | Number input (1 col) | Yes |
| **Pricing Type** | Per Person or Per Reservation | Dropdown (1 col) | No (defaults to per_person) |
| **Disc. After (h)** | Hours before discount applies | Number input (1 col) | No |
| **Discount (%)** | Discount percentage | Number input (1 col) | No |
| **Initial Slots** | Number of spaces to create | Number input (1 col) | Yes |
| **Description** | Optional description | Text input (full width) | No |

## Usage Example

### Creating a Conference Room Space Type:

1. Open "Create or Extend Space Type" section
2. Fill in the form:
   - **Type Name:** Conference Room
   - **Rate (₱/h):** 350
   - **Pricing Type:** Per Reservation ← New field!
   - **Disc. After (h):** 3
   - **Discount (%):** 20
   - **Initial Slots:** 1
   - **Description:** Meeting room for team discussions

3. Click "Save Type & Create Slots"
4. Result: Creates a new space type with per-reservation pricing

### Creating Individual Workspaces:

1. Fill in the form:
   - **Type Name:** Private Desk
   - **Rate (₱/h):** 50
   - **Pricing Type:** Per Person ← Default
   - **Initial Slots:** 10

2. Result: Creates 10 private desk spaces with per-person pricing

## How It Works

### For New Space Types:
- The pricing_type is saved when creating the space type
- All individual spaces inherit this pricing behavior
- Customers will see the correct pricing label

### For Existing Space Types:
- When extending an existing type (adding more slots):
  - The form creates new spaces under the existing type
  - The existing type's pricing_type is NOT changed
  - New spaces inherit the type's existing pricing_type

## Integration with Existing Features

### 1. Edit Pricing Modal
- Already includes pricing type dropdown
- Can change pricing type after creation
- Updates all future reservations

### 2. Customer View
- Automatically displays correct pricing label
- "per person per hour" for per_person types
- "per reservation per hour" for per_reservation types

### 3. Space Details
- Each space inherits pricing type from its space type
- Pricing calculations respect the pricing type

## Validation

**Backend:**
- `pricing_type` must be either `'per_person'` or `'per_reservation'`
- Defaults to `'per_person'` if not provided
- Laravel validation ensures data integrity

**Frontend:**
- Dropdown restricts to valid values only
- Form resets to default (`'per_person'`) after submission
- No custom validation needed (constrained by dropdown)

## Files Modified

### Backend (1 file)
1. `app/Http/Controllers/SpaceManagementController.php`
   - Added `pricing_type` validation
   - Added `pricing_type` to space type creation
   - Defaults to `'per_person'`

### Frontend (1 file)
1. `resources/js/Pages/SpaceManagement/Index.vue`
   - Added `pricing_type` to form state
   - Added pricing type dropdown to UI
   - Updated reset logic
   - Updated save payload for edit pricing modal

## Testing Checklist

- [x] Backend validation accepts valid pricing types
- [x] Backend defaults to 'per_person' if not provided
- [x] Frontend form includes pricing type dropdown
- [x] Frontend build completes successfully
- [ ] Create new space type with "Per Person" - verify creation
- [ ] Create new space type with "Per Reservation" - verify creation
- [ ] Extend existing space type - verify pricing type not changed
- [ ] Edit pricing for existing type - verify pricing type updates
- [ ] Customer view displays correct label for new types

## Benefits

✅ **Complete Control** - Set pricing type at creation time  
✅ **Consistent Interface** - Same field in create and edit forms  
✅ **Clear Defaults** - Defaults to per_person for backward compatibility  
✅ **Easy to Use** - Simple dropdown selection  
✅ **No Confusion** - Pricing type set from the start  

## Migration Path

### For Existing Space Types:
- All existing types default to `'per_person'` (from migration)
- Conference Room manually updated to `'per_reservation'`
- Can be changed via "Edit Pricing" modal

### For New Space Types:
- Choose pricing type during creation
- No need to edit after creation (unless changing)

---

**Date Implemented:** November 3, 2025  
**Status:** ✅ Complete - Ready for Testing  
**Build:** ✅ Successful (3.92s)
