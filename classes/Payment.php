<?php
require_once 'Database.php';

class Payment {
    private $db;
    private $razorpayKeyId;
    private $razorpayKeySecret;

    public function __construct() {
        $this->db = Database::getInstance();
        
        // Load Razorpay keys from environment or config
        // For now, using test keys (should be moved to config)
        $this->razorpayKeyId = 'rzp_test_YOUR_KEY_ID';
        $this->razorpayKeySecret = 'YOUR_KEY_SECRET';
    }

    /**
     * Create Razorpay order
     */
    public function createRazorpayOrder($amount, $currency = 'INR', $receipt = null) {
        $url = 'https://api.razorpay.com/v1/orders';
        
        $data = [
            'amount' => $amount * 100, // Amount in paise
            'currency' => $currency,
            'receipt' => $receipt ?? 'order_' . time(),
            'payment_capture' => 1
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->razorpayKeyId . ':' . $this->razorpayKeySecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Failed to create Razorpay order");
        }

        return json_decode($response, true);
    }

    /**
     * Verify Razorpay payment signature
     */
    public function verifyPaymentSignature($orderId, $paymentId, $signature) {
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->razorpayKeySecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Record payment transaction
     */
    public function recordTransaction($subscriptionId, $paymentData) {
        return $this->db->insert('payment_transactions', [
            'subscription_id' => $subscriptionId,
            'razorpay_payment_id' => $paymentData['payment_id'] ?? null,
            'razorpay_order_id' => $paymentData['order_id'] ?? null,
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'INR',
            'status' => $paymentData['status'] ?? 'pending',
            'payment_method' => $paymentData['method'] ?? null,
            'transaction_date' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get payment history for a subscription
     */
    public function getPaymentHistory($subscriptionId) {
        return $this->db->fetchAll(
            "SELECT * FROM payment_transactions 
             WHERE subscription_id = ? 
             ORDER BY transaction_date DESC",
            [$subscriptionId]
        );
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($transactionId, $status) {
        return $this->db->update('payment_transactions',
            ['status' => $status],
            'id = ?',
            [$transactionId]
        );
    }

    /**
     * Get Razorpay key for frontend
     */
    public function getRazorpayKey() {
        return $this->razorpayKeyId;
    }

    /**
     * Fetch payment details from Razorpay
     */
    public function fetchPaymentDetails($paymentId) {
        $url = "https://api.razorpay.com/v1/payments/{$paymentId}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->razorpayKeyId . ':' . $this->razorpayKeySecret);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Failed to fetch payment details");
        }

        return json_decode($response, true);
    }

    /**
     * Calculate total revenue for a subscription
     */
    public function getTotalRevenue($subscriptionId) {
        $result = $this->db->fetchOne(
            "SELECT SUM(amount) as total 
             FROM payment_transactions 
             WHERE subscription_id = ? AND status = 'success'",
            [$subscriptionId]
        );

        return $result['total'] ?? 0;
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions($subscriptionId, $limit = 10) {
        return $this->db->fetchAll(
            "SELECT * FROM payment_transactions 
             WHERE subscription_id = ? 
             ORDER BY transaction_date DESC 
             LIMIT ?",
            [$subscriptionId, $limit]
        );
    }
}
