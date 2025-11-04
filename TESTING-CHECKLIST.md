# Pre-Release Testing Checklist

## Food Resource Directory v2.0.0

Use this checklist to verify all functionality before deploying to production.

---

## ✅ Environment Setup

- [ ] WordPress 6.0+ installed
- [ ] PHP 7.4+ verified (`php -v`)
- [ ] ACF plugin installed and activated
- [ ] Plugin activated successfully
- [ ] No PHP errors in debug.log

---

## ✅ Configuration

### Mapbox Tokens
- [ ] Navigate to Settings → Food Resource Directory
- [ ] Enter valid Mapbox Public Token (starts with `pk.`)
- [ ] Optionally enter Secret Token (starts with `sk.`)
- [ ] Click Save Settings
- [ ] Verify "Settings Saved" confirmation message
- [ ] Try invalid token format - should show error message

### ACF Setup
- [ ] Custom post type `food-resource` created
- [ ] Field group "Food Resource Info" created
- [ ] All required fields present (see ACF-FIELD-STRUCTURE.md)
- [ ] Field names match exactly (e.g., `street_address`, not `address`)
- [ ] Hours fields use FLAT structure (not nested groups)

---

## ✅ Manual Location Creation

- [ ] Navigate to Food Resources → Add New
- [ ] Enter location name as title
- [ ] Fill in all required address fields
- [ ] Add phone number (10 digits)
- [ ] Add website URL
- [ ] Select service types
- [ ] Select languages
- [ ] Set hours for at least one day
- [ ] Add eligibility and notes
- [ ] Click Publish
- [ ] No errors displayed
- [ ] Location appears in Food Resources list

---

## ✅ CSV Import Feature

### Template Download
- [ ] Navigate to Food Resources → Import Locations
- [ ] Click "Download CSV Template"
- [ ] File downloads as `food-resource-import-template.csv`
- [ ] Template opens in Excel/Google Sheets
- [ ] All column headers present and correct

### CSV Validation
- [ ] Create test CSV with 3-5 locations
- [ ] Include at least one location with invalid data (e.g., wrong county, bad ZIP)
- [ ] Upload CSV file
- [ ] Preview shows total/valid/invalid counts
- [ ] Invalid rows highlighted in red
- [ ] Error messages clear and helpful
- [ ] Valid rows highlighted in blue/green

### Import Execution
- [ ] Click "Import Valid Rows"
- [ ] Confirm import dialog appears
- [ ] Click OK to proceed
- [ ] Import completes without errors
- [ ] Results page shows success/failed/skipped counts
- [ ] Navigate to Food Resources list
- [ ] All valid locations imported correctly
- [ ] Coordinates geocoded automatically
- [ ] Phone numbers formatted correctly

---

## ✅ Frontend Display

### Initial Page Load
- [ ] Navigate to page with `[food_resource_directory]` shortcode
- [ ] Page loads without errors
- [ ] Map displays correctly (desktop)
- [ ] Mobile defaults to list view
- [ ] All locations visible on map
- [ ] Results count displays correctly

### Map View
- [ ] Map loads with correct center/zoom
- [ ] All location markers visible
- [ ] Markers cluster when zoomed out
- [ ] Click cluster - zooms in
- [ ] Click single marker - popup opens
- [ ] Popup shows:
  - [ ] Location name
  - [ ] Service type chip
  - [ ] Address
  - [ ] Phone (if present)
  - [ ] Website (if present)
  - [ ] Directions link
  - [ ] "More Info" button
- [ ] Click "More Info" - modal opens with full details
- [ ] Map navigation controls work (zoom, rotate)

### List View
- [ ] Click "List" toggle button
- [ ] List displays all locations
- [ ] Each card shows:
  - [ ] Location name
  - [ ] Service type chip
  - [ ] Address
  - [ ] Action buttons (phone, directions, website)
  - [ ] "More Info" button
- [ ] Pagination appears if >20 results
- [ ] Click "More Info" - modal opens

### Detail Modal
- [ ] Modal displays over map/list
- [ ] Shows all location information:
  - [ ] Name and service type
  - [ ] Address
  - [ ] Phone (clickable tel: link)
  - [ ] Website (clickable, opens in new tab)
  - [ ] Languages spoken
  - [ ] Eligibility requirements
  - [ ] Additional notes
- [ ] Action buttons work (Call, Visit Website, Directions)
- [ ] "Suggest edits" link works
- [ ] Click X or outside modal - closes modal
- [ ] ESC key closes modal

---

## ✅ Search & Filters

### Location Search
- [ ] Enter valid address in search box
- [ ] Click search icon or press Enter
- [ ] User marker appears on map
- [ ] Map flies to user location
- [ ] Distance filter slider appears
- [ ] All locations show distance in miles
- [ ] List sorts by distance automatically
- [ ] Sort dropdown enables "Distance" option

### Service Type Filter
- [ ] Click "Services" dropdown
- [ ] Select "Food Pantry"
- [ ] Only food pantries display
- [ ] Results count updates
- [ ] Clear selection - all types show again
- [ ] Select multiple services
- [ ] Locations with ANY selected service display

### County Filter
- [ ] Select "Macomb County"
- [ ] Only Macomb locations display
- [ ] Results count updates
- [ ] Change to "Oakland County"
- [ ] Results update immediately

### Language Filter
- [ ] Languages dropdown populated with available languages only
- [ ] Select "Spanish"
- [ ] Only locations offering Spanish display
- [ ] Select multiple languages
- [ ] Locations with ANY selected language display

### Distance Filter
- [ ] Only visible after location search
- [ ] Slider starts at 25 miles
- [ ] Drag slider to 10 miles
- [ ] Results filter after 300ms (debounced)
- [ ] Only locations within 10 miles display
- [ ] Distance updates smoothly without lag

### Combined Filters
- [ ] Enter location search
- [ ] Select service type
- [ ] Select county
- [ ] Set distance
- [ ] All filters work together (AND logic)
- [ ] Results count accurate
- [ ] Map and list stay in sync

### Reset Filters
- [ ] Click "Reset" button
- [ ] All filters clear
- [ ] Location search clears
- [ ] User marker removed
- [ ] Distance filter hides
- [ ] All locations display again
- [ ] Map returns to default view

---

## ✅ Sorting

### List View Sorting
- [ ] Sort by "Name (A-Z)" - alphabetical order
- [ ] Sort by "County" - grouped by county
- [ ] Sort by "Distance" (only after location search)
  - [ ] Closest locations first
  - [ ] Locations without coordinates at end
- [ ] Sorting persists when switching filters
- [ ] Pagination resets to page 1 when sorting changes

---

## ✅ Responsive Design

### Desktop (1920x1080)
- [ ] Map and list views work
- [ ] Filters display correctly
- [ ] Modal is centered
- [ ] No layout issues

### Tablet (768x1024)
- [ ] Defaults to list view
- [ ] Toggle between map/list works
- [ ] Filters collapse behind toggle button
- [ ] Touch interactions work
- [ ] Modal adapts to screen size

### Mobile (375x667)
- [ ] Defaults to list view
- [ ] Map view works if toggled
- [ ] Filters accessible via button
- [ ] Cards stack vertically
- [ ] Modal fills screen appropriately
- [ ] Phone links clickable
- [ ] Directions open in maps app

---

## ✅ Performance

### Caching
- [ ] First load queries database
- [ ] Subsequent loads use cache (faster)
- [ ] Edit a location
- [ ] Cache clears automatically
- [ ] Next load queries database again
- [ ] Import locations
- [ ] Cache clears automatically

### Rate Limiting
- [ ] Search for location 10 times quickly
- [ ] 11th search shows rate limit error
- [ ] Wait 1 minute
- [ ] Searches work again

### Load Times
- [ ] Initial page load < 3 seconds
- [ ] Filter changes < 1 second
- [ ] Map interactions smooth (60fps)
- [ ] No console errors
- [ ] No JavaScript warnings

---

## ✅ Browser Testing

Test in each browser (see BROWSER-SUPPORT.md):

### Chrome/Edge
- [ ] All features work
- [ ] Map renders correctly
- [ ] WebGL enabled

### Firefox
- [ ] All features work
- [ ] Map renders correctly
- [ ] No privacy blocking issues

### Safari
- [ ] All features work
- [ ] Map renders correctly
- [ ] No WebKit-specific issues

### Mobile Safari (iOS)
- [ ] Map renders correctly
- [ ] Touch gestures work
- [ ] Phone links work
- [ ] No layout issues

### Chrome Android
- [ ] Map renders correctly
- [ ] Touch gestures work
- [ ] All features functional

---

## ✅ Error Handling

### Network Errors
- [ ] Disable internet
- [ ] Try to search location
- [ ] Appropriate error message shown
- [ ] Re-enable internet
- [ ] Functionality restored

### Invalid Data
- [ ] Location with missing coordinates
- [ ] Still appears in list (not on map)
- [ ] No JavaScript errors
- [ ] Distance shows as N/A

### API Errors
- [ ] Use invalid Mapbox token
- [ ] Map fails to load gracefully
- [ ] Error message displayed
- [ ] Page doesn't crash

---

## ✅ Security

### Admin Functions
- [ ] Non-admin users can't access import page
- [ ] Non-admin users can't access settings
- [ ] AJAX actions verify nonces
- [ ] File uploads validate file type
- [ ] SQL injection attempts blocked

### XSS Protection
- [ ] Location names with `<script>` tags
- [ ] Properly escaped in display
- [ ] No script execution
- [ ] HTML entities displayed as text

---

## ✅ Accessibility

- [ ] Keyboard navigation works
- [ ] Tab through all interactive elements
- [ ] Enter/Space activates buttons
- [ ] ESC closes modal
- [ ] Focus indicators visible
- [ ] Screen reader announces content (test with NVDA/JAWS if possible)
- [ ] Color contrast meets WCAG AA standards
- [ ] Form labels properly associated

---

## ✅ Cleanup & Uninstall

### Plugin Deactivation
- [ ] Deactivate plugin
- [ ] No PHP errors
- [ ] Locations still exist
- [ ] Reactivate works

### Plugin Uninstall
- [ ] Delete plugin
- [ ] Verify uninstall.php runs
- [ ] Check database:
  - [ ] `frd_mapbox_public_token` option deleted
  - [ ] `frd_mapbox_secret_token` option deleted
  - [ ] All transients deleted
  - [ ] All `food-resource` posts deleted
- [ ] ACF fields remain (as expected)

---

## ✅ Final Checks

- [ ] No PHP errors in debug.log
- [ ] No JavaScript errors in console
- [ ] No broken images/icons
- [ ] All links work
- [ ] All external resources load (Mapbox, Select2, etc.)
- [ ] Performance is acceptable
- [ ] User experience is smooth
- [ ] Documentation is accurate (README, CONTEXT, etc.)
- [ ] Version number correct everywhere (2.0.0)

---

## 🎉 Ready for Release!

Once all items are checked:

1. ✅ Commit all changes to git
2. ✅ Tag release as v2.0.0
3. ✅ Push to GitHub
4. ✅ Create release notes
5. ✅ Deploy to production
6. ✅ Monitor for issues

---

**Testing Date:** _____________

**Tested By:** _____________

**Notes:**
