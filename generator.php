<?php
/**
 * Data Generator for 1 Million Customer Records
 * Fulfills Requirement #1 (Source Data Generation)
 */

// 1. Setup Environment
set_time_limit(0); // Allow script to run as long as needed
$filePath = 'data/1M-customers.txt';

if (!is_dir('data')) {
    mkdir('data', 0777, true);
}

// 2. Sample Data Pool
$firstNames = ["Robert", "Janny", "Deborah", "Michael", "Sarah", "David", "Emma", "James"];
$lastNames  = ["Branch", "Gaines", "Yankosky", "Smith", "Johnson", "Williams", "Brown"];
$cities     = ["Haines city", "Augusta", "Milford", "Chicago", "Austin", "Seattle"];
$states     = ["FL", "GA", "IN", "IL", "TX", "WA"];

$file = fopen($filePath, 'w');

echo "Generating 1,000,000 rows in $filePath...\n";

for ($i = 0; $i < 1000000; $i++) {
    $fName = $firstNames[array_rand($firstNames)];
    $lName = $lastNames[array_rand($lastNames)];
    $city  = $cities[array_rand($cities)];
    $state = $states[array_rand($states)];
    $zip   = rand(10000, 99999);
    
    // Requirement #3: Valid Phone Format (555-555-5555)
    $phone = rand(200, 999) . "-" . rand(200, 999) . "-" . rand(1000, 9999);
    
    // Requirement #3 & #4: Valid Email vs Invalid Email
    // We intentionally make 1% invalid to test the invalid_customers table logic
    if ($i % 100 === 0) {
        $email = "invalid-email-at-domain.com"; // Missing @
    } else {
        $email = strtolower($fName . "." . $lName . $i . "@example.com");
    }
    
    $ip = rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255);

    // 3. Construct Row Array
    // Index mapping: 0:First, 1:Last, 2:City, 3:State, 4:Zip, 5:Phone, 6:Email, 7:IP
    $rowData = [$fName, $lName, $city, $state, $zip, $phone, $email, $ip];

    // 4. Write to file using Tab delimiter (\t)
    fputcsv($file, $rowData, "\t");

    // Output progress every 100k rows
    if ($i % 100000 === 0 && $i > 0) {
        echo "Progress: " . ($i / 10000) . "% complete...\n";
    }
}

fclose($file);
echo "Success! 1,000,000 rows generated.\n";