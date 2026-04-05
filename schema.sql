-- WS301-LOAN Database Schema
-- Run in phpMyAdmin: Create database 'loan_db' if not exists, then execute this script

DROP DATABASE IF EXISTS loan_db;
CREATE DATABASE loan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loan_db;

-- Users table (registered accounts)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL COMMENT 'Username or email',
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_type ENUM('basic', 'premium') NOT NULL DEFAULT 'basic',
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    gender ENUM('male', 'female', 'other'),
    birthday DATE NOT NULL,
    age INT DEFAULT 0 COMMENT 'Computed from birthday',
    phone VARCHAR(11) NOT NULL COMMENT 'PH format 09xxxxxxxxx',
    bank_name VARCHAR(255) NOT NULL,
    bank_account VARCHAR(50) NOT NULL,
    account_holder VARCHAR(255) NOT NULL,
    tin VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    company_address TEXT NOT NULL,
    company_phone VARCHAR(11) NOT NULL,
    position VARCHAR(255) NOT NULL,
    monthly_earnings DECIMAL(10,2) NOT NULL,
    proof_billing_path VARCHAR(500),
    valid_id_path VARCHAR(500),
    coe_path VARCHAR(500),
    status ENUM('pending', 'active', 'disabled') DEFAULT 'pending',
    verified TINYINT(1) DEFAULT 0,
    savings_balance DECIMAL(10,2) DEFAULT 0.00,
    current_loan_amount DECIMAL(10,2) DEFAULT 0.00,
    max_loan_amount DECIMAL(10,2) DEFAULT 10000.00 COMMENT 'Increases gradually up to 50000',
    max_tenure_months INT DEFAULT 12,
    last_savings_activity DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_account_type (account_type),
    INDEX idx_email (email)
);

-- Blocked emails
CREATE TABLE blocked_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    blocked_by INT,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pending registrations (separate for approval, merge on approve?)
CREATE TABLE registration_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    -- ... all fields like users but no id/pass, link to uploads
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Uploads (docs)
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('proof_billing', 'valid_id', 'coe') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Loan requests/applications
CREATE TABLE loan_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tenure_months INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
);

-- Active loans
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    principal DECIMAL(10,2) NOT NULL,
    interest DECIMAL(10,2) NOT NULL COMMENT '3%',
    received_amount DECIMAL(10,2) NOT NULL,
    tenure_months INT NOT NULL,
    current_month INT DEFAULT 1,
    status ENUM('active', 'paid', 'default') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Loan transactions/increases
CREATE TABLE loan_transactions (
    no INT AUTO_INCREMENT PRIMARY KEY,
    tx_id VARCHAR(20) UNIQUE NOT NULL COMMENT 'Random unique e.g. LN-YYYYMMDD-XXXX',
    user_id INT NOT NULL,
    type ENUM('apply', 'increase') NOT NULL,
    amount DECIMAL(10,2),
    tenure_months INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Savings transactions
CREATE TABLE savings_transactions (
    no INT AUTO_INCREMENT PRIMARY KEY,
    tx_id VARCHAR(20) UNIQUE NOT NULL COMMENT 'Random unique SV-YYYYMMDD-XXXX',
    user_id INT NOT NULL,
    category ENUM('deposit', 'withdrawal') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2),
    status ENUM('pending', 'completed', 'failed', 'rejected') DEFAULT 'pending',
    request_id INT NULL,
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_category (user_id, category),
    INDEX idx_status_date (status, created_at)
);

-- Savings withdrawal requests
CREATE TABLE savings_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Billing
CREATE TABLE billing (
    id INT AUTO_INCREMENT PRIMARY KEY,  
    user_id INT NOT NULL,
    generated_date DATE NOT NULL,
    due_date DATE NOT NULL,
    loan_principal DECIMAL(10,2),
    monthly_amount DECIMAL(10,2),
    interest DECIMAL(10,2),
    penalty DECIMAL(10,2) DEFAULT 0,
    total_due DECIMAL(10,2),
    status ENUM('pending', 'completed', 'overdue') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_status (status)
);

-- Admin users (separate)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Company earnings for money back
CREATE TABLE company_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    total_income DECIMAL(12,2) NOT NULL,
    money_back_distributed DECIMAL(10,2) DEFAULT 0,
    distributed_at TIMESTAMP NULL,
    UNIQUE KEY unique_year (year)
);

-- Insert test admin: username 'admin', pass 'admin123' (hash later)
INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$10$K.ExampleHashForAdmin'); -- Update after

-- Indexes for performance
CREATE INDEX idx_users_status_type ON users(status, account_type);
CREATE INDEX idx_loans_user ON loans(user_id);
CREATE INDEX idx_billing_user_status ON billing(user_id, status);

-- Sample data after import
-- INSERT test users, etc. in next step

-- End schema. Run this to setup!

