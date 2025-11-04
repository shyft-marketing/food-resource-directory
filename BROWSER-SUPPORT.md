# Browser Support

## Food Resource Directory Plugin v2.0.0

This plugin uses modern web technologies and has been tested to work with the following browsers:

### Fully Supported Browsers

- **Chrome/Edge** (Chromium-based): Version 90+ (Released April 2021+)
- **Firefox**: Version 88+ (Released April 2021+)
- **Safari**: Version 14+ (Released September 2020+)
- **Mobile Safari (iOS)**: Version 14+ (Released September 2020+)
- **Chrome for Android**: Version 90+ (Released April 2021+)

### Key Technology Requirements

The plugin relies on the following browser features:

1. **Mapbox GL JS v2.15.0**
   - Requires WebGL support
   - Requires ES6+ JavaScript features
   - Does not support IE11

2. **CSS Features**
   - CSS Grid Layout
   - CSS Flexbox
   - CSS Custom Properties (CSS Variables)
   - Modern CSS selectors

3. **JavaScript Features**
   - ES6+ (Arrow functions, template literals, const/let)
   - Promises
   - Fetch API
   - Array methods (map, filter, find, etc.)

### Not Supported

- **Internet Explorer 11 and earlier**: Not supported due to lack of WebGL and ES6+ support
- **Safari 13 and earlier**: Limited support, may experience issues
- **Opera Mini**: Not supported due to limited JavaScript support

### Progressive Enhancement

The plugin is designed with progressive enhancement in mind:

- Core functionality requires JavaScript to be enabled
- The map requires WebGL support
- Users with JavaScript disabled will see a message prompting them to enable it
- Mobile devices automatically default to list view for better usability

### Recommendations

For the best user experience:

1. Keep browsers updated to the latest stable versions
2. Enable JavaScript
3. Ensure WebGL is not blocked by browser settings or extensions
4. Use a modern browser from the supported list above

### Testing

This plugin has been tested on:

- Desktop: Chrome 120+, Firefox 120+, Safari 17+, Edge 120+
- Mobile: iOS Safari 17+, Chrome for Android 120+
- Tablet: iPad (iOS 17+), Android tablets (Chrome 120+)

### Known Issues

- **Safari on older macOS versions**: Map rendering may be slower on devices with limited GPU capabilities
- **Firefox with strict privacy settings**: Some features may require adjustments to tracking protection settings
- **Mobile browsers in low-power mode**: Map animations may be reduced

For issues or questions about browser compatibility, please contact support or file an issue on GitHub.
