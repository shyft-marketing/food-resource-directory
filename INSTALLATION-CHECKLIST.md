# Food Resource Directory - Quick Installation Checklist

## ✅ Pre-Installation
- [ ] WordPress 6.8.3+ installed
- [ ] Advanced Custom Fields PRO installed and activated
- [ ] Mapbox account created (free tier: https://account.mapbox.com/)
- [ ] Mapbox access token obtained

## ✅ Plugin Installation
- [ ] Upload `food-resource-directory` folder to `/wp-content/plugins/`
- [ ] Activate plugin in WordPress Admin → Plugins

## ✅ ACF Setup (Custom Post Type)
- [ ] Go to ACF → Post Types → Add New
- [ ] Post Type Key: `food-resource`
- [ ] Configure settings (see CONTEXT.md for details)
- [ ] Save

## ✅ ACF Setup (Field Group)
- [ ] Go to ACF → Field Groups → Add New
- [ ] Name: "Food Resource Info"
- [ ] Location rule: Post Type = food-resource
- [ ] Add all fields as specified in CONTEXT.md:
  - [ ] Address fields (street_address, city, state, zip, county)
  - [ ] Contact fields (phone, url)
  - [ ] Service fields (services, languages)
  - [ ] Hours group with 7 day sub-groups
  - [ ] Additional fields (eligibility, notes)
- [ ] Save field group

## ✅ Configuration
- [ ] Edit `food-resource-directory.php`
- [ ] Replace Mapbox token on line 57 and line 263
- [ ] Save file

## ✅ Page Setup
- [ ] Create new page or edit existing page
- [ ] Add shortcode: `[food_resource_directory]`
- [ ] Publish page
- [ ] Test on frontend

## ✅ Testing
- [ ] Add at least one test location with complete data
- [ ] Visit the directory page
- [ ] Verify map displays
- [ ] Verify list displays
- [ ] Test location search
- [ ] Test all filters
- [ ] Test detail modal
- [ ] Test on mobile device

## 🔧 Customization (Optional)
- [ ] Adjust colors in `assets/css/style.css`
- [ ] Adjust default map center/zoom in plugin file
- [ ] Adjust map style in `assets/js/script.js`

## 📝 Data Entry
- [ ] Begin adding real food resource locations
- [ ] Ensure all addresses are complete and accurate
- [ ] Fill in all available fields for best results

## 🚀 Launch
- [ ] Test thoroughly with real data
- [ ] Share the page URL with your community
- [ ] Monitor for issues and user feedback

---

Need help? See CONTEXT.md for detailed documentation and troubleshooting.

**Support Contact**: [Your contact information]
**Last Updated**: October 30, 2025
