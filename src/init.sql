CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    dob DATE,
    passed_class VARCHAR(100),
    school_name VARCHAR(255),
    mother_name VARCHAR(255),
    father_name VARCHAR(255),
    permanent_address VARCHAR(255),
    temporary_address VARCHAR(255),
    phone VARCHAR(50),
    photo VARCHAR(255),
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- First, clear any existing admin users
DELETE FROM admin_users WHERE username = 'admin';

-- Then insert the admin user with the new password hash
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$YEz3UmB6gRFyQwrNUIhkBOK0iuXhZjA3V6ww3ob84yDK72VquF7vy'); -- password: admin123

CREATE TABLE IF NOT EXISTS registration_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
);