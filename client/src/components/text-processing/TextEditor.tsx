import { useState, useRef, useEffect } from "react";
import { useToast } from "@/hooks/use-toast";

export type FormatCommand = "bold" | "italic" | "underline" | "clear";

type TextEditorProps = {
  value: string;
  onChange: (value: string) => void;
  onFormatCommand: (command: FormatCommand) => void;
};

export default function TextEditor({ value, onChange, onFormatCommand }: TextEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null);
  const [wordCount, setWordCount] = useState(0);
  const [charCount, setCharCount] = useState(0);
  const { toast } = useToast();

  useEffect(() => {
    if (editorRef.current) {
      editorRef.current.innerHTML = value;
    }
    updateCounts(value);
  }, [value]);

  const updateCounts = (text: string) => {
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;
    const chars = text.length;
    
    setWordCount(words);
    setCharCount(chars);
  };

  const handleEditorChange = () => {
    if (editorRef.current) {
      const newContent = editorRef.current.innerHTML;
      onChange(newContent);
      updateCounts(editorRef.current.textContent || "");
    }
  };

  const handleFormat = (command: FormatCommand) => {
    onFormatCommand(command);
  };

  const handleCopy = () => {
    if (editorRef.current && editorRef.current.textContent) {
      navigator.clipboard.writeText(editorRef.current.textContent);
      toast({
        title: "Copied to clipboard",
        description: "Text has been copied to clipboard successfully",
      });
    }
  };

  return (
    <div className="mb-6">
      <div className="flex justify-between items-center mb-3">
        <div className="flex space-x-1">
          <button 
            className="p-1.5 rounded hover:bg-surface-hover transition-colors tooltip"
            onClick={() => handleFormat("bold")}
          >
            <i className="ri-bold"></i>
            <span className="tooltip-text text-xs">Bold</span>
          </button>
          <button 
            className="p-1.5 rounded hover:bg-surface-hover transition-colors tooltip"
            onClick={() => handleFormat("italic")}
          >
            <i className="ri-italic"></i>
            <span className="tooltip-text text-xs">Italic</span>
          </button>
          <button 
            className="p-1.5 rounded hover:bg-surface-hover transition-colors tooltip"
            onClick={() => handleFormat("underline")}
          >
            <i className="ri-underline"></i>
            <span className="tooltip-text text-xs">Underline</span>
          </button>
          <div className="border-r border-border mx-1 h-6"></div>
          <button 
            className="p-1.5 rounded hover:bg-surface-hover transition-colors tooltip"
            onClick={() => handleFormat("clear")}
          >
            <i className="ri-format-clear"></i>
            <span className="tooltip-text text-xs">Clear formatting</span>
          </button>
          <div className="border-r border-border mx-1 h-6"></div>
          <button 
            className="p-1.5 rounded hover:bg-surface-hover transition-colors tooltip"
            onClick={handleCopy}
          >
            <i className="ri-clipboard-line"></i>
            <span className="tooltip-text text-xs">Copy</span>
          </button>
        </div>
        <div className="flex items-center space-x-2 text-muted-foreground text-sm">
          <span>{wordCount} words</span>
          <span>|</span>
          <span>{charCount} characters</span>
        </div>
      </div>
      <div
        ref={editorRef}
        className="editor p-4 focus:outline-none min-h-[300px] rounded-md border border-border bg-surface-accent transition-all"
        contentEditable="true"
        onInput={handleEditorChange}
        data-placeholder="Enter your text here..."
      />
    </div>
  );
}
