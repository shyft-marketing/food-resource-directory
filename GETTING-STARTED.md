# Getting Started with Food Resource Directory Plugin

## 🎉 Welcome!

Your Food Resource Directory plugin is ready to use! This plugin will help you create an interactive map and directory of food pantries and soup kitchines in Macomb, Oakland, and Wayne Counties.

## 📦 What's Included

```
food-resource-directory/
├── 📄 README.md                    # GitHub-ready documentation
├── 📄 CONTEXT.md                   # Comprehensive technical documentation
├── 📄 CONFIGURATION.md             # Mapbox token setup guide
├── 📄 INSTALLATION-CHECKLIST.md   # Step-by-step installation guide
├── 📄 .gitignore                   # Git ignore rules
├── 🔧 food-resource-directory.php  # Main plugin file
├── 📁 templates/
│   └── directory.php               # Frontend display template
└── 📁 assets/
    ├── css/
    │   └── style.css               # Styles
    └── js/
        └── script.js               # Interactive functionality
```

## 🚀 Quick Start (5 Steps)

### 1️⃣ Upload to WordPress
- Upload the entire `food-resource-directory` folder to `/wp-content/plugins/`
- Activate in WordPress Admin → Plugins

### 2️⃣ Set Up ACF Fields
- You already have the custom post type `food-resource` created
- You already have the field group "Food Resource Info" created
- Everything should be ready to go!

### 3️⃣ Verify Mapbox Token (Already Done!)
Your Mapbox token is already configured in the plugin:
```
sk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0cWlhdDBhYzAybXB1Zmo4d3JrMmYifQ.3ZzRIkqmh9uPRv5WWz7MKA
```

No changes needed unless you want to use a different token.

### 4️⃣ Add to a Page
Create a page and add the shortcode:
```
[food_resource_directory]
```

### 5️⃣ Add Locations
Go to WordPress Admin → Food Resources → Add New and start adding locations!

## 📋 What You Told Me You Already Have Set Up

✅ **Custom Post Type**: `food-resource`
✅ **Field Group**: "Food Resource Info"  
✅ **All ACF Fields**: Configured with proper names and types
✅ **Mapbox Token**: Valid and ready to use

## 🎨 Features

### Map View
- Interactive Mapbox map
- Click markers for quick info
- Click again for full details
- User location search

### List View
- Sortable by distance, name, county
- Filter by services, days, county
- Click cards for full details

### Filters
- Service type (Food Pantry, Soup Kitchen, Other)
- Days of the week
- County (Macomb, Oakland, Wayne)
- Distance from user location

## 📝 Adding Your First Location

1. WordPress Admin → Food Resources → Add New
2. Title: "Example Food Pantry"
3. Fill in:
   - Street Address: "123 Main St"
   - City: "Detroit"
   - State: "MI"
   - ZIP: "48201"
   - County: "Wayne County"
   - Phone: "3135551234"
   - Services: Select "Food Pantry"
   - Languages: Select "English"
   - Hours: Check "Monday" as open, set times
4. Publish!

## 🔧 Customization Options

### Colors
Edit `assets/css/style.css` - search for `:root {` to find color variables

### Map Center/Zoom
Edit `food-resource-directory.php` around line 60:
```php
'defaultCenter' => array(-83.0458, 42.5803),
'defaultZoom' => 9
```

### Map Style
Edit `assets/js/script.js` around line 30 - change Mapbox style

## 📚 Documentation

- **README.md** - Overview and basic usage
- **CONTEXT.md** - Detailed technical documentation (read this for troubleshooting!)
- **CONFIGURATION.md** - Mapbox token setup
- **INSTALLATION-CHECKLIST.md** - Step-by-step installation

## 🐛 Troubleshooting

**Locations not showing on map?**
- Check that addresses are complete
- Verify Mapbox token is correct
- Look at browser console for errors

**Filters not working?**
- Verify ACF field names match exactly
- Check browser console for JavaScript errors

See CONTEXT.md for detailed troubleshooting.

## 🤝 Next Steps for Claude Code

When you continue with Claude Code, you can:

1. **Customize styling** to match your nonprofit's brand
2. **Add new features** like:
   - Print-friendly version
   - Export to PDF
   - "Open Now" indicator
   - Social sharing buttons
3. **Optimize performance** if you have hundreds of locations
4. **Add analytics** to track which locations are most viewed

## 💡 Tips

- Start with 5-10 test locations to verify everything works
- Make sure all addresses are complete for accurate geocoding  
- Test on mobile devices - many users will access on phones
- Use the filters to help users find what they need quickly

## ✉️ Questions?

All the documentation you need is in the included files:
- Quick questions → README.md
- Technical details → CONTEXT.md
- Setup help → INSTALLATION-CHECKLIST.md
- Token issues → CONFIGURATION.md

## 🎯 Your Mission

You're building something that will genuinely help people in need find food resources during a difficult time. That's amazing! 

The plugin is ready to go - just upload it, add your locations, and start helping your community.

---

**Plugin Version**: 1.0.0  
**Created**: October 30, 2025  
**Built for**: Community food resource access during 2025 government shutdown

**Good luck with your important work! 🌟**
