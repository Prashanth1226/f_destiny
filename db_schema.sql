CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,

    role ENUM('donor','ngo','volunteer') NOT NULL,

    age INT NULL,
    designation VARCHAR(100) NULL,
    bio TEXT NULL,
    photo VARCHAR(255) NULL,

    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,

    food_name VARCHAR(255) NOT NULL,
    quantity VARCHAR(100) NOT NULL,
    description TEXT,
    address VARCHAR(255) NOT NULL,
    expiry DATE,

    status ENUM('Pending','Approved','Rejected','Picked','Delivered') DEFAULT 'Pending'
    -- Pending / Approved / Rejected / Picked / Delivered

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (donor_id) REFERENCES users(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS food_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,

    ngo_id INT NOT NULL,
    donor_id INT NOT NULL,
    donation_id INT NOT NULL,

    message TEXT,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending'
    -- Pending / Approved / Rejected

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_request (ngo_id, donation_id),
    UNIQUE KEY unique_assignment (donation_id, volunteer_id)

    FOREIGN KEY (ngo_id) REFERENCES users(id)
    ON DELETE CASCADE,

    FOREIGN KEY (donation_id) REFERENCES donations(id)
    ON DELETE CASCADE

    FOREIGN KEY (donor_id) REFERENCES users(id)
    ON DELETE CASCADE,
);

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    donation_id INT NOT NULL,
    volunteer_id INT NOT NULL,

    status ENUM('Assigned','Picked Up','Delivered') DEFAULT 'Assigned',

    pickup_time DATETIME NULL,
    delivery_time DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (donation_id) REFERENCES donations(id),
    FOREIGN KEY (volunteer_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    title VARCHAR(255),
    message TEXT,
    is_read TINYINT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
);

CREATE INDEX idx_donor_id ON donations(donor_id);
CREATE INDEX idx_ngo_id ON food_requests(ngo_id);
CREATE INDEX idx_donation_id ON food_requests(donation_id);
CREATE INDEX idx_volunteer_id ON assignments(volunteer_id);

CREATE TABLE request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);