<?php
session_start();
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Database.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$user = $auth->getCurrentUser();

// Get fiscal years
$fiscalYears = $db->fetchAll("SELECT * FROM fiscal_years ORDER BY start_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .report-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .report-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        .report-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .report-description {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 20px;
        }
        .report-btn {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        .report-btn:hover {
            background: var(--primary-dark);
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
                    <div class="card-header">
                        <h3 class="card-title">Financial Reports</h3>
                        <div>
                            <select id="fiscalYearFilter" class="form-control" style="width: 200px;">
                                <option value="">Select Fiscal Year</option>
                                <?php foreach ($fiscalYears as $fy): ?>
                                    <option value="<?php echo $fy['id']; ?>">
                                        <?php echo htmlspecialchars($fy['year_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="padding: 30px;">
                        <div class="reports-grid">
                            <!-- Trial Balance -->
                            <div class="report-card" onclick="openReport('trial-balance')">
                                <div class="report-icon" style="color: #3498db;">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                                <div class="report-title">Trial Balance</div>
                                <div class="report-description">
                                    View all account balances to ensure debits equal credits
                                </div>
                                <a href="#" class="report-btn" onclick="event.stopPropagation(); openReport('trial-balance')">
                                    <i class="fas fa-chart-bar"></i> View Report
                                </a>
                            </div>
                            
                            <!-- Profit & Loss -->
                            <div class="report-card" onclick="openReport('profit-loss')">
                                <div class="report-icon" style="color: #27ae60;">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="report-title">Profit & Loss Statement</div>
                                <div class="report-description">
                                    Income and expenses summary showing net profit or loss
                                </div>
                                <a href="#" class="report-btn" onclick="event.stopPropagation(); openReport('profit-loss')">
                                    <i class="fas fa-chart-bar"></i> View Report
                                </a>
                            </div>
                            
                            <!-- Balance Sheet -->
                            <div class="report-card" onclick="openReport('balance-sheet')">
                                <div class="report-icon" style="color: #e74c3c;">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div class="report-title">Balance Sheet</div>
                                <div class="report-description">
                                    Assets, liabilities, and equity at a specific point in time
                                </div>
                                <a href="#" class="report-btn" onclick="event.stopPropagation(); openReport('balance-sheet')">
                                    <i class="fas fa-chart-bar"></i> View Report
                                </a>
                            </div>
                            
                            <!-- General Ledger -->
                            <div class="report-card" onclick="openReport('ledger')">
                                <div class="report-icon" style="color: #f39c12;">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="report-title">General Ledger</div>
                                <div class="report-description">
                                    Detailed transaction history for all accounts
                                </div>
                                <a href="#" class="report-btn" onclick="event.stopPropagation(); openReport('ledger')">
                                    <i class="fas fa-chart-bar"></i> View Report
                                </a>
                            </div>
                            
                            <!-- Cash Flow Statement -->
                            <div class="report-card" onclick="openReport('cash-flow')">
                                <div class="report-icon" style="color: #9b59b6;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="report-title">Cash Flow Statement</div>
                                <div class="report-description">
                                    Cash inflows and outflows from operations, investing, and financing
                                </div>
                                <a href="#" class="report-btn" onclick="event.stopPropagation(); openReport('cash-flow')">
                                    <i class="fas fa-chart-bar"></i> View Report
                                </a>
                            </div>
                            
                            <!-- Account Statement -->
                            <div class="report-card" onclick="openReport('account-statement')">
                                <div class="report-icon" style="color: #16a085;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="report-title">Account Statement</div>
                                <div class="report-description">
                                    Detailed statement for a specific account with running balance
                                </div>
                                <a href="#" class="report-btn" onclick="event.stopPropagation(); openReport('account-statement')">
                                    <i class="fas fa-chart-bar"></i> View Report
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openReport(reportType) {
            const fiscalYear = document.getElementById('fiscalYearFilter').value;
            let url = `../accounting/reports/${reportType}.php`;
            
            if (fiscalYear) {
                url += `?fiscal_year=${fiscalYear}`;
            }
            
            window.location.href = url;
        }
    </script>
</body>
</html>
