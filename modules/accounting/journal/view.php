<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../classes/Auth.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/CodeGenerator.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$codeGen = new CodeGenerator();
$user = $auth->getCurrentUser();

// Get next entry number
$nextEntryNumber = $codeGen->generateJournalEntryNumber();

// Get fiscal years
$fiscalYears = $db->fetchAll("SELECT * FROM fiscal_years WHERE is_closed = 0 ORDER BY start_date DESC");

// Get active fiscal year (current date falls within)
$currentFiscalYear = $db->fetchOne("
    SELECT * FROM fiscal_years 
    WHERE CURDATE() BETWEEN start_date AND end_date 
    AND is_closed = 0 
    LIMIT 1
");

// Get all active accounts
$accounts = $db->fetchAll("
    SELECT coa.*, at.name as type_name, at.category
    FROM chart_of_accounts coa
    JOIN account_types at ON coa.account_type_id = at.id
    WHERE coa.is_active = 1
    ORDER BY coa.account_code
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Validate debit-credit balance
        $totalDebit = 0;
        $totalCredit = 0;
        
        if (isset($_POST['lines']) && is_array($_POST['lines'])) {
            foreach ($_POST['lines'] as $line) {
                if (empty($line['account_id'])) continue;
                $totalDebit += floatval($line['debit_amount'] ?? 0);
                $totalCredit += floatval($line['credit_amount'] ?? 0);
            }
        }
        
        // Check if debits equal credits
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception("Debits and Credits must be equal. Debit: ₹" . number_format($totalDebit, 2) . ", Credit: ₹" . number_format($totalCredit, 2));
        }
        
        if ($totalDebit == 0 || $totalCredit == 0) {
            throw new Exception("Journal entry must have at least one debit and one credit entry.");
        }
        
        // Insert journal entry
        $entryId = $db->insert('journal_entries', [
            'entry_number' => $_POST['entry_number'],
            'entry_date' => $_POST['entry_date'],
            'fiscal_year_id' => $_POST['fiscal_year_id'],
            'reference_type' => $_POST['reference_type'] ?? null,
            'reference_id' => $_POST['reference_id'] ?? null,
            'description' => $_POST['description'],
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'status' => $_POST['status'] ?? 'Draft',
            'created_by' => $user['id'],
            'posted_at' => ($_POST['status'] ?? 'Draft') === 'Posted' ? date('Y-m-d H:i:s') : null
        ]);
        
        // Insert journal entry lines
        if (isset($_POST['lines']) && is_array($_POST['lines'])) {
            foreach ($_POST['lines'] as $line) {
                if (empty($line['account_id'])) continue;
                
                $db->insert('journal_entry_lines', [
                    'journal_entry_id' => $entryId,
                    'account_id' => $line['account_id'],
                    'debit_amount' => floatval($line['debit_amount'] ?? 0),
                    'credit_amount' => floatval($line['credit_amount'] ?? 0),
                    'description' => $line['description'] ?? ''
                ]);
            }
        }
        
        $db->commit();
        header('Location: index.php?success=Journal entry created successfully');
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Journal Entry - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .journal-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
            padding-left: 12px;
        }
        .lines-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .lines-table th {
            background: var(--light-bg);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
        }
        .lines-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        .lines-table input, .lines-table select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }
        .balance-display {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .balance-item {
            text-align: center;
        }
        .balance-item label {
            display: block;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        .balance-item .amount {
            font-size: 20px;
            font-weight: bold;
        }
        .balanced {
            color: var(--success-color);
        }
        .unbalanced {
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include INCLUDES_PATH . '/header.php'; ?>
            
            <div class="content-area">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="journal-form">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2><i class="fas fa-edit"></i> Create Journal Entry</h2>
                        <a href="index.php" class="btn" style="background: var(--border-color);">
                            <i class="fas fa-arrow-left"></i> Back to Entries
                        </a>
                    </div>
                    
                    <form method="POST" id="journalForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Entry Number *</label>
                                <input type="text" name="entry_number" class="form-control" value="<?php echo $nextEntryNumber; ?>" readonly style="background-color: #f0f0f0;">
                                <small style="color: var(--text-secondary);">Auto-generated</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Entry Date *</label>
                                <input type="date" name="entry_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Fiscal Year *</label>
                                <select name="fiscal_year_id" class="form-control" required>
                                    <?php foreach ($fiscalYears as $fy): ?>
                                        <option value="<?php echo $fy['id']; ?>" <?php echo ($currentFiscalYear && $fy['id'] == $currentFiscalYear['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fy['year_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="description" class="form-control" rows="2" required placeholder="Enter journal entry description"></textarea>
                        </div>
                        
                        <h4 style="margin: 30px 0 15px; color: var(--primary-color);">Entry Lines</h4>
                        
                        <table class="lines-table" id="linesTable">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Account</th>
                                    <th style="width: 25%;">Line Description</th>
                                    <th style="width: 15%;">Debit</th>
                                    <th style="width: 15%;">Credit</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="linesBody">
                                <!-- Initial rows will be added by JavaScript -->
                            </tbody>
                        </table>
                        
                        <button type="button" class="btn btn-secondary" onclick="addLine()">
                            <i class="fas fa-plus"></i> Add Line
                        </button>
                        
                        <div class="balance-display">
                            <div class="balance-item">
                                <label>Total Debit</label>
                                <div class="amount" id="totalDebit">₹0.00</div>
                            </div>
                            <div class="balance-item">
                                <label>Total Credit</label>
                                <div class="amount" id="totalCredit">₹0.00</div>
                            </div>
                            <div class="balance-item">
                                <label>Difference</label>
                                <div class="amount" id="difference">₹0.00</div>
                            </div>
                            <div class="balance-item">
                                <label>Status</label>
                                <div class="amount" id="balanceStatus">-</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="Draft">Draft</option>
                                    <option value="Posted">Posted</option>
                                </select>
                                <small style="color: var(--text-secondary);">Posted entries cannot be edited</small>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <a href="index.php" class="btn" style="background: var(--border-color);">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Create Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let lineIndex = 0;
        const accountsData = <?php echo json_encode($accounts); ?>;
        
        // Initialize with 2 lines
        $(document).ready(function() {
            addLine();
            addLine();
        });
        
        function addLine() {
            const tbody = document.getElementById('linesBody');
            const tr = document.createElement('tr');
            tr.className = 'line-row';
            tr.innerHTML = `
                <td>
                    <select name="lines[${lineIndex}][account_id]" class="account-select" required>
                        <option value="">Select Account</option>
                        ${accountsData.map(acc => `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`).join('')}
                    </select>
                </td>
                <td><input type="text" name="lines[${lineIndex}][description]" class="line-description"></td>
                <td><input type="number" name="lines[${lineIndex}][debit_amount]" class="debit-input" value="0" step="0.01" min="0" onchange="calculateBalance()"></td>
                <td><input type="number" name="lines[${lineIndex}][credit_amount]" class="credit-input" value="0" step="0.01" min="0" onchange="calculateBalance()"></td>
                <td><button type="button" class="btn btn-sm" style="background: var(--danger-color); color: white;" onclick="removeLine(this)"><i class="fas fa-trash"></i></button></td>
            `;
            
            tbody.appendChild(tr);
            
            // Initialize Select2 on new row
            $(tr).find('.account-select').select2({
                placeholder: "Select Account",
                allowClear: true,
                width: '100%'
            });
            
            lineIndex++;
        }
        
        function removeLine(btn) {
            const rows = document.querySelectorAll('.line-row');
            if (rows.length > 1) {
                btn.closest('tr').remove();
                calculateBalance();
            } else {
                alert('At least one line is required');
            }
        }
        
        function calculateBalance() {
            let totalDebit = 0;
            let totalCredit = 0;
            
            document.querySelectorAll('.line-row').forEach(row => {
                const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
                const credit = parseFloat(row.querySelector('.credit-input').value) || 0;
                totalDebit += debit;
                totalCredit += credit;
            });
            
            const difference = Math.abs(totalDebit - totalCredit);
            const isBalanced = difference < 0.01;
            
            document.getElementById('totalDebit').textContent = '₹' + totalDebit.toFixed(2);
            document.getElementById('totalCredit').textContent = '₹' + totalCredit.toFixed(2);
            document.getElementById('difference').textContent = '₹' + difference.toFixed(2);
            
            const statusEl = document.getElementById('balanceStatus');
            if (isBalanced && totalDebit > 0) {
                statusEl.textContent = 'Balanced ✓';
                statusEl.className = 'amount balanced';
                document.getElementById('submitBtn').disabled = false;
            } else {
                statusEl.textContent = 'Unbalanced ✗';
                statusEl.className = 'amount unbalanced';
                document.getElementById('submitBtn').disabled = true;
            }
        }
        
        // Prevent entering both debit and credit on same line
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('debit-input') && parseFloat(e.target.value) > 0) {
                const row = e.target.closest('tr');
                row.querySelector('.credit-input').value = 0;
            }
            if (e.target.classList.contains('credit-input') && parseFloat(e.target.value) > 0) {
                const row = e.target.closest('tr');
                row.querySelector('.debit-input').value = 0;
            }
        });
    </script>
</body>
</html>
