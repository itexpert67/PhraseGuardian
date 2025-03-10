-- SQL Script to insert default subscription plans in phpMyAdmin

-- Insert Basic (Free) Plan
INSERT INTO `subscription_plans` 
(`name`, `description`, `price`, `interval`, `features`, `is_active`) 
VALUES 
('Basic', 'Essential paraphrasing and plagiarism checking', 0.00, 'monthly', 
'["20 paraphrasing operations per month", "5 plagiarism checks per month", "Basic styles (Standard, Simple)", "Limited features"]', 
1);

-- Insert Premium Plan
INSERT INTO `subscription_plans` 
(`name`, `description`, `price`, `interval`, `features`, `is_active`) 
VALUES 
('Premium', 'Advanced text processing with unlimited operations', 9.99, 'monthly', 
'["Unlimited paraphrasing", "Unlimited plagiarism checks", "All paraphrasing styles", "Priority processing", "No ads", "Download reports"]', 
1);

-- Insert Professional Plan
INSERT INTO `subscription_plans` 
(`name`, `description`, `price`, `interval`, `features`, `is_active`) 
VALUES 
('Professional', 'Complete text processing suite for professionals', 19.99, 'monthly', 
'["Everything in Premium", "API access", "Advanced grammar checking", "Custom paraphrasing styles", "Bulk processing", "Document upload (DOCX, PDF)", "Priority support"]', 
1);