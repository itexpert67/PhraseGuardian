import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PlagiarismMatch } from '@shared/schema';

// Types
type AdvancedPlagiarismResultsProps = {
  result: {
    text: string;
    totalMatchPercentage: number;
    matches: PlagiarismMatch[];
  } | null;
  isLoading: boolean;
  onFixPlagiarism: () => void;
};

export default function AdvancedPlagiarismResults({ 
  result, 
  isLoading, 
  onFixPlagiarism 
}: AdvancedPlagiarismResultsProps) {
  const [activeTab, setActiveTab] = useState<string>("overview");
  
  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center p-6 space-y-4 min-h-[400px]">
        <div className="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full"></div>
        <p className="text-muted-foreground">Analyzing text for plagiarism...</p>
      </div>
    );
  }
  
  if (!result) {
    return (
      <div className="flex flex-col items-center justify-center p-6 space-y-4 min-h-[400px]">
        <p className="text-muted-foreground">Submit your text to check for plagiarism.</p>
      </div>
    );
  }
  
  const getSeverityClass = (percentage: number) => {
    if (percentage < 20) return "text-green-500";
    if (percentage < 40) return "text-yellow-500";
    if (percentage < 60) return "text-orange-500";
    return "text-red-500";
  };
  
  const getSeverityDescription = (percentage: number) => {
    if (percentage < 20) return "Low plagiarism - generally acceptable";
    if (percentage < 40) return "Moderate plagiarism - may need some revision";
    if (percentage < 60) return "High plagiarism - significant revision needed";
    return "Very high plagiarism - complete rewrite recommended";
  };
  
  const getPlagiarismRiskLevel = (percentage: number) => {
    if (percentage < 15) return "Very Low";
    if (percentage < 30) return "Low";
    if (percentage < 45) return "Moderate";
    if (percentage < 60) return "High";
    return "Very High";
  };
  
  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle className="flex justify-between items-center">
            <span>Plagiarism Analysis</span>
            <span 
              className={`text-lg font-bold ${getSeverityClass(result.totalMatchPercentage)}`}
            >
              {result.totalMatchPercentage}%
            </span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div>
              <div className="flex justify-between mb-2">
                <span className="text-sm">Originality</span>
                <span className="text-sm">{100 - result.totalMatchPercentage}%</span>
              </div>
              <Progress value={100 - result.totalMatchPercentage} className="h-2" />
            </div>
            
            <div>
              <div className="flex justify-between mb-2">
                <span className="text-sm">Plagiarism</span>
                <span className="text-sm">{result.totalMatchPercentage}%</span>
              </div>
              <Progress value={result.totalMatchPercentage} className={`h-2 bg-muted ${result.totalMatchPercentage > 40 ? 'data-[value]:bg-red-500' : 'data-[value]:bg-yellow-500'}`} />
            </div>
            
            <div className="pt-2">
              <p className="text-sm font-medium">Risk Level: 
                <span className={`ml-2 ${getSeverityClass(result.totalMatchPercentage)}`}>
                  {getPlagiarismRiskLevel(result.totalMatchPercentage)}
                </span>
              </p>
              <p className="text-sm text-muted-foreground mt-1">
                {getSeverityDescription(result.totalMatchPercentage)}
              </p>
            </div>
            
            {result.totalMatchPercentage > 0 && (
              <Button 
                variant="outline" 
                className="w-full mt-4" 
                onClick={onFixPlagiarism}
              >
                Fix Plagiarism with AI Paraphrasing
              </Button>
            )}
          </div>
        </CardContent>
      </Card>
      
      {result.matches.length > 0 && (
        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="matches">Matched Sources ({result.matches.length})</TabsTrigger>
            <TabsTrigger value="highlighted">Highlighted Text</TabsTrigger>
          </TabsList>
          
          <TabsContent value="overview" className="pt-4">
            <Card>
              <CardContent className="pt-6">
                <div className="space-y-4">
                  <div className="flex justify-between">
                    <span>Total matched sources:</span>
                    <span className="font-medium">{result.matches.length}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Highest match percentage:</span>
                    <span className="font-medium">
                      {Math.max(...result.matches.map(m => m.matchPercentage))}%
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span>Total words checked:</span>
                    <span className="font-medium">
                      {result.text.split(/\s+/).length}
                    </span>
                  </div>
                  
                  <div className="pt-4">
                    <h4 className="font-medium mb-2">Recommendations:</h4>
                    <ul className="list-disc pl-5 space-y-1 text-sm">
                      {result.totalMatchPercentage > 40 && (
                        <li>Rewrite sections with high similarity to original sources</li>
                      )}
                      {result.totalMatchPercentage > 20 && (
                        <li>Properly cite all sources used in your text</li>
                      )}
                      {result.totalMatchPercentage > 30 && (
                        <li>Use more of your own ideas and analysis</li>
                      )}
                      {result.totalMatchPercentage > 10 && (
                        <li>Use quotation marks for direct quotes</li>
                      )}
                      <li>Consider using our AI paraphrasing tool to rephrase problematic sections</li>
                    </ul>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          
          <TabsContent value="matches" className="pt-4">
            <div className="space-y-4">
              {result.matches.map((match) => (
                <Card key={match.id}>
                  <CardContent className="pt-6">
                    <div className="space-y-3">
                      <div className="flex justify-between items-start">
                        <h4 className="font-medium text-sm">{match.source}</h4>
                        <span className={`text-sm font-bold ${getSeverityClass(match.matchPercentage)}`}>
                          {match.matchPercentage}%
                        </span>
                      </div>
                      
                      <div className="border-l-4 border-muted-foreground/20 pl-4 py-1 text-sm italic text-muted-foreground">
                        {match.text}
                      </div>
                      
                      <div className="flex items-center justify-between text-xs">
                        <a 
                          href={match.url} 
                          target="_blank" 
                          rel="noopener noreferrer"
                          className="text-primary hover:underline"
                        >
                          View Source
                        </a>
                        <span className="text-muted-foreground">
                          {match.text.split(/\s+/).length} words
                        </span>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </TabsContent>
          
          <TabsContent value="highlighted" className="pt-4">
            <Card>
              <CardContent className="pt-6">
                <div 
                  className="prose dark:prose-invert max-w-none"
                  dangerouslySetInnerHTML={{ __html: result.text }}
                ></div>
                <div className="mt-4 text-xs text-muted-foreground">
                  <p>* Highlighted sections indicate detected plagiarism. Hover over highlighted text to see the source.</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      )}
      
      {result.matches.length === 0 && result.totalMatchPercentage === 0 && (
        <Card>
          <CardContent className="pt-6">
            <div className="flex flex-col items-center justify-center p-6 text-center">
              <div className="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center mb-4">
                <svg 
                  xmlns="http://www.w3.org/2000/svg" 
                  className="h-8 w-8 text-green-500" 
                  fill="none" 
                  viewBox="0 0 24 24" 
                  stroke="currentColor"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h3 className="text-lg font-medium mb-2">No Plagiarism Detected</h3>
              <p className="text-muted-foreground text-sm">
                Great job! Your text appears to be original. Keep up the good work!
              </p>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}