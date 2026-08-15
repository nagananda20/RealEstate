-- RealEstateHub unified database
CREATE DATABASE IF NOT EXISTS realestatehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE realestatehub;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS property_amenities, amenities, reviews, search_history, contact_messages,
notifications, messages, visits, enquiries, favorites, property_images, properties,
agents, property_categories, locations, settings, users;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    profile_image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    email_verified_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE agents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    specialization VARCHAR(150) DEFAULT NULL,
    experience INT DEFAULT 0,
    license_number VARCHAR(100) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_agents_status (status)
) ENGINE=InnoDB;

CREATE TABLE property_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT 'India',
    pincode VARCHAR(20) DEFAULT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_locations_city (city)
) ENGINE=InnoDB;

CREATE TABLE properties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id INT UNSIGNED DEFAULT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    location_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    property_type VARCHAR(50) NOT NULL DEFAULT 'House',
    listing_type VARCHAR(20) NOT NULL DEFAULT 'sale',
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    security_deposit DECIMAL(15,2) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    location VARCHAR(200) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT 'India',
    pincode VARCHAR(20) DEFAULT NULL,
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    balconies INT DEFAULT 0,
    area DECIMAL(12,2) DEFAULT 0,
    area_sqft DECIMAL(12,2) DEFAULT 0,
    parking INT DEFAULT 0,
    parking_spaces INT DEFAULT 0,
    floor_number INT DEFAULT NULL,
    total_floors INT DEFAULT NULL,
    property_age INT DEFAULT NULL,
    facing VARCHAR(30) DEFAULT NULL,
    furnished VARCHAR(50) DEFAULT 'Unfurnished',
    image VARCHAR(255) DEFAULT NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_properties_agent FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL,
    CONSTRAINT fk_properties_category FOREIGN KEY (category_id) REFERENCES property_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_properties_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_properties_price (price),
    INDEX idx_properties_type (property_type),
    INDEX idx_properties_listing (listing_type),
    INDEX idx_properties_status (status),
    INDEX idx_properties_featured (featured)
) ENGINE=InnoDB;

CREATE TABLE property_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id INT UNSIGNED NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_property_images_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_property_images_property (property_id)
) ENGINE=InnoDB;

CREATE TABLE amenities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE property_amenities (
    property_id INT UNSIGNED NOT NULL,
    amenity_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (property_id, amenity_id),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, property_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    property_id INT UNSIGNED NOT NULL,
    agent_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL,
    INDEX idx_enquiries_status (status)
) ENGINE=InnoDB;

CREATE TABLE visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    property_id INT UNSIGNED NOT NULL,
    agent_id INT UNSIGNED DEFAULT NULL,
    visitor_name VARCHAR(100) DEFAULT NULL,
    visitor_phone VARCHAR(30) DEFAULT NULL,
    visitor_email VARCHAR(150) DEFAULT NULL,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    message TEXT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL,
    INDEX idx_visits_date (visit_date),
    INDEX idx_visits_status (status)
) ENGINE=InnoDB;

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_messages_receiver (receiver_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'general',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE search_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    keyword VARCHAR(200) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    property_type VARCHAR(50) DEFAULT NULL,
    listing_type VARCHAR(20) DEFAULT NULL,
    min_price DECIMAL(15,2) DEFAULT NULL,
    max_price DECIMAL(15,2) DEFAULT NULL,
    bedrooms INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (name,email,phone,password,role,status)
VALUES ('Administrator','admin@realestatehub.com','9876543210',
'$2y$12$5Kau1TsGzXosZTykUHjs6OGlyU.xxm2BXfGa9Hv68rU9GlPW2fi6S',
'admin','active')
ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role), status=VALUES(status);

INSERT INTO agents (name,email,phone,password,experience,specialization,license_number,bio,status)
VALUES ('John Agent','john@realestatehub.com','9876543211',
'$2y$12$5Kau1TsGzXosZTykUHjs6OGlyU.xxm2BXfGa9Hv68rU9GlPW2fi6S',
5,'Residential Properties','REH-1001','Experienced real estate professional.','active')
ON DUPLICATE KEY UPDATE name=VALUES(name), status=VALUES(status);

INSERT INTO property_categories (name,slug,description) VALUES
('Apartment','apartment','Apartments and flats'),
('Villa','villa','Independent villas'),
('House','house','Independent houses'),
('Plot','plot','Residential plots'),
('Commercial','commercial','Commercial properties'),
('Office','office','Office spaces') ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO settings (setting_key,setting_value) VALUES
('site_name','RealEstateHub'),
('site_email','admin@realestatehub.com'),
('maintenance','0'),
('email_notifications','1'),
('new_property_notifications','1'),
('new_enquiry_notifications','1') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
