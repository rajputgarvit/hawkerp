<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';
require_once '../classes/CodeGenerator.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$codeGen = new CodeGenerator();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate required fields
    if (empty($input['name']) || empty($input['selling_price']) || empty($input['uom_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name, Selling Price, and UOM are required']);
        exit;
    }
    
    try {
        $productCode = $codeGen->generateProductCode();
        
        $data = [
            'product_code' => $productCode,
            'name' => $input['name'],
            'category_id' => !empty($input['category_id']) ? $input['category_id'] : null,
            'description' => $input['description'] ?? '',
            'uom_id' => $input['uom_id'],
            'product_type' => $input['product_type'] ?? 'Goods',
            'hsn_code' => $input['hsn_code'] ?? '',
            'barcode' => $input['barcode'] ?? '',
            'reorder_level' => $input['reorder_level'] ?? 0,
            'standard_cost' => $input['standard_cost'] ?? 0,
            'selling_price' => $input['selling_price'],
            'tax_rate' => $input['tax_rate'] ?? 0
        ];
        
        $productId = $db->insert('products', $data);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added successfully',
            'product' => [
                'id' => $productId,
                'name' => $data['name'],
                'product_code' => $data['product_code'],
                'selling_price' => $data['selling_price'],
                'tax_rate' => $data['tax_rate']
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding product: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
