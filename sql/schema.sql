-- piggyquest schema for DbGate / Navicat
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) DEFAULT 'user',
  banned TINYINT(1) DEFAULT 0,
  ban_reason VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS players (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  level INT NOT NULL DEFAULT 1,
  exp INT NOT NULL DEFAULT 0,
  coin INT NOT NULL DEFAULT 0,
  last_feed_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pigs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  player_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  last_fed_at DATETIME NULL,
  FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS foods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NOT NULL DEFAULT 'Food'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- user inventory of food (per player)
CREATE TABLE IF NOT EXISTS user_food (
  player_id INT NOT NULL,
  food_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 0,
  PRIMARY KEY (player_id, food_id),
  FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
  FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY,
  feed_cooldown INT NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_quests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  player_id INT NOT NULL,
  quest_key VARCHAR(50) NOT NULL,
  date DATE NOT NULL,
  progress INT NOT NULL DEFAULT 0,
  goal INT NOT NULL,
  reward_coin INT NOT NULL,
  claimed TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
  INDEX(player_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feed_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  player_id INT NOT NULL,
  pig_id INT NOT NULL,
  food_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX(player_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seed foods and settings
INSERT INTO foods (id, name, category) VALUES (1, 'Apple', 'Food'), (2, 'Corn', 'Food'), (3, 'Carrot', 'Food')
  ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO settings (id, feed_cooldown) VALUES (1, 10)
  ON DUPLICATE KEY UPDATE feed_cooldown=VALUES(feed_cooldown);
