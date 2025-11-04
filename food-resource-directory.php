<?php
/**
    * Plugin Name: Food Resource Directory
    * Plugin URI: https://github.com/shyft-marketing/food-resource-directory
    * Description: Interactive map and filterable directory of food pantries and soup kitchens with ACF integration
    * Version: 2.0.0
    * Author: SHYFT
    * Author URI: https://shyft.wtf
    * License: GPL v2 or later
    * GitHub Plugin URI: shyft-marketing/food-resource-directory
    * Primary Branch: main
    * Plugin Icon: assets/icon-256x256.png
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FRD_VERSION', '2.0.0');
define('FRD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FRD_PLUGIN_URL', plugin_dir_url(__FILE__));

class Food_Resource_Directory {

    /**
     * Constructor
     */
    public function __construct() {
        // Check for ACF dependency
        add_action('admin_init', array($this, 'check_dependencies'));
        add_action('admin_notices', array($this, 'dependency_notice'));

        // Register settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));

        // Only initialize if ACF is active
        if ($this->is_acf_active()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_shortcode('food_resource_directory', array($this, 'render_directory'));
            add_action('wp_ajax_frd_get_locations', array($this, 'ajax_get_locations'));
            add_action('wp_ajax_nopriv_frd_get_locations', array($this, 'ajax_get_locations'));
            add_action('wp_ajax_frd_geocode', array($this, 'ajax_geocode'));
            add_action('wp_ajax_nopriv_frd_geocode', array($this, 'ajax_geocode'));
            
            // Import functionality
            add_action('admin_menu', array($this, 'add_import_page'));
            add_action('admin_post_frd_download_template', array($this, 'download_template'));
            add_action('wp_ajax_frd_upload_preview', array($this, 'ajax_upload_preview'));
            add_action('wp_ajax_frd_get_preview', array($this, 'ajax_get_preview'));
            add_action('wp_ajax_frd_confirm_import', array($this, 'ajax_confirm_import'));
            add_action('wp_ajax_frd_get_results', array($this, 'ajax_get_results'));
            
            // Cache invalidation hooks
            add_action('save_post_food-resource', array($this, 'clear_location_caches'));
            add_action('delete_post', array($this, 'clear_location_caches'));
            add_action('acf/save_post', array($this, 'clear_location_caches'), 20);
        }
    }

    /**
     * Check if ACF is active
     */
    private function is_acf_active() {
        return class_exists('ACF');
    }

    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        if (!$this->is_acf_active()) {
            set_transient('frd_missing_acf', true, 5);
        }
    }

    /**
     * Show admin notice if dependencies are missing
     */
    public function dependency_notice() {
        if (get_transient('frd_missing_acf')) {
            ?>
            <div class="notice notice-error">
                <p><strong>Food Resource Directory:</strong> This plugin requires Advanced Custom Fields (ACF) or ACF PRO to be installed and activated. <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>">Install ACF now</a>.</p>
            </div>
            <?php
            delete_transient('frd_missing_acf');
        }
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_settings_page() {
        add_options_page(
            'Food Resource Directory Settings',
            'Food Resource Directory',
            'manage_options',
            'food-resource-directory',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('frd_settings', 'frd_mapbox_public_token', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_mapbox_token'),
            'default' => 'pk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0bDlrejBhMXQybnB2Zng5aW85M3UifQ.dsT7ITwivyDeR0j07AZkgA'
        ));
        
        register_setting('frd_settings', 'frd_mapbox_secret_token', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_mapbox_token'),
            'default' => ''
        ));

        add_settings_section(
            'frd_mapbox_section',
            'Mapbox Configuration',
            array($this, 'render_mapbox_section_info'),
            'frd_settings'
        );

        add_settings_field(
            'frd_mapbox_public_token',
            'Mapbox Public Token',
            array($this, 'render_public_token_field'),
            'frd_settings',
            'frd_mapbox_section'
        );

        add_settings_field(
            'frd_mapbox_secret_token',
            'Mapbox Secret Token',
            array($this, 'render_secret_token_field'),
            'frd_settings',
            'frd_mapbox_section'
        );
    }
    
    /**
     * Sanitize Mapbox token
     */
    public function sanitize_mapbox_token($token) {
        $token = sanitize_text_field($token);
        
        // Validate Mapbox token format (starts with sk. or pk.)
        if (!empty($token) && !preg_match('/^(sk|pk)\.[a-zA-Z0-9_-]+$/', $token)) {
            add_settings_error('frd_messages', 'invalid_token', 
                'Invalid Mapbox token format. Token must start with sk. or pk.', 'error');
            // Return current value instead of invalid one
            $setting_name = strpos($token, 'sk.') === 0 ? 'frd_mapbox_secret_token' : 'frd_mapbox_public_token';
            return get_option($setting_name, '');
        }
        
        return $token;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error('frd_messages', 'frd_message', 'Settings Saved', 'updated');
        }

        settings_errors('frd_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('frd_settings');
                do_settings_sections('frd_settings');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render section info for Mapbox settings
     */
    public function render_mapbox_section_info() {
        echo '<p>Configure your Mapbox API tokens. The Public Token is used for displaying the map in the browser, and the Secret Token (optional) is used for server-side geocoding operations.</p>';
    }

    /**
     * Render public token field
     */
    public function render_public_token_field() {
        $value = get_option('frd_mapbox_public_token', 'pk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0bDlrejBhMXQybnB2Zng5aW85M3UifQ.dsT7ITwivyDeR0j07AZkgA');
        ?>
        <input type="text" 
               id="frd_mapbox_public_token" 
               name="frd_mapbox_public_token" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="pk.ey...">
        <p class="description">
            <strong>Required:</strong> Your Mapbox Public Token for client-side map display.<br>
            Get your token from <a href="https://account.mapbox.com/access-tokens/" target="_blank" rel="noopener noreferrer">Mapbox Account</a>.<br>
            Token must start with "pk."
        </p>
        <?php
    }

    /**
     * Render secret token field
     */
    public function render_secret_token_field() {
        $value = get_option('frd_mapbox_secret_token', '');
        ?>
        <input type="text" 
               id="frd_mapbox_secret_token" 
               name="frd_mapbox_secret_token" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="sk.ey...">
        <p class="description">
            <strong>Optional:</strong> Your Mapbox Secret Token for server-side geocoding operations.<br>
            Leave blank to use the Public Token for all operations.<br>
            <em>Note: Secret tokens can only be used server-side.</em><br>
            Get your token from <a href="https://account.mapbox.com/access-tokens/" target="_blank" rel="noopener noreferrer">Mapbox Account</a>.<br>
            Token must start with "sk."
        </p>
        <?php
    }

    /**
     * Get Mapbox public token for client-side use
     * Always returns the public token (secret tokens cannot be used in browser)
     */
    private function get_mapbox_public_token() {
        return get_option('frd_mapbox_public_token', 'pk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0bDlrejBhMXQybnB2Zng5aW85M3UifQ.dsT7ITwivyDeR0j07AZkgA');
    }

    /**
     * Get Mapbox token for server-side use
     * Returns Secret Token if set, otherwise returns Public Token
     */
    private function get_mapbox_server_token() {
        $secret_token = get_option('frd_mapbox_secret_token', '');
        
        if (!empty($secret_token)) {
            return $secret_token;
        }
        
        // Fallback to Public Token
        return $this->get_mapbox_public_token();
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'food_resource_directory')) {
            
            // Mapbox GL JS v2
            wp_enqueue_style('mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css', array(), '2.15.0');
            wp_enqueue_script('mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js', array(), '2.15.0', true);
            
            // Mapbox GL Geocoder
            wp_enqueue_style('mapbox-geocoder', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css', array(), '5.0.0');
            wp_enqueue_script('mapbox-geocoder', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js', array('mapbox-gl'), '5.0.0', true);

            // Select2 for multi-select dropdowns
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);

            // Plugin styles (load after Mapbox and Select2)
            wp_enqueue_style('frd-styles', FRD_PLUGIN_URL . 'assets/css/style.css', array('mapbox-gl', 'select2'), FRD_VERSION);

            // Plugin script
            wp_enqueue_script('frd-script', FRD_PLUGIN_URL . 'assets/js/script.js', array('jquery', 'mapbox-gl', 'mapbox-geocoder', 'select2'), FRD_VERSION, true);
            
            // Localize script with AJAX URL and settings
            wp_localize_script('frd-script', 'frdData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('frd_nonce'),
                'mapboxToken' => $this->get_mapbox_public_token(),
                'pluginUrl' => FRD_PLUGIN_URL,
                'defaultCenter' => array(-83.0458, 42.5803), // Center of the three counties
                'defaultZoom' => 9,
                'availableLanguages' => $this->get_available_languages()
            ));
        }
    }
    
    /**
     * Render the directory shortcode
     */
    public function render_directory($atts) {
        $atts = shortcode_atts(array(
            'default_view' => 'map' // 'map' or 'list'
        ), $atts);
        
        ob_start();
        include FRD_PLUGIN_DIR . 'templates/directory.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX handler to get locations with filters
     */
    public function ajax_get_locations() {
        check_ajax_referer('frd_nonce', 'nonce');

        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $user_location = isset($_POST['user_location']) ? $_POST['user_location'] : null;
        
        // Check cache first
        $cache_key = 'frd_locations_' . md5(serialize($filters) . serialize($user_location));
        $cached = get_transient($cache_key);
        
        if ($cached !== false && defined('WP_DEBUG') && !WP_DEBUG) {
            wp_send_json_success($cached);
        }

        $args = array(
            'post_type' => 'food-resource',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        // Apply meta query filters
        $meta_query = array('relation' => 'AND');
        
        // Filter by services
        if (!empty($filters['services']) && is_array($filters['services'])) {
            $service_query = array('relation' => 'OR');
            foreach ($filters['services'] as $service) {
                $service_query[] = array(
                    'key' => 'services',
                    'value' => '"' . $service . '"',
                    'compare' => 'LIKE'
                );
            }
            $meta_query[] = $service_query;
        }
        
        // Filter by county
        if (!empty($filters['county'])) {
            $meta_query[] = array(
                'key' => 'county',
                'value' => $filters['county'],
                'compare' => '='
            );
        }
        
        // Filter by languages
        if (!empty($filters['languages']) && is_array($filters['languages'])) {
            $language_query = array('relation' => 'OR');
            foreach ($filters['languages'] as $language) {
                $language_query[] = array(
                    'key' => 'languages',
                    'value' => '"' . $language . '"',
                    'compare' => 'LIKE'
                );
            }
            $meta_query[] = $language_query;
        }
        
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }
        
        $query = new WP_Query($args);
        $locations = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $location = $this->get_location_data($post_id);

                // Calculate distance if user location is provided
                if ($user_location && isset($user_location['lat']) && isset($user_location['lng'])) {
                    // Only calculate distance if location has coordinates
                    if ($location['latitude'] && $location['longitude']) {
                        $location['distance'] = $this->calculate_distance(
                            $user_location['lat'],
                            $user_location['lng'],
                            $location['latitude'],
                            $location['longitude']
                        );
                    }
                }

                $locations[] = $location;
            }
            wp_reset_postdata();
        }

        // Sort by distance if user location is provided
        if ($user_location) {
            usort($locations, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
        }
        
        // Apply extensibility filter
        $locations = apply_filters('frd_locations_data', $locations, $filters);
        
        // Cache results (1 hour)
        set_transient($cache_key, $locations, HOUR_IN_SECONDS);

        wp_send_json_success($locations);
    }
    
    /**
     * Format phone number for display: 5555555555 -> (555) 555-5555
     */
    private function format_phone_display($phone) {
        if (empty($phone)) {
            return '';
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Check if it's a valid 10-digit US number
        if (strlen($phone) == 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4);
        }

        // Return original if not 10 digits
        return $phone;
    }

    /**
     * Format phone number for tel: link: 5555555555 -> +15555555555
     */
    private function format_phone_link($phone) {
        if (empty($phone)) {
            return '';
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add +1 for US numbers (assuming 10-digit US numbers)
        if (strlen($phone) == 10) {
            return '+1' . $phone;
        }

        // Return with + prefix if already has country code
        if (strlen($phone) == 11 && substr($phone, 0, 1) == '1') {
            return '+' . $phone;
        }

        // Return original if format is unclear
        return $phone;
    }

    /**
     * Get formatted location data for a post
     */
    private function get_location_data($post_id) {
        $street_address = get_field('street_address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $zip = get_field('zip', $post_id);

        // Build full address
        $full_address = trim($street_address . ', ' . $city . ', ' . $state . ' ' . $zip);

        // Get coordinates (we'll geocode these on first load)
        $coordinates = get_post_meta($post_id, '_frd_coordinates', true);
        if (empty($coordinates)) {
            $coordinates = $this->geocode_address($full_address);
            if ($coordinates) {
                update_post_meta($post_id, '_frd_coordinates', $coordinates);
            }
        }

        // Get services
        $services = get_field('services', $post_id);
        if (!is_array($services)) {
            $services = $services ? array($services) : array();
        }
        
        // Get languages
        $languages = get_field('languages', $post_id);
        if (!is_array($languages)) {
            $languages = $languages ? array($languages) : array();
        }

        // Get and format phone number
        $phone_raw = get_field('phone', $post_id);
        $phone_display = $this->format_phone_display($phone_raw);
        $phone_link = $this->format_phone_link($phone_raw);

        return array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'street_address' => $street_address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'full_address' => $full_address,
            'phone' => $phone_display,
            'phone_link' => $phone_link,
            'website' => get_field('url', $post_id),
            'services' => $services,
            'languages' => $languages,
            'eligibility' => get_field('eligibility', $post_id),
            'notes' => get_field('notes', $post_id),
            'county' => get_field('county', $post_id),
            'latitude' => isset($coordinates['lat']) ? $coordinates['lat'] : null,
            'longitude' => isset($coordinates['lng']) ? $coordinates['lng'] : null,
            'distance' => null
        );
    }
    
    /**
     * Geocode an address using Mapbox
     */
    private function geocode_address($address) {
        $mapbox_token = $this->get_mapbox_server_token();
        $url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($address) . '.json?access_token=' . $mapbox_token . '&country=US&proximity=-83.0458,42.5803';

        $response = wp_remote_get($url, array('timeout' => 15));

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('FRD Geocoding Error: ' . $response->get_error_message());
            }
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['features'][0]['center'])) {
            return array(
                'lng' => $data['features'][0]['center'][0],
                'lat' => $data['features'][0]['center'][1]
            );
        }

        return null;
    }
    
    /**
     * AJAX handler for geocoding user input
     */
    public function ajax_geocode() {
        check_ajax_referer('frd_nonce', 'nonce');
        
        // Rate limit: 10 requests per minute per user/IP
        $user_id = get_current_user_id();
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
        $rate_key = 'frd_geocode_rate_' . ($user_id ?: $ip);
        
        $count = get_transient($rate_key);
        if ($count !== false && $count >= 10) {
            wp_send_json_error('Rate limit exceeded. Please wait a moment before searching again.');
        }
        
        set_transient($rate_key, ($count ?: 0) + 1, MINUTE_IN_SECONDS);
        
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        
        if (empty($address)) {
            wp_send_json_error('Address is required');
        }
        
        $coordinates = $this->geocode_address($address);
        
        if ($coordinates) {
            wp_send_json_success($coordinates);
        } else {
            wp_send_json_error('Could not geocode address');
        }
    }
    
    /**
     * Get list of languages that are actually used in at least one location
     */
    private function get_available_languages() {
        // Check cache first
        $cached = get_transient('frd_available_languages');
        if ($cached !== false) {
            return $cached;
        }
        
        $args = array(
            'post_type' => 'food-resource',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        );
        
        $query = new WP_Query($args);
        $all_languages = array();
        
        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $languages = get_field('languages', $post_id);
                if (is_array($languages) && !empty($languages)) {
                    $all_languages = array_merge($all_languages, $languages);
                }
            }
        }
        
        // Get unique languages and sort alphabetically
        $unique_languages = array_unique($all_languages);
        sort($unique_languages);
        
        // Cache for 1 hour
        set_transient('frd_available_languages', $unique_languages, HOUR_IN_SECONDS);
        
        return $unique_languages;
    }
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        if (is_null($lat2) || is_null($lon2)) {
            return 999999; // Return large number for locations without coordinates
        }
        
        $earth_radius = 3959; // miles
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earth_radius * $c;
        
        return round($distance, 2);
    }
    /**
     * Add import page to admin menu
     */
    public function add_import_page() {
        add_submenu_page(
            'edit.php?post_type=food-resource',
            'Import Locations',
            'Import Locations',
            'manage_options',
            'food-resource-directory-import',
            array($this, 'render_import_page')
        );
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        include FRD_PLUGIN_DIR . 'includes/admin/import-page.php';
    }
    
    /**
     * Download CSV template
     */
    public function download_template() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'frd_download_template')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        require_once FRD_PLUGIN_DIR . 'includes/class-frd-csv-parser.php';
        $parser = new FRD_CSV_Parser();
        
        $csv_content = $parser->generate_template();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="food-resource-import-template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $csv_content;
        exit;
    }
    
    /**
     * AJAX: Upload and preview CSV
     */
    public function ajax_upload_preview() {
        check_ajax_referer('frd_import_upload', 'frd_import_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        if (!isset($_FILES['import_file'])) {
            wp_send_json_error(array('message' => 'No file uploaded'));
        }
        
        $file = $_FILES['import_file'];
        $temp_file = '';
        
        try {
            // Check file type
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file_ext !== 'csv') {
                wp_send_json_error(array('message' => 'Only CSV files are allowed'));
            }
            
            // Move uploaded file
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['basedir'] . '/frd-import-' . time() . '.csv';
            
            if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
                wp_send_json_error(array('message' => 'Failed to save uploaded file'));
            }
            
            // Parse and validate
            require_once FRD_PLUGIN_DIR . 'includes/class-frd-importer.php';
            $importer = new FRD_Importer();
            
            $result = $importer->parse_and_validate($temp_file);
            
            if (!$result['success']) {
                throw new Exception(implode(' ', $result['errors']));
            }
            
            // Store data in transient for preview
            set_transient('frd_import_data_' . get_current_user_id(), array(
                'file' => $temp_file,
                'data' => $result
            ), 3600); // 1 hour
            
            wp_send_json_success(array('message' => 'File uploaded successfully'));
            
        } catch (Exception $e) {
            // Clean up temp file on error
            if ($temp_file && file_exists($temp_file)) {
                @unlink($temp_file);
            }
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Get preview data
     */
    public function ajax_get_preview() {
        check_ajax_referer('frd_import_preview', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $import_data = get_transient('frd_import_data_' . get_current_user_id());
        
        if (!$import_data) {
            wp_send_json_error(array('message' => 'No import data found. Please upload a file first.'));
        }
        
        $result = $import_data['data'];
        
        wp_send_json_success(array(
            'total_rows' => $result['total_rows'],
            'valid_rows' => $result['valid_rows'],
            'invalid_rows' => $result['invalid_rows'],
            'preview' => $result['data']
        ));
    }
    
    /**
     * AJAX: Confirm and execute import
     */
    public function ajax_confirm_import() {
        check_ajax_referer('frd_import_confirm', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $import_data = get_transient('frd_import_data_' . get_current_user_id());
        
        if (!$import_data) {
            wp_send_json_error(array('message' => 'No import data found'));
        }
        
        require_once FRD_PLUGIN_DIR . 'includes/class-frd-importer.php';
        $importer = new FRD_Importer();
        
        $results = $importer->import_data($import_data['data']['data']);
        
        // Clean up temp file
        if (file_exists($import_data['file'])) {
            @unlink($import_data['file']);
        }
        
        // Store results in transient
        set_transient('frd_import_results_' . get_current_user_id(), $results, 3600);
        
        // Delete import data
        delete_transient('frd_import_data_' . get_current_user_id());
        
        wp_send_json_success(array('message' => 'Import completed'));
    }
    
    /**
     * AJAX: Get import results
     */
    public function ajax_get_results() {
        check_ajax_referer('frd_import_results', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $results = get_transient('frd_import_results_' . get_current_user_id());
        
        if (!$results) {
            wp_send_json_error(array('message' => 'No import results found'));
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Plugin activation hook
     */
    public static function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Food Resource Directory requires PHP 7.4 or higher. You are running PHP ' . PHP_VERSION);
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Food Resource Directory requires WordPress 6.0 or higher. You are running WordPress ' . get_bloginfo('version'));
        }

        // Check for ACF
        if (!class_exists('ACF')) {
            set_transient('frd_activation_notice', 'acf_missing', 60);
        } else {
            set_transient('frd_activation_notice', 'success', 60);
        }

        // Flush rewrite rules for custom post type
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Clear all location-related caches
     * Called when a food-resource post is saved or deleted
     */
    public function clear_location_caches($post_id = null) {
        // If this is a specific post, check if it's a food-resource
        if ($post_id && get_post_type($post_id) !== 'food-resource') {
            return;
        }
        
        // Clear the available languages cache
        delete_transient('frd_available_languages');
        
        // Clear all location query caches
        // Since we use dynamic cache keys based on filters, we need to delete by pattern
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_frd_locations_%' 
             OR option_name LIKE '_transient_timeout_frd_locations_%'"
        );
        
        // Clear WP object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_delete('frd_available_languages', 'frd');
        }
    }
}

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('Food_Resource_Directory', 'activate'));
register_deactivation_hook(__FILE__, array('Food_Resource_Directory', 'deactivate'));

// Initialize the plugin
new Food_Resource_Directory();
