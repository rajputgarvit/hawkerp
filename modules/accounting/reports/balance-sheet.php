<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get fiscal years
$fiscalYears = $db->fetchAll("SELECT * FROM fiscal_years ORDER BY start_date DESC");

// Get selected date (default to today)
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');

// Calculate Balance Sheet
$assets = [];
$liabilities = [];
$equity = [];
$totalAssets = 0;
$totalLiabilities = 0;
$totalEquity = 0;

// Get Assets
$assetAccounts = $db->fetchAll("
    SELECT coa.id, coa.account_code, coa.account_name
    FROM chart_of_accounts coa
    JOIN account_types at ON coa.account_type_id = at.id
    WHERE at.category = 'Asset' AND coa.is_active = 1 AND coa.company_id = ?
    ORDER BY coa.account_code
", [$user['company_id']]);

foreach ($assetAccounts as $account) {
    $totals = $db->fetchOne("
        SELECT 
            COALESCE(SUM(jel.debit_amount), 0) - COALESCE(SUM(jel.credit_amount), 0) as balance
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        WHERE jel.account_id = ? AND je.status = 'Posted'
        AND je.entry_date <= ? AND je.company_id = ?
    ", [$account['id'], $asOfDate, $user['company_id']]);
    
    $balance = floatval($totals['balance']);
    if (abs($balance) > 0.01) {
        $assets[] = [
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'amount' => $balance
        ];
        $totalAssets += $balance;
    }
}

// Get Liabilities
$liabilityAccounts = $db->fetchAll("
    SELECT coa.id, coa.account_code, coa.account_name
    FROM chart_of_accounts coa
    JOIN account_types at ON coa.account_type_id = at.id
    WHERE at.category = 'Liability' AND coa.is_active = 1 AND coa.company_id = ?
    ORDER BY coa.account_code
", [$user['company_id']]);

foreach ($liabilityAccounts as $account) {
    $totals = $db->fetchOne("
        SELECT 
            COALESCE(SUM(jel.credit_amount), 0) - COALESCE(SUM(jel.debit_amount), 0) as balance
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        WHERE jel.account_id = ? AND je.status = 'Posted'
        AND je.entry_date <= ? AND je.company_id = ?
    ", [$account['id'], $asOfDate, $user['company_id']]);
    
    $balance = floatval($totals['balance']);
    if (abs($balance) > 0.01) {
        $liabilities[] = [
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'amount' => $balance
        ];
        $totalLiabilities += $balance;
    }
}

// Get Equity
$equityAccounts = $db->fetchAll("
    SELECT coa.id, coa.account_code, coa.account_name
    FROM chart_of_accounts coa
    JOIN account_types at ON coa.account_type_id = at.id
    WHERE at.category = 'Equity' AND coa.is_active = 1 AND coa.company_id = ?
    ORDER BY coa.account_code
", [$user['company_id']]);

foreach ($equityAccounts as $account) {
    $totals = $db->fetchOne("
        SELECT 
            COALESCE(SUM(jel.credit_amount), 0) - COALESCE(SUM(jel.debit_amount), 0) as balance
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        WHERE jel.account_id = ? AND je.status = 'Posted'
        AND je.entry_date <= ? AND je.company_id = ?
    ", [$account['id'], $asOfDate, $user['company_id']]);
    
    $balance = floatval($totals['balance']);
    if (abs($balance) > 0.01) {
        $equity[] = [
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'amount' => $balance
        ];
        $totalEquity += $balance;
    }
}

$totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
$difference = abs($totalAssets - $totalLiabilitiesAndEquity);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-header { text-align: center; margin-bottom: 30px; }
        .report-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .report-section { margin: 30px 0; }
        .section-title { background: var(--primary-color); color: white; padding: 10px 15px; font-weight: 600; margin-bottom: 10px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table td { padding: 8px 15px; border-bottom: 1px solid var(--border-color); }
        .report-table .total-row { background: var(--light-bg); font-weight: bold; border-top: 2px solid var(--primary-color); }
        .report-table .grand-total { background: var(--primary-color); color: white; font-weight: bold; font-size: 18px; }
        .text-right { text-align: right; }
        .balanced { color: var(--success-color); }
        .unbalanced { color: var(--danger-color); }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <div class="card">
                    <div class="card-header no-print">
                        <h3 class="card-title">Balance Sheet</h3>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="window.print()" class="btn btn-sm" style="background: var(--secondary-color); color: white;">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <a href="reports.php" class="btn btn-sm" style="background: var(--border-color);">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    
                    <div style="padding: 30px;">
                        <!-- Filters -->
                        <form method="GET" class="no-print" style="background: var(--light-bg); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>As of Date</label>
                                    <input type="date" name="as_of_date" class="form-control" value="<?php echo $asOfDate; ?>">
                                </div>
                                <div class="form-group" style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Apply
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Report -->
                        <div class="report-header">
                            <div class="report-title"><?php echo APP_NAME; ?></div>
                            <div class="report-title">Balance Sheet</div>
                            <div style="color: var(--text-secondary); font-size: 14px;">
                                As of <?php echo date('d M Y', strtotime($asOfDate)); ?>
                            </div>
                        </div>
                        
                        <!-- Assets -->
                        <div class="report-section">
                            <div class="section-title">ASSETS</div>
                            <table class="report-table">
                                <tbody>
                                    <?php if (empty($assets)): ?>
                                        <tr><td colspan="2" style="text-align: center; color: var(--text-secondary); padding: 20px;">No assets recorded</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($assets as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['account_code'] . ' - ' . $row['account_name']); ?></td>
                                                <td class="text-right">₹<?php echo number_format($row['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr class="total-row">
                                        <td>Total Assets</td>
                                        <td class="text-right">₹<?php echo number_format($totalAssets, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Liabilities -->
                        <div class="report-section">
                            <div class="section-title">LIABILITIES</div>
                            <table class="report-table">
                                <tbody>
                                    <?php if (empty($liabilities)): ?>
                                        <tr><td colspan="2" style="text-align: center; color: var(--text-secondary); padding: 20px;">No liabilities recorded</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($liabilities as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['account_code'] . ' - ' . $row['account_name']); ?></td>
                                                <td class="text-right">₹<?php echo number_format($row['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr class="total-row">
                                        <td>Total Liabilities</td>
                                        <td class="text-right">₹<?php echo number_format($totalLiabilities, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Equity -->
                        <div class="report-section">
                            <div class="section-title">EQUITY</div>
                            <table class="report-table">
                                <tbody>
                                    <?php if (empty($equity)): ?>
                                        <tr><td colspan="2" style="text-align: center; color: var(--text-secondary); padding: 20px;">No equity recorded</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($equity as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['account_code'] . ' - ' . $row['account_name']); ?></td>
                                                <td class="text-right">₹<?php echo number_format($row['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr class="total-row">
                                        <td>Total Equity</td>
                                        <td class="text-right">₹<?php echo number_format($totalEquity, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Totals -->
                        <table class="report-table">
                            <tr class="grand-total">
                                <td>TOTAL LIABILITIES & EQUITY</td>
                                <td class="text-right">₹<?php echo number_format($totalLiabilitiesAndEquity, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; padding: 15px; font-weight: bold;">Balance Check:</td>
                                <td style="padding: 15px;">
                                    <?php if ($difference < 0.01): ?>
                                        <span class="balanced"><i class="fas fa-check-circle"></i> Balanced (Assets = Liabilities + Equity)</span>
                                    <?php else: ?>
                                        <span class="unbalanced"><i class="fas fa-exclamation-circle"></i> Unbalanced (Difference: ₹<?php echo number_format($difference, 2); ?>)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
