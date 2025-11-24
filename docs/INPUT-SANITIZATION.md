# Input Sanitization Guide

## Overview

This document describes the input sanitization strategy implemented across the platform to prevent security vulnerabilities, particularly SQL injection and XSS attacks, as well as Livewire update failures caused by malicious input.

## Security Concerns

### 1. SQL Injection Prevention
Malicious input like `'select*'` or SQL commands can break database queries if not properly sanitized. While Laravel's Eloquent ORM provides protection through parameterized queries, additional sanitization prevents edge cases and improves robustness.

### 2. Livewire Update Failures
Special characters in Livewire `wire:model` inputs can cause "Method Not Allowed" errors when Livewire attempts to process the update. Client-side sanitization prevents these characters from reaching Livewire.

### 3. XSS Prevention
While Blade templates automatically escape output, input sanitization provides an additional layer of protection.

## Sanitization Methods

### Method 1: Strict Alphanumeric + Hyphen (For IDs, References, QR Codes)

**Use Case:** Payment references, QR code text, ticket IDs, search fields

**Allowed Characters:** Letters (a-z, A-Z), digits (0-9), and hyphens (-)

**Implementation:**
- **Client-side:** JavaScript event listeners (keypress, paste, input) with regex: `/[^a-zA-Z0-9\-]/g`
- **Server-side:** PHP `preg_replace('/[^a-zA-Z0-9\-]/', '', $value)`

**Example Fields:**
- `searchId` (scanner-validator.blade.php)
- `searchPaymentRef` (verification-manager.blade.php)
- `paymentRef` (ticket-generator.blade.php)
- `qrCodeText` (ticket-generator.blade.php)

### Method 2: Name Sanitization (For Person Names)

**Use Case:** Holder names, user names

**Allowed Characters:** Letters, digits, spaces, hyphens, apostrophes, periods

**Implementation:**
- **Client-side:** Regex: `/[^a-zA-Z0-9\s'\-.]/g`
- **Server-side:** PHP `preg_replace('/[^a-zA-Z0-9\s\'\-.]/', '', $value)`

**Example Fields:**
- `holderName` (ticket-generator.blade.php)

### Method 3: Text Field Sanitization (For Descriptions, Locations)

**Use Case:** Descriptions, locations, general text fields

**Allowed Characters:** Letters, digits, spaces, common punctuation (.,!?;:), hyphens, apostrophes

**Implementation:**
- **Client-side:** Regex: `/[^a-zA-Z0-9\s.,!?;:'\-]/g`
- **Server-side:** PHP `preg_replace('/[^a-zA-Z0-9\s.,!?;:\'\-]/', '', $value)`

**Example Fields:**
- `description` (admin-event-ticket-types.blade.php)
- `location` (admin-events.blade.php - form input)

### Method 4: Color Name Sanitization

**Use Case:** Armband colors, color names

**Allowed Characters:** Letters, spaces, hyphens

**Implementation:**
- **Client-side:** Regex: `/[^a-zA-Z\s\-]/g`
- **Server-side:** PHP `preg_replace('/[^a-zA-Z\s\-]/', '', $value)`

**Example Fields:**
- `armband_color` (admin-event-ticket-types.blade.php)

## Implementation Pattern

### Client-Side Sanitization

All sanitized inputs use a three-layer client-side approach:

1. **Keypress Handler:** Prevents invalid characters from being typed
2. **Paste Handler:** Sanitizes pasted content
3. **Input Handler:** Catches any characters that slip through

The handlers use the **capture phase** (`true` as third parameter) to run before Livewire's event handlers, ensuring sanitization happens before Livewire processes the input.

### Server-Side Sanitization

Server-side sanitization is implemented using `updated{PropertyName}()` methods in Livewire Volt components. This provides a backup layer of protection in case client-side sanitization is bypassed.

**Pattern:**
```php
public function updatedPropertyName($value): void
{
    try {
        $inputValue = is_string($value) ? trim($value) : (string) $value;
        $sanitized = $this->sanitizeInput($inputValue);
        $sanitizedString = $sanitized ?? '';
        
        if ($sanitizedString !== $inputValue) {
            $this->propertyName = $sanitizedString;
            // Log sanitization if needed
        }
    } catch (\Exception $e) {
        // Log error and clear input
        $this->propertyName = '';
    }
}
```

## Files with Sanitization

### Fully Implemented ✅

#### `resources/views/livewire/scanner-validator.blade.php`
- ✅ `searchId` - Strict alphanumeric + hyphen
  - Client-side: JavaScript event listeners (keypress, paste, input)
  - Server-side: `updatedSearchId()` method

#### `resources/views/livewire/verification-manager.blade.php`
- ✅ `searchPaymentRef` - Strict alphanumeric + hyphen
  - Client-side: JavaScript event listeners (keypress, paste, input)
  - Server-side: `updatedSearchPaymentRef()` method

#### `resources/views/livewire/ticket-generator.blade.php`
- ✅ `holderName` - Name sanitization (letters, digits, spaces, hyphens, apostrophes, periods)
  - Client-side: JavaScript event listeners (keypress, paste, input)
  - Server-side: `updatedHolderName()` method with `sanitizeName()`
  - Note: `paymentRef` and `qrCodeText` are auto-generated, not user input

#### `resources/views/livewire/admin-event-ticket-types.blade.php`
- ✅ `name` - Name sanitization (letters, digits, spaces, hyphens, apostrophes, periods)
  - Client-side: JavaScript event listeners
  - Server-side: `updatedName()` method with `sanitizeName()`
- ✅ `description` - Text field sanitization (letters, digits, spaces, common punctuation)
  - Client-side: JavaScript event listeners
  - Server-side: `updatedDescription()` method with `sanitizeText()`
- ✅ `armband_color` - Color name sanitization (letters, spaces, hyphens)
  - Client-side: JavaScript event listeners
  - Server-side: `updatedArmbandColor()` method with `sanitizeColor()`

## Testing

When testing sanitization:

1. **Try SQL injection attempts:** `'select*'`, `'; DROP TABLE--`, `1' OR '1'='1`
2. **Try special characters:** `!@#$%^&*()`, `<>?:"{}|`
3. **Try paste operations:** Copy and paste malicious strings
4. **Verify Livewire updates:** Ensure no "Method Not Allowed" errors occur
5. **Check database queries:** Verify no SQL injection occurs

## Best Practices

1. **Always sanitize on both client and server side** - Client-side provides UX, server-side provides security
2. **Use appropriate sanitization level** - Don't over-sanitize (e.g., names should allow spaces and apostrophes)
3. **Log sanitization events** - Helps with debugging and security monitoring
4. **Test edge cases** - Empty strings, null values, very long strings
5. **Document sanitization rules** - Makes it clear what characters are allowed for each field type

## Maintenance

When adding new input fields:

1. Determine the appropriate sanitization level based on the field's purpose
2. Implement both client-side and server-side sanitization
3. Add the field to this documentation
4. Test with malicious input
5. Update the implementation checklist above

