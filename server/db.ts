import { drizzle } from "drizzle-orm/neon-serverless";
import { neon } from "@neondatabase/serverless";
import { Logger } from "drizzle-orm/logger";

// Simple logger for SQL queries
class ConsoleLogger implements Logger {
  logQuery(query: string, params: unknown[]): void {
    console.log("SQL Query:", query);
    if (params.length > 0) {
      console.log("Params:", params);
    }
  }
}

// Create a PostgreSQL connection
const sql = neon(process.env.DATABASE_URL!);

// Initialize drizzle with the connection
export const db = drizzle(sql, { 
  logger: process.env.NODE_ENV !== "production" ? new ConsoleLogger() : undefined 
});