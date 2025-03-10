import { useState } from "react";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import { ParaphrasingStyle } from "./ParaphrasingOptions";

export type ParaphraseResult = {
  text: string;
  uniquenessScore: number;
  readabilityScore: number;
  wordsChanged: number;
  wordsChangedPercent: number;
};

type ResultsAreaProps = {
  isLoading: boolean;
  result: ParaphraseResult | null;
  onReparaphrase: () => void;
  onStyleChange: () => void;
  originalText: string;
};

export default function ResultsArea({ 
  isLoading, 
  result, 
  onReparaphrase, 
  onStyleChange,
  originalText
}: ResultsAreaProps) {
  const { toast } = useToast();
  const [showComparison, setShowComparison] = useState(false);
  
  const handleCopy = () => {
    if (result) {
      navigator.clipboard.writeText(result.text);
      toast({
        title: "Copied to clipboard",
        description: "Results have been copied to clipboard successfully",
      });
    }
  };
  
  const handleDownload = () => {
    if (result) {
      const element = document.createElement("a");
      const file = new Blob([result.text], {type: "text/plain"});
      element.href = URL.createObjectURL(file);
      element.download = "paraphrased-text.txt";
      document.body.appendChild(element);
      element.click();
      document.body.removeChild(element);
      toast({
        title: "Downloaded",
        description: "Results have been downloaded successfully",
      });
    }
  };

  return (
    <div className="card p-6 mb-6 border border-border bg-surface rounded-md">
      <div className="flex justify-between items-center mb-6">
        <h3 className="font-heading text-lg font-semibold text-foreground">Results</h3>
        <div className="flex space-x-2">
          <Button 
            variant="secondary" 
            size="sm" 
            className="flex items-center gap-1.5"
            onClick={handleCopy}
            disabled={!result || isLoading}
          >
            <i className="ri-clipboard-line"></i>
            <span>Copy</span>
          </Button>
          <Button 
            variant="secondary" 
            size="sm"
            className="flex items-center gap-1.5"
            onClick={handleDownload}
            disabled={!result || isLoading}
          >
            <i className="ri-download-2-line"></i>
            <span>Download</span>
          </Button>
        </div>
      </div>

      {isLoading ? (
        <div className="flex flex-col items-center justify-center py-12">
          <span className="loader mb-4"></span>
          <p className="text-muted-foreground">Processing your content...</p>
        </div>
      ) : result ? (
        <>
          <div className="mb-4 p-4 bg-surface-accent rounded-md border border-border">
            <div dangerouslySetInnerHTML={{ __html: result.text }} />
          </div>

          {showComparison && (
            <div className="mb-4 p-4 bg-surface rounded-md border border-border">
              <h4 className="font-heading font-medium text-foreground mb-2">Original Text:</h4>
              <p className="text-muted-foreground">{originalText}</p>
            </div>
          )}

          <div className="bg-surface-accent rounded-md p-4 mb-6">
            <div className="flex items-center mb-3">
              <i className="ri-information-line text-primary mr-2"></i>
              <h4 className="font-heading font-medium text-foreground">Analysis Summary</h4>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="flex flex-col">
                <span className="text-muted-foreground text-sm mb-1">Uniqueness Score</span>
                <div className="flex items-center">
                  <div className="w-full bg-surface rounded-full h-2.5 mr-2">
                    <div 
                      className="bg-accent h-2.5 rounded-full" 
                      style={{ width: `${result.uniquenessScore}%` }}
                    ></div>
                  </div>
                  <span className="text-accent font-medium">{result.uniquenessScore}%</span>
                </div>
              </div>
              <div className="flex flex-col">
                <span className="text-muted-foreground text-sm mb-1">Readability</span>
                <div className="flex items-center">
                  <div className="w-full bg-surface rounded-full h-2.5 mr-2">
                    <div 
                      className="bg-primary h-2.5 rounded-full" 
                      style={{ width: `${result.readabilityScore}%` }}
                    ></div>
                  </div>
                  <span className="text-primary font-medium">{result.readabilityScore}%</span>
                </div>
              </div>
              <div className="flex flex-col">
                <span className="text-muted-foreground text-sm mb-1">Words Changed</span>
                <span className="text-foreground font-medium">
                  {result.wordsChanged} words ({result.wordsChangedPercent}%)
                </span>
              </div>
            </div>
          </div>

          <div className="flex justify-between">
            <Button 
              variant="link" 
              className="text-primary hover:text-primary-hover transition-colors p-0"
              onClick={() => setShowComparison(!showComparison)}
            >
              <span className="flex items-center">
                <i className="ri-compare-line mr-1.5"></i>
                {showComparison ? "Hide original" : "Compare with original"}
              </span>
            </Button>
            <Button 
              variant="link"
              className="text-primary hover:text-primary-hover transition-colors p-0"
              onClick={onStyleChange}
            >
              <span className="flex items-center">
                <i className="ri-refresh-line mr-1.5"></i>
                Try different style
              </span>
            </Button>
          </div>
        </>
      ) : (
        <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground">
          <i className="ri-quill-pen-line text-3xl mb-3"></i>
          <p>Your paraphrased text will appear here</p>
          <p className="text-sm mt-1">Start by entering text and clicking "Paraphrase"</p>
        </div>
      )}
    </div>
  );
}
