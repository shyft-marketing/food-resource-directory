<?php
/**
 * CSV Parser for Food Resource Directory
 * Handles parsing and basic validation of CSV import files
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRD_CSV_Parser {
    
    /**
     * Required column headers
     */
    private $required_columns = array(
        'Title',
        'Street Address',
        'City',
        'State',
        'ZIP',
        'County'
    );
    
    /**
     * All expected column headers in order
     */
    private $expected_columns = array(
        'Title',
        'Street Address',
        'City',
        'State',
        'ZIP',
        'County',
        'Phone',
        'Website',
        'Services',
        'Languages',
        'Monday Open',
        'Monday Open Time',
        'Monday Close Time',
        'Tuesday Open',
        'Tuesday Open Time',
        'Tuesday Close Time',
        'Wednesday Open',
        'Wednesday Open Time',
        'Wednesday Close Time',
        'Thursday Open',
        'Thursday Open Time',
        'Thursday Close Time',
        'Friday Open',
        'Friday Open Time',
        'Friday Close Time',
        'Saturday Open',
        'Saturday Open Time',
        'Saturday Close Time',
        'Sunday Open',
        'Sunday Open Time',
        'Sunday Close Time',
        'Hours Note',
        'Eligibility Requirements',
        'Additional Notes'
    );
    
    /**
     * Parse CSV file and return data
     * 
     * @param string $file_path Path to CSV file
     * @return array Array with 'success', 'data', 'errors', 'headers'
     */
    public function parse($file_path) {
        $result = array(
            'success' => false,
            'data' => array(),
            'errors' => array(),
            'headers' => array()
        );
        
        // Check if file exists
        if (!file_exists($file_path)) {
            $result['errors'][] = 'File not found.';
            return $result;
        }
        
        // Check file size (max 5MB)
        $file_size = filesize($file_path);
        if ($file_size > 5242880) { // 5MB in bytes
            $result['errors'][] = 'File too large. Maximum file size is 5MB.';
            return $result;
        }
        
        // Open file
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            $result['errors'][] = 'Could not open file for reading.';
            return $result;
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $result['errors'][] = 'Could not read CSV headers.';
            fclose($handle);
            return $result;
        }
        
        // Trim headers
        $headers = array_map('trim', $headers);
        $result['headers'] = $headers;
        
        // Validate headers
        $missing_columns = array_diff($this->required_columns, $headers);
        if (!empty($missing_columns)) {
            $result['errors'][] = 'Missing required columns: ' . implode(', ', $missing_columns);
            fclose($handle);
            return $result;
        }
        
        // Create column map
        $column_map = array();
        foreach ($headers as $index => $header) {
            $column_map[$header] = $index;
        }
        
        // Read data rows
        $row_number = 1; // Start at 1 (header is 0)
        $data = array();
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Map row data to column names
            $row_data = array();
            foreach ($headers as $index => $header) {
                $row_data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }
            
            $row_data['_row_number'] = $row_number;
            $data[] = $row_data;
        }
        
        fclose($handle);
        
        $result['success'] = true;
        $result['data'] = $data;
        
        return $result;
    }
    
    /**
     * Generate CSV template file
     * 
     * @return string CSV content
     */
    public function generate_template() {
        $csv = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($csv, $this->expected_columns);
        
        // Write example row 1
        fputcsv($csv, array(
            'Community Food Bank',           // Title
            '123 Main Street',               // Street Address
            'Detroit',                       // City
            'MI',                           // State
            '48201',                        // ZIP
            'Wayne County',                 // County
            '3135550100',                   // Phone
            'https://example.com',          // Website
            'Food Pantry, Soup Kitchen',    // Services
            'English, Spanish',             // Languages
            'TRUE',                         // Monday Open
            '9:00 AM',                      // Monday Open Time
            '5:00 PM',                      // Monday Close Time
            'TRUE',                         // Tuesday Open
            '9:00 AM',                      // Tuesday Open Time
            '5:00 PM',                      // Tuesday Close Time
            'TRUE',                         // Wednesday Open
            '9:00 AM',                      // Wednesday Open Time
            '5:00 PM',                      // Wednesday Close Time
            'TRUE',                         // Thursday Open
            '9:00 AM',                      // Thursday Open Time
            '5:00 PM',                      // Thursday Close Time
            'TRUE',                         // Friday Open
            '9:00 AM',                      // Friday Open Time
            '5:00 PM',                      // Friday Close Time
            'FALSE',                        // Saturday Open
            '',                             // Saturday Open Time
            '',                             // Saturday Close Time
            'FALSE',                        // Sunday Open
            '',                             // Sunday Open Time
            '',                             // Sunday Close Time
            '',                             // Hours Note
            'Must show ID and proof of residency', // Eligibility
            'Please call ahead for special dietary needs' // Notes
        ));
        
        // Write example row 2
        fputcsv($csv, array(
            'Hope Center Pantry',           // Title
            '456 Oak Avenue',               // Street Address
            'Warren',                       // City
            'MI',                           // State
            '48089',                        // ZIP
            'Macomb County',                // County
            '5865551234',                   // Phone
            '',                             // Website
            'Food Pantry',                  // Services
            'English, Arabic',              // Languages
            'FALSE',                        // Monday Open
            '',                             // Monday Open Time
            '',                             // Monday Close Time
            'TRUE',                         // Tuesday Open
            '10:00 AM',                     // Tuesday Open Time
            '2:00 PM',                      // Tuesday Close Time
            'FALSE',                        // Wednesday Open
            '',                             // Wednesday Open Time
            '',                             // Wednesday Close Time
            'TRUE',                         // Thursday Open
            '10:00 AM',                     // Thursday Open Time
            '2:00 PM',                      // Thursday Close Time
            'FALSE',                        // Friday Open
            '',                             // Friday Open Time
            '',                             // Friday Close Time
            'TRUE',                         // Saturday Open
            '9:00 AM',                      // Saturday Open Time
            '12:00 PM',                     // Saturday Close Time
            'FALSE',                        // Sunday Open
            '',                             // Sunday Open Time
            '',                             // Sunday Close Time
            '',                             // Hours Note
            '',                             // Eligibility
            'Limited quantities available'  // Notes
        ));
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return $content;
    }
    
    /**
     * Get expected columns
     * 
     * @return array
     */
    public function get_expected_columns() {
        return $this->expected_columns;
    }
    
    /**
     * Get required columns
     * 
     * @return array
     */
    public function get_required_columns() {
        return $this->required_columns;
    }
}
