USE loan_db;

-- Update admin password to admin123 (new fresh hash)
UPDATE admins SET password_hash = '$2y$10$DhQoONm5yRguHzDV3S7BfONHBk7lGFY/8EUKsqvQ/FI6ZEExFlA7G' WHERE username = 'admin';

SELECT * FROM admins;

