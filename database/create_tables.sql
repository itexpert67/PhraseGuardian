-- Database creation script for Text Processor
-- This script creates the database and all required tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS text_processor;

-- Use the database
USE text_processor;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_subscribed BOOLEAN DEFAULT 0,
  stripe_customer_id VARCHAR(100),
  subscription_tier VARCHAR(50) DEFAULT 'Basic',
  subscription_start DATETIME,
  subscription_end DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Subscription plans table
CREATE TABLE IF NOT EXISTS subscription_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10, 2) NOT NULL,
  interval VARCHAR(20) NOT NULL, -- 'monthly', 'yearly'
  stripe_price_id VARCHAR(100),
  features JSON,  -- Store features as JSON array
  is_active BOOLEAN DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  stripe_subscription_id VARCHAR(100),
  status VARCHAR(20) NOT NULL, -- 'active', 'canceled', 'expired'
  current_period_start DATETIME NOT NULL,
  current_period_end DATETIME NOT NULL,
  cancel_at_period_end BOOLEAN DEFAULT 0,
  canceled_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subscription_id INT,
  amount DECIMAL(10, 2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'USD',
  stripe_payment_id VARCHAR(100),
  payment_method VARCHAR(50),
  status VARCHAR(20) NOT NULL, -- 'succeeded', 'pending', 'failed'
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
);

-- Text processing history table
CREATE TABLE IF NOT EXISTS text_processing_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(255),
  original_text TEXT NOT NULL,
  processed_text TEXT,
  processing_type VARCHAR(20) NOT NULL, -- 'paraphrased', 'plagiarism'
  style VARCHAR(50), -- Style used for paraphrasing
  plagiarism_percentage DECIMAL(5, 2),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Plagiarism results table
CREATE TABLE IF NOT EXISTS plagiarism_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  history_id INT NOT NULL,
  total_percentage DECIMAL(5, 2) NOT NULL,
  matches_json JSON,  -- Store match details as JSON
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (history_id) REFERENCES text_processing_history(id) ON DELETE CASCADE
);

-- Plagiarism sources table (for sample sources to check against)
CREATE TABLE IF NOT EXISTS plagiarism_sources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  source_name VARCHAR(255) NOT NULL,
  source_url VARCHAR(255),
  source_text TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default subscription plans
INSERT INTO subscription_plans (name, description, price, interval, features, is_active) VALUES
('Basic', 'Free tier with limited features', 0.00, 'monthly', '["Limited paraphrasing (20/month)", "Limited plagiarism checks (5/month)", "Basic customer support"]', 1),
('Premium', 'Advanced features for regular users', 9.99, 'monthly', '["Unlimited paraphrasing", "Unlimited plagiarism checks", "Priority support", "Multiple paraphrasing styles", "Advanced plagiarism detection"]', 1),
('Professional', 'Complete solution for professional writers', 19.99, 'monthly', '["Unlimited paraphrasing", "Unlimited plagiarism checks", "Premium support", "All paraphrasing styles", "Advanced plagiarism detection", "Batch processing", "API access"]', 1);

-- Insert sample plagiarism sources for testing
INSERT INTO plagiarism_sources (source_name, source_url, source_text) VALUES
('Wikipedia - Artificial Intelligence', 'https://en.wikipedia.org/wiki/Artificial_intelligence', 'Artificial intelligence (AI) is intelligence demonstrated by machines, as opposed to intelligence displayed by humans or other animals. Example tasks in which this is done include speech recognition, computer vision, translation between (natural) languages, as well as other mappings of inputs.'),
('Research Paper - Machine Learning', 'https://example.com/research/machine-learning', 'Machine learning is a field of inquiry devoted to understanding and building methods that ''learn'', that is, methods that leverage data to improve performance on some set of tasks. It is seen as a part of artificial intelligence.'),
('Academic Journal - Deep Learning', 'https://example.com/journal/deep-learning', 'Deep learning is part of a broader family of machine learning methods based on artificial neural networks with representation learning. Learning can be supervised, semi-supervised or unsupervised.');