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

// Get selected fiscal year or current
$selectedFiscalYear = $_GET['fiscal_year'] ?? null;
if (!$selectedFiscalYear) {
    $currentFY = $db->fetchOne("SELECT * FROM fiscal_years WHERE CURDATE() BETWEEN start_date AND end_date AND company_id = ? LIMIT 1", [$user['company_id']]);
    $selectedFiscalYear = $currentFY['id'] ?? null;
}

// Get date range
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? date('Y-m-d');

if (!$startDate && $selectedFiscalYear) {
    $fy = $db->fetchOne("SELECT * FROM fiscal_years WHERE id = ? AND company_id = ?", [$selectedFiscalYear, $user['company_id']]);
    $startDate = $fy['start_date'];
    $endDate = $fy['end_date'];
}

// Calculate P&L
$income = [];
$expenses = [];
$totalIncome = 0;
$totalExpenses = 0;

if ($startDate && $endDate) {
    // Get income accounts
    $incomeAccounts = $db->fetchAll("
        SELECT coa.id, coa.account_code, coa.account_name
        FROM chart_of_accounts coa
        JOIN account_types at ON coa.account_type_id = at.id
        WHERE at.category = 'Income' AND coa.is_active = 1 AND coa.company_id = ?
        ORDER BY coa.account_code
    ", [$user['company_id']]);
    
    foreach ($incomeAccounts as $account) {
        $totals = $db->fetchOne("
            SELECT 
                COALESCE(SUM(jel.credit_amount), 0) - COALESCE(SUM(jel.debit_amount), 0) as balance
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = ? AND je.status = 'Posted'
            AND je.entry_date BETWEEN ? AND ? AND je.company_id = ?
        ", [$account['id'], $startDate, $endDate, $user['company_id']]);
        
        $balance = floatval($totals['balance']);
        if (abs($balance) > 0.01) {
            $income[] = [
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'amount' => $balance
            ];
            $totalIncome += $balance;
        }
    }
    
    // Get expense accounts
    $expenseAccounts = $db->fetchAll("
        SELECT coa.id, coa.account_code, coa.account_name
        FROM chart_of_accounts coa
        JOIN account_types at ON coa.account_type_id = at.id
        WHERE at.category = 'Expense' AND coa.is_active = 1 AND coa.company_id = ?
        ORDER BY coa.account_code
    ", [$user['company_id']]);
    
    foreach ($expenseAccounts as $account) {
        $totals = $db->fetchOne("
            SELECT 
                COALESCE(SUM(jel.debit_amount), 0) - COALESCE(SUM(jel.credit_amount), 0) as balance
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = ? AND je.status = 'Posted'
            AND je.entry_date BETWEEN ? AND ? AND je.company_id = ?
        ", [$account['id'], $startDate, $endDate, $user['company_id']]);
        
        $balance = floatval($totals['balance']);
        if (abs($balance) > 0.01) {
            $expenses[] = [
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'amount' => $balance
            ];
            $totalExpenses += $balance;
        }
    }
}

$netProfit = $totalIncome - $totalExpenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .report-section {
            margin: 30px 0;
        }
        .section-title {
            background: var(--primary-color);
            color: white;
            padding: 10px 15px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table td {
            padding: 8px 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .report-table .total-row {
            background: var(--light-bg);
            font-weight: bold;
            border-top: 2px solid var(--primary-color);
        }
        .report-table .net-row {
            background: var(--primary-color);
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .text-right {
            text-align: right;
        }
        .profit {
            color: var(--success-color);
        }
        .loss {
            color: var(--danger-color);
        }
        @media print {
            .no-print {
                display: none;
            }
        }
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
                        <h3 class="card-title">Profit & Loss Statement</h3>
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
                                    <label>Fiscal Year</label>
                                    <select name="fiscal_year" class="form-control">
                                        <option value="">Custom Date Range</option>
                                        <?php foreach ($fiscalYears as $fy): ?>
                                            <option value="<?php echo $fy['id']; ?>" <?php echo $fy['id'] == $selectedFiscalYear ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($fy['year_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                </div>
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
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
                            <div class="report-title">Profit & Loss Statement</div>
                            <div style="color: var(--text-secondary); font-size: 14px;">
                                Period: <?php echo date('d M Y', strtotime($startDate)); ?> to <?php echo date('d M Y', strtotime($endDate)); ?>
                            </div>
                        </div>
                        
                        <!-- Income Section -->
                        <div class="report-section">
                            <div class="section-title">INCOME</div>
                            <table class="report-table">
                                <tbody>
                                    <?php if (empty($income)): ?>
                                        <tr>
                                            <td colspan="2" style="text-align: center; color: var(--text-secondary); padding: 20px;">
                                                No income recorded
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($income as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['account_code'] . ' - ' . $row['account_name']); ?></td>
                                                <td class="text-right">₹<?php echo number_format($row['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr class="total-row">
                                        <td>Total Income</td>
                                        <td class="text-right">₹<?php echo number_format($totalIncome, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Expenses Section -->
                        <div class="report-section">
                            <div class="section-title">EXPENSES</div>
                            <table class="report-table">
                                <tbody>
                                    <?php if (empty($expenses)): ?>
                                        <tr>
                                            <td colspan="2" style="text-align: center; color: var(--text-secondary); padding: 20px;">
                                                No expenses recorded
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($expenses as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['account_code'] . ' - ' . $row['account_name']); ?></td>
                                                <td class="text-right">₹<?php echo number_format($row['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr class="total-row">
                                        <td>Total Expenses</td>
                                        <td class="text-right">₹<?php echo number_format($totalExpenses, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Net Profit/Loss -->
                        <table class="report-table">
                            <tr class="net-row">
                                <td>
                                    <?php echo $netProfit >= 0 ? 'NET PROFIT' : 'NET LOSS'; ?>
                                </td>
                                <td class="text-right">
                                    ₹<?php echo number_format(abs($netProfit), 2); ?>
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
