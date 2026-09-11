-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `dating_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dating_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `google_id` VARCHAR(150) DEFAULT NULL UNIQUE,
  `password_hash` VARCHAR(255) NULL,
  `birthdate` DATE DEFAULT '2000-01-01',
  `gender` ENUM('female', 'male', 'nonbinary') DEFAULT 'female',
  `looking_for` ENUM('male', 'female', 'everyone') DEFAULT 'male',
  `bio` TEXT DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `location_city` VARCHAR(100) DEFAULT 'No Location',
  `distance_km` INT DEFAULT 5,
  `avatar_url` VARCHAR(255) DEFAULT 'assets/images/default_avatar.jpg',
  `photos` JSON DEFAULT NULL,
  `interests` JSON DEFAULT NULL,
  `fcm_token` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Swipes Table
CREATE TABLE IF NOT EXISTS `swipes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `swiper_id` INT NOT NULL,
  `target_id` INT NOT NULL,
  `swipe_type` ENUM('like', 'dislike', 'superlike') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_swipe` (`swiper_id`, `target_id`),
  FOREIGN KEY (`swiper_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Matches Table
CREATE TABLE IF NOT EXISTS `matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user1_id` INT NOT NULL,
  `user2_id` INT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
  `requested_by` INT DEFAULT NULL,
  `matched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_match` (`user1_id`, `user2_id`),
  FOREIGN KEY (`user1_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user2_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Messages Table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message_text` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. User Preferences Table
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `min_age` INT DEFAULT 18,
  `max_age` INT DEFAULT 35,
  `max_distance` INT DEFAULT 50,
  `gender_preference` ENUM('male', 'female', 'everyone') DEFAULT 'everyone',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo Seed Users Data for Instant Login & Testing
INSERT IGNORE INTO `users` (`id`, `full_name`, `email`, `password_hash`, `birthdate`, `gender`, `looking_for`, `bio`, `occupation`, `location_city`, `distance_km`, `avatar_url`, `interests`) VALUES
(1, 'Sophia Miller', 'sophia@example.com', '$2y$10$wT.L56E7t0R3bW8k3N9k1e.1P.2Y.3X.4Z.5A.6B.7C.8D.9E.0F', '2001-05-14', 'female', 'male', 'Coffee enthusiast, UX designer & traveler ☕🎨', 'UI/UX Designer', 'Mumbai', 3, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80', '["Coffee", "Travel", "Music"]'),
(2, 'Aarav Sharma', 'aarav@example.com', '$2y$10$wT.L56E7t0R3bW8k3N9k1e.1P.2Y.3X.4Z.5A.6B.7C.8D.9E.0F', '1998-09-20', 'male', 'female', 'Tech entrepreneur, fitness fanatic & foodie 🚀🏋️‍♂️', 'Software Engineer', 'Delhi', 5, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80', '["Fitness", "Tech", "Cooking"]'),
(3, 'Ananya Roy', 'ananya@example.com', '$2y$10$wT.L56E7t0R3bW8k3N9k1e.1P.2Y.3X.4Z.5A.6B.7C.8D.9E.0F', '2000-02-18', 'female', 'male', 'Art lover, beach lover & dog mom 🐶🏖️', 'Fashion Stylist', 'Bangalore', 8, 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=600&q=80', '["Art", "Dogs", "Photography"]');

INSERT IGNORE INTO `user_preferences` (`user_id`, `min_age`, `max_age`, `max_distance`, `gender_preference`) VALUES
(1, 18, 35, 50, 'everyone'),
(2, 18, 35, 50, 'everyone'),
(3, 18, 35, 50, 'everyone');
