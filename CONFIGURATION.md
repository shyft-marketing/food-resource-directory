# Configuration Guide

## Mapbox Token Setup

You need to replace the Mapbox token in **TWO** locations in the `food-resource-directory.php` file.

### Your Mapbox Token
```
sk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0cWlhdDBhYzAybXB1Zmo4d3JrMmYifQ.3ZzRIkqmh9uPRv5WWz7MKA
```

**Note**: This token is already included in the plugin! You only need to change it if:
- You want to use a different Mapbox account
- This token expires or stops working
- You want to use a token with different permissions

### Location 1: Line ~57 (JavaScript Configuration)

Find this section in `food-resource-directory.php`:

```php
wp_localize_script('frd-script', 'frdData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('frd_nonce'),
    'mapboxToken' => 'YOUR_TOKEN_HERE',  // ← REPLACE THIS
    'defaultCenter' => array(-83.0458, 42.5803),
    'defaultZoom' => 9
));
```

Replace `'YOUR_TOKEN_HERE'` with your token.

### Location 2: Line ~263 (Geocoding Function)

Find this section in `food-resource-directory.php`:

```php
private function geocode_address($address) {
    $mapbox_token = 'YOUR_TOKEN_HERE';  // ← REPLACE THIS
    $url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($address) . '.json?access_token=' . $mapbox_token . '&country=US&proximity=-83.0458,42.5803';
    // ...
}
```

Replace `'YOUR_TOKEN_HERE'` with your token.

## Default Map Center

The map is currently centered on the three-county area (Macomb, Oakland, Wayne in Michigan).

**Current coordinates**: Longitude: -83.0458, Latitude: 42.5803

To change the default center, edit these values in **both locations** in the plugin file.

## Mapbox Token Permissions

Your token needs these permissions (scopes):
- ✅ `styles:read` - For map styles
- ✅ `geocoding` - For address search and geocoding
- ✅ `directions` - Optional, for future features

Free Mapbox accounts include:
- 50,000 free map loads per month
- 100,000 free geocoding requests per month

This should be more than sufficient for most nonprofit use cases.

## Security Best Practice (Advanced)

For better security, you can store the token in `wp-config.php` instead of the plugin file:

1. Add to `wp-config.php`:
   ```php
   define('FRD_MAPBOX_TOKEN', 'your-token-here');
   ```

2. In the plugin file, replace both instances with:
   ```php
   defined('FRD_MAPBOX_TOKEN') ? FRD_MAPBOX_TOKEN : ''
   ```

This keeps the token out of your codebase and version control.

---

**Questions?** See CONTEXT.md for more detailed documentation.
