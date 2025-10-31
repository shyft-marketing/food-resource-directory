# Food Resource Directory Plugin - Context & Documentation

## Overview

The Food Resource Directory plugin is a WordPress plugin designed to display an interactive map and filterable list of food pantries, soup kitchens, and other food resources. It integrates with Advanced Custom Fields (ACF) to manage location data and uses Mapbox for geocoding and map display.

## Purpose

This plugin was created to help connect people in need with food resources across Macomb County, Oakland County, and Wayne County in Michigan during the 2025 government shutdown when SNAP benefits expired. It provides an easy-to-use interface for finding nearby food assistance with robust filtering options.

## Architecture

### Components

1. **Custom Post Type**: `food-resource` (created via ACF)
2. **ACF Field Group**: "Food Resource Info" with all location details
3. **Plugin Files**:
   - `food-resource-directory.php` - Main plugin file
   - `templates/directory.php` - Frontend template
   - `assets/css/style.css` - Stylesheet
   - `assets/js/script.js` - JavaScript functionality

### Technology Stack

- **WordPress**: 6.8.3+
- **PHP**: 7.4+
- **ACF PRO**: For custom fields management
- **Mapbox GL JS**: For interactive mapping
- **jQuery**: For DOM manipulation and AJAX
- **Mapbox Geocoding API**: For address geocoding

## Initial Setup

### 1. ACF Custom Post Type Setup

You should have already created this, but for reference:

**Custom Post Type Settings:**
- Post Type Key: `food-resource`
- Plural Label: `Food Resources`
- Singular Label: `Food Resource`
- Settings:
  - ✅ Public
  - ✅ Show in REST API
  - ✅ Has Archive
  - ❌ Exclude from search (recommended)
  - Supports: Title, Editor (optional)

### 2. ACF Field Group Setup

**Field Group: "Food Resource Info"**

Location Rules: Show this field group if Post Type is equal to food-resource

**Fields Structure:**

```
📄 Post Title (WordPress default) = Name of the food resource

📍 Address Information:
├─ street_address (Text)
├─ city (Text)
├─ state (Select - single, return value)
├─ zip (Number)
└─ county (Select - single, return value)
   Options: Macomb County, Oakland County, Wayne County

📞 Contact Information:
├─ phone (Number)
└─ url (URL)

🏪 Service Information:
├─ services (Select - multiple, return value)
│  Options: Food Pantry, Soup Kitchen, Other
└─ languages (Select - multiple, return label)
   Options: Full list of languages (see Languages_Options.txt)

⏰ Hours (Group):
└─ For each day (monday through sunday):
    ├─ open (True/False)
    ├─ open_time (Time Picker)
    └─ close_time (Time Picker)

📝 Additional Information:
├─ eligibility (Text Area)
└─ notes (Text Area)
```

### 3. Plugin Installation

1. Upload the entire `food-resource-directory` folder to `/wp-content/plugins/`
2. Ensure the directory structure is:
   ```
   /wp-content/plugins/food-resource-directory/
   ├── food-resource-directory.php
   ├── templates/
   │   └── directory.php
   └── assets/
       ├── css/
       │   └── style.css
       └── js/
           └── script.js
   ```
3. Activate the plugin in WordPress Admin → Plugins

### 4. Adding the Directory to a Page

1. Create or edit a page in WordPress
2. Add the shortcode: `[food_resource_directory]`
3. Optional parameter: `[food_resource_directory default_view="list"]` to start with list view

**Recommended:** Use a full-width page template for best display.

## How It Works

### Data Flow

1. **Location Entry**: Admin enters food resource data via ACF fields in WordPress admin
2. **Geocoding**: When locations are loaded, the plugin geocodes addresses using Mapbox API and caches coordinates
3. **Frontend Display**: The shortcode renders the directory interface
4. **User Interaction**: Users can:
   - Toggle between map and list views
   - Enter their location to see distances
   - Filter by service type, days open, county, and distance
   - Click locations for full details
   - Get directions, call, or visit websites

### Key Features

#### Map View
- Interactive Mapbox map centered on the three-county area
- Red markers for each food resource location
- Blue marker for user's location (when entered)
- Popups with basic information on marker click
- Click markers to open full detail modal
- Auto-zoom to fit all visible markers

#### List View
- Sortable by distance, name, or county
- Distance shown when user enters location
- Service tags for quick identification
- Abbreviated hours display
- Quick action buttons (call, website, directions)
- Click cards for full details

#### Filtering System
- **Service Type**: Food Pantry, Soup Kitchen, Other
- **Days of Week**: Multi-select to find locations open on specific days
- **County**: Macomb, Oakland, or Wayne
- **Distance**: Slider to set maximum distance (only visible after entering location)
- **Location Search**: Enter address, city, or ZIP code to calculate distances

#### Detail Modal
- Complete information about each location
- Full week's hours displayed
- All services and languages listed
- Eligibility requirements and notes
- Action buttons for call, website, directions

### Caching & Performance

- **Geocoding Cache**: Coordinates are cached in post meta (`_frd_coordinates`) to avoid repeated API calls
- **AJAX Loading**: Locations loaded dynamically to reduce initial page load
- **Efficient Filtering**: Client-side filtering after initial load for instant results

## Usage Guide for Admins

### Adding a New Food Resource

1. Go to WordPress Admin → Food Resources → Add New
2. Enter the **Title** (name of the food resource)
3. Fill in all required fields:
   - **Address Information**: Complete address with street, city, state, ZIP, and county
   - **Contact**: Phone number and website (if available)
   - **Services**: Select all that apply
   - **Languages**: Select all languages spoken at this location
   - **Hours**: For each day, check "Open" and set times (or leave unchecked if closed)
   - **Eligibility**: Any requirements for accessing services
   - **Notes**: Additional important information
4. Click **Publish**

### Editing Existing Locations

1. Go to WordPress Admin → Food Resources
2. Click on the location to edit
3. Make changes to any fields
4. Click **Update**

**Note**: If you change the address, the geocoded coordinates will automatically update on the next page load.

### Bulk Import (Future Feature)

Currently, locations must be entered manually. For bulk import, consider:
- Using ACF's CSV import functionality
- WP All Import plugin with ACF add-on
- Custom import script (requires development)

## Customization Guide

### Styling

Edit `assets/css/style.css` to customize:

**Color Scheme:**
```css
:root {
    --frd-primary: #2563eb;        /* Primary blue */
    --frd-primary-dark: #1e40af;   /* Darker blue for hovers */
    --frd-secondary: #64748b;      /* Gray */
    --frd-success: #16a34a;        /* Green (not currently used) */
    --frd-danger: #dc2626;         /* Red for markers */
    /* ... other colors ... */
}
```

**Map Height:**
```css
#frd-map {
    height: 600px; /* Adjust as needed */
}
```

**Responsive Breakpoint:**
```css
@media (max-width: 768px) {
    /* Mobile styles */
}
```

### Map Settings

Edit `food-resource-directory.php` to change map defaults:

```php
// In the enqueue_scripts method:
'defaultCenter' => array(-83.0458, 42.5803), // [lng, lat]
'defaultZoom' => 9
```

### Mapbox Style

Edit `assets/js/script.js` to change the map appearance:

```javascript
map = new mapboxgl.Map({
    container: 'frd-map',
    style: 'mapbox://styles/mapbox/streets-v12', // Change style here
    center: frdData.defaultCenter,
    zoom: frdData.defaultZoom
});
```

**Available Mapbox styles:**
- `mapbox://styles/mapbox/streets-v12` (default)
- `mapbox://styles/mapbox/outdoors-v12`
- `mapbox://styles/mapbox/light-v11`
- `mapbox://styles/mapbox/dark-v11`
- `mapbox://styles/mapbox/satellite-v9`
- `mapbox://styles/mapbox/satellite-streets-v12`

### Adding New Filter Options

To add new service types or modify existing filters:

1. Update ACF field choices in WordPress Admin → Custom Fields
2. Update the filter options in `templates/directory.php`
3. No code changes needed - filters work dynamically with ACF field values

## Troubleshooting

### Locations Not Appearing on Map

**Possible Causes:**
1. **Missing Coordinates**: Address couldn't be geocoded
   - Solution: Edit the location and verify the address is correct and complete
   - Delete the `_frd_coordinates` post meta to force re-geocoding
   
2. **Invalid Mapbox Token**: Token is incorrect or expired
   - Solution: Verify the token in `food-resource-directory.php`
   
3. **JavaScript Errors**: Check browser console for errors
   - Solution: Ensure jQuery is loaded and no conflicts exist

### Filters Not Working

**Possible Causes:**
1. **Field Name Mismatch**: ACF field names don't match what the plugin expects
   - Solution: Verify field names in ACF match the documentation exactly
   
2. **AJAX Errors**: Check browser console and server error logs
   - Solution: Verify AJAX URL and nonce are correct

### Distance Calculation Issues

**Possible Causes:**
1. **User Location Not Set**: User hasn't entered a location
   - Solution: Ensure user enters a valid address in the location search
   
2. **Geocoding Failure**: Mapbox couldn't geocode the user's address
   - Solution: Try a more specific address or use a ZIP code

### Performance Issues

**Solutions:**
1. Limit the number of locations loaded (add pagination in future version)
2. Optimize geocoding by ensuring all locations have cached coordinates
3. Consider adding caching for AJAX responses

## Security Considerations

### Current Security Measures

1. **Nonce Verification**: All AJAX requests verify WordPress nonces
2. **Data Sanitization**: User input is sanitized before processing
3. **Capability Checks**: Only published posts are displayed
4. **Mapbox Token**: Token is for geocoding only (read-only operations)

### Recommended Additional Measures

1. **Rate Limiting**: Add rate limiting for geocoding requests
2. **Token Security**: Consider storing Mapbox token in wp-config.php:
   ```php
   // In wp-config.php:
   define('FRD_MAPBOX_TOKEN', 'your-token-here');
   
   // In plugin file, replace direct token with:
   'mapboxToken' => defined('FRD_MAPBOX_TOKEN') ? FRD_MAPBOX_TOKEN : ''
   ```

## Known Limitations

1. **No Pagination**: All locations load at once (may be slow with 500+ locations)
2. **Manual Entry**: No bulk import functionality built-in
3. **Single Map Style**: Mapbox style is hard-coded (not configurable via admin)
4. **No Analytics**: Doesn't track which locations are viewed/called most often
5. **No User Accounts**: Can't save favorite locations or create user preferences

## Future Enhancement Ideas

### Short Term
- [ ] Add print stylesheet for printable directory
- [ ] Add export functionality (PDF or Excel)
- [ ] Add sharing buttons for social media
- [ ] Add "Copy Address" button
- [ ] Show "Open Now" status based on current time

### Medium Term
- [ ] Add pagination or lazy loading for better performance
- [ ] Add CSV import/export functionality
- [ ] Add admin dashboard with location statistics
- [ ] Add location status (active/inactive/temporarily closed)
- [ ] Add location verification system

### Long Term
- [ ] Multi-language support for frontend
- [ ] User accounts with saved favorites
- [ ] Mobile app version
- [ ] SMS/text message integration
- [ ] Integration with 211 database
- [ ] Real-time availability updates

## API Reference

### Shortcode

```php
[food_resource_directory]
```

**Parameters:**
- `default_view` (string): 'map' or 'list' (default: 'map')

**Example:**
```php
[food_resource_directory default_view="list"]
```

### AJAX Actions

**Get Locations:**
```javascript
{
    action: 'frd_get_locations',
    nonce: frdData.nonce,
    filters: {
        services: ['Food Pantry'],
        days: ['monday', 'tuesday'],
        county: 'Wayne County'
    },
    user_location: {
        lat: 42.3314,
        lng: -83.0458
    }
}
```

**Geocode Address:**
```javascript
{
    action: 'frd_geocode',
    nonce: frdData.nonce,
    address: '123 Main St, Detroit, MI 48201'
}
```

### JavaScript Events

The plugin doesn't currently emit custom events, but you can listen for standard events:

```javascript
// When map is loaded
map.on('load', function() {
    console.log('Map loaded');
});

// When location is clicked (via delegation)
$(document).on('click', '.frd-location-card', function() {
    // Custom logic
});
```

## Support & Contribution

### Getting Help

If you encounter issues:

1. Check the Troubleshooting section above
2. Review browser console for JavaScript errors
3. Check WordPress debug log for PHP errors
4. Verify ACF field structure matches documentation

### Contributing

This plugin is open source. To contribute:

1. Fork the repository on GitHub
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Reporting Bugs

When reporting bugs, please include:
- WordPress version
- PHP version
- ACF version
- Browser and version
- Steps to reproduce
- Error messages (from console and/or debug log)
- Screenshots if applicable

## Credits

- **Developed for**: [Your Organization Name]
- **Mapping**: Mapbox GL JS
- **Icons**: Inline SVG icons
- **Framework**: WordPress with Advanced Custom Fields PRO

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Changelog

### Version 1.0.0 - October 2025
- Initial release
- Map view with Mapbox integration
- List view with sorting
- Filtering by services, days, county, and distance
- Location search with distance calculation
- Responsive design for mobile and desktop
- Detail modal with full information
- Integration with ACF custom fields

---

## Quick Reference Commands

### Regenerate Coordinates for All Locations

If you need to force re-geocoding of all locations (e.g., after fixing address data):

```php
// Run this in a custom script or via WP-CLI
$args = array(
    'post_type' => 'food-resource',
    'posts_per_page' => -1
);
$locations = get_posts($args);

foreach ($locations as $location) {
    delete_post_meta($location->ID, '_frd_coordinates');
}

// Coordinates will regenerate on next page load
```

### Clear All Filters (JavaScript Console)

```javascript
$('#frd-reset-filters').click();
```

### Get All Locations Data (JavaScript Console)

```javascript
console.log(allLocations);
```

---

**Document Version**: 1.0.0  
**Last Updated**: October 30, 2025  
**Plugin Version**: 1.0.0
