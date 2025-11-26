<?php
require_once 'Database.php';

class ReferenceData {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getStates() {
        try {
            return $this->db->fetchAll("SELECT * FROM indian_states ORDER BY state_name");
        } catch (Exception $e) {
            // Fallback or empty array if table doesn't exist yet
            return [];
        }
    }

    public function getCountries() {
        try {
            return $this->db->fetchAll("SELECT * FROM countries ORDER BY country_name");
        } catch (Exception $e) {
            return [];
        }
    }
}
