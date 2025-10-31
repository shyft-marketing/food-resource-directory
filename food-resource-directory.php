<?php
/**
    * Plugin Name: Food Resource Directory
    * Plugin URI: https://github.com/shyft-marketing/food-resource-directory
    * Description: Interactive map and filterable directory of food pantries and soup kitchens with ACF integration
    * Version: 1.0.1
    * Author: SHYFT
    * Author URI: https://shyft.wtf
    * License: GPL v2 or later
    * GitHub Plugin URI: shyft-marketing/food-resource-directory
    * Primary Branch: main
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FRD_VERSION', '1.0.0');
define('FRD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FRD_PLUGIN_URL', plugin_dir_url(__FILE__));

class Food_Resource_Directory {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('food_resource_directory', array($this, 'render_directory'));
        add_action('wp_ajax_frd_get_locations', array($this, 'ajax_get_locations'));
        add_action('wp_ajax_nopriv_frd_get_locations', array($this, 'ajax_get_locations'));
        add_action('wp_ajax_frd_geocode', array($this, 'ajax_geocode'));
        add_action('wp_ajax_nopriv_frd_geocode', array($this, 'ajax_geocode'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'food_resource_directory')) {
            
            // Mapbox GL JS
            wp_enqueue_style('mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css', array(), '2.15.0');
            wp_enqueue_script('mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js', array(), '2.15.0', true);
            
            // Mapbox GL Geocoder
            wp_enqueue_style('mapbox-geocoder', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css', array(), '5.0.0');
            wp_enqueue_script('mapbox-geocoder', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js', array('mapbox-gl'), '5.0.0', true);
            
            // Plugin styles
            wp_enqueue_style('frd-styles', FRD_PLUGIN_URL . 'assets/css/style.css', array(), FRD_VERSION);
            
            // Plugin script
            wp_enqueue_script('frd-script', FRD_PLUGIN_URL . 'assets/js/script.js', array('jquery', 'mapbox-gl', 'mapbox-geocoder'), FRD_VERSION, true);
            
            // Localize script with AJAX URL and settings
            wp_localize_script('frd-script', 'frdData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('frd_nonce'),
                'mapboxToken' => 'pk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0bDlrejBhMXQybnB2Zng5aW85M3UifQ.dsT7ITwivyDeR0j07AZkgA',
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
        
        return array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'street_address' => $street_address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'full_address' => $full_address,
            'phone' => get_field('phone', $post_id),
            'website' => get_field('url', $post_id),
            'services' => $services,
            'languages' => $languages,
            'eligibility' => get_field('eligibility', $post_id),
            'notes' => get_field('notes', $post_id),
            'county' => get_field('county', $post_id),
            'hours' => $hours,
            'hours_text' => $this->get_hours_text($post_id),
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
    private function get_hours_text($post_id) {
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

        $mapbox_token = 'pk.eyJ1IjoibWFjb21iZGVmZW5kZXJzIiwiYSI6ImNtaGU0bDlrejBhMXQybnB2Zng5aW85M3UifQ.dsT7ITwivyDeR0j07AZkgA';
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
}

// Initialize the plugin
new Food_Resource_Directory();
