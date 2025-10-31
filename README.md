# Food Resource Directory - WordPress Plugin

A WordPress plugin that displays an interactive map and filterable directory of food pantries, soup kitchens, and other food resources. Built with Advanced Custom Fields (ACF) integration and Mapbox mapping.

![WordPress Version](https://img.shields.io/badge/WordPress-6.8.3%2B-blue)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-GPL--2.0-green)

## Features

### 🗺️ Interactive Map View
- Mapbox-powered interactive map
- Click markers for location details
- Auto-zoom to fit all visible locations
- User location marker when searching

### 📋 Sortable List View
- Toggle between map and list views
- Sort by distance, name, or county
- Click cards for full details
- Quick action buttons (call, website, directions)

### 🔍 Powerful Filtering
- **Service Type**: Food Pantry, Soup Kitchen, Other
- **Days of Week**: Find locations open on specific days
- **County**: Filter by Macomb, Oakland, or Wayne County
- **Distance**: Set maximum distance from your location
- **Location Search**: Enter address, city, or ZIP to see distances

### 📱 Fully Responsive
- Mobile-friendly design
- Touch-optimized interface
- Works on all screen sizes

## Requirements

- WordPress 6.8.3 or higher
- PHP 7.4 or higher
- Advanced Custom Fields PRO plugin
- Mapbox account (free tier works)

## Installation

1. **Download or clone this repository**
   ```bash
   git clone https://github.com/yourusername/food-resource-directory.git
   ```

2. **Upload to WordPress**
   - Upload the `food-resource-directory` folder to `/wp-content/plugins/`
   - Or upload as a ZIP file via WordPress Admin → Plugins → Add New

3. **Install ACF PRO**
   - Purchase and install [Advanced Custom Fields PRO](https://www.advancedcustomfields.com/pro/)

4. **Activate the plugin**
   - Go to WordPress Admin → Plugins
   - Activate "Food Resource Directory"

5. **Set up ACF fields**
   - See [Setup Guide](#setup-guide) below

## Setup Guide

### Step 1: Create Custom Post Type in ACF

1. Go to **ACF → Post Types → Add New**
2. Configure:
   - Post Type Key: `food-resource`
   - Plural Label: `Food Resources`
   - Singular Label: `Food Resource`
   - Settings:
     - ✅ Public
     - ✅ Show in REST API
     - ✅ Has Archive
     - ✅ Exclude from search (optional)

### Step 2: Create Field Group in ACF

**⚠️ IMPORTANT**: See [ACF-FIELD-STRUCTURE.md](ACF-FIELD-STRUCTURE.md) for the EXACT field configuration.

The plugin requires specific field names and structure. Here's a summary:

1. Go to **ACF → Field Groups → Add New**
2. Name: "Food Resource Info"
3. Location: Post Type is equal to `food-resource`
4. Add these fields (use FLAT structure, not nested groups):

**Address Information:**
- `street_address` (Text)
- `city` (Text)
- `state` (Select - single, return value)
  - Add all US states
- `zip` (Number)
- `county` (Select - single, return value)
  - Macomb County
  - Oakland County
  - Wayne County

**Contact Information:**
- `phone` (Number)
- `url` (URL)

**Service Information:**
- `services` (Select - multiple, return value)
  - Food Pantry
  - Soup Kitchen
  - Other
- `languages` (Select - multiple, return label)
  - Add all languages you want to support

**Hours (FLAT structure - NOT nested groups!):**

For each day, create 3 separate fields:
- `hours_monday_open` (True/False)
- `hours_monday_open_time` (Time Picker, conditional on open=true)
- `hours_monday_close_time` (Time Picker, conditional on open=true)
- `hours_tuesday_open` (True/False)
- `hours_tuesday_open_time` (Time Picker, conditional on open=true)
- `hours_tuesday_close_time` (Time Picker, conditional on open=true)
- *(Repeat for wednesday, thursday, friday, saturday, sunday)*

**See [ACF-FIELD-STRUCTURE.md](ACF-FIELD-STRUCTURE.md) for complete step-by-step instructions.**

**Additional Information:**
- `eligibility` (Text Area)
- `notes` (Text Area)

### Step 3: Configure Mapbox Token

Edit `food-resource-directory.php` and update the Mapbox token on line 57:

```php
'mapboxToken' => 'YOUR_MAPBOX_TOKEN_HERE'
```

And line 263:

```php
$mapbox_token = 'YOUR_MAPBOX_TOKEN_HERE';
```

**Get a free Mapbox token:** https://account.mapbox.com/

### Step 4: Add to Your Site

1. Create a new page or edit an existing one
2. Add the shortcode:
   ```
   [food_resource_directory]
   ```
3. Publish the page

**Optional:** Start with list view instead of map:
```
[food_resource_directory default_view="list"]
```

## Usage

### Adding Food Resources

1. Go to **WordPress Admin → Food Resources → Add New**
2. Enter the location name as the **Title**
3. Fill in all fields in the "Food Resource Info" section
4. Click **Publish**

### For Site Visitors

1. **View Locations**: Choose between map or list view
2. **Enter Your Location**: Type an address, city, or ZIP code to see distances
3. **Apply Filters**: Select service types, days, county, or distance
4. **Click for Details**: Click markers or list items for full information
5. **Take Action**: Call, visit website, or get directions

## Customization

### Colors

Edit `assets/css/style.css` and modify CSS variables:

```css
:root {
    --frd-primary: #2563eb;        /* Primary color */
    --frd-primary-dark: #1e40af;   /* Darker shade */
    --frd-danger: #dc2626;         /* Marker color */
    /* ... */
}
```

### Map Settings

Edit `food-resource-directory.php` around line 60:

```php
'defaultCenter' => array(-83.0458, 42.5803), // [longitude, latitude]
'defaultZoom' => 9
```

### Map Style

Edit `assets/js/script.js` around line 30:

```javascript
style: 'mapbox://styles/mapbox/streets-v12', // Change Mapbox style
```

## File Structure

```
food-resource-directory/
├── food-resource-directory.php    # Main plugin file
├── README.md                       # This file
├── CONTEXT.md                      # Detailed documentation
├── templates/
│   └── directory.php              # Frontend template
└── assets/
    ├── css/
    │   └── style.css              # Stylesheet
    └── js/
        └── script.js              # JavaScript functionality
```

## Troubleshooting

### Locations not appearing on map

- Verify addresses are complete and correct
- Check that Mapbox token is valid
- Check browser console for JavaScript errors

### Filters not working

- Verify ACF field names match exactly
- Check that locations have the fields populated
- Clear browser cache

### Distance calculation not working

- Ensure user enters a valid address
- Verify Mapbox geocoding is working
- Check browser console for errors

See [CONTEXT.md](CONTEXT.md) for detailed troubleshooting guide.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Support

For issues, questions, or feature requests:
- Open an issue on GitHub
- See [CONTEXT.md](CONTEXT.md) for detailed documentation

## Credits

- **Mapping**: [Mapbox GL JS](https://www.mapbox.com/)
- **Custom Fields**: [Advanced Custom Fields PRO](https://www.advancedcustomfields.com/)
- **Framework**: WordPress

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

## Changelog

### 1.0.0 - October 2025
- Initial release
- Map view with Mapbox integration
- List view with sorting
- Comprehensive filtering system
- Location search and distance calculation
- Responsive design
- Detail modal
- ACF integration

---

**Made with ❤️ to help connect people with food resources**
