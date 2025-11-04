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
        'County',
        'Organization',
        'Type',
        'Street Address',
        'City',
        'State',
        'ZIP Code'
    );
    
    /**
     * All expected column headers in order
     */
    private $expected_columns = array(
        'County',
        'Organization',
        'Type',
        'Street Address',
        'City',
        'State',
        'ZIP Code',
        'Phone',
        'Website',
        'Languages',
        'Eligibility Requirements',
        'Notes'
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
            'Wayne County',                 // County
            'Community Food Bank',          // Organization
            'Food Pantry, Soup Kitchen',    // Type
            '123 Main Street',              // Street Address
            'Detroit',                      // City
            'MI',                           // State
            '48201',                        // ZIP Code
            '3135550100',                   // Phone
            'https://example.com',          // Website
            'English, Spanish',             // Languages
            'Must show ID and proof of residency', // Eligibility Requirements
            'Please call ahead for special dietary needs' // Notes
        ));
        
        // Write example row 2
        fputcsv($csv, array(
            'Macomb County',                // County
            'Hope Center Pantry',           // Organization
            'Food Pantry',                  // Type
            '456 Oak Avenue',               // Street Address
            'Warren',                       // City
            'MI',                           // State
            '48089',                        // ZIP Code
            '5865551234',                   // Phone
            '',                             // Website
            'English, Arabic',              // Languages
            '',                             // Eligibility Requirements
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
