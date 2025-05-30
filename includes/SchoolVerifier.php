<?php
// includes/SchoolVerifier.php - DUO BRIN Database Verificatie
class SchoolVerifier {
    private $schools_data = null;
    private $csv_file = 'checks/hoofdvestigingenvo1mei.csv';
    
    public function __construct() {
        $this->loadSchoolsData();
    }
    
    /**
     * Laad DUO CSV data
     */
    private function loadSchoolsData() {
        if (!file_exists($this->csv_file)) {
            error_log("DUO CSV file not found: " . $this->csv_file);
            return;
        }
        
        $csv_content = file_get_contents($this->csv_file);
        if ($csv_content === false) {
            error_log("Could not read DUO CSV file");
            return;
        }
        
        // Parse CSV with semicolon delimiter (DUO standard)
        $lines = str_getcsv($csv_content, "\n");
        $header = str_getcsv(array_shift($lines), ';');
        
        $this->schools_data = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line, ';');
            if (count($row) !== count($header)) continue;
            
            $school = array_combine($header, $row);
            
            // Index by BRIN code for fast lookup
            $brin = $school['INSTELLINGSCODE'] ?? '';
            if ($brin) {
                $this->schools_data[strtoupper($brin)] = $school;
            }
        }
        
        error_log("Loaded " . count($this->schools_data) . " schools from DUO database");
    }
    
    /**
     * Verificeer school op basis van BRIN code
     */
    public function verifyByBrin($brin_code) {
        if (!$this->schools_data) {
            return ['success' => false, 'error' => 'School database not available'];
        }
        
        $brin = strtoupper(trim($brin_code));
        
        if (!isset($this->schools_data[$brin])) {
            return ['success' => false, 'error' => 'BRIN code not found in DUO database'];
        }
        
        $school = $this->schools_data[$brin];
        
        return [
            'success' => true,
            'school' => [
                'brin' => $school['INSTELLINGSCODE'],
                'name' => $school['INSTELLINGSNAAM'],
                'address' => trim($school['STRAATNAAM'] . ' ' . $school['HUISNUMMER-TOEVOEGING']),
                'postcode' => $school['POSTCODE'],
                'city' => $school['PLAATSNAAM'],
                'phone' => $school['TELEFOONNUMMER'],
                'website' => $school['INTERNETADRES'],
                'province' => $school['PROVINCIE'],
                'denomination' => $school['DENOMINATIE'],
                'education_type' => $school['ONDERWIJSSTRUCTUUR']
            ]
        ];
    }
    
    /**
     * Zoek scholen op naam (fuzzy search)
     */
    public function searchByName($search_name, $limit = 10) {
        if (!$this->schools_data) {
            return ['success' => false, 'error' => 'School database not available'];
        }
        
        $search_lower = strtolower(trim($search_name));
        $results = [];
        
        foreach ($this->schools_data as $brin => $school) {
            $school_name = strtolower($school['INSTELLINGSNAAM']);
            
            // Exact match
            if (strpos($school_name, $search_lower) !== false) {
                $results[] = [
                    'brin' => $school['INSTELLINGSCODE'],
                    'name' => $school['INSTELLINGSNAAM'],
                    'city' => $school['PLAATSNAAM'],
                    'website' => $school['INTERNETADRES'],
                    'match_score' => $this->calculateMatchScore($search_lower, $school_name)
                ];
            }
            
            if (count($results) >= $limit) break;
        }
        
        // Sort by match score
        usort($results, function($a, $b) {
            return $b['match_score'] - $a['match_score'];
        });
        
        return ['success' => true, 'schools' => $results];
    }
    
    /**
     * Verificeer email domain tegen school website
     */
    public function verifyEmailDomain($email, $brin_code) {
        $verification = $this->verifyByBrin($brin_code);
        if (!$verification['success']) {
            return $verification;
        }
        
        $email_domain = strtolower(substr(strrchr($email, '@'), 1));
        $school_website = strtolower($verification['school']['website']);
        
        // Remove www. prefix
        $school_domain = preg_replace('/^www\./', '', $school_website);
        
        if ($email_domain === $school_domain) {
            return [
                'success' => true,
                'match' => 'exact',
                'message' => 'Email domain matches school website exactly'
            ];
        }
        
        // Check if domains are related (subdomain, etc.)
        if (strpos($email_domain, $school_domain) !== false || strpos($school_domain, $email_domain) !== false) {
            return [
                'success' => true,
                'match' => 'partial',
                'message' => 'Email domain appears related to school website',
                'email_domain' => $email_domain,
                'school_domain' => $school_domain
            ];
        }
        
        return [
            'success' => false,
            'match' => 'none',
            'message' => 'Email domain does not match school website',
            'email_domain' => $email_domain,
            'school_domain' => $school_domain
        ];
    }
    
    /**
     * Alle verificaties in één keer
     */
    public function fullVerification($brin_code, $name, $email, $address = null) {
        $result = [
            'brin_valid' => false,
            'name_match' => false,
            'email_domain_match' => false,
            'address_match' => false,
            'confidence_score' => 0,
            'school_data' => null,
            'warnings' => []
        ];
        
        // BRIN verificatie
        $brin_check = $this->verifyByBrin($brin_code);
        if (!$brin_check['success']) {
            $result['warnings'][] = $brin_check['error'];
            return $result;
        }
        
        $school = $brin_check['school'];
        $result['brin_valid'] = true;
        $result['school_data'] = $school;
        $result['confidence_score'] += 30; // BRIN valid = 30 points
        
        // Naam verificatie (fuzzy match)
        $name_similarity = $this->calculateMatchScore(strtolower($name), strtolower($school['name']));
        if ($name_similarity > 70) {
            $result['name_match'] = true;
            $result['confidence_score'] += 25; // Name match = 25 points
        } else {
            $result['warnings'][] = "School name does not closely match DUO database ({$name_similarity}% similarity)";
        }
        
        // Email domain verificatie
        $email_check = $this->verifyEmailDomain($email, $brin_code);
        if ($email_check['success']) {
            $result['email_domain_match'] = true;
            $points = $email_check['match'] === 'exact' ? 35 : 20;
            $result['confidence_score'] += $points;
        } else {
            $result['warnings'][] = $email_check['message'];
        }
        
        // Adres verificatie (optioneel)
        if ($address) {
            $school_address = $school['address'] . ', ' . $school['postcode'] . ' ' . $school['city'];
            $address_similarity = $this->calculateMatchScore(strtolower($address), strtolower($school_address));
            if ($address_similarity > 60) {
                $result['address_match'] = true;
                $result['confidence_score'] += 10; // Address match = 10 points
            }
        }
        
        return $result;
    }
    
    /**
     * Calculate similarity score between two strings
     */
    private function calculateMatchScore($str1, $str2) {
        similar_text($str1, $str2, $percent);
        return round($percent, 1);
    }
    
    /**
     * Get statistics about loaded data
     */
    public function getStats() {
        if (!$this->schools_data) {
            return ['total_schools' => 0, 'last_updated' => null];
        }
        
        return [
            'total_schools' => count($this->schools_data),
            'last_updated' => filemtime($this->csv_file) ? date('Y-m-d H:i:s', filemtime($this->csv_file)) : null,
            'provinces' => array_unique(array_column($this->schools_data, 'PROVINCIE'))
        ];
    }
}
?>