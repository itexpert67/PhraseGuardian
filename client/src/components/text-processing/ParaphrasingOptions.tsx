import { Button } from "@/components/ui/button";

export type ParaphrasingStyle = 
  | "standard" 
  | "fluent" 
  | "academic" 
  | "simple" 
  | "creative" 
  | "business";

type ParaphrasingOptionsProps = {
  selectedStyle: ParaphrasingStyle;
  onStyleChange: (style: ParaphrasingStyle) => void;
};

export default function ParaphrasingOptions({ 
  selectedStyle, 
  onStyleChange 
}: ParaphrasingOptionsProps) {
  const styles: ParaphrasingStyle[] = [
    "standard", "fluent", "academic", "simple", "creative", "business"
  ];

  return (
    <div className="flex flex-wrap gap-3 mb-6">
      <div className="text-sm mr-2 mt-1 text-muted-foreground">Paraphrasing style:</div>
      {styles.map(style => (
        <Button
          key={style}
          variant={selectedStyle === style ? "default" : "ghost"}
          className={`px-3 py-1 h-auto text-sm rounded-full ${
            selectedStyle === style 
              ? "bg-primary text-white" 
              : "bg-surface-hover text-muted-foreground hover:text-foreground"
          }`}
          onClick={() => onStyleChange(style)}
        >
          {style.charAt(0).toUpperCase() + style.slice(1)}
        </Button>
      ))}
    </div>
  );
}
