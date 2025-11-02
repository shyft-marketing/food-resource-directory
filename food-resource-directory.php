<?php
/**
    * Plugin Name: Food Resource Directory
    * Plugin URI: https://github.com/shyft-marketing/food-resource-directory
    * Description: Interactive map and filterable directory of food pantries and soup kitchens with ACF integration
    * Version: 1.0.95
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
define('FRD_VERSION', '1.0.6');
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
        register_setting('frd_settings', 'frd_mapbox_secret_token', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));

        add_settings_section(
            'frd_mapbox_section',
            'Mapbox Configuration',
            array($this, 'render_mapbox_section_info'),
            'frd_settings'
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
        echo '<p>Configure your Mapbox API token for server-side operations (geocoding). The default Public Token will continue to be used for displaying the map in the browser.</p>';
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
            <strong>Optional:</strong> Enter your Mapbox Secret Token for server-side geocoding operations.<br>
            Leave blank to use the default Public Token for all operations.<br>
            <em>Note: Secret tokens can only be used server-side; the Public Token will always be used for the client-side map display.</em><br>
            Get your token from <a href="https://account.mapbox.com/access-tokens/" target="_blank">Mapbox Account</a>.
        </p>
        <?php
    }

    /**
     * Get Mapbox public token for client-side use
     * Always returns the public token (secret tokens cannot be used in browser)
     */
    private function get_mapbox_public_token() {
        // Default Public Token (always used for client-side JavaScript)
        return 'pk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0bDlrejBhMXQybnB2Zng5aW85M3UifQ.dsT7ITwivyDeR0j07AZkgA';
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
                'defaultZoom' => 9
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

        error_log('FRD: ajax_get_locations called');

        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $user_location = isset($_POST['user_location']) ? $_POST['user_location'] : null;

        error_log('FRD: Filters: ' . print_r($filters, true));
        error_log('FRD: User location: ' . print_r($user_location, true));

        $args = array(
            'post_type' => 'food-resource',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        );

        error_log('FRD: Query args: ' . print_r($args, true));
        
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
        
        // Filter by days of week
        if (!empty($filters['days']) && is_array($filters['days'])) {
            $day_query = array('relation' => 'OR');
            foreach ($filters['days'] as $day) {
                $day_lower = strtolower($day);
                $day_query[] = array(
                    'key' => 'hours_' . $day_lower . '_open',
                    'value' => '1',
                    'compare' => '='
                );
            }
            $meta_query[] = $day_query;
        }
        
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }
        
        $query = new WP_Query($args);
        $locations = array();

        error_log('FRD: Query found ' . $query->found_posts . ' posts');

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                error_log('FRD: Processing post ID: ' . $post_id . ' - ' . get_the_title());

                $location = $this->get_location_data($post_id);

                error_log('FRD: Location data: ' . print_r($location, true));

                // Calculate distance if user location is provided
                if ($user_location && isset($user_location['lat']) && isset($user_location['lng'])) {
                    $location['distance'] = $this->calculate_distance(
                        $user_location['lat'],
                        $user_location['lng'],
                        $location['latitude'],
                        $location['longitude']
                    );
                }

                $locations[] = $location;
            }
            wp_reset_postdata();
        } else {
            error_log('FRD: No posts found!');
        }

        // Sort by distance if user location is provided
        if ($user_location) {
            usort($locations, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
        }

        error_log('FRD: Returning ' . count($locations) . ' locations');

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
        error_log('FRD: Getting location data for post ' . $post_id);

        $street_address = get_field('street_address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $zip = get_field('zip', $post_id);

        error_log('FRD: ACF fields - street: ' . $street_address . ', city: ' . $city . ', state: ' . $state . ', zip: ' . $zip);

        // Build full address
        $full_address = trim($street_address . ', ' . $city . ', ' . $state . ' ' . $zip);

        error_log('FRD: Full address: ' . $full_address);

        // Get coordinates (we'll geocode these on first load)
        $coordinates = get_post_meta($post_id, '_frd_coordinates', true);
        if (empty($coordinates)) {
            error_log('FRD: No cached coordinates, geocoding...');
            $coordinates = $this->geocode_address($full_address);
            if ($coordinates) {
                error_log('FRD: Geocoded successfully: ' . print_r($coordinates, true));
                update_post_meta($post_id, '_frd_coordinates', $coordinates);
            } else {
                error_log('FRD: Geocoding failed for address: ' . $full_address);
            }
        } else {
            error_log('FRD: Using cached coordinates: ' . print_r($coordinates, true));
        }
        
        // Get special hours note (for "Appointment Only", etc.)
        $hours_other_hours = get_field('hours_other_hours', $post_id);

        // Get hours
        $hours = $this->format_hours($post_id);

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
        error_log('FRD: Raw phone from ACF: ' . var_export($phone_raw, true) . ' (type: ' . gettype($phone_raw) . ')');

        $phone_display = $this->format_phone_display($phone_raw);
        $phone_link = $this->format_phone_link($phone_raw);

        error_log('FRD: Formatted phone display: ' . $phone_display);
        error_log('FRD: Formatted phone link: ' . $phone_link);

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
            'hours' => $hours,
            'hours_other_hours' => $hours_other_hours,
            'hours_text' => $this->get_hours_text($post_id, $hours_other_hours),
            'latitude' => isset($coordinates['lat']) ? $coordinates['lat'] : null,
            'longitude' => isset($coordinates['lng']) ? $coordinates['lng'] : null,
            'distance' => null
        );
    }
    
    /**
     * Format hours for display
     */
    private function format_hours($post_id) {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $hours = array();
        
        foreach ($days as $day) {
            $is_open = get_field('hours_' . $day . '_open', $post_id);
            if ($is_open) {
                $open_time = get_field('hours_' . $day . '_open_time', $post_id);
                $close_time = get_field('hours_' . $day . '_close_time', $post_id);
                
                $hours[$day] = array(
                    'open' => true,
                    'open_time' => $open_time,
                    'close_time' => $close_time
                );
            } else {
                $hours[$day] = array('open' => false);
            }
        }
        
        return $hours;
    }
    
    /**
     * Get hours as readable text
     */
    private function get_hours_text($post_id, $hours_other_hours = null) {
        // If there's a special hours note (and it's not "Regular hours"), return that instead
        if (!empty($hours_other_hours) && $hours_other_hours !== 'Regular hours') {
            return $hours_other_hours;
        }

        $days = array(
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
        );

        $hours_text = array();

        foreach ($days as $key => $label) {
            $is_open = get_field('hours_' . $key . '_open', $post_id);
            if ($is_open) {
                $open_time = get_field('hours_' . $key . '_open_time', $post_id);
                $close_time = get_field('hours_' . $key . '_close_time', $post_id);

                $hours_text[] = $label . ': ' . $open_time . ' - ' . $close_time;
            }
        }

        return !empty($hours_text) ? implode('<br>', $hours_text) : 'Hours not available';
    }
    
    /**
     * Geocode an address using Mapbox
     */
    private function geocode_address($address) {
        error_log('FRD: Geocoding address: ' . $address);

        $mapbox_token = $this->get_mapbox_server_token();
        $url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($address) . '.json?access_token=' . $mapbox_token . '&country=US&proximity=-83.0458,42.5803';

        error_log('FRD: Geocoding URL: ' . $url);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            error_log('FRD: Geocoding WP Error: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        error_log('FRD: Geocoding response body: ' . $body);

        $data = json_decode($body, true);

        if (isset($data['features'][0]['center'])) {
            $coords = array(
                'lng' => $data['features'][0]['center'][0],
                'lat' => $data['features'][0]['center'][1]
            );
            error_log('FRD: Geocoding successful: ' . print_r($coords, true));
            return $coords;
        }

        error_log('FRD: Geocoding failed - no features found in response');
        return null;
    }
    
    /**
     * AJAX handler for geocoding user input
     */
    public function ajax_geocode() {
        check_ajax_referer('frd_nonce', 'nonce');
        
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
}

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('Food_Resource_Directory', 'activate'));
register_deactivation_hook(__FILE__, array('Food_Resource_Directory', 'deactivate'));

// Initialize the plugin
new Food_Resource_Directory();
