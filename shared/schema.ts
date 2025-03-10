import { pgTable, text, serial, integer, boolean, timestamp, decimal } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

// Users schema
export const users = pgTable("users", {
  id: serial("id").primaryKey(),
  username: text("username").notNull().unique(),
  password: text("password").notNull(),
  email: text("email"),
  isSubscribed: boolean("is_subscribed").default(false).notNull(),
  stripeCustomerId: text("stripe_customer_id"),
  subscriptionTier: text("subscription_tier").default("free"),
  subscriptionStart: timestamp("subscription_start"),
  subscriptionEnd: timestamp("subscription_end"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

// Subscription Plans
export const subscriptionPlans = pgTable("subscription_plans", {
  id: serial("id").primaryKey(),
  name: text("name").notNull(),
  description: text("description").notNull(),
  price: decimal("price", { precision: 10, scale: 2 }).notNull(),
  interval: text("interval").notNull(), // monthly, yearly
  stripePriceId: text("stripe_price_id"),
  features: text("features").notNull(), // JSON stringified array of features
  isActive: boolean("is_active").default(true).notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const insertPlanSchema = createInsertSchema(subscriptionPlans).omit({
  id: true,
  createdAt: true,
});

export type InsertPlan = z.infer<typeof insertPlanSchema>;
export type SubscriptionPlan = typeof subscriptionPlans.$inferSelect;

// Payment Records
export const payments = pgTable("payments", {
  id: serial("id").primaryKey(),
  userId: integer("user_id").references(() => users.id).notNull(),
  amount: decimal("amount", { precision: 10, scale: 2 }).notNull(),
  currency: text("currency").default("usd").notNull(),
  status: text("status").notNull(), // succeeded, pending, failed
  stripePaymentId: text("stripe_payment_id"),
  stripeInvoiceId: text("stripe_invoice_id"),
  paymentMethod: text("payment_method").notNull(),
  description: text("description"),
  metadata: text("metadata"), // JSON stringified additional data
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const insertPaymentSchema = createInsertSchema(payments).omit({
  id: true,
  createdAt: true,
});

export type InsertPayment = z.infer<typeof insertPaymentSchema>;
export type Payment = typeof payments.$inferSelect;

// Subscriptions
export const subscriptions = pgTable("subscriptions", {
  id: serial("id").primaryKey(),
  userId: integer("user_id").references(() => users.id).notNull(),
  planId: integer("plan_id").references(() => subscriptionPlans.id).notNull(),
  status: text("status").notNull(), // active, canceled, past_due, unpaid
  stripeSubscriptionId: text("stripe_subscription_id"),
  currentPeriodStart: timestamp("current_period_start").notNull(),
  currentPeriodEnd: timestamp("current_period_end").notNull(),
  cancelAtPeriodEnd: boolean("cancel_at_period_end").default(false).notNull(),
  canceledAt: timestamp("canceled_at"),
  createdAt: timestamp("created_at").defaultNow().notNull(),
  updatedAt: timestamp("updated_at").defaultNow().notNull(),
});

export const insertSubscriptionSchema = createInsertSchema(subscriptions).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export type InsertSubscription = z.infer<typeof insertSubscriptionSchema>;
export type Subscription = typeof subscriptions.$inferSelect;

export const insertUserSchema = createInsertSchema(users).pick({
  username: true,
  password: true,
  email: true,
});

export type InsertUser = z.infer<typeof insertUserSchema>;
export type User = typeof users.$inferSelect;

// Text processing history schema
export const textProcessingHistory = pgTable("text_processing_history", {
  id: serial("id").primaryKey(),
  userId: integer("user_id").references(() => users.id),
  title: text("title").notNull(),
  originalText: text("original_text").notNull(),
  processedText: text("processed_text"),
  processingType: text("processing_type").notNull(), // "paraphrase" or "plagiarism"
  style: text("style"), // only for paraphrasing
  plagiarismPercentage: integer("plagiarism_percentage"), // only for plagiarism check
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const insertHistorySchema = createInsertSchema(textProcessingHistory).omit({
  id: true,
  createdAt: true,
});

export type InsertHistory = z.infer<typeof insertHistorySchema>;
export type TextProcessingHistory = typeof textProcessingHistory.$inferSelect;

// Paraphrasing request schema
export const paraphraseRequestSchema = z.object({
  text: z.string().min(1, "Text is required"),
  style: z.enum(["standard", "fluent", "academic", "simple", "creative", "business"])
});

export type ParaphraseRequest = z.infer<typeof paraphraseRequestSchema>;

// Plagiarism check request schema
export const plagiarismRequestSchema = z.object({
  text: z.string().min(1, "Text is required")
});

export type PlagiarismRequest = z.infer<typeof plagiarismRequestSchema>;

// Response schemas
export const paraphraseResponseSchema = z.object({
  text: z.string(),
  uniquenessScore: z.number(),
  readabilityScore: z.number(),
  wordsChanged: z.number(),
  wordsChangedPercent: z.number(),
});

export type ParaphraseResponse = z.infer<typeof paraphraseResponseSchema>;

export const plagiarismMatchSchema = z.object({
  id: z.string(),
  text: z.string(),
  matchPercentage: z.number(),
  source: z.string(),
  url: z.string(),
});

export const plagiarismResponseSchema = z.object({
  text: z.string(),
  totalMatchPercentage: z.number(),
  matches: z.array(plagiarismMatchSchema),
});

export type PlagiarismMatch = z.infer<typeof plagiarismMatchSchema>;
export type PlagiarismResponse = z.infer<typeof plagiarismResponseSchema>;
