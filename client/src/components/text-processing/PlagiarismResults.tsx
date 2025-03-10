import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";

export type PlagiarismMatch = {
  id: string;
  text: string;
  matchPercentage: number;
  source: string;
  url: string;
};

export type PlagiarismResult = {
  text: string;
  totalMatchPercentage: number;
  matches: PlagiarismMatch[];
};

type PlagiarismResultsProps = {
  result: PlagiarismResult | null;
  isLoading: boolean;
  onFixPlagiarism: () => void;
};

export default function PlagiarismResults({ 
  result, 
  isLoading, 
  onFixPlagiarism 
}: PlagiarismResultsProps) {
  const { toast } = useToast();
  
  const handleGenerateReport = () => {
    if (result) {
      toast({
        title: "Report Generated",
        description: "Plagiarism report has been generated and is ready to download",
      });
      
      // Create and download a simple text report
      const content = `
        Plagiarism Report
        -----------------
        Total Plagiarism: ${result.totalMatchPercentage}%
        
        Matched Sources:
        ${result.matches.map(match => (
          `- ${match.source} (${match.matchPercentage}% match)
           "${match.text}"
           Source: ${match.url}
          `
        )).join('\n\n')}
      `;
      
      const element = document.createElement("a");
      const file = new Blob([content], {type: "text/plain"});
      element.href = URL.createObjectURL(file);
      element.download = "plagiarism-report.txt";
      document.body.appendChild(element);
      element.click();
      document.body.removeChild(element);
    }
  };

  if (isLoading) {
    return (
      <div className="card p-6 mb-6 border border-border bg-surface rounded-md">
        <div className="flex justify-between items-center mb-6">
          <h3 className="font-heading text-lg font-semibold text-foreground">Plagiarism Results</h3>
        </div>
        <div className="flex flex-col items-center justify-center py-12">
          <span className="loader mb-4"></span>
          <p className="text-muted-foreground">Checking for plagiarism...</p>
        </div>
      </div>
    );
  }

  if (!result) {
    return (
      <div className="card p-6 mb-6 border border-border bg-surface rounded-md">
        <div className="flex justify-between items-center mb-6">
          <h3 className="font-heading text-lg font-semibold text-foreground">Plagiarism Results</h3>
        </div>
        <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground">
          <i className="ri-search-eye-line text-3xl mb-3"></i>
          <p>No plagiarism check has been performed yet</p>
          <p className="text-sm mt-1">Enter your text and click "Check Plagiarism"</p>
        </div>
      </div>
    );
  }

  return (
    <div className="card p-6 mb-6 border border-border bg-surface rounded-md">
      <div className="flex justify-between items-center mb-6">
        <h3 className="font-heading text-lg font-semibold text-foreground">Plagiarism Results</h3>
        <span className={`px-3 py-1 bg-opacity-20 rounded-full text-sm font-medium ${
          result.totalMatchPercentage > 20 
            ? "bg-destructive text-destructive" 
            : "bg-accent text-accent"
        }`}>
          {result.totalMatchPercentage}% Plagiarized
        </span>
      </div>

      <div className="mb-6 p-4 bg-surface-accent rounded-md border border-border">
        <div dangerouslySetInnerHTML={{ __html: result.text }} />
      </div>

      <div className="mb-6">
        <h4 className="font-heading font-medium text-foreground mb-3">Matched Sources</h4>
        
        <div className="space-y-4">
          {result.matches.map(match => (
            <div key={match.id} className="p-4 bg-surface-accent rounded-md">
              <div className="flex justify-between mb-2">
                <h5 className="font-medium text-foreground">{match.source}</h5>
                <span className="text-destructive text-sm">{match.matchPercentage}% match</span>
              </div>
              <p className="text-sm text-muted-foreground mb-2">
                "{match.text}"
              </p>
              <a 
                href={match.url} 
                target="_blank" 
                rel="noopener noreferrer" 
                className="text-primary text-sm hover:underline"
              >
                {match.url}
              </a>
            </div>
          ))}
        </div>
      </div>

      <div className="flex justify-between">
        <Button
          className="flex items-center gap-2"
          onClick={onFixPlagiarism}
        >
          <i className="ri-quill-pen-line"></i>
          <span>Fix Plagiarism</span>
        </Button>
        <Button
          variant="outline"
          className="flex items-center gap-2"
          onClick={handleGenerateReport}
        >
          <i className="ri-file-chart-line"></i>
          <span>Generate Report</span>
        </Button>
      </div>
    </div>
  );
}
