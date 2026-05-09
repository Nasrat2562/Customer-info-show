<?php
class Validator {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function isValid($email, $phone) {
        // Requirement #3: Filter by email and phone format
        $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
        $phoneValid = preg_match('/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/', $phone) || strlen($phone) >= 10;
        return $emailValid && $phoneValid;
    }

    public function isDuplicate($email) {
        $stmt = $this->db->prepare("SELECT id FROM valid_customers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ? true : false;
    }
}