-- ============================================
-- MyCampus Database – Complete Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS mycampus_db;
USE mycampus_db;

-- 1. Students
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(50) UNIQUE NOT NULL,
    real_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    class VARCHAR(50) NOT NULL,
    year VARCHAR(20) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    bio VARCHAR(250) DEFAULT NULL,
    gender VARCHAR(10) DEFAULT NULL,
    account_status ENUM('active', 'inactive') DEFAULT 'active',
    privacy ENUM('public', 'private') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_barcode (barcode),
    INDEX idx_username (username),
    INDEX idx_class (class)
);

-- 2. Departments (for QR scanning)
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) UNIQUE NOT NULL,
    dept_code VARCHAR(20) UNIQUE NOT NULL,
    qr_code_path VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Events (for sliding banners)
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type ENUM('academic', 'sports', 'cultural', 'exam', 'scholarship', 'general') NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    venue VARCHAR(200),
    image_url VARCHAR(255),          -- URL/path to event image
    link VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- 4. Posts (with media column for multiple files)
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    content TEXT,
    media TEXT,                      -- Comma-separated filenames (images/videos)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
);

-- 5. Post Media (optional – keep for backward compatibility, but media column is preferred)
CREATE TABLE post_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    media_url VARCHAR(255) NOT NULL,
    media_thumbnail VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post (post_id)
);

-- 6. Likes
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, student_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_post (post_id)
);

-- 7. Comments (with reply support)
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    student_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_post (post_id)
);

-- 8. Follows
CREATE TABLE follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
);

-- 9. Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    from_student_id INT,
    notification_type ENUM('like', 'comment', 'follow', 'share', 'event', 'system') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (from_student_id) REFERENCES students(id) ON DELETE SET NULL,
    INDEX idx_student_read (student_id, is_read)
);

-- 10. Attendance Records (QR scan)
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    department_id INT NOT NULL,
    entry_date DATE NOT NULL,
    entry_time TIME NOT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_attendance (student_id, department_id, entry_date),
    INDEX idx_student_date (student_id, entry_date)
);

-- 11. Leaderboard (aggregated points)
CREATE TABLE leaderboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    department VARCHAR(50) NOT NULL,
    total_points INT DEFAULT 0,
    events_participated INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_points (total_points)
);

-- 12. Chats (conversations)
CREATE TABLE chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message TEXT,
    last_message_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_users (user1_id, user2_id)
);

-- 13. Messages
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT,
    media_type ENUM('none','image','video') DEFAULT 'none',
    media_url VARCHAR(255),
    is_seen BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_chat (chat_id),
    INDEX idx_seen (is_seen)
);

-- 14. Blocks
CREATE TABLE blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 15. Admins
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 16. Activity Logs (for admin monitoring)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_admin (admin_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- ============================================
-- Insert Sample Data
-- ============================================

-- Departments (QR codes)
INSERT INTO departments (dept_name, dept_code, qr_code_path) VALUES
('Marathi', 'MAR', 'qr_codes/mar.png'),
('English', 'ENG', 'qr_codes/eng.png'),
('Geography', 'GEO', 'qr_codes/geo.png'),
('Math', 'MATH', 'qr_codes/math.png'),
('Sports', 'SPT', 'qr_codes/spt.png'),
('Library', 'LIB', 'qr_codes/lib.png'),
('BBA(CA)', 'BBACA', 'qr_codes/bbaca.png'),
('BBA', 'BBA', 'qr_codes/bba.png'),
('BSC(CS)', 'BSC_CS', 'qr_codes/bsc_cs.png'),
('Bcom', 'BCOM', 'qr_codes/bcom.png'),
('BA', 'BA', 'qr_codes/ba.png'),
('BSC', 'BSC', 'qr_codes/bsc.png'),
('PHYSIC Lab', 'PHY_LAB', 'qr_codes/phy_lab.png'),
('BIO Lab', 'BIO_LAB', 'qr_codes/bio_lab.png'),
('CHEMISTRY Lab', 'CHEM_LAB', 'qr_codes/chem_lab.png'),
('BOTANY Lab', 'BOT_LAB', 'qr_codes/bot_lab.png'),
('BCA Lab', 'BCA_LAB', 'qr_codes/bca_lab.png'),
('BCS Lab', 'BCS_LAB', 'qr_codes/bcs_lab.png'),
('Carrier Katta', 'CAREER', 'qr_codes/career.png'),
('NCC', 'NCC', 'qr_codes/ncc.png'),
('NSS', 'NSS', 'qr_codes/nss.png');

-- Admin user (password: Admin@123)
INSERT INTO admins (username, email, password, full_name, role) 
VALUES ('admin', 'admin@mycampus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin');

-- Sample events (for carousel)
INSERT INTO events (title, description, event_type, event_date, event_time, venue, image_url) VALUES
('Annual Sports Meet 2024', 'Inter-department sports competition. Participate and win prizes!', 'sports', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'College Ground', NULL),
('Tech Fest 2024', 'Hackathon, coding competition, and tech exhibitions.', 'cultural', DATE_ADD(CURDATE(), INTERVAL 10 DAY), '10:00:00', 'Main Auditorium', NULL),
('Semester Exams', 'End semester examinations for all departments.', 'exam', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:30:00', 'Exam Halls', NULL),
('Scholarship Deadline', 'Merit scholarship applications closing soon.', 'scholarship', DATE_ADD(CURDATE(), INTERVAL 20 DAY), '17:00:00', 'Admin Office', NULL),
('Cultural Night', 'Music, dance, and drama performances.', 'cultural', DATE_ADD(CURDATE(), INTERVAL 15 DAY), '18:00:00', 'Open Air Theatre', NULL);

-- ============================================
-- Triggers for automatic updates
-- ============================================

DELIMITER //

-- Update leaderboard after attendance
CREATE TRIGGER update_leaderboard_after_attendance
AFTER INSERT ON attendance_records
FOR EACH ROW
BEGIN
    DECLARE student_dept VARCHAR(50);
    DECLARE total_pts INT;
    DECLARE total_events INT;
    SELECT department INTO student_dept FROM students WHERE id = NEW.student_id;
    SELECT COUNT(*) INTO total_events FROM attendance_records WHERE student_id = NEW.student_id;
    SET total_pts = total_events * 10;
    INSERT INTO leaderboard (student_id, department, total_points, events_participated)
    VALUES (NEW.student_id, student_dept, total_pts, total_events)
    ON DUPLICATE KEY UPDATE
    total_points = total_pts,
    events_participated = total_events;
END//

-- Notification on like
CREATE TRIGGER create_notification_on_like
AFTER INSERT ON likes
FOR EACH ROW
BEGIN
    DECLARE post_owner INT;
    DECLARE liker_name VARCHAR(100);
    SELECT student_id INTO post_owner FROM posts WHERE id = NEW.post_id;
    SELECT real_name INTO liker_name FROM students WHERE id = NEW.student_id;
    IF post_owner != NEW.student_id THEN
        INSERT INTO notifications (student_id, from_student_id, notification_type, title, message, link)
        VALUES (post_owner, NEW.student_id, 'like', 'New Like', 
                CONCAT(liker_name, ' liked your post'), 
                CONCAT('../index.php?post=', NEW.post_id));
    END IF;
END//

-- Notification on comment
CREATE TRIGGER create_notification_on_comment
AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    DECLARE post_owner INT;
    DECLARE commenter_name VARCHAR(100);
    SELECT student_id INTO post_owner FROM posts WHERE id = NEW.post_id;
    SELECT real_name INTO commenter_name FROM students WHERE id = NEW.student_id;
    IF post_owner != NEW.student_id THEN
        INSERT INTO notifications (student_id, from_student_id, notification_type, title, message, link)
        VALUES (post_owner, NEW.student_id, 'comment', 'New Comment', 
                CONCAT(commenter_name, ' commented: ', SUBSTRING(NEW.comment, 1, 50)), 
                CONCAT('../index.php?post=', NEW.post_id));
    END IF;
END//

-- Notification on follow
CREATE TRIGGER create_notification_on_follow
AFTER INSERT ON follows
FOR EACH ROW
BEGIN
    DECLARE follower_name VARCHAR(100);
    SELECT real_name INTO follower_name FROM students WHERE id = NEW.follower_id;
    IF NEW.follower_id != NEW.following_id THEN
        INSERT INTO notifications (student_id, from_student_id, notification_type, title, message, link)
        VALUES (NEW.following_id, NEW.follower_id, 'follow', 'New Follower', 
                CONCAT(follower_name, ' started following you'), 
                CONCAT('../profile.php?user_id=', NEW.follower_id));
    END IF;
END//

DELIMITER ;

-- ============================================
-- Views for convenience
-- ============================================

CREATE VIEW posts_with_counts AS
SELECT p.*, 
       (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
FROM posts p;

CREATE VIEW leaderboard_full AS
SELECT l.*, s.real_name, s.username, s.profile_pic, s.class
FROM leaderboard l
JOIN students s ON l.student_id = s.id
ORDER BY l.total_points DESC;

-- ============================================
-- Additional ALTER statements (for later features)
-- ============================================

-- Ensure posts table has the 'media' column (store multiple files as CSV)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS media TEXT AFTER content;

-- Add foreign key for events.created_by if not exists (optional)
ALTER TABLE events ADD CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL;

-- Add index to speed up notifications query
ALTER TABLE notifications ADD INDEX idx_created (created_at);

-- For better performance on posts feed
ALTER TABLE posts ADD INDEX idx_student_created (student_id, created_at);

-- If you want to store the default 'default-avatar.png' for students
ALTER TABLE students ALTER COLUMN profile_pic SET DEFAULT 'default-avatar.png';