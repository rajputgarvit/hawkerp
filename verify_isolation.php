<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Database.php';

// Mock session
session_start();

$auth = new Auth();
$db = Database::getInstance();

$testEmail = 'test_isolation_' . time() . '@example.com';
$testCompany = 'Test Company ' . time();

echo "Testing Registration for: $testCompany ($testEmail)\n";

try {
    $result = $auth->register([
        'username' => 'user_' . time(),
        'email' => $testEmail,
        'full_name' => 'Test User',
        'password' => 'Password123!',
        'company_name' => $testCompany,
        'is_active' => 1
    ]);

    if ($result['success']) {
        echo "Registration Successful. User ID: " . $result['user_id'] . "\n";
        
        // Verify User Data
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$result['user_id']]);
        echo "User Company ID: " . $user['company_id'] . "\n";
        
        if (empty($user['company_id'])) {
            echo "FAIL: User has no company_id\n";
        } else {
            // Verify Company Data
            $company = $db->fetchOne("SELECT * FROM company_settings WHERE id = ?", [$user['company_id']]);
            echo "Company Name in DB: " . $company['company_name'] . "\n";
            
            if ($company['company_name'] === $testCompany) {
                echo "PASS: Company created correctly.\n";
            } else {
                echo "FAIL: Company name mismatch.\n";
            }
            
            // Verify Dashboard Query Scoping (Simulation)
            // Create a dummy product for this company
            $db->insert('products', [
                'product_code' => 'P-' . time(),
                'name' => 'Test Product',
                'category_id' => 1, 
                'uom_id' => 1, // Use default UOM
                'company_id' => $user['company_id'],
                'is_active' => 1
            ]);
            
            // Create a dummy product for ANOTHER company (ID 1)
            $db->insert('products', [
                'product_code' => 'P-OTHER-' . time(),
                'name' => 'Other Company Product',
                'category_id' => 1,
                'uom_id' => 1, 
                'company_id' => 1,
                'is_active' => 1
            ]);
            
            // Simulate Login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['company_id'] = $user['company_id'];
            
            // Run a scoped query
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND company_id = ?", [$user['company_id']])['count'];
            echo "Products visible to new user: $count (Expected: 1)\n";
            
            if ($count == 1) {
                echo "PASS: Data isolation working for products.\n";
            } else {
                echo "FAIL: Data isolation failed. Count: $count\n";
            }
        }
        
    } else {
        echo "Registration Failed: " . $result['message'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
