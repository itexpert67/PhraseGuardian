import { useState, useCallback } from "react";
import { FormatCommand } from "@/components/text-processing/TextEditor";

export function useTextEditor(initialText: string = "") {
  const [text, setText] = useState(initialText);

  const applyFormatting = useCallback((command: FormatCommand) => {
    if (typeof document === "undefined") return;
    
    switch (command) {
      case "bold":
        document.execCommand("bold", false);
        break;
      case "italic":
        document.execCommand("italic", false);
        break;
      case "underline":
        document.execCommand("underline", false);
        break;
      case "clear":
        // Safely unwrap all formatting, retaining only plain text
        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0) {
          const range = selection.getRangeAt(0);
          const content = range.cloneContents();
          const text = content.textContent || "";
          range.deleteContents();
          range.insertNode(document.createTextNode(text));
          selection.removeAllRanges();
          selection.addRange(range);
        } else {
          // If nothing is selected, get the active element (likely the editor)
          const activeElement = document.activeElement as HTMLElement;
          if (activeElement && activeElement.isContentEditable) {
            const plainText = activeElement.textContent || "";
            activeElement.innerHTML = plainText;
          }
        }
        break;
    }
  }, []);

  return {
    text,
    setText,
    applyFormatting
  };
}
