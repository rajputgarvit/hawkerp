<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate required fields
    if (empty($input['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category Name is required']);
        exit;
    }
    
    $user = $auth->getCurrentUser();

    try {
        $data = [
            'company_id' => $user['company_id'],
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'is_active' => 1
        ];
        
        $categoryId = $db->insert('product_categories', $data);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Category added successfully',
            'category' => [
                'id' => $categoryId,
                'name' => $data['name']
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding category: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
