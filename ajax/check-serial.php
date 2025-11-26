<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $serialNumber = trim($data['serial_number'] ?? '');
    $excludeInvoiceId = $data['exclude_invoice_id'] ?? null;
    
    if (empty($serialNumber)) {
        echo json_encode(['success' => false, 'message' => 'Serial number is required']);
        exit;
    }
    
    try {
        // Check if serial number exists in any non-cancelled invoice
        $query = "
            SELECT i.invoice_number 
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE ii.serial_number = ? 
            AND i.company_id = ?
            AND i.status != 'Cancelled'
        ";
        
        $params = [$serialNumber, $user['company_id']];
        
        if ($excludeInvoiceId) {
            $query .= " AND i.id != ?";
            $params[] = $excludeInvoiceId;
        }
        
        $result = $db->fetchOne($query, $params);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'exists' => true, 
                'message' => "Serial Number '$serialNumber' is already sold in Invoice " . $result['invoice_number']
            ]);
        } else {
            echo json_encode(['success' => true, 'exists' => false]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
