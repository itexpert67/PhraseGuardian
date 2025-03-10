-- Create database tables for text paraphrasing and plagiarism checking application

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  is_subscribed BOOLEAN DEFAULT FALSE NOT NULL,
  paypal_customer_id VARCHAR(255),
  subscription_tier VARCHAR(50) DEFAULT 'free',
  subscription_start DATETIME,
  subscription_end DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Subscription Plans table
CREATE TABLE IF NOT EXISTS subscription_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  interval VARCHAR(50) NOT NULL,
  paypal_plan_id VARCHAR(255),
  features TEXT NOT NULL,
  is_active BOOLEAN DEFAULT TRUE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'usd' NOT NULL,
  status VARCHAR(50) NOT NULL,
  paypal_transaction_id VARCHAR(255),
  paypal_payer_id VARCHAR(255),
  payment_method VARCHAR(50) DEFAULT 'paypal' NOT NULL,
  description TEXT,
  metadata TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  status VARCHAR(50) NOT NULL,
  paypal_subscription_id VARCHAR(255),
  current_period_start DATETIME NOT NULL,
  current_period_end DATETIME NOT NULL,
  cancel_at_period_end BOOLEAN DEFAULT FALSE NOT NULL,
  canceled_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Text processing history table
CREATE TABLE IF NOT EXISTS text_processing_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(255) NOT NULL,
  original_text TEXT NOT NULL,
  processed_text TEXT,
  processing_type VARCHAR(50) NOT NULL,
  style VARCHAR(50),
  plagiarism_percentage INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add indexes for performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_subscription ON users(subscription_tier);
CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_payments_user_id ON payments(user_id);
CREATE INDEX idx_text_processing_history_user_id ON text_processing_history(user_id);
CREATE INDEX idx_text_processing_history_type ON text_processing_history(processing_type);