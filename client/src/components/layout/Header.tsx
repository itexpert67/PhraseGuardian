import { Button } from "@/components/ui/button";
import { useState } from "react";
import { Link } from "wouter";

export default function Header() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <header className="sticky top-0 z-10 bg-surface border-b border-border px-4 lg:px-6">
      <div className="container mx-auto py-4 flex justify-between items-center">
        <div className="flex items-center space-x-2">
          <div className="flex items-center">
            <i className="ri-quill-pen-line text-primary text-2xl mr-2"></i>
            <h1 className="font-heading text-xl font-bold text-foreground">TextRefine</h1>
          </div>
          <span className="bg-primary text-xs text-white px-2 py-0.5 rounded-full">Beta</span>
        </div>
        
        <div className="hidden md:flex items-center space-x-6">
          <Link href="#features">
            <a className="text-muted-foreground hover:text-foreground transition-colors">Features</a>
          </Link>
          <Link href="#pricing">
            <a className="text-muted-foreground hover:text-foreground transition-colors">Pricing</a>
          </Link>
          <Link href="#about">
            <a className="text-muted-foreground hover:text-foreground transition-colors">About</a>
          </Link>
          <Link href="#faq">
            <a className="text-muted-foreground hover:text-foreground transition-colors">FAQ</a>
          </Link>
        </div>
        
        <div className="flex items-center space-x-3">
          <button className="text-muted-foreground hover:text-foreground transition-colors">
            <i className="ri-settings-4-line text-xl"></i>
          </button>
          <Button variant="default" className="hidden md:block">
            Try Premium
          </Button>
          <button 
            className="md:hidden text-muted-foreground hover:text-foreground transition-colors"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          >
            <i className="ri-menu-line text-xl"></i>
          </button>
        </div>
      </div>
      
      {mobileMenuOpen && (
        <div className="md:hidden bg-surface border-t border-border py-2">
          <div className="container mx-auto px-4 flex flex-col">
            <Link href="#features">
              <a className="text-muted-foreground hover:text-foreground py-2 transition-colors">Features</a>
            </Link>
            <Link href="#pricing">
              <a className="text-muted-foreground hover:text-foreground py-2 transition-colors">Pricing</a>
            </Link>
            <Link href="#about">
              <a className="text-muted-foreground hover:text-foreground py-2 transition-colors">About</a>
            </Link>
            <Link href="#faq">
              <a className="text-muted-foreground hover:text-foreground py-2 transition-colors">FAQ</a>
            </Link>
            <Button variant="default" className="mt-3 mb-2">
              Try Premium
            </Button>
          </div>
        </div>
      )}
    </header>
  );
}
