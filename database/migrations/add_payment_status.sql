ALTER TABLE invoices
ADD COLUMN payment_status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Overdue') DEFAULT 'Unpaid' AFTER due_date;
