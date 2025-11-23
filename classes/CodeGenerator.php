<?php

class CodeGenerator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate next employee code
     * Format: EMP001, EMP002, etc.
     */
    public function generateEmployeeCode() {
        $lastCode = $this->db->fetchOne("SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1");
        
        if (!$lastCode) {
            return 'EMP001';
        }
        
        $number = (int) substr($lastCode['employee_code'], 3);
        $newNumber = $number + 1;
        
        return 'EMP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next product code
     * Format: PRD001, PRD002, etc.
     */
    public function generateProductCode() {
        $lastCode = $this->db->fetchOne("SELECT product_code FROM products ORDER BY id DESC LIMIT 1");
        
        if (!$lastCode) {
            return 'PRD001';
        }
        
        $number = (int) substr($lastCode['product_code'], 3);
        $newNumber = $number + 1;
        
        return 'PRD' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next customer code
     * Format: CUST001, CUST002, etc.
     */
    public function generateCustomerCode() {
        $lastCode = $this->db->fetchOne("SELECT customer_code FROM customers ORDER BY id DESC LIMIT 1");
        
        if (!$lastCode) {
            return 'CUST001';
        }
        
        $number = (int) substr($lastCode['customer_code'], 4);
        $newNumber = $number + 1;
        
        return 'CUST' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next supplier code
     * Format: SUPP001, SUPP002, etc.
     */
    public function generateSupplierCode() {
        $lastCode = $this->db->fetchOne("SELECT supplier_code FROM suppliers ORDER BY id DESC LIMIT 1");
        
        if (!$lastCode) {
            return 'SUPP001';
        }
        
        $number = (int) substr($lastCode['supplier_code'], 4);
        $newNumber = $number + 1;
        
        return 'SUPP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next sales order number
     * Format: SO-2024-001, SO-2024-002, etc.
     */
    public function generateSalesOrderNumber() {
        $year = date('Y');
        $lastOrder = $this->db->fetchOne(
            "SELECT order_number FROM sales_orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["SO-$year-%"]
        );
        
        if (!$lastOrder) {
            return "SO-$year-001";
        }
        
        $parts = explode('-', $lastOrder['order_number']);
        $number = (int) end($parts);
        $newNumber = $number + 1;
        
        return "SO-$year-" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next purchase order number
     * Format: PO-2024-001, PO-2024-002, etc.
     */
    public function generatePurchaseOrderNumber() {
        $year = date('Y');
        $lastOrder = $this->db->fetchOne(
            "SELECT po_number FROM purchase_orders WHERE po_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["PO-$year-%"]
        );
        
        if (!$lastOrder) {
            return "PO-$year-001";
        }
        
        $parts = explode('-', $lastOrder['po_number']);
        $number = (int) end($parts);
        $newNumber = $number + 1;
        
        return "PO-$year-" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next invoice number
     * Format: INV-2024-001, INV-2024-002, etc.
     */
    public function generateInvoiceNumber() {
        $year = date('Y');
        $lastInvoice = $this->db->fetchOne(
            "SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["INV-$year-%"]
        );
        
        if (!$lastInvoice) {
            return "INV-$year-001";
        }
        
        $parts = explode('-', $lastInvoice['invoice_number']);
        $number = (int) end($parts);
        $newNumber = $number + 1;
        
        return "INV-$year-" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next quotation number
     * Format: QT-2024-001, QT-2024-002, etc.
     */
    public function generateQuotationNumber() {
        $year = date('Y');
        $lastQuote = $this->db->fetchOne(
            "SELECT quotation_number FROM quotations WHERE quotation_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["QT-$year-%"]
        );
        
        if (!$lastQuote) {
            return "QT-$year-001";
        }
        
        $parts = explode('-', $lastQuote['quotation_number']);
        $number = (int) end($parts);
        $newNumber = $number + 1;
        
        return "QT-$year-" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate next lead number
     * Format: LEAD-2024-001, LEAD-2024-002, etc.
     */
    public function generateLeadNumber() {
        $year = date('Y');
        $lastLead = $this->db->fetchOne(
            "SELECT lead_number FROM leads WHERE lead_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["LEAD-$year-%"]
        );
        
        if (!$lastLead) {
            return "LEAD-$year-001";
        }
        
        $parts = explode('-', $lastLead['lead_number']);
        $number = (int) end($parts);
        $newNumber = $number + 1;
        
        return "LEAD-$year-" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate warehouse code
     * Format: WH001, WH002, etc.
     */
    public function generateWarehouseCode() {
        $lastCode = $this->db->fetchOne("SELECT code FROM warehouses ORDER BY id DESC LIMIT 1");
        
        if (!$lastCode) {
            return 'WH001';
        }
        
        $number = (int) substr($lastCode['code'], 2);
        $newNumber = $number + 1;
        
        return 'WH' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}
