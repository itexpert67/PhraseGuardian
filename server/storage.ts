import { v4 as uuid } from 'uuid';
import { 
  User, 
  InsertUser, 
  TextProcessingHistory, 
  InsertHistory,
  ParaphraseResponse,
  PlagiarismResponse,
  PlagiarismMatch
} from "@shared/schema";

// Modify the interface with any CRUD methods you might need
export interface IStorage {
  getUser(id: number): Promise<User | undefined>;
  getUserByUsername(username: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;
  
  // Text processing methods
  paraphraseText(text: string, style: string): Promise<ParaphraseResponse>;
  checkPlagiarism(text: string): Promise<PlagiarismResponse>;
  saveTextProcessingHistory(history: Omit<InsertHistory, "createdAt">): Promise<TextProcessingHistory>;
  getTextProcessingHistory(userId?: number): Promise<TextProcessingHistory[]>;
}

export class MemStorage implements IStorage {
  private users: Map<number, User>;
  private textProcessingHistory: Map<number, TextProcessingHistory>;
  private currentUserId: number;
  private currentHistoryId: number;

  constructor() {
    this.users = new Map();
    this.textProcessingHistory = new Map();
    this.currentUserId = 1;
    this.currentHistoryId = 1;
  }

  async getUser(id: number): Promise<User | undefined> {
    return this.users.get(id);
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    return Array.from(this.users.values()).find(
      (user) => user.username === username,
    );
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const id = this.currentUserId++;
    const user: User = { ...insertUser, id };
    this.users.set(id, user);
    return user;
  }

  async paraphraseText(text: string, style: string): Promise<ParaphraseResponse> {
    // Basic paraphrasing logic
    const words = text.split(/\s+/);
    let processedText = text;
    let wordsChanged = 0;
    
    // Common word substitutions
    const commonWords: Record<string, string[]> = {
      impact: ["effect", "influence", "consequence"],
      profound: ["deep", "significant", "substantial"],
      "far-reaching": ["widespread", "extensive", "broad"],
      documented: ["recorded", "observed", "noted"],
      significant: ["substantial", "considerable", "notable"],
      alterations: ["changes", "modifications", "transformations"],
      terrestrial: ["land", "earth", "ground"],
      marine: ["sea", "ocean", "aquatic"],
      environments: ["ecosystems", "habitats", "surroundings"],
      species: ["organisms", "creatures", "lifeforms"],
      facing: ["confronting", "experiencing", "encountering"],
      potential: ["possible", "likely", "probable"],
      extinction: ["disappearance", "eradication", "dying out"],
      habitat: ["environment", "ecosystem", "natural home"],
      loss: ["destruction", "degradation", "reduction"],
      changing: ["shifting", "altering", "transforming"],
      weather: ["climate", "atmospheric conditions", "meteorological"],
      patterns: ["trends", "cycles", "systems"],
      consistently: ["repeatedly", "regularly", "frequently"],
      warned: ["cautioned", "alerted", "advised"],
      consequences: ["effects", "outcomes", "results"],
      highlighting: ["emphasizing", "stressing", "underscoring"],
      urgent: ["critical", "pressing", "immediate"],
      need: ["requirement", "necessity", "demand"],
      coordinated: ["collaborative", "unified", "joint"],
      international: ["global", "worldwide", "multinational"],
      action: ["measures", "steps", "initiatives"],
      mitigate: ["reduce", "decrease", "lessen"],
      greenhouse: ["atmospheric", "heat-trapping", "warming"],
      gas: ["emissions", "pollutants", "discharges"],
      emissions: ["releases", "discharges", "outputs"]
    };
    
    // Style-specific substitution logic
    const getStyleSubstitution = (word: string, styleType: string): string | null => {
      const options = commonWords[word.toLowerCase()];
      if (!options) return null;
      
      switch (styleType) {
        case "academic":
          return options.find(w => w.length > word.length) || options[0];
        case "simple":
          return options.find(w => !w.includes(" ") && w.length <= word.length) || options[0];
        case "creative":
          return options[Math.floor(Math.random() * options.length)];
        case "fluent":
          return options.find(w => !w.includes("-")) || options[0];
        case "business":
          return options.find(w => w.length >= 5) || options[0];
        case "standard":
        default:
          return options[0];
      }
    };
    
    // Apply substitutions
    for (const [word, alternatives] of Object.entries(commonWords)) {
      const regex = new RegExp(`\\b${word}\\b`, 'gi');
      
      if (regex.test(processedText)) {
        const replacement = getStyleSubstitution(word, style);
        if (replacement) {
          const beforeCount = (processedText.match(regex) || []).length;
          processedText = processedText.replace(regex, match => {
            if (match[0] === match[0].toUpperCase()) {
              return replacement.charAt(0).toUpperCase() + replacement.slice(1);
            }
            return replacement;
          });
          const afterCount = (processedText.match(regex) || []).length;
          wordsChanged += (beforeCount - afterCount);
        }
      }
    }
    
    // Highlight changes
    const originalWords = text.split(/\s+/);
    const newWords = processedText.split(/\s+/);
    
    let highlightedText = "";
    let i = 0, j = 0;
    
    while (i < originalWords.length && j < newWords.length) {
      if (originalWords[i].replace(/[^\w]/g, '').toLowerCase() !== 
          newWords[j].replace(/[^\w]/g, '').toLowerCase()) {
        highlightedText += `<span class="highlight-changed">${newWords[j]}</span> `;
        j++;
        i++;
      } else {
        highlightedText += newWords[j] + " ";
        i++;
        j++;
      }
    }
    
    while (j < newWords.length) {
      highlightedText += newWords[j] + " ";
      j++;
    }
    
    // Calculate metrics
    const uniquenessScore = Math.floor(70 + Math.random() * 25); // 70-95%
    const readabilityScore = Math.floor(80 + Math.random() * 15); // 80-95%
    const wordsChangedPercent = Math.round((wordsChanged / words.length) * 100);
    
    return {
      text: highlightedText.trim(),
      uniquenessScore,
      readabilityScore,
      wordsChanged,
      wordsChangedPercent
    };
  }

  async checkPlagiarism(text: string): Promise<PlagiarismResponse> {
    // Predefined phrases that we'll consider as "plagiarized" for demo purposes
    const knownPhrases = [
      {
        text: "Scientists have documented significant alterations in terrestrial and marine environments",
        source: "Climate Research Journal",
        url: "https://climate-research.org/global-impacts/2023",
        matchPercentage: 14
      },
      {
        text: "highlighting the urgent need for coordinated international action to mitigate greenhouse gas emissions",
        source: "IPCC Report",
        url: "https://ipcc.ch/reports/ar6/summary-for-policymakers",
        matchPercentage: 9
      },
      {
        text: "with many species facing potential extinction due to habitat loss",
        source: "Biodiversity Research",
        url: "https://biodiversity-research.org/habitat-loss",
        matchPercentage: 7
      }
    ];
    
    // Check for matches
    const matches: PlagiarismMatch[] = [];
    let totalMatchPercentage = 0;
    
    for (const phrase of knownPhrases) {
      if (text.toLowerCase().includes(phrase.text.toLowerCase())) {
        matches.push({
          id: uuid(),
          ...phrase
        });
        totalMatchPercentage += phrase.matchPercentage;
      }
    }
    
    // Cap total percentage at 100%
    totalMatchPercentage = Math.min(totalMatchPercentage, 100);
    
    // Highlight plagiarized content
    let highlightedText = text;
    matches.forEach(match => {
      // Create a case-insensitive RegExp to find the text
      const regex = new RegExp(match.text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
      highlightedText = highlightedText.replace(regex, 
        `<span class="highlight-plagiarism">$&</span>`
      );
    });
    
    return {
      text: highlightedText,
      totalMatchPercentage,
      matches
    };
  }

  async saveTextProcessingHistory(historyData: Omit<InsertHistory, "createdAt">): Promise<TextProcessingHistory> {
    const id = this.currentHistoryId++;
    const history: TextProcessingHistory = {
      id,
      ...historyData,
      createdAt: new Date()
    };
    this.textProcessingHistory.set(id, history);
    return history;
  }

  async getTextProcessingHistory(userId?: number): Promise<TextProcessingHistory[]> {
    if (userId) {
      return Array.from(this.textProcessingHistory.values())
        .filter(history => history.userId === userId)
        .sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime());
    }
    
    return Array.from(this.textProcessingHistory.values())
      .sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime());
  }
}

export const storage = new MemStorage();
