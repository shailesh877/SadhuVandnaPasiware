-- 🔥 SADHU VANDANA DATABASE SCHEMA UPDATES 🔥
-- Copy and run these queries in your MySQL (phpMyAdmin) to ensure all features work correctly.

-- 1. Table for Blocked Users (Upgraded with Platform Support)
CREATE TABLE IF NOT EXISTS tbl_blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    chat_platform ENUM('marriage', 'community') DEFAULT 'marriage',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block_platform (blocker_id, blocked_id, chat_platform)
);

-- 2. Table for Audio/Video Calls (Agora Support)
CREATE TABLE IF NOT EXISTS tbl_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller_id INT NOT NULL,
    receiver_id INT NOT NULL,
    type VARCHAR(10) NOT NULL DEFAULT 'video',
    status VARCHAR(20) NOT NULL DEFAULT 'ringing', 
    caller_peer_id VARCHAR(100) NOT NULL,
    chat_platform ENUM('marriage', 'community') DEFAULT 'marriage',
    duration INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Table for Friend Requests / Followers (Community Mode)
CREATE TABLE IF NOT EXISTS tbl_followers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    status ENUM('pending', 'accepted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES tbl_members(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES tbl_members(id) ON DELETE CASCADE
);

-- 4. Adding Platform Support to Messages
ALTER TABLE tbl_messages ADD COLUMN IF NOT EXISTS is_community TINYINT(1) DEFAULT 0;
ALTER TABLE tbl_messages ADD COLUMN IF NOT EXISTS chat_platform ENUM('marriage', 'community') DEFAULT 'marriage';

-- 5. Table for Real-time Typing Indicators
CREATE TABLE IF NOT EXISTS tbl_typing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    target_profile_id INT NOT NULL,
    is_typing TINYINT(1) DEFAULT 0,
    chat_platform ENUM('marriage', 'community') DEFAULT 'marriage',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ux_pair_platform (profile_id, target_profile_id, chat_platform)
);
