-- Insert default subscription plans

-- Basic plan (free)
INSERT INTO subscription_plans (name, description, price, interval, features, is_active)
VALUES (
  'Basic',
  'Essential paraphrasing and plagiarism checking',
  0.00,
  'monthly',
  '["20 paraphrasing operations per month", "5 plagiarism checks per month", "Basic styles (Standard, Simple)", "Limited features"]',
  TRUE
);

-- Premium plan
INSERT INTO subscription_plans (name, description, price, interval, paypal_plan_id, features, is_active)
VALUES (
  'Premium',
  'Advanced text processing with unlimited operations',
  9.99,
  'monthly',
  'P-PREMIUM-MONTHLY', -- Replace with your actual PayPal plan ID
  '["Unlimited paraphrasing", "Unlimited plagiarism checks", "All paraphrasing styles", "Priority processing", "No ads", "Download reports"]',
  TRUE
);

-- Professional plan
INSERT INTO subscription_plans (name, description, price, interval, paypal_plan_id, features, is_active)
VALUES (
  'Professional',
  'Complete text processing suite for professionals',
  19.99,
  'monthly',
  'P-PRO-MONTHLY', -- Replace with your actual PayPal plan ID
  '["Everything in Premium", "API access", "Advanced grammar checking", "Custom paraphrasing styles", "Bulk processing", "Document upload (DOCX, PDF)", "Priority support"]',
  TRUE
);