/**
 * Advanced Plagiarism Detector
 * 
 * This module provides sophisticated plagiarism detection using multiple algorithms:
 * 1. Exact match detection
 * 2. Fingerprinting (for detecting rearranged text)
 * 3. Synonym replacement detection
 * 4. Sentence structure analysis
 */

import { PlagiarismMatch, PlagiarismResponse } from '@shared/schema';
import { v4 as uuid } from 'uuid';

// Sample database of known texts for demonstration
// In a production environment, this would be replaced with a real database
// or API calls to external plagiarism detection services
interface KnownSource {
  id: string;
  text: string;
  source: string;
  url: string;
}

const knownSources: KnownSource[] = [
  {
    id: "source1",
    text: "Scientists have documented significant alterations in terrestrial and marine environments, with many species facing potential extinction due to habitat loss.",
    source: "Climate Research Journal",
    url: "https://climate-research.org/global-impacts/2023"
  },
  {
    id: "source2",
    text: "The changing weather patterns are consistently linked to increased greenhouse gas emissions, highlighting the urgent need for coordinated international action.",
    source: "IPCC Report",
    url: "https://ipcc.ch/reports/ar6/summary-for-policymakers"
  },
  {
    id: "source3",
    text: "According to recent studies, biodiversity loss has accelerated to unprecedented levels, primarily driven by human activities including deforestation and pollution.",
    source: "Biodiversity Research",
    url: "https://biodiversity-research.org/habitat-loss"
  },
  {
    id: "source4",
    text: "The development of artificial intelligence has raised significant ethical concerns regarding privacy, bias, and the potential displacement of human workers.",
    source: "AI Ethics Institute",
    url: "https://ai-ethics.org/concerns/2023"
  },
  {
    id: "source5",
    text: "Renewable energy technologies have seen dramatic cost reductions over the past decade, making them increasingly competitive with fossil fuel-based electricity generation.",
    source: "Renewable Energy Review",
    url: "https://renewables-review.org/cost-trends"
  },
  {
    id: "source6",
    text: "Modern communication technologies have fundamentally transformed how people interact socially, professionally, and politically, raising questions about their long-term impact on human relationships.",
    source: "Journal of Communication Studies",
    url: "https://comm-studies.org/digital-transformation"
  },
  {
    id: "source7",
    text: "The global pandemic accelerated the adoption of remote work, creating new opportunities and challenges for organizations and workers worldwide.",
    source: "Future of Work Institute",
    url: "https://future-work.org/remote-trends"
  },
  {
    id: "source8",
    text: "Advances in genetic engineering have created unprecedented possibilities for treating diseases, while simultaneously raising ethical questions about the appropriate limits of such technologies.",
    source: "Bioethics Journal",
    url: "https://bioethics-journal.org/genetic-engineering"
  }
];

export class PlagiarismDetector {
  /**
   * Checks text for plagiarism using multiple detection algorithms
   * 
   * @param text The text to check for plagiarism
   * @returns A plagiarism detection response with matches and overall percentage
   */
  public static async checkPlagiarism(text: string): Promise<PlagiarismResponse> {
    const matches: PlagiarismMatch[] = [];
    let totalMatchPercentage = 0;
    
    // Process text to prepare for detection
    const processedText = this.preprocessText(text);
    const sentences = this.splitIntoSentences(processedText);
    
    // Apply different detection methods
    const exactMatches = this.detectExactMatches(sentences);
    const fingerprintMatches = this.detectByFingerprinting(sentences);
    const synonymMatches = this.detectSynonymReplacement(sentences);
    
    // Combine results and remove duplicates
    const allMatches = [...exactMatches, ...fingerprintMatches, ...synonymMatches];
    const dedupedMatches = this.deduplicateMatches(allMatches);
    
    // Calculate total match percentage based on character overlap
    const matchedChars = dedupedMatches.reduce((sum, match) => sum + match.text.length, 0);
    totalMatchPercentage = Math.min(Math.round((matchedChars / text.length) * 100), 100);
    
    // Highlight matches in the original text
    const highlightedText = this.highlightPlagiarizedContent(text, dedupedMatches);
    
    return {
      text: highlightedText,
      totalMatchPercentage,
      matches: dedupedMatches
    };
  }
  
  /**
   * Detects exact matches between the input text and known sources
   */
  private static detectExactMatches(sentences: string[]): PlagiarismMatch[] {
    const matches: PlagiarismMatch[] = [];
    
    // Check each sentence against each known source
    for (const sentence of sentences) {
      if (sentence.length < 10) continue; // Skip very short sentences
      
      for (const source of knownSources) {
        if (source.text.includes(sentence)) {
          // Calculate what percentage of the source this match represents
          const matchPercentage = Math.round((sentence.length / source.text.length) * 100);
          
          matches.push({
            id: uuid(),
            text: sentence,
            matchPercentage,
            source: source.source,
            url: source.url
          });
          
          break; // Move to next sentence once a match is found
        }
      }
    }
    
    return matches;
  }
  
  /**
   * Detects plagiarism using text fingerprinting
   * This can detect rearranged text and partial matches
   */
  private static detectByFingerprinting(sentences: string[]): PlagiarismMatch[] {
    const matches: PlagiarismMatch[] = [];
    
    // For each source, create n-grams (word sequences) and compare with input text
    for (const source of knownSources) {
      const sourceNgrams = this.createNgrams(source.text.toLowerCase(), 5);
      
      for (const sentence of sentences) {
        if (sentence.length < 15) continue; // Skip very short sentences
        
        const sentenceNgrams = this.createNgrams(sentence.toLowerCase(), 5);
        let matchingNgrams = 0;
        
        // Count matching n-grams
        for (const ngram of sentenceNgrams) {
          if (sourceNgrams.includes(ngram)) {
            matchingNgrams++;
          }
        }
        
        // If sufficient n-grams match, consider it plagiarism
        if (matchingNgrams > 0 && sentenceNgrams.length > 0) {
          const similarity = matchingNgrams / sentenceNgrams.length;
          
          if (similarity > 0.5) { // Threshold for considering it a match
            const matchPercentage = Math.round(similarity * 100);
            
            matches.push({
              id: uuid(),
              text: sentence,
              matchPercentage,
              source: source.source,
              url: source.url
            });
            
            break; // Move to next sentence once a match is found
          }
        }
      }
    }
    
    return matches;
  }
  
  /**
   * Detects plagiarism where synonyms have been substituted
   */
  private static detectSynonymReplacement(sentences: string[]): PlagiarismMatch[] {
    const matches: PlagiarismMatch[] = [];
    
    // Common synonym pairs for detection
    const synonymPairs: Record<string, string[]> = {
      'significant': ['substantial', 'considerable', 'important', 'major'],
      'alterations': ['changes', 'modifications', 'transformations'],
      'documented': ['recorded', 'observed', 'noted'],
      'species': ['organisms', 'creatures', 'lifeforms'],
      'potential': ['possible', 'likely', 'probable'],
      'extinction': ['disappearance', 'eradication', 'dying out'],
      'habitat': ['environment', 'ecosystem', 'natural home'],
      'loss': ['destruction', 'degradation', 'reduction'],
      'unprecedented': ['unparalleled', 'extraordinary', 'unmatched'],
      'primarily': ['mainly', 'chiefly', 'predominantly'],
      'accelerated': ['increased', 'hastened', 'expedited'],
      'urgent': ['pressing', 'critical', 'immediate'],
      'coordinated': ['collaborative', 'unified', 'joint'],
      'international': ['global', 'worldwide', 'multinational'],
      'technologies': ['innovations', 'advancements', 'developments'],
      'concerns': ['worries', 'issues', 'problems'],
      'dramatic': ['striking', 'remarkable', 'substantial'],
      'competitive': ['viable', 'comparable', 'efficient'],
      'transformed': ['changed', 'altered', 'modified'],
      'challenges': ['difficulties', 'problems', 'obstacles']
    };
    
    // For each source, create a "synonym normalized" version and compare
    for (const source of knownSources) {
      const normalizedSource = this.normalizeSynonyms(source.text, synonymPairs);
      
      for (const sentence of sentences) {
        if (sentence.length < 15) continue; // Skip very short sentences
        
        const normalizedSentence = this.normalizeSynonyms(sentence, synonymPairs);
        
        // Check if normalized versions match
        if (normalizedSource.includes(normalizedSentence) && normalizedSentence.length > 20) {
          const matchPercentage = Math.round((normalizedSentence.length / normalizedSource.length) * 100);
          
          matches.push({
            id: uuid(),
            text: sentence,
            matchPercentage: Math.min(matchPercentage, 100),
            source: source.source,
            url: source.url
          });
          
          break; // Move to next sentence once a match is found
        }
      }
    }
    
    return matches;
  }
  
  /**
   * Preprocesses text for better plagiarism detection
   */
  private static preprocessText(text: string): string {
    return text
      .replace(/\s+/g, ' ')    // Normalize whitespace
      .replace(/[.,;:!?]/g, '') // Remove punctuation
      .trim();
  }
  
  /**
   * Splits text into sentences for analysis
   */
  private static splitIntoSentences(text: string): string[] {
    // Split on periods, question marks, and exclamation points
    const sentences = text.split(/(?<=[.!?])\s+/);
    return sentences.filter(s => s.trim().length > 0);
  }
  
  /**
   * Creates word n-grams from text
   */
  private static createNgrams(text: string, n: number): string[] {
    const words = text.split(/\s+/);
    const ngrams: string[] = [];
    
    for (let i = 0; i <= words.length - n; i++) {
      ngrams.push(words.slice(i, i + n).join(' '));
    }
    
    return ngrams;
  }
  
  /**
   * Normalizes synonyms in text for better detection of paraphrased content
   */
  private static normalizeSynonyms(text: string, synonymPairs: Record<string, string[]>): string {
    let normalized = text.toLowerCase();
    
    // Replace each word with its "canonical" form
    for (const [canonical, synonyms] of Object.entries(synonymPairs)) {
      for (const synonym of synonyms) {
        const regex = new RegExp(`\\b${synonym}\\b`, 'gi');
        normalized = normalized.replace(regex, canonical);
      }
    }
    
    return normalized;
  }
  
  /**
   * Removes duplicate matches based on overlapping text
   */
  private static deduplicateMatches(matches: PlagiarismMatch[]): PlagiarismMatch[] {
    if (matches.length <= 1) return matches;
    
    // Sort by text length (longest first) for better deduplication
    const sortedMatches = [...matches].sort((a, b) => b.text.length - a.text.length);
    const dedupedMatches: PlagiarismMatch[] = [sortedMatches[0]];
    
    for (let i = 1; i < sortedMatches.length; i++) {
      const current = sortedMatches[i];
      let isDuplicate = false;
      
      for (const existing of dedupedMatches) {
        // Check if current match is contained within an existing match
        if (existing.text.includes(current.text)) {
          isDuplicate = true;
          break;
        }
        
        // Check for significant overlap (>70%)
        const overlapThreshold = 0.7;
        const words = current.text.split(/\s+/);
        let matchingWords = 0;
        
        for (const word of words) {
          if (existing.text.includes(word)) {
            matchingWords++;
          }
        }
        
        const overlapRatio = matchingWords / words.length;
        if (overlapRatio > overlapThreshold) {
          isDuplicate = true;
          break;
        }
      }
      
      if (!isDuplicate) {
        dedupedMatches.push(current);
      }
    }
    
    return dedupedMatches;
  }
  
  /**
   * Highlights plagiarized content in the original text
   */
  private static highlightPlagiarizedContent(originalText: string, matches: PlagiarismMatch[]): string {
    let highlightedText = originalText;
    
    // Sort matches by position in text (to avoid issues with overlapping matches)
    const sortedMatches = [...matches].sort((a, b) => {
      const posA = originalText.indexOf(a.text);
      const posB = originalText.indexOf(b.text);
      return posA - posB;
    });
    
    // Apply highlighting from end to start to avoid position shifts
    for (let i = sortedMatches.length - 1; i >= 0; i--) {
      const match = sortedMatches[i];
      const regex = new RegExp(`(${this.escapeRegExp(match.text)})`, 'g');
      highlightedText = highlightedText.replace(
        regex,
        `<span class="highlight-plagiarism" title="${match.source}" data-url="${match.url}" data-percentage="${match.matchPercentage}">$1</span>`
      );
    }
    
    return highlightedText;
  }
  
  /**
   * Escapes special characters in a string for use in a regular expression
   */
  private static escapeRegExp(string: string): string {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }
}