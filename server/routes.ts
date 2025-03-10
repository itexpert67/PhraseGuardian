import express, { Request, Response } from "express";
import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { 
  paraphraseRequestSchema, 
  plagiarismRequestSchema, 
  ParaphraseRequest,
  PlagiarismRequest
} from "@shared/schema";
import { ZodError } from "zod";
import { fromZodError } from "zod-validation-error";

export async function registerRoutes(app: Express): Promise<Server> {
  const httpServer = createServer(app);
  const router = express.Router();

  // Health check endpoint
  router.get("/health", (_req: Request, res: Response) => {
    res.json({ status: "ok" });
  });

  // Paraphrase text endpoint
  router.post("/paraphrase", async (req: Request, res: Response) => {
    try {
      // Validate request body
      const data = paraphraseRequestSchema.parse(req.body) as ParaphraseRequest;
      
      // Process the text
      const result = await storage.paraphraseText(data.text, data.style);
      
      // Return the result
      res.json(result);
    } catch (error) {
      if (error instanceof ZodError) {
        const validationError = fromZodError(error);
        res.status(400).json({ error: validationError.message });
      } else {
        console.error("Error processing paraphrase request:", error);
        res.status(500).json({ error: "Failed to process text" });
      }
    }
  });

  // Check plagiarism endpoint
  router.post("/plagiarism", async (req: Request, res: Response) => {
    try {
      // Validate request body
      const data = plagiarismRequestSchema.parse(req.body) as PlagiarismRequest;
      
      // Check plagiarism
      const result = await storage.checkPlagiarism(data.text);
      
      // Return the result
      res.json(result);
    } catch (error) {
      if (error instanceof ZodError) {
        const validationError = fromZodError(error);
        res.status(400).json({ error: validationError.message });
      } else {
        console.error("Error processing plagiarism check request:", error);
        res.status(500).json({ error: "Failed to check plagiarism" });
      }
    }
  });

  // Save text processing history endpoint (optional for authenticated users)
  router.post("/history", async (req: Request, res: Response) => {
    try {
      // In a real app, this would be secured and require authentication
      const { title, originalText, processedText, processingType, style, plagiarismPercentage } = req.body;
      
      // Save history entry
      const historyEntry = await storage.saveTextProcessingHistory({
        userId: null, // Anonymous for now
        title,
        originalText,
        processedText,
        processingType,
        style,
        plagiarismPercentage
      });
      
      res.json({ success: true, historyId: historyEntry.id });
    } catch (error) {
      console.error("Error saving history:", error);
      res.status(500).json({ error: "Failed to save history" });
    }
  });

  // Mount the router under /api prefix
  app.use("/api", router);

  return httpServer;
}
