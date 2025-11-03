# Bulk Import Feature Documentation

## Overview

The Food Resource Directory plugin now includes a comprehensive CSV bulk import feature that allows administrators to import multiple locations at once.

## Access

**Location:** WordPress Admin → Food Resources → Import Locations

**Required Permission:** `manage_options` (Administrator)

## Features

### 1. CSV Template
- **Download:** Click "Download CSV Template" button on the import page
- **Includes:** 33 columns with 2 pre-filled example rows
- **Format:** Standard CSV with headers

### 2. Import Workflow

#### Step 1: Upload
1. Download the CSV template
2. Fill in your location data
3. Upload the completed CSV file
4. System automatically validates the data

#### Step 2: Preview
- View summary of import (total, valid, invalid rows)
- See first 10 rows with validation status
- Color-coded status indicators (green = valid, red = invalid)
- Detailed error messages for invalid rows
- Warnings for potential issues (duplicates, etc.)

#### Step 3: Import
- Click "Import Valid Rows" to proceed
- Invalid rows are automatically skipped
- Progress handled in batches (25 rows per batch)
- Background processing prevents timeouts

#### Step 4: Results
- Summary statistics (successful, skipped, failed)
- Detailed error report for skipped rows
- Links to view imported locations or import more

## Validation Rules

### Required Fields
- Title
- Street Address
- City
- State (2-letter code, e.g., MI)
- ZIP (5 digits)
- County (must be: Macomb County, Oakland County, or Wayne County)

### Format Requirements

**Phone Number:**
- Must be exactly 10 digits
- Non-numeric characters will be stripped
- Example: 3135550100

**State:**
- Must be valid 2-letter US state code
- Case-insensitive
- Example: MI, mi, Mi all valid

**ZIP Code:**
- Must be exactly 5 digits
- No hyphens or other characters
- Example: 48201

**County:**
- Must match one of the three allowed counties exactly
- Extensible via filter: `frd_import_allowed_counties`

**Services:**
- Comma-separated values
- Must be one of: Food Pantry, Soup Kitchen, Other
- Example: "Food Pantry, Soup Kitchen"

**Languages:**
- Comma-separated values
- Any values accepted
- Example: "English, Spanish, Arabic"

**Hours Other Hours:**
- Optional dropdown field
- Valid values: "Regular hours", "Appointment only", "Hours unknown", "Call to confirm"
- If set to anything other than "Regular hours", day/time fields will be ignored
- Leave empty or set to "Regular hours" to use day/time fields

**Hours:**
- Open fields: TRUE or FALSE (case-insensitive)
- Time fields: Flexible format (9:00 AM, 9am, 09:00)
- If day is open, both open and close times required
- Only used when Hours Other Hours is empty or "Regular hours"

**Website:**
- Must be valid URL
- Must use http:// or https:// protocol
- Example: https://example.com

### Duplicate Handling
- If location title already exists, adds suffix " (2)", " (3)", etc.
- Prevents overwriting existing data
- User is warned in preview

## Technical Details

### Files Structure
```
includes/
  class-frd-csv-parser.php       - CSV file parsing
  class-frd-import-validator.php - Data validation
  class-frd-importer.php          - Import execution
  admin/
    import-page.php               - Admin UI
```

### Security
- Nonce verification on all AJAX requests
- Capability checks (`manage_options`)
- File type validation (CSV only)
- File size limit (5MB max)
- Input sanitization on all fields
- URL protocol validation

### Performance
- Batch processing (25 rows per batch)
- Transient storage for import data
- Automatic cleanup of temp files
- 0.1 second pause between batches
- Prevents server timeouts

### Data Storage
- Import data stored in transients (1 hour expiration)
- Temp CSV files stored in WordPress uploads directory
- Automatic cleanup after import
- Uses WordPress post meta for coordinates

## Extending the Feature

### Add More Allowed Counties
```php
add_filter('frd_import_allowed_counties', function($counties) {
    $counties[] = 'New County';
    return $counties;
});
```

### Modify Batch Size
Edit `includes/class-frd-importer.php`:
```php
public function import_data($data, $batch_size = 25) {
    // Change 25 to your preferred batch size
}
```

## Troubleshooting

### Import Fails Immediately
- Check file size (must be under 5MB)
- Ensure file is CSV format
- Verify all required columns are present

### Some Rows Skipped
- Download error report to see specific issues
- Check validation rules above
- Fix errors in CSV and re-import

### Import Hangs/Times Out
- Reduce batch size in code
- Import in smaller chunks
- Check server PHP execution time limit

### Locations Missing Coordinates
- Geocoding happens automatically on first load
- May take time for large imports
- Check Mapbox API token if geocoding fails

## CSV Template Column Reference

1. Title
2. Street Address
3. City
4. State
5. ZIP
6. County
7. Phone
8. Website
9. Services
10. Languages
11-30. Hours fields (Monday-Sunday: Open, Open Time, Close Time)
31. Hours Note
32. Eligibility Requirements
33. Additional Notes

## Support

For issues or questions:
1. Check validation error messages
2. Review this documentation
3. Contact plugin developer
