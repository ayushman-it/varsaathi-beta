-- ========================================================
-- VARSAATHI MATRIMONY & DATING - COMPLETE DATABASE SCHEMA
-- Execute this SQL script in phpMyAdmin / MySQL Workbench
-- Target Database: u325640649_varsaathi (or your MySQL database)
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Core Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `google_id` VARCHAR(150) DEFAULT NULL UNIQUE,
  `password_hash` VARCHAR(255) NULL DEFAULT NULL,
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
  `is_private` TINYINT(1) DEFAULT 0,
  `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Saathi Matrimonial Profiles Table
CREATE TABLE IF NOT EXISTS `saathi_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `status` ENUM('draft', 'published', 'paused') DEFAULT 'draft',
  `created_by` VARCHAR(50) DEFAULT 'Self',
  `headline` VARCHAR(255) DEFAULT NULL,
  `marital_status` VARCHAR(50) DEFAULT 'Never Married',
  `have_children` VARCHAR(50) DEFAULT 'No',
  `height_cm` INT DEFAULT 165,
  `weight_kg` INT DEFAULT NULL,
  `body_type` VARCHAR(50) DEFAULT 'Average',
  `complexion` VARCHAR(50) DEFAULT 'Fair',
  `mother_tongue` VARCHAR(100) DEFAULT 'Hindi',
  `languages_spoken` TEXT DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT 'Chhattisgarh',
  `country` VARCHAR(100) DEFAULT 'India',
  `highest_qualification` VARCHAR(150) DEFAULT 'Bachelor Degree',
  `degree` VARCHAR(150) DEFAULT NULL,
  `specialization` VARCHAR(150) DEFAULT NULL,
  `college` VARCHAR(150) DEFAULT NULL,
  `occupation_type` VARCHAR(100) DEFAULT 'Private Job',
  `company_name` VARCHAR(150) DEFAULT NULL,
  `annual_income` VARCHAR(100) DEFAULT 'Prefer not to say',
  `religion` VARCHAR(100) DEFAULT NULL,
  `caste_community` VARCHAR(100) DEFAULT NULL,
  `sub_caste` VARCHAR(100) DEFAULT NULL,
  `gotra` VARCHAR(100) DEFAULT NULL,
  `manglik_status` VARCHAR(50) DEFAULT 'Prefer not to say',
  `rashi` VARCHAR(100) DEFAULT NULL,
  `nakshatra` VARCHAR(100) DEFAULT NULL,
  `birth_time` VARCHAR(50) DEFAULT NULL,
  `birth_place` VARCHAR(150) DEFAULT NULL,
  `family_type` VARCHAR(50) DEFAULT 'Nuclear Family',
  `family_values` VARCHAR(50) DEFAULT 'Moderate',
  `family_status` VARCHAR(50) DEFAULT 'Middle Class',
  `family_location` VARCHAR(150) DEFAULT NULL,
  `father_occupation` VARCHAR(150) DEFAULT NULL,
  `mother_occupation` VARCHAR(150) DEFAULT NULL,
  `brothers_count` INT DEFAULT 0,
  `brothers_married` INT DEFAULT 0,
  `sisters_count` INT DEFAULT 0,
  `sisters_married` INT DEFAULT 0,
  `own_house` VARCHAR(20) DEFAULT 'No',
  `own_car` VARCHAR(20) DEFAULT 'No',
  `diet` VARCHAR(50) DEFAULT 'Vegetarian',
  `smoking` VARCHAR(50) DEFAULT 'No',
  `drinking` VARCHAR(50) DEFAULT 'No',
  `fitness` VARCHAR(50) DEFAULT 'Occasionally',
  `partner_min_age` INT DEFAULT 18,
  `partner_max_age` INT DEFAULT 45,
  `partner_min_height` INT DEFAULT 140,
  `partner_max_height` INT DEFAULT 210,
  `partner_marital_status` VARCHAR(255) DEFAULT 'Any',
  `partner_religion` VARCHAR(255) DEFAULT 'Any',
  `partner_community` VARCHAR(255) DEFAULT 'Any',
  `partner_education` VARCHAR(255) DEFAULT 'Any',
  `partner_occupation` VARCHAR(255) DEFAULT 'Any',
  `partner_location` VARCHAR(255) DEFAULT 'Any',
  `marriage_timeline` VARCHAR(100) DEFAULT 'Within 1 Year',
  `partner_expectations` TEXT DEFAULT NULL,
  `is_paused` TINYINT(1) DEFAULT 0,
  `is_published` TINYINT(1) DEFAULT 0,
  `completion_pct` INT DEFAULT 0,
  `privacy_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Swipes & Interest Table
CREATE TABLE IF NOT EXISTS `swipes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `swiper_id` INT NOT NULL,
  `target_id` INT NOT NULL,
  `swipe_type` ENUM('like', 'dislike', 'superlike') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_swipe` (`swiper_id`, `target_id`),
  FOREIGN KEY (`swiper_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Matches & Connections Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Messages Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. WebRTC Call Signals Table
CREATE TABLE IF NOT EXISTS `call_signals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT NOT NULL,
  `caller_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `call_type` ENUM('audio', 'video') NOT NULL DEFAULT 'video',
  `peer_id` VARCHAR(100) NOT NULL,
  `status` ENUM('calling', 'accepted', 'rejected', 'ended') NOT NULL DEFAULT 'calling',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_match` (`match_id`),
  INDEX `idx_receiver` (`receiver_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User Discovery Preferences Table
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `min_age` INT DEFAULT 18,
  `max_age` INT DEFAULT 45,
  `max_distance` INT DEFAULT 50,
  `gender_preference` ENUM('male', 'female', 'everyone') DEFAULT 'everyone',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. AI Recommendation Cache Table
CREATE TABLE IF NOT EXISTS `saathi_ai_cache` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `candidate_id` INT NOT NULL,
  `score` INT NOT NULL,
  `confidence` VARCHAR(50) DEFAULT 'high',
  `reasons_json` JSON NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_cache` (`user_id`, `candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
