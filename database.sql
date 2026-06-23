-- ============================================================
-- 3ETube Database Schema (FINAL)
-- Universitas Bumigora | Kelompok 3 Kelas E
-- ============================================================

CREATE DATABASE IF NOT EXISTS `3etube` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `3etube`;

CREATE TABLE `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)  NOT NULL UNIQUE,
  `email`      VARCHAR(100) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `full_name`  VARCHAR(100) DEFAULT NULL,
  `avatar`     VARCHAR(255) DEFAULT NULL,
  `bio`        TEXT         DEFAULT NULL,
  `role`       ENUM('user','admin') DEFAULT 'user',
  `is_active`  TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `categories` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `slug` VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE `genres` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `slug` VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE `videos` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `title`        VARCHAR(200) NOT NULL,
  `slug`         VARCHAR(220) NOT NULL UNIQUE,
  `description`  TEXT DEFAULT NULL,
  `video_url`    VARCHAR(255) NOT NULL,
  `thumbnail`    VARCHAR(255) DEFAULT NULL,
  `duration_sec` INT NOT NULL DEFAULT 0,
  `category_id`  INT DEFAULT NULL,
  `views`        INT DEFAULT 0,
  `status`       ENUM('live','draft','banned') DEFAULT 'draft',
  `uploaded_by`  INT DEFAULT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `video_genres` (
  `video_id` INT NOT NULL,
  `genre_id` INT NOT NULL,
  PRIMARY KEY (`video_id`,`genre_id`),
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`genre_id`) REFERENCES `genres`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `ratings` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` INT NOT NULL,
  `user_id`  INT NOT NULL,
  `score`    TINYINT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_rating` (`video_id`,`user_id`),
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `comments` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `video_id`   INT DEFAULT NULL,
  `user_id`    INT NOT NULL,
  `parent_id`  INT DEFAULT NULL,
  `body`       TEXT NOT NULL,
  `likes`      INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`video_id`)  REFERENCES `videos`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `posts` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `video_id`   INT DEFAULT NULL,
  `body`       TEXT NOT NULL,
  `likes`      INT DEFAULT 0,
  `reposts`    INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `post_replies` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT NOT NULL,
  `user_id`    INT NOT NULL,
  `body`       TEXT NOT NULL,
  `likes`      INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `saved_posts` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`  INT NOT NULL,
  `post_id`  INT NOT NULL,
  `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_saved_post` (`user_id`,`post_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `actor_id`   INT DEFAULT NULL,
  `type`       ENUM('reply','like','comment','system') DEFAULT 'system',
  `post_id`    INT DEFAULT NULL,
  `video_id`   INT DEFAULT NULL,
  `message`    VARCHAR(255) NOT NULL,
  `is_read`    TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`actor_id`) REFERENCES `users`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`post_id`)  REFERENCES `posts`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `watch_history` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `video_id`     INT NOT NULL,
  `progress_sec` INT DEFAULT 0,
  `watched_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_history` (`user_id`,`video_id`),
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `saved_videos` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`  INT NOT NULL,
  `video_id` INT NOT NULL,
  `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_saved` (`user_id`,`video_id`),
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `video_likes` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`  INT NOT NULL,
  `video_id` INT NOT NULL,
  UNIQUE KEY `uq_vlike` (`user_id`,`video_id`),
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- password semua akun: password
INSERT INTO `users` (`username`,`email`,`password`,`full_name`,`role`) VALUES
('admin','admin@3etube.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrator','admin'),
('nata_aykal','nata@3etube.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Nata Aykal','user'),
('rendi_d','rendi@3etube.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Rendi D','user'),
('fariz_a','fariz@3etube.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Fariz A','user'),
('sari_k','sari@3etube.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Sari K','user');

INSERT INTO `categories` (`name`,`slug`) VALUES
('Documentary','documentary'),('Drama','drama'),('Short Film','short-film'),
('Series','series'),('Action','action'),('Nature','nature');

INSERT INTO `genres` (`name`,`slug`) VALUES
('Romance','romance'),('Thriller','thriller'),('Comedy','comedy'),('Horror','horror'),
('Sci-Fi','sci-fi'),('Mystery','mystery'),('Adventure','adventure'),('Crime','crime');

INSERT INTO `videos` (`title`,`slug`,`description`,`video_url`,`thumbnail`,`duration_sec`,`category_id`,`views`,`status`,`uploaded_by`) VALUES
('The Last Voyage','the-last-voyage','An epic journey across uncharted waters. A crew of unlikely heroes faces impossible odds.','uploads/videos/last_voyage.mp4',NULL,6720,2,2400000,'live',1),
('Ocean Deep Documentary','ocean-deep-documentary','Explore the deepest parts of the ocean and the creatures that live there.','uploads/videos/ocean_deep.mp4',NULL,4320,1,1100000,'live',1),
('Neon Nights Series Ep.1','neon-nights-ep1','A neo-noir urban series set in 2040.','uploads/videos/neon_nights_e1.mp4',NULL,2760,4,980000,'live',1),
('Neon Nights Series Ep.2','neon-nights-ep2','Episode 2 — The underground syndicate revealed.','uploads/videos/neon_nights_e2.mp4',NULL,2880,4,870000,'live',1),
('Midnight in the City','midnight-in-the-city','A drama unfolding over one sleepless night.','uploads/videos/midnight.mp4',NULL,6300,2,2100000,'live',1),
('Roots and Branches','roots-and-branches','Documentary on ancestral heritage.','uploads/videos/roots.mp4',NULL,3480,1,1800000,'live',1),
('Edge of Tomorrow','edge-of-tomorrow','Action-packed sci-fi thriller.','uploads/videos/edge.mp4',NULL,7800,5,3200000,'live',1),
('The Silent Forest','the-silent-forest','A quiet drama set deep in old-growth forest.','uploads/videos/silent_forest.mp4',NULL,4920,3,980000,'live',1),
('Grand Canyon Above and Below','grand-canyon','Cinematic exploration of the Grand Canyon.','uploads/videos/grand_canyon.mp4',NULL,4380,6,680000,'live',1),
('Parallel Lives','parallel-lives','Two strangers whose lives mirror each other.','uploads/videos/parallel.mp4',NULL,5760,2,540000,'live',1),
('Deep Sea Wonders','deep-sea-wonders','Rare creatures of the deep sea.','uploads/videos/deep_sea.mp4',NULL,4140,6,420000,'live',1),
('Coral Reef Life Under Threat','coral-reef','Scientists race to save coral reefs.','uploads/videos/coral.mp4',NULL,2640,1,380000,'live',1),
('Quiet Storm Short Film','quiet-storm','A short film about unexpected calm.','uploads/videos/quiet_storm.mp4',NULL,1980,3,540000,'live',1),
('Lost in Translation City','lost-in-translation-city','Drama set across language barriers.','uploads/videos/lost.mp4',NULL,5400,2,760000,'live',1),
('Wildfire Season','wildfire-season','Documentary on the global wildfire crisis.','uploads/videos/wildfire.mp4',NULL,4260,1,310000,'live',1),
('The Architecture of Light','architecture-light','Exploring how light shapes great buildings.','uploads/videos/arch_light.mp4',NULL,3720,1,290000,'live',1),
('Beneath the Surface','beneath-surface','A psychological short drama.','uploads/videos/beneath.mp4',NULL,2520,3,220000,'live',1),
('City Without Sleep','city-without-sleep','Urban life documentary — 24 hours in the city.','uploads/videos/city.mp4',NULL,4680,1,410000,'live',1),
('The Heist','the-heist','A gripping action series premiere.','uploads/videos/heist.mp4',NULL,3300,5,550000,'live',1),
('Northern Lights Chase','northern-lights','Chasing aurora borealis across Scandinavia.','uploads/videos/northern.mp4',NULL,3960,6,620000,'live',1),
('After the Rain','after-the-rain','A quiet story of rebuilding after loss.','uploads/videos/after_rain.mp4',NULL,5100,2,340000,'live',1),
('Desert Crossing','desert-crossing','One man, one motorcycle, across the Sahara.','uploads/videos/desert.mp4',NULL,4500,1,480000,'live',1),
('Neon Nights Ep.7 Finale','neon-nights-ep7','Season finale — the truth comes out.','uploads/videos/neon_nights_e7.mp4',NULL,3120,4,1600000,'draft',1);

INSERT INTO `video_genres` (`video_id`,`genre_id`) VALUES
(1,1),(1,7),(3,4),(3,6),(4,8),(4,6),(7,5),(7,2),(10,1),(10,6),(19,8),(19,2);

INSERT INTO `ratings` (`video_id`,`user_id`,`score`) VALUES
(1,2,5),(1,3,4),(1,4,5),(2,2,4),(2,5,5),(5,3,4),(5,4,3),
(7,2,5),(7,3,5),(7,4,4),(7,5,5),(10,2,3),(10,5,4);

INSERT INTO `comments` (`video_id`,`user_id`,`body`,`likes`) VALUES
(1,3,'This film changed my perspective completely. The cinematography is incredible.',2100),
(1,4,'Finally a film with a proper ending. 10/10 recommend.',841),
(1,2,'The lighthouse scene symbolism is mind-blowing.',309);

INSERT INTO `posts` (`user_id`,`video_id`,`body`,`likes`,`reposts`) VALUES
(3,1,'Just finished The Last Voyage for the 3rd time and I still cry at the ending. Drop your fav scene below!',2100,410),
(2,1,'Anyone noticed the hidden symbolism in the lighthouse scene? Full breakdown thread below.',830,220),
(4,NULL,'Anyone have a list of all the 3ETube documentaries sorted by rating? On a documentary marathon this week.',214,88),
(5,7,'Edge of Tomorrow deserves way more views than it has. The third act twist is insane.',560,140);

INSERT INTO `post_replies` (`post_id`,`user_id`,`body`,`likes`) VALUES
(1,2,'Literally same. The score during that scene destroyed me.',98),
(2,4,'Wait I never caught that, rewatching tonight.',44);

INSERT INTO `notifications` (`user_id`,`actor_id`,`type`,`post_id`,`message`) VALUES
(3,2,'reply',1,'Rendi_D membalas post kamu'),
(2,4,'reply',2,'Fariz_A membalas post kamu');
