import { ParaphrasingStyle } from "@/components/text-processing/ParaphrasingOptions";
import { ParaphraseResult } from "@/components/text-processing/ResultsArea";
import { PlagiarismResult, PlagiarismMatch } from "@/components/text-processing/PlagiarismResults";
import { v4 as uuid } from 'uuid';

// Client-side text processing functions (fallbacks if API is not available)

/**
 * Process text with client-side algorithm for paraphrasing
 * This is a fallback if the API is unavailable
 */
export async function processText(
  text: string,
  style: ParaphrasingStyle
): Promise<ParaphraseResult> {
  // Simulate network delay
  await new Promise(resolve => setTimeout(resolve, 1000));

  // Split text into words
  const words = text.split(/\s+/);
  
  // Basic paraphrasing logic based on style
  let paraphrasedText = text;
  let wordsChanged = 0;
  
  // Very basic paraphrasing algorithm for demonstration
  // In a real app, this would use a more sophisticated NLP approach
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

  // Define style-specific substitution patterns
  const getStyleSubstitution = (word: string, style: ParaphrasingStyle): string | null => {
    const options = commonWords[word.toLowerCase()];
    if (!options) return null;
    
    switch (style) {
      case "academic":
        // More formal language
        return options.find(w => w.length > word.length) || options[0];
      case "simple":
        // Simpler language
        return options.find(w => !w.includes(" ") && w.length <= word.length) || options[0];
      case "creative":
        // More varied language
        return options[Math.floor(Math.random() * options.length)];
      case "fluent":
        // Smooth flowing language
        return options.find(w => !w.includes("-")) || options[0];
      case "business":
        // Professional language
        return options.find(w => w.length >= 5) || options[0];
      case "standard":
      default:
        return options[0];
    }
  };
  
  // Apply substitutions and track changes
  let processedText = text;
  for (const [word, alternatives] of Object.entries(commonWords)) {
    const regex = new RegExp(`\\b${word}\\b`, 'gi');
    
    if (regex.test(processedText)) {
      const replacement = getStyleSubstitution(word, style);
      if (replacement) {
        const beforeCount = (processedText.match(regex) || []).length;
        processedText = processedText.replace(regex, match => {
          // Preserve original capitalization
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
  
  // Highlight the changes in the processed text
  const originalWords = text.split(/\s+/);
  const newWords = processedText.split(/\s+/);
  
  let highlightedText = "";
  let i = 0, j = 0;
  
  while (i < originalWords.length && j < newWords.length) {
    if (originalWords[i].replace(/[^\w]/g, '').toLowerCase() !== 
        newWords[j].replace(/[^\w]/g, '').toLowerCase()) {
      highlightedText += `<span class="highlight-changed">${newWords[j]}</span> `;
      j++;
      // Skip the original word
      i++;
    } else {
      highlightedText += newWords[j] + " ";
      i++;
      j++;
    }
  }
  
  // Add any remaining words
  while (j < newWords.length) {
    highlightedText += newWords[j] + " ";
    j++;
  }
  
  // Calculate metrics
  const uniquenessScore = Math.floor(60 + Math.random() * 30); // 60-90%
  const readabilityScore = Math.floor(75 + Math.random() * 20); // 75-95%
  const wordsChangedPercent = Math.round((wordsChanged / words.length) * 100);
  
  return {
    text: highlightedText.trim(),
    uniquenessScore,
    readabilityScore,
    wordsChanged,
    wordsChangedPercent
  };
}

/**
 * Check text for plagiarism with client-side algorithm
 * This is a fallback if the API is unavailable
 */
export async function checkPlagiarism(text: string): Promise<PlagiarismResult> {
  // Simulate network delay
  await new Promise(resolve => setTimeout(resolve, 1500));
  
  // This is a mock implementation for client-side fallback
  // In a real application, this would connect to a plagiarism detection service
  
  // Detect common phrases that might be plagiarized
  const commonPhrases = [
    {
      text: "Scientists have documented significant alterations in terrestrial and marine environments, with many species facing potential extinction due to habitat loss and changing weather patterns.",
      source: "Climate Research Journal",
      url: "https://climate-research.org/global-impacts/2023",
      matchPercentage: 14
    },
    {
      text: "highlighting the urgent need for coordinated international action to mitigate greenhouse gas emissions.",
      source: "IPCC Report",
      url: "https://ipcc.ch/reports/ar6/summary-for-policymakers",
      matchPercentage: 9
    }
  ];
  
  // Randomly determine if there's a match based on the content
  const matches: PlagiarismMatch[] = [];
  let totalMatchPercentage = 0;
  
  // Check for phrase matches
  for (const phrase of commonPhrases) {
    if (text.includes(phrase.text)) {
      matches.push({
        id: uuid(),
        ...phrase
      });
      totalMatchPercentage += phrase.matchPercentage;
    }
  }
  
  // If no specific matches, add a small random match for demonstration
  if (matches.length === 0 && text.length > 100) {
    // Extract a random phrase from the text
    const words = text.split(' ');
    const startIndex = Math.floor(Math.random() * (words.length - 10));
    const randomPhrase = words.slice(startIndex, startIndex + 10).join(' ');
    
    matches.push({
      id: uuid(),
      text: randomPhrase,
      source: "Academic Database Entry",
      url: "https://example-academic-source.edu/article",
      matchPercentage: Math.floor(Math.random() * 10) + 5 // 5-15%
    });
    
    totalMatchPercentage = matches[0].matchPercentage;
  }
  
  // Highlight plagiarized content in the text
  let highlightedText = text;
  matches.forEach(match => {
    highlightedText = highlightedText.replace(
      match.text,
      `<span class="highlight-plagiarism">${match.text}</span>`
    );
  });
  
  return {
    text: highlightedText,
    totalMatchPercentage,
    matches
  };
}
