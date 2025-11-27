<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();
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

// Calculate trial balance
$trialBalance = [];
$totalDebit = 0;
$totalCredit = 0;

if ($startDate && $endDate) {
    // Get all accounts with transactions
    $accounts = $db->fetchAll("
        SELECT DISTINCT coa.id, coa.account_code, coa.account_name, at.category
        FROM chart_of_accounts coa
        JOIN account_types at ON coa.account_type_id = at.id
        WHERE coa.is_active = 1 AND coa.company_id = ?
        ORDER BY coa.account_code
    ", [$user['company_id']]);
    
    foreach ($accounts as $account) {
        // Calculate total debits and credits for this account
        $totals = $db->fetchOne("
            SELECT 
                COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                COALESCE(SUM(jel.credit_amount), 0) as total_credit
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = ?
            AND je.status = 'Posted'
            AND je.entry_date BETWEEN ? AND ? AND je.company_id = ?
        ", [$account['id'], $startDate, $endDate, $user['company_id']]);
        
        $debit = floatval($totals['total_debit']);
        $credit = floatval($totals['total_credit']);
        $balance = $debit - $credit;
        
        // Only include accounts with balance
        if (abs($balance) > 0.01) {
            $trialBalance[] = [
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'category' => $account['category'],
                'debit' => $balance > 0 ? $balance : 0,
                'credit' => $balance < 0 ? abs($balance) : 0
            ];
            
            $totalDebit += $balance > 0 ? $balance : 0;
            $totalCredit += $balance < 0 ? abs($balance) : 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance - <?php echo APP_NAME; ?></title>
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
        .report-period {
            color: var(--text-secondary);
            font-size: 14px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .report-table th {
            background: var(--primary-color);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .report-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
        }
        .report-table .total-row {
            background: var(--light-bg);
            font-weight: bold;
            border-top: 2px solid var(--primary-color);
        }
        .text-right {
            text-align: right;
        }
        .balanced {
            color: var(--success-color);
            font-weight: bold;
        }
        .unbalanced {
            color: var(--danger-color);
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
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
                        <h3 class="card-title">Trial Balance</h3>
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
                            <div class="report-title">Trial Balance</div>
                            <div class="report-period">
                                Period: <?php echo date('d M Y', strtotime($startDate)); ?> to <?php echo date('d M Y', strtotime($endDate)); ?>
                            </div>
                        </div>
                        
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Category</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($trialBalance)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                            No transactions found for the selected period
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($trialBalance as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['account_code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['account_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                                            <td class="text-right">
                                                <?php echo $row['debit'] > 0 ? '₹' . number_format($row['debit'], 2) : '-'; ?>
                                            </td>
                                            <td class="text-right">
                                                <?php echo $row['credit'] > 0 ? '₹' . number_format($row['credit'], 2) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <td colspan="3">TOTAL</td>
                                        <td class="text-right">₹<?php echo number_format($totalDebit, 2); ?></td>
                                        <td class="text-right">₹<?php echo number_format($totalCredit, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" style="text-align: right; padding: 15px; font-weight: bold;">
                                            Balance Status:
                                        </td>
                                        <td colspan="2" style="padding: 15px;">
                                            <?php
                                            $difference = abs($totalDebit - $totalCredit);
                                            if ($difference < 0.01) {
                                                echo '<span class="balanced"><i class="fas fa-check-circle"></i> Balanced</span>';
                                            } else {
                                                echo '<span class="unbalanced"><i class="fas fa-exclamation-circle"></i> Unbalanced (Difference: ₹' . number_format($difference, 2) . ')</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
