import { Card, CardContent } from "@/components/ui/card";

export type HistoryItem = {
  id: string;
  title: string;
  timestamp: string;
  displayTime: string;
  type: "paraphrased" | "plagiarism";
  plagiarismPercentage?: number;
};

type SidebarProps = {
  activeTab: string;
  history: HistoryItem[];
  onTabChange: (tab: string) => void;
  onHistoryClick: (historyId: string) => void;
  onHistoryClear: () => void;
};

export default function Sidebar({
  activeTab,
  history,
  onTabChange,
  onHistoryClick,
  onHistoryClear
}: SidebarProps) {
  const tabs = [
    { id: "paraphraser", icon: "ri-book-2-line", label: "Paraphraser" },
    { id: "plagiarism", icon: "ri-search-eye-line", label: "Plagiarism Checker" },
    { id: "grammar", icon: "ri-spell-check-line", label: "Grammar Check" },
    { id: "summarizer", icon: "ri-character-recognition-line", label: "Summarizer" },
    { id: "translator", icon: "ri-translate-2", label: "Translator" },
  ];

  return (
    <div className="lg:w-64 shrink-0">
      <Card className="mb-6">
        <CardContent className="p-4">
          <div className="flex justify-between items-center mb-4">
            <h3 className="font-heading font-semibold text-foreground">Tools</h3>
          </div>
          <div className="space-y-2">
            {tabs.map(tab => (
              <button
                key={tab.id}
                className={`flex items-center w-full p-2 rounded-md ${
                  activeTab === tab.id 
                    ? "bg-surface-accent text-foreground" 
                    : "hover:bg-surface-hover text-muted-foreground hover:text-foreground transition-colors"
                }`}
                onClick={() => onTabChange(tab.id)}
              >
                <i className={`${tab.icon} mr-3 ${activeTab === tab.id ? "text-primary" : ""}`}></i>
                <span>{tab.label}</span>
              </button>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="p-4">
          <div className="flex justify-between items-center mb-4">
            <h3 className="font-heading font-semibold text-foreground">History</h3>
            <button 
              className="text-muted-foreground hover:text-foreground transition-colors"
              onClick={onHistoryClear}
            >
              <i className="ri-delete-bin-line"></i>
            </button>
          </div>
          
          {history.length > 0 ? (
            <>
              {history.map((item, index) => (
                <div 
                  key={item.id}
                  className={`border-b border-border py-3 ${index === history.length - 1 ? "border-b-0" : ""}`}
                >
                  <div className="flex justify-between mb-1">
                    <span className="text-sm font-medium text-foreground line-clamp-1">{item.title}</span>
                    <span className="text-xs text-muted-foreground">{item.displayTime}</span>
                  </div>
                  <div className="flex justify-between items-center">
                    {item.type === "paraphrased" ? (
                      <span className="text-xs text-accent">Paraphrased</span>
                    ) : (
                      <span className="text-xs text-destructive">Plagiarism: {item.plagiarismPercentage}%</span>
                    )}
                    <button 
                      className="text-muted-foreground hover:text-foreground transition-colors"
                      onClick={() => onHistoryClick(item.id)}
                    >
                      <i className="ri-arrow-right-s-line"></i>
                    </button>
                  </div>
                </div>
              ))}
              
              <button className="block w-full text-center text-sm text-primary hover:text-primary-hover mt-3 transition-colors">
                View all history
              </button>
            </>
          ) : (
            <div className="py-4 text-center text-muted-foreground">
              <i className="ri-history-line block mb-2 text-2xl"></i>
              <p>No history yet</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
