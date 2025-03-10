import { pgTable, text, serial, integer, boolean, timestamp } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

// Users schema
export const users = pgTable("users", {
  id: serial("id").primaryKey(),
  username: text("username").notNull().unique(),
  password: text("password").notNull(),
});

export const insertUserSchema = createInsertSchema(users).pick({
  username: true,
  password: true,
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
