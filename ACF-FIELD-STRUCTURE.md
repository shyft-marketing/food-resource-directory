# ACF Field Structure - Exact Configuration

This document shows the **EXACT** ACF field structure that the plugin code expects.

## ⚠️ IMPORTANT: Use Flat Field Names (Not Nested Groups)

The plugin code expects **flat field names**, not nested group structures. Follow this guide exactly.

---

## Step 1: Create Custom Post Type

**ACF → Post Types → Add New**

- **Post Type Key**: `food-resource`
- **Plural Label**: `Food Resources`
- **Singular Label**: `Food Resource`
- **Settings**:
  - ✅ Public
  - ✅ Show in REST API
  - ✅ Has Archive
  - ✅ Exclude from search (optional)
  - **Supports**: Title, Editor (optional - can disable editor if you want)

---

## Step 2: Create Field Group

**ACF → Field Groups → Add New**

- **Field Group Name**: `Food Resource Info`
- **Location Rule**: Post Type is equal to `food-resource`

---

## Step 3: Add Fields (In This Exact Order)

### 📍 ADDRESS INFORMATION

#### Field 1: Street Address
- **Field Label**: `Street Address`
- **Field Name**: `street_address`
- **Field Type**: `Text`
- **Required**: Yes

#### Field 2: City
- **Field Label**: `City`
- **Field Name**: `city`
- **Field Type**: `Text`
- **Required**: Yes

#### Field 3: State
- **Field Label**: `State`
- **Field Name**: `state`
- **Field Type**: `Select`
- **Required**: Yes
- **Choices** (add all US states in format: `MI : Michigan`):
  ```
  AL : Alabama
  AK : Alaska
  AZ : Arizona
  ... (add all 50 states)
  MI : Michigan
  ... (continue)
  ```
- **Return Format**: `Value`

#### Field 4: ZIP Code
- **Field Label**: `ZIP Code`
- **Field Name**: `zip`
- **Field Type**: `Text` (NOT Number - to preserve leading zeros)
- **Required**: Yes

#### Field 5: County
- **Field Label**: `County`
- **Field Name**: `county`
- **Field Type**: `Select`
- **Required**: Yes
- **Choices**:
  ```
  Macomb County : Macomb County
  Oakland County : Oakland County
  Wayne County : Wayne County
  ```
- **Return Format**: `Value`

---

### 📞 CONTACT INFORMATION

#### Field 6: Phone Number
- **Field Label**: `Phone Number`
- **Field Name**: `phone`
- **Field Type**: `Text` (NOT Number)
- **Placeholder**: `555-123-4567`
- **Required**: No

#### Field 7: Website
- **Field Label**: `Website`
- **Field Name**: `url`
- **Field Type**: `URL`
- **Required**: No

---

### 🏪 SERVICE INFORMATION

#### Field 8: Services
- **Field Label**: `Services`
- **Field Name**: `services`
- **Field Type**: `Select`
- **Allow Multiple**: Yes
- **Required**: Yes
- **Choices**:
  ```
  Food Pantry : Food Pantry
  Soup Kitchen : Soup Kitchen
  Other : Other
  ```
- **Return Format**: `Value`

#### Field 9: Languages
- **Field Label**: `Languages Spoken`
- **Field Name**: `languages`
- **Field Type**: `Select`
- **Allow Multiple**: Yes
- **Required**: No
- **Choices** (add as many as needed):
  ```
  English : English
  Spanish : Spanish
  Arabic : Arabic
  Chinese : Chinese
  French : French
  German : German
  Italian : Italian
  Polish : Polish
  Russian : Russian
  Vietnamese : Vietnamese
  ```
- **Return Format**: `Label`

---

### 📝 ADDITIONAL INFORMATION

#### Field 10: Eligibility Requirements
- **Field Label**: `Eligibility Requirements`
- **Field Name**: `eligibility`
- **Field Type**: `Textarea`
- **Rows**: 3
- **Required**: No
- **Instructions**: `Enter any eligibility requirements or restrictions (e.g., "Must show ID", "Must be county resident", etc.)`

#### Field 11: Additional Notes
- **Field Label**: `Additional Notes`
- **Field Name**: `notes`
- **Field Type**: `Textarea`
- **Rows**: 3
- **Required**: No
- **Instructions**: `Any additional important information about this location`

---

## Field Name Reference (For Copy/Paste)

When creating fields, use these **EXACT** field names:

```
street_address
city
state
zip
county
phone
url
services
languages
eligibility
notes
```

---

## Testing Your Setup

After creating all fields, test by:

1. **Add a test location**: Food Resources → Add New
2. **Fill in all fields**
3. **Publish**
4. **View the directory page**
5. **Check browser console** for "FRD:" messages
6. **Check WordPress debug.log** for ACF field retrieval

If fields are named correctly, you'll see the location appear on the map and in the list view.

---

## Common Mistakes to Avoid

❌ **DON'T** create nested groups for hours
✅ **DO** create flat fields with names like `hours_monday_open`

❌ **DON'T** use "Number" type for phone or ZIP
✅ **DO** use "Text" type to preserve formatting

❌ **DON'T** forget to set "Return Format" correctly
✅ **DO** set services to "Value" and languages to "Label"

❌ **DON'T** skip conditional logic on time fields
✅ **DO** hide time fields when day is not open

---

## Need Help?

If locations aren't showing up:

1. Check browser console (F12) for JavaScript errors
2. Check WordPress debug.log for PHP errors
3. Verify field names match exactly (case-sensitive!)
4. Make sure ACF PRO is installed and active
5. Test with a simple address first (e.g., "123 Main St, Detroit, MI 48201")
