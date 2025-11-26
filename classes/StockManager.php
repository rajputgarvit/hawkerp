<?php
/**
 * Stock Manager Class
 * Handles all stock-related operations including updates from purchases and sales
 */

class StockManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Update stock balance for a product in a warehouse
     */
    private function updateStockBalance($productId, $warehouseId, $quantityChange) {
        // Check if stock balance record exists
        $existing = $this->db->fetchOne(
            "SELECT * FROM stock_balance WHERE product_id = ? AND warehouse_id = ?",
            [$productId, $warehouseId]
        );
        
        if ($existing) {
            // Update existing balance
            $newQuantity = $existing['quantity'] + $quantityChange;
            $this->db->update('stock_balance', [
                'quantity' => $newQuantity
            ], 'product_id = ? AND warehouse_id = ?', [$productId, $warehouseId]);
        } else {
            // Create new balance record
            $this->db->insert('stock_balance', [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => max(0, $quantityChange),
                'reserved_quantity' => 0
            ]);
        }
    }
    
    /**
     * Record stock transaction
     */
    private function recordTransaction($type, $productId, $warehouseId, $quantity, $referenceType = null, $referenceId = null, $remarks = null, $userId = null) {
        $this->db->insert('stock_transactions', [
            'transaction_type' => $type,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'remarks' => $remarks,
            'created_by' => $userId
        ]);
    }
    
    /**
     * Add stock (from purchase)
     */
    public function addStock($productId, $warehouseId, $quantity, $referenceType = null, $referenceId = null, $remarks = null, $userId = null) {
        // Balance updated via trigger on stock_transactions
        $this->recordTransaction('IN', $productId, $warehouseId, $quantity, $referenceType, $referenceId, $remarks, $userId);
    }
    
    /**
     * Remove stock (from sales)
     */
    public function removeStock($productId, $warehouseId, $quantity, $referenceType = null, $referenceId = null, $remarks = null, $userId = null) {
        // Check if sufficient stock available
        $balance = $this->db->fetchOne(
            "SELECT available_quantity FROM stock_balance WHERE product_id = ? AND warehouse_id = ?",
            [$productId, $warehouseId]
        );
        
        if (!$balance || $balance['available_quantity'] < $quantity) {
            throw new Exception("Insufficient stock available");
        }
        
        // Balance updated via trigger on stock_transactions
        $this->recordTransaction('OUT', $productId, $warehouseId, $quantity, $referenceType, $referenceId, $remarks, $userId);
    }
    
    /**
     * Reserve stock (for sales orders)
     */
    public function reserveStock($productId, $warehouseId, $quantity) {
        $balance = $this->db->fetchOne(
            "SELECT available_quantity FROM stock_balance WHERE product_id = ? AND warehouse_id = ?",
            [$productId, $warehouseId]
        );
        
        if (!$balance || $balance['available_quantity'] < $quantity) {
            throw new Exception("Insufficient stock available to reserve");
        }
        
        $this->db->query(
            "UPDATE stock_balance SET reserved_quantity = reserved_quantity + ? WHERE product_id = ? AND warehouse_id = ?",
            [$quantity, $productId, $warehouseId]
        );
    }
    
    /**
     * Release reserved stock
     */
    public function releaseReservedStock($productId, $warehouseId, $quantity) {
        $this->db->query(
            "UPDATE stock_balance SET reserved_quantity = reserved_quantity - ? WHERE product_id = ? AND warehouse_id = ?",
            [$quantity, $productId, $warehouseId]
        );
    }
    
    /**
     * Get current stock for a product in a warehouse
     */
    public function getStock($productId, $warehouseId) {
        return $this->db->fetchOne(
            "SELECT * FROM stock_balance WHERE product_id = ? AND warehouse_id = ?",
            [$productId, $warehouseId]
        );
    }
    
    /**
     * Get stock across all warehouses for a product
     */
    public function getTotalStock($productId) {
        $result = $this->db->fetchOne(
            "SELECT 
                SUM(quantity) as total_quantity,
                SUM(reserved_quantity) as total_reserved,
                SUM(available_quantity) as total_available
            FROM stock_balance 
            WHERE product_id = ?",
            [$productId]
        );
        
        return $result;
    }
    
    /**
     * Adjust stock (manual adjustment)
     */
    public function adjustStock($productId, $warehouseId, $newQuantity, $remarks = null, $userId = null) {
        $current = $this->getStock($productId, $warehouseId);
        $currentQty = $current ? $current['quantity'] : 0;
        $difference = $newQuantity - $currentQty;
        
        $this->updateStockBalance($productId, $warehouseId, $difference);
        $this->recordTransaction('ADJUSTMENT', $productId, $warehouseId, abs($difference), null, null, $remarks, $userId);
    }
}
?>
