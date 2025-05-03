-- Insert admin user with hashed password (password: admin123)
INSERT INTO users (username, email, password, is_admin, created_at) 
VALUES ('admin', 'admin@skillshare-hub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, datetime('now'));
