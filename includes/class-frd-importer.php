<?php
/**
 * Core Importer for Food Resource Directory
 * Handles the actual import process
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRD_Importer {
    
    /**
     * Parser instance
     */
    private $parser;
    
    /**
     * Validator instance
     */
    private $validator;
    
    /**
     * Import results
     */
    private $results;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once FRD_PLUGIN_DIR . 'includes/class-frd-csv-parser.php';
        require_once FRD_PLUGIN_DIR . 'includes/class-frd-import-validator.php';
        
        $this->parser = new FRD_CSV_Parser();
        $this->validator = new FRD_Import_Validator();
        
        $this->results = array(
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => array()
        );
    }
    
    /**
     * Parse and validate CSV file
     * 
     * @param string $file_path Path to CSV file
     * @return array Parse and validation results
     */
    public function parse_and_validate($file_path) {
        // Parse CSV
        $parse_result = $this->parser->parse($file_path);
        
        if (!$parse_result['success']) {
            return $parse_result;
        }
        
        // Get all organization names for duplicate checking
        $all_titles = array();
        foreach ($parse_result['data'] as $row) {
            if (!empty($row['Organization'])) {
                $all_titles[] = $row['Organization'];
            }
        }
        
        // Validate each row
        $validated_data = array();
        $valid_count = 0;
        $invalid_count = 0;
        
        foreach ($parse_result['data'] as $row) {
            $validation = $this->validator->validate_row($row, $all_titles);
            
            $row['_validation'] = $validation;
            $validated_data[] = $row;
            
            if ($validation['valid']) {
                $valid_count++;
            } else {
                $invalid_count++;
            }
        }
        
        return array(
            'success' => true,
            'data' => $validated_data,
            'headers' => $parse_result['headers'],
            'total_rows' => count($validated_data),
            'valid_rows' => $valid_count,
            'invalid_rows' => $invalid_count
        );
    }
    
    /**
     * Import validated data
     * 
     * @param array $data Validated data
     * @param int $batch_size Number of rows to process per batch
     * @return array Import results
     */
    public function import_data($data, $batch_size = 25) {
        $this->results = array(
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => array(),
            'imported_ids' => array()
        );
        
        $batch_count = 0;
        
        foreach ($data as $row) {
            // Skip invalid rows
            if (!$row['_validation']['valid']) {
                $this->results['skipped']++;
                $this->results['errors'][] = array(
                    'row' => $row['_row_number'],
                    'title' => $row['Organization'],
                    'errors' => $row['_validation']['errors']
                );
                continue;
            }
            
            // Import row
            $result = $this->import_row($row);
            
            if ($result['success']) {
                $this->results['success']++;
                $this->results['imported_ids'][] = $result['post_id'];
            } else {
                $this->results['failed']++;
                $this->results['errors'][] = array(
                    'row' => $row['_row_number'],
                    'title' => $row['Organization'],
                    'errors' => $result['errors']
                );
            }
            
            $batch_count++;
            
            // Allow other processes to run between batches
            if ($batch_count >= $batch_size) {
                $batch_count = 0;
                // This allows the server to breathe
                usleep(100000); // 0.1 second
            }
        }
        
        return $this->results;
    }
    
    /**
     * Import a single row
     * 
     * @param array $row Row data
     * @return array Result with 'success' and 'post_id' or 'errors'
     */
    private function import_row($row) {
        // Get unique title from Organization field
        $title = $this->get_unique_title($row['Organization']);
        
        // Create post
        $post_data = array(
            'post_title' => $title,
            'post_type' => 'food-resource',
            'post_status' => 'publish'
        );
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'errors' => array($post_id->get_error_message())
            );
        }
        
        // Set ACF fields
        $this->set_acf_fields($post_id, $row);
        
        return array(
            'success' => true,
            'post_id' => $post_id
        );
    }
    
    /**
     * Set ACF fields for a post
     * 
     * @param int $post_id Post ID
     * @param array $row Row data
     */
    private function set_acf_fields($post_id, $row) {
        // Check if ACF is available
        if (!function_exists('update_field')) {
            return;
        }
        // Address fields
        update_field('street_address', $row['Street Address'], $post_id);
        update_field('city', $row['City'], $post_id);
        update_field('state', strtoupper(trim($row['State'])), $post_id);
        update_field('zip', $row['ZIP Code'], $post_id);
        update_field('county', $row['County'], $post_id);
        
        // Contact fields
        if (!empty($row['Phone'])) {
            // Clean phone number to digits only
            $phone = preg_replace('/[^0-9]/', '', $row['Phone']);
            // Only save if it's a valid 10-digit number
            if (strlen($phone) === 10) {
                update_field('phone', $phone, $post_id);
            }
        }
        
        if (!empty($row['Website'])) {
            update_field('url', $row['Website'], $post_id);
        }
        
        // Type/Services (comma-separated to array)
        if (!empty($row['Type'])) {
            $services = array_map('trim', explode(',', $row['Type']));
            update_field('services', $services, $post_id);
        }
        
        // Languages (comma-separated to array)
        if (!empty($row['Languages'])) {
            $languages = array_map('trim', explode(',', $row['Languages']));
            update_field('languages', $languages, $post_id);
        }
        
        // Other Hours field
        $hours_other_hours = !empty($row['Other Hours']) ? trim($row['Other Hours']) : '';
        if (!empty($hours_other_hours)) {
            update_field('hours_other_hours', $hours_other_hours, $post_id);
        }
        
        // Only set day/time fields if Other Hours is empty or "Regular hours"
        if (empty($hours_other_hours) || $hours_other_hours === 'Regular hours') {
            // Hours for each day
            $days = array('Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays', 'Sundays');
            $day_names = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            $day_labels = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            
            foreach ($days as $index => $day) {
                $day_lower = $day_names[$index];
                $day_label = $day_labels[$index];
                $open_field = 'Open ' . $day . '?';
                $open_time_field = $day_label . ' Open Time';
                $close_time_field = $day_label . ' Close Time';
                
                $is_open = $this->parse_boolean($row[$open_field]);
                
                update_field('hours_' . $day_lower . '_open', $is_open ? '1' : '0', $post_id);
                
                if ($is_open && !empty($row[$open_time_field]) && !empty($row[$close_time_field])) {
                    // Format time
                    $open_time = $this->format_time($row[$open_time_field]);
                    $close_time = $this->format_time($row[$close_time_field]);
                    
                    update_field('hours_' . $day_lower . '_open_time', $open_time, $post_id);
                    update_field('hours_' . $day_lower . '_close_time', $close_time, $post_id);
                }
            }
        }
        
        // Additional fields
        if (!empty($row['Eligibility Requirements'])) {
            update_field('eligibility', $row['Eligibility Requirements'], $post_id);
        }
        
        if (!empty($row['Notes'])) {
            update_field('notes', $row['Notes'], $post_id);
        }
    }
    
    /**
     * Get unique title (add suffix if duplicate)
     * 
     * @param string $title Original title
     * @return string Unique title
     */
    private function get_unique_title($title) {
        $original_title = $title;
        $suffix = 2;
        
        while (get_page_by_title($title, OBJECT, 'food-resource')) {
            $title = $original_title . ' (' . $suffix . ')';
            $suffix++;
        }
        
        return $title;
    }
    
    /**
     * Parse boolean value
     * 
     * @param string $value
     * @return bool
     */
    private function parse_boolean($value) {
        if (empty($value)) {
            return false;
        }
        
        $value = strtolower(trim($value));
        return in_array($value, array('1', 'true', 'yes', 'y'));
    }
    
    /**
     * Format time to ACF expected format
     * 
     * @param string $time Time string
     * @return string Formatted time
     */
    private function format_time($time) {
        $timestamp = strtotime($time);
        if ($timestamp === false) {
            return $time; // Return as-is if can't parse
        }
        
        return date('g:i a', $timestamp);
    }
    
    /**
     * Get parser instance
     * 
     * @return FRD_CSV_Parser
     */
    public function get_parser() {
        return $this->parser;
    }
    
    /**
     * Get validator instance
     * 
     * @return FRD_Import_Validator
     */
    public function get_validator() {
        return $this->validator;
    }
    
    /**
     * Generate error report CSV
     * 
     * @param array $errors Errors array
     * @return string CSV content
     */
    public function generate_error_report($errors) {
        $csv = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($csv, array('Row Number', 'Title', 'Error Type', 'Details'));
        
        // Write errors
        foreach ($errors as $error) {
            $row_num = isset($error['row']) ? $error['row'] : 'N/A';
            $title = isset($error['title']) ? $error['title'] : 'N/A';
            
            if (!empty($error['errors'])) {
                foreach ($error['errors'] as $err) {
                    fputcsv($csv, array($row_num, $title, 'Error', $err));
                }
            }
            
            if (!empty($error['warnings'])) {
                foreach ($error['warnings'] as $warn) {
                    fputcsv($csv, array($row_num, $title, 'Warning', $warn));
                }
            }
        }
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return $content;
    }
}
