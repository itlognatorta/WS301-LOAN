-- Sample Data for testing - Run after schema.sql import
USE loan_db;

-- Test Admin (password: admin123)
UPDATE admins SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';

-- Test Premium User (email: test@premium.com, password: test123)
INSERT INTO users (
    username, email, password_hash, account_type, name, address, gender, birthday, phone, bank_name, bank_account, account_holder, tin, company_name, company_address, company_phone, position, monthly_earnings, 
    status, verified
) VALUES (
    'testpremium', 'test@premium.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'premium', 'Test Premium User', '123 Test St, Manila', 'male', '1990-01-01', '09171234567', 'BPI', '1234567890', 'Test Premium', '123456789', 'Test Corp', '456 Corp St', '028123456', 'Manager', 50000.00, 'active', 1
);

-- Test Basic User (email: test@basic.com, password: test123)
INSERT INTO users (
    username, email, password_hash, account_type, name, address, gender, birthday, phone, bank_name, bank_account, account_holder, tin, company_name, company_address, company_phone, position, monthly_earnings, 
    status, verified
) VALUES (
    'testbasic', 'test@basic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'basic', 'Test Basic User', '789 Test Ave', 'female', '1985-05-15', '09991234567', 'BDO', '0987654321', 'Test Basic', '987654321', 'Basic Inc', '789 Inc Ave', '029876543', 'Staff', 25000.00, 'active', 1
);

-- Test loan for premium
INSERT INTO loan_requests (user_id, amount, tenure_months, status, approved_by) VALUES (1, 10000, 12, 'approved', 1);
INSERT INTO loans (user_id, principal, interest, received_amount, tenure_months) VALUES (1, 10000, 300, 9700, 12);

-- Test savings tx for premium
INSERT INTO savings_transactions (tx_id, no, user_id, category, amount, balance_after, status) VALUES ('SV-20240101-1234', 1, 1, 'deposit', 1000, 1000, 'completed');

UPDATE users SET savings_balance = 1000 WHERE id = 1;

-- Test billing
INSERT INTO billing (user_id, generated_date, due_date, loan_principal, monthly_amount, interest, total_due, status) VALUES (1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 28 DAY), 9700, 808.33, 300, 9108.33, 'pending');

-- Test company earnings
INSERT INTO company_earnings (year, total_income) VALUES (2024, 1000000);

-- Run this in phpMyAdmin after schema!
```
**Next Steps:**
1. Import schema.sql, then sample_data.sql.
2. Visit http://localhost/WS301-LOAN/ - stats should show.
3. Register new or login test@premium.com / test123
4. Confirm, then I build dashboard.php etc.
