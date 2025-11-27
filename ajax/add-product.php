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
    
    $user = $auth->getCurrentUser();

    try {
        $productCode = $codeGen->generateProductCode();
        
        $data = [
            'company_id' => $user['company_id'],
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
            'tax_rate' => $input['tax_rate'] ?? 0,
            'has_serial_number' => !empty($input['has_serial_number']) ? 1 : 0,
            'has_warranty' => !empty($input['has_warranty']) ? 1 : 0,
            'has_expiry_date' => !empty($input['has_expiry_date']) ? 1 : 0
        ];
        
        $productId = $db->insert('products', $data);
        
        // Handle Default Warehouse (IN_HOUSE) and Stock (100)
        $warehouseName = 'IN_HOUSE';
        $warehouseCode = 'IN_HOUSE';
        
        // Check if warehouse exists
        $warehouse = $db->fetchOne("SELECT id FROM warehouses WHERE company_id = ? AND code = ?", [$user['company_id'], $warehouseCode]);
        
        if ($warehouse) {
            $warehouseId = $warehouse['id'];
        } else {
            // Create warehouse
            $warehouseId = $db->insert('warehouses', [
                'company_id' => $user['company_id'],
                'name' => $warehouseName,
                'code' => $warehouseCode,
                'is_active' => 1
            ]);
        }
        
        // Add initial stock
        if ($warehouseId) {
            $db->insert('stock_balance', [
                'company_id' => $user['company_id'],
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => 100,
                'reserved_quantity' => 0
            ]);
            
            // Also log this initial stock transaction
            $db->insert('stock_transactions', [
                'company_id' => $user['company_id'],
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'IN',
                'quantity' => 100,
                'reference_type' => 'Opening Stock',
                'reference_id' => 0,
                'notes' => 'Initial stock from Quick Add'
            ]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added successfully',
            'product' => [
                'id' => $productId,
                'name' => $data['name'],
                'product_code' => $data['product_code'],
                'selling_price' => $data['selling_price'],
                'tax_rate' => $data['tax_rate'],
                'has_serial_number' => $data['has_serial_number'],
                'has_warranty' => $data['has_warranty'],
                'has_expiry_date' => $data['has_expiry_date']
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
