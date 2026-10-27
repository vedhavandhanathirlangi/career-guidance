-- Keeps your existing v1 data and adds what v2 needs.
USE career_guidance_db;

ALTER TABLE careers
    ADD COLUMN work_style VARCHAR(255) NULL,
    ADD COLUMN difficulty VARCHAR(20) NOT NULL DEFAULT 'Beginner';

UPDATE careers SET interest_tags='coding,design,creativity', work_style='remote,independent,team', difficulty='Beginner' WHERE title='Web Developer';
UPDATE careers SET interest_tags='analysis,coding,communication', work_style='remote,team,independent', difficulty='Intermediate' WHERE title='Data Analyst';
UPDATE careers SET interest_tags='design,creativity,communication', work_style='team,remote', difficulty='Beginner' WHERE title='UI/UX Designer';
UPDATE careers SET interest_tags='security,coding,analysis', work_style='team,hands-on', difficulty='Advanced' WHERE title='Cybersecurity Analyst';

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS saved_careers (
    user_id INT NOT NULL,
    career_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, career_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    career_id INT NOT NULL,
    item_type VARCHAR(10) NOT NULL,
    item_key VARCHAR(150) NOT NULL,
    UNIQUE KEY uniq_progress (user_id, career_id, item_type, item_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(10) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempts (scope, ip, attempted_at)
);
