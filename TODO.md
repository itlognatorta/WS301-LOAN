# WS301-LOAN Project TODO

## Approved Plan Steps (Step-by-step implementation)

### Phase 1: Database Setup
- [x] Create `schema.sql` with all tables (users, loan_requests, loans, savings, transactions, billing, etc.)
- [ ] User imports schema.sql into phpMyAdmin (create loan_db, run script)
- [ ] Test: Visit http://localhost/WS301-LOAN/index.php – stats >0? (add test data if needed)

### Phase 2: Core Auth & Config
- [x] Update `db_connect.php` to PDO prepared statements
- [x] Create `includes/config.php` (constants, functions: validate_phone, age_from_bday, gen_txid, hash_pass)
- [x] Create `login.php` (form + session logic)
- [x] Create `register.php` (full form, uploads to uploads/ folder create if needed, pending status, premium limit 50)


### Phase 3: User Dashboard
- [ ] Create `dashboard.php` (session check, profile, loans apply, savings deposit/withdraw req premium, billing current/history/search)
- [ ] Create `apply_loan.php` (POST handler)
- [ ] Create `savings_action.php` (deposit/withdraw req)

### Phase 4: Admin Panel
- [ ] Create `admin/login.php`
- [ ] Create `admin/index.php` (dashboard stats, earnings input)
- [ ] `admin/users.php` (list, approve reg, edit type/status, block email)
- [ ] `admin/loans.php` (list requests, approve/reject+reason+notify)
- [ ] `admin/savings.php` (requests approve)
- [ ] `admin/billing.php` (overview)

### Phase 5: Billing & Logic
- [ ] Implement billing generation on loan approve (due 28days, monthly)
- [ ] Overdue check +2% penalty (page load or cron sim)
- [ ] Loan increase logic (payments complete)
- [ ] Money back calc/add to premium savings
- [ ] Downgrade premium if savings=0 90days

### Phase 6: UI/Polish
- [ ] includes/header.php footer.php shared
- [ ] Update index.css for forms/tables/searches
- [ ] JS validation, ajax, DataTables for lists
- [ ] Test all flows, add sample data

**Next: schema.sql**

