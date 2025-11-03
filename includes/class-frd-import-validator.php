<?php
/**
 * Import Validator for Food Resource Directory
 * Validates import data against rules
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRD_Import_Validator {
    
    /**
     * Valid US state codes
     */
    private $valid_states = array(
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
        'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
        'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
        'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
        'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
    );
    
    /**
     * Valid service types
     */
    private $valid_services = array(
        'Food Pantry',
        'Soup Kitchen',
        'Other'
    );
    
    /**
     * Valid hours other hours options
     */
    private $valid_hours_options = array(
        'Regular hours',
        'Appointment only',
        'Hours unknown',
        'Call to confirm'
    );
    
    /**
     * Allowed counties (filterable)
     */
    private $allowed_counties = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Allow counties to be filtered for extensibility
        $this->allowed_counties = apply_filters('frd_import_allowed_counties', array(
            'Macomb County',
            'Oakland County',
            'Wayne County'
        ));
    }
    
    /**
     * Validate a single row of data
     * 
     * @param array $row Row data
     * @param array $all_titles All titles in import (for duplicate checking)
     * @return array Array with 'valid' (bool) and 'errors' (array)
     */
    public function validate_row($row, $all_titles = array()) {
        $result = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array()
        );
        
        // Required fields
        $this->validate_required($row, $result);
        
        // Title uniqueness within import
        $this->validate_title_unique($row, $all_titles, $result);
        
        // Address fields
        $this->validate_state($row, $result);
        $this->validate_zip($row, $result);
        $this->validate_county($row, $result);
        
        // Contact fields
        $this->validate_phone($row, $result);
        $this->validate_website($row, $result);
        
        // Service fields
        $this->validate_services($row, $result);
        
        // Hours Other Hours field
        $this->validate_hours_other_hours($row, $result);
        
        // Hours
        $this->validate_hours($row, $result);
        
        return $result;
    }
    
    /**
     * Validate required fields
     */
    private function validate_required($row, &$result) {
        $required = array('Title', 'Street Address', 'City', 'State', 'ZIP', 'County');
        
        foreach ($required as $field) {
            if (empty($row[$field])) {
                $result['valid'] = false;
                $result['errors'][] = "Missing required field: {$field}";
            }
        }
    }
    
    /**
     * Validate title is unique within import
     */
    private function validate_title_unique($row, $all_titles, &$result) {
        if (empty($row['Title'])) {
            return;
        }
        
        $title = $row['Title'];
        $count = 0;
        
        foreach ($all_titles as $existing_title) {
            if ($existing_title === $title) {
                $count++;
            }
        }
        
        if ($count > 1) {
            $result['warnings'][] = "Duplicate title in import file - will be created with suffix";
        }
        
        // Check if title exists in WordPress
        $existing = get_page_by_title($title, OBJECT, 'food-resource');
        if ($existing) {
            $result['warnings'][] = "Location with this title already exists - will create duplicate with suffix";
        }
    }
    
    /**
     * Validate state code
     */
    private function validate_state($row, &$result) {
        if (empty($row['State'])) {
            return;
        }
        
        $state = strtoupper(trim($row['State']));
        
        if (!in_array($state, $this->valid_states)) {
            $result['valid'] = false;
            $result['errors'][] = "Invalid state code: {$row['State']} (must be 2-letter US state code, e.g., MI)";
        }
    }
    
    /**
     * Validate ZIP code
     */
    private function validate_zip($row, &$result) {
        if (empty($row['ZIP'])) {
            return;
        }
        
        $zip = trim($row['ZIP']);
        
        // Must be exactly 5 digits
        if (!preg_match('/^\d{5}$/', $zip)) {
            $result['valid'] = false;
            $result['errors'][] = "Invalid ZIP code: {$zip} (must be exactly 5 digits)";
        }
    }
    
    /**
     * Validate county
     */
    private function validate_county($row, &$result) {
        if (empty($row['County'])) {
            return;
        }
        
        $county = trim($row['County']);
        
        if (!in_array($county, $this->allowed_counties)) {
            $result['valid'] = false;
            $result['errors'][] = "Invalid county: {$county} (must be one of: " . implode(', ', $this->allowed_counties) . ")";
        }
    }
    
    /**
     * Validate phone number
     */
    private function validate_phone($row, &$result) {
        if (empty($row['Phone'])) {
            return; // Optional field
        }
        
        $phone = trim($row['Phone']);
        
        // Remove any non-digit characters for validation
        $digits_only = preg_replace('/[^0-9]/', '', $phone);
        
        // Must be exactly 10 digits
        if (strlen($digits_only) !== 10) {
            $result['valid'] = false;
            $result['errors'][] = "Invalid phone number: {$phone} (must be 10 digits with no special characters, e.g., 3135550100)";
        }
        
        // Check if original had non-digit characters
        if ($phone !== $digits_only) {
            $result['warnings'][] = "Phone number contains non-numeric characters - will be cleaned to: {$digits_only}";
        }
    }
    
    /**
     * Validate website URL
     */
    private function validate_website($row, &$result) {
        if (empty($row['Website'])) {
            return; // Optional field
        }
        
        $url = trim($row['Website']);
        
        // Check if it's a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $result['valid'] = false;
            $result['errors'][] = "Invalid website URL: {$url}";
            return;
        }
        
        // Check for dangerous protocols
        $parsed = parse_url($url);
        if (isset($parsed['scheme'])) {
            $scheme = strtolower($parsed['scheme']);
            if (!in_array($scheme, array('http', 'https'))) {
                $result['valid'] = false;
                $result['errors'][] = "Invalid URL protocol: {$url} (must be http:// or https://)";
            }
        }
    }
    
    /**
     * Validate services
     */
    private function validate_services($row, &$result) {
        if (empty($row['Services'])) {
            $result['valid'] = false;
            $result['errors'][] = "Services field is required";
            return;
        }
        
        $services = array_map('trim', explode(',', $row['Services']));
        $invalid_services = array();
        
        foreach ($services as $service) {
            if (!in_array($service, $this->valid_services)) {
                $invalid_services[] = $service;
            }
        }
        
        if (!empty($invalid_services)) {
            $result['valid'] = false;
            $result['errors'][] = "Invalid service(s): " . implode(', ', $invalid_services) . " (valid: " . implode(', ', $this->valid_services) . ")";
        }
    }
    
    /**
     * Validate Hours Other Hours field
     */
    private function validate_hours_other_hours($row, &$result) {
        if (empty($row['Hours Other Hours'])) {
            return; // Optional field
        }
        
        $value = trim($row['Hours Other Hours']);
        
        if (!in_array($value, $this->valid_hours_options)) {
            $result['valid'] = false;
            $result['errors'][] = "Invalid 'Hours Other Hours' value: {$value} (must be one of: " . implode(', ', $this->valid_hours_options) . ")";
        }
        
        // If not "Regular hours", warn that day/time fields will be ignored
        if ($value !== 'Regular hours' && $value !== '') {
            $result['warnings'][] = "Hours Other Hours is set to '{$value}' - day/time fields will be ignored";
        }
    }
    
    /**
     * Validate hours fields
     */
    private function validate_hours($row, &$result) {
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        
        foreach ($days as $day) {
            $open_field = $day . ' Open';
            $open_time_field = $day . ' Open Time';
            $close_time_field = $day . ' Close Time';
            
            if (empty($row[$open_field])) {
                continue;
            }
            
            $is_open = $this->parse_boolean($row[$open_field]);
            
            if ($is_open) {
                // If open, must have times
                if (empty($row[$open_time_field]) || empty($row[$close_time_field])) {
                    $result['valid'] = false;
                    $result['errors'][] = "{$day}: If 'Open' is TRUE, opening and closing times are required";
                }
                
                // Validate time format
                if (!empty($row[$open_time_field])) {
                    $this->validate_time_format($row[$open_time_field], "{$day} Open Time", $result);
                }
                
                if (!empty($row[$close_time_field])) {
                    $this->validate_time_format($row[$close_time_field], "{$day} Close Time", $result);
                }
            }
        }
    }
    
    /**
     * Validate time format
     */
    private function validate_time_format($time, $field_name, &$result) {
        $time = trim($time);
        
        // Try to parse the time
        $parsed = strtotime($time);
        
        if ($parsed === false) {
            $result['valid'] = false;
            $result['errors'][] = "{$field_name}: Invalid time format '{$time}' (examples: 9:00 AM, 9am, 09:00)";
        }
    }
    
    /**
     * Parse boolean value from string
     * 
     * @param string $value
     * @return bool
     */
    private function parse_boolean($value) {
        $value = strtolower(trim($value));
        return in_array($value, array('1', 'true', 'yes', 'y'));
    }
    
    /**
     * Get valid services
     * 
     * @return array
     */
    public function get_valid_services() {
        return $this->valid_services;
    }
    
    /**
     * Get allowed counties
     * 
     * @return array
     */
    public function get_allowed_counties() {
        return $this->allowed_counties;
    }
}
