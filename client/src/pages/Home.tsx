import { useState, useEffect } from "react";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Header from "@/components/layout/Header";
import Footer from "@/components/layout/Footer";
import Sidebar, { HistoryItem } from "@/components/layout/Sidebar";
import TextEditor, { FormatCommand } from "@/components/text-processing/TextEditor";
import ParaphrasingOptions, { ParaphrasingStyle } from "@/components/text-processing/ParaphrasingOptions";
import ResultsArea, { ParaphraseResult } from "@/components/text-processing/ResultsArea";
import PlagiarismResults, { PlagiarismResult } from "@/components/text-processing/PlagiarismResults";
import { useTextEditor } from "@/hooks/use-text-editor";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { processText, checkPlagiarism } from "@/lib/text-utils";
import { v4 as uuid } from 'uuid';

const EXAMPLE_TEXT = "The impact of climate change on global ecosystems is profound and far-reaching. Scientists have documented significant alterations in terrestrial and marine environments, with many species facing potential extinction due to habitat loss and changing weather patterns. The Intergovernmental Panel on Climate Change (IPCC) has consistently warned about these consequences in their assessment reports, highlighting the urgent need for coordinated international action to mitigate greenhouse gas emissions.";

export default function Home() {
  const [activeTab, setActiveTab] = useState<string>("paraphraser");
  const { text, setText, applyFormatting } = useTextEditor();
  const [isProcessing, setIsProcessing] = useState(false);
  const [paraphrasingStyle, setParaphrasingStyle] = useState<ParaphrasingStyle>("standard");
  const [history, setHistory] = useState<HistoryItem[]>([]);
  const [paraphraseResult, setParaphraseResult] = useState<ParaphraseResult | null>(null);
  const [plagiarismResult, setPlagiarismResult] = useState<PlagiarismResult | null>(null);
  const { toast } = useToast();

  // Reset results when changing tabs
  useEffect(() => {
    if (activeTab === "paraphraser") {
      setPlagiarismResult(null);
    } else if (activeTab === "plagiarism") {
      setParaphraseResult(null);
    }
  }, [activeTab]);

  const handleFormatCommand = (command: FormatCommand) => {
    applyFormatting(command);
  };

  const handleParaphrase = async () => {
    if (!text.trim()) {
      toast({
        title: "Empty text",
        description: "Please enter some text to paraphrase",
        variant: "destructive",
      });
      return;
    }

    try {
      setIsProcessing(true);
      
      // Call the API
      const response = await apiRequest("POST", "/api/paraphrase", {
        text,
        style: paraphrasingStyle
      });
      
      const result = await response.json();
      setParaphraseResult(result);
      
      // Add to history
      const newHistoryItem: HistoryItem = {
        id: uuid(),
        title: text.substring(0, 30) + (text.length > 30 ? "..." : ""),
        timestamp: new Date().toISOString(),
        displayTime: "Just now",
        type: "paraphrased"
      };
      
      setHistory(prev => [newHistoryItem, ...prev]);
    } catch (error) {
      // Fallback to client-side processing if API fails
      const result = await processText(text, paraphrasingStyle);
      setParaphraseResult(result);
      
      toast({
        title: "Using client-side processing",
        description: "Server is busy. Using browser-based processing instead.",
      });

      // Add to history
      const newHistoryItem: HistoryItem = {
        id: uuid(),
        title: text.substring(0, 30) + (text.length > 30 ? "..." : ""),
        timestamp: new Date().toISOString(),
        displayTime: "Just now",
        type: "paraphrased"
      };
      
      setHistory(prev => [newHistoryItem, ...prev]);
    } finally {
      setIsProcessing(false);
    }
  };

  const handleCheckPlagiarism = async () => {
    if (!text.trim()) {
      toast({
        title: "Empty text",
        description: "Please enter some text to check for plagiarism",
        variant: "destructive",
      });
      return;
    }

    try {
      setIsProcessing(true);
      
      // Call the API
      const response = await apiRequest("POST", "/api/plagiarism", {
        text
      });
      
      const result = await response.json();
      setPlagiarismResult(result);
      
      // Add to history
      const newHistoryItem: HistoryItem = {
        id: uuid(),
        title: text.substring(0, 30) + (text.length > 30 ? "..." : ""),
        timestamp: new Date().toISOString(),
        displayTime: "Just now",
        type: "plagiarism",
        plagiarismPercentage: result.totalMatchPercentage
      };
      
      setHistory(prev => [newHistoryItem, ...prev]);
    } catch (error) {
      // Fallback to client-side processing if API fails
      const result = await checkPlagiarism(text);
      setPlagiarismResult(result);
      
      toast({
        title: "Using client-side processing",
        description: "Server is busy. Using browser-based processing instead.",
      });

      // Add to history
      const newHistoryItem: HistoryItem = {
        id: uuid(),
        title: text.substring(0, 30) + (text.length > 30 ? "..." : ""),
        timestamp: new Date().toISOString(),
        displayTime: "Just now",
        type: "plagiarism",
        plagiarismPercentage: result.totalMatchPercentage
      };
      
      setHistory(prev => [newHistoryItem, ...prev]);
    } finally {
      setIsProcessing(false);
    }
  };

  const handleFixPlagiarism = () => {
    if (plagiarismResult) {
      setActiveTab("paraphraser");
      toast({
        title: "Ready to fix plagiarism",
        description: "Click 'Paraphrase' to rewrite the text and fix plagiarism issues.",
      });
    }
  };

  const handleUpload = () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = ".txt,.doc,.docx,.pdf";
    
    input.onchange = (e) => {
      const file = (e.target as HTMLInputElement).files?.[0];
      
      if (file) {
        if (file.type === "text/plain") {
          const reader = new FileReader();
          reader.onload = (e) => {
            const content = e.target?.result as string;
            setText(content);
          };
          reader.readAsText(file);
        } else {
          toast({
            title: "Unsupported file format",
            description: "Only plain text (.txt) files are currently supported for direct import",
            variant: "destructive"
          });
        }
      }
    };
    
    input.click();
  };

  const handleClear = () => {
    setText("");
    setParaphraseResult(null);
    setPlagiarismResult(null);
  };

  const handleExample = () => {
    setText(EXAMPLE_TEXT);
  };

  const handleHistoryClear = () => {
    setHistory([]);
  };

  const handleHistoryClick = (historyId: string) => {
    const item = history.find(h => h.id === historyId);
    if (item) {
      if (item.type === "paraphrased") {
        setActiveTab("paraphraser");
      } else if (item.type === "plagiarism") {
        setActiveTab("plagiarism");
      }
      toast({
        title: "History item loaded",
        description: `Loaded "${item.title}"`,
      });
    }
  };

  return (
    <div className="flex flex-col min-h-screen">
      <Header />
      
      <main className="flex-grow container mx-auto px-4 py-6 lg:px-6">
        <div className="flex flex-col lg:flex-row gap-6">
          <Sidebar 
            activeTab={activeTab}
            history={history}
            onTabChange={setActiveTab}
            onHistoryClick={handleHistoryClick}
            onHistoryClear={handleHistoryClear}
          />
          
          <div className="flex-grow">
            <div className="mb-6">
              <h2 className="font-heading text-2xl font-bold text-foreground mb-2">
                {activeTab === "paraphraser" && "Paraphraser"}
                {activeTab === "plagiarism" && "Plagiarism Checker"}
                {activeTab === "grammar" && "Grammar Check"}
                {activeTab === "summarizer" && "Summarizer"}
                {activeTab === "translator" && "Translator"}
              </h2>
              <p className="text-muted-foreground">
                {activeTab === "paraphraser" && "Rewrite your content to make it unique and improve its style."}
                {activeTab === "plagiarism" && "Check your content for plagiarism against web sources."}
                {activeTab === "grammar" && "Fix grammatical errors and improve your writing."}
                {activeTab === "summarizer" && "Create concise summaries of longer texts."}
                {activeTab === "translator" && "Translate your content to different languages."}
              </p>
            </div>

            <Card className="p-6 mb-6">
              <div className="flex border-b border-border mb-6">
                <button 
                  className={`px-4 py-2 font-heading font-medium ${
                    activeTab === "paraphraser" 
                      ? "text-primary border-b-2 border-primary" 
                      : "text-muted-foreground hover:text-foreground transition-colors"
                  }`}
                  onClick={() => setActiveTab("paraphraser")}
                >
                  Paraphraser
                </button>
                <button 
                  className={`px-4 py-2 font-heading font-medium ${
                    activeTab === "plagiarism" 
                      ? "text-primary border-b-2 border-primary" 
                      : "text-muted-foreground hover:text-foreground transition-colors"
                  }`}
                  onClick={() => setActiveTab("plagiarism")}
                >
                  Plagiarism Checker
                </button>
                <button 
                  className={`px-4 py-2 font-heading font-medium ${
                    activeTab === "grammar" 
                      ? "text-primary border-b-2 border-primary" 
                      : "text-muted-foreground hover:text-foreground transition-colors"
                  }`}
                  onClick={() => setActiveTab("grammar")}
                >
                  Grammar
                </button>
              </div>

              <TextEditor 
                value={text} 
                onChange={setText}
                onFormatCommand={handleFormatCommand}
              />

              {activeTab === "paraphraser" && (
                <ParaphrasingOptions 
                  selectedStyle={paraphrasingStyle} 
                  onStyleChange={setParaphrasingStyle}
                />
              )}

              <div className="flex flex-col sm:flex-row gap-4 justify-between">
                <div className="flex flex-wrap gap-2">
                  {activeTab === "paraphraser" && (
                    <Button
                      className="flex items-center gap-2"
                      onClick={handleParaphrase}
                      disabled={isProcessing || !text.trim()}
                    >
                      <i className="ri-quill-pen-line"></i>
                      <span>Paraphrase</span>
                    </Button>
                  )}
                  
                  {activeTab === "plagiarism" && (
                    <Button
                      className="flex items-center gap-2"
                      onClick={handleCheckPlagiarism}
                      disabled={isProcessing || !text.trim()}
                    >
                      <i className="ri-search-eye-line"></i>
                      <span>Check Plagiarism</span>
                    </Button>
                  )}
                  
                  <Button
                    variant="secondary"
                    className="flex items-center gap-2"
                    onClick={handleUpload}
                  >
                    <i className="ri-upload-2-line"></i>
                    <span>Upload Document</span>
                  </Button>
                </div>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    className="flex items-center gap-2"
                    onClick={handleClear}
                  >
                    <i className="ri-delete-bin-line"></i>
                    <span>Clear</span>
                  </Button>
                  <Button
                    variant="outline"
                    className="flex items-center gap-2"
                    onClick={handleExample}
                  >
                    <i className="ri-file-text-line"></i>
                    <span>Example</span>
                  </Button>
                </div>
              </div>
            </Card>

            {activeTab === "paraphraser" ? (
              <ResultsArea 
                isLoading={isProcessing}
                result={paraphraseResult}
                onReparaphrase={handleParaphrase}
                onStyleChange={() => {
                  toast({
                    title: "Change style",
                    description: "Select a different style above and click 'Paraphrase' again.",
                  });
                }}
                originalText={text}
              />
            ) : activeTab === "plagiarism" ? (
              <PlagiarismResults 
                result={plagiarismResult}
                isLoading={isProcessing}
                onFixPlagiarism={handleFixPlagiarism}
              />
            ) : (
              <Card className="p-6 mb-6 flex items-center justify-center min-h-[300px]">
                <div className="text-center text-muted-foreground">
                  <i className="ri-tools-line text-3xl mb-3"></i>
                  <p className="text-lg">Coming Soon</p>
                  <p className="mt-2">This feature is under development and will be available soon!</p>
                </div>
              </Card>
            )}

            <Card className="p-6 bg-surface border-2 border-primary">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="font-heading text-lg font-semibold text-foreground mb-2">Upgrade to Premium</h3>
                  <p className="text-muted-foreground">Get unlimited checks, priority processing, and advanced tools.</p>
                  <ul className="mt-3 space-y-1">
                    <li className="flex items-center text-sm text-muted-foreground">
                      <i className="ri-check-line text-accent mr-2"></i>
                      Unlimited paraphrasing
                    </li>
                    <li className="flex items-center text-sm text-muted-foreground">
                      <i className="ri-check-line text-accent mr-2"></i>
                      Advanced plagiarism detection
                    </li>
                    <li className="flex items-center text-sm text-muted-foreground">
                      <i className="ri-check-line text-accent mr-2"></i>
                      No ads or waiting times
                    </li>
                  </ul>
                </div>
                <div className="hidden md:block">
                  <Button>Upgrade Now</Button>
                </div>
              </div>
              <div className="md:hidden mt-4">
                <Button className="w-full">Upgrade Now</Button>
              </div>
            </Card>
          </div>
        </div>
      </main>
      
      <Footer />
    </div>
  );
}
