import { Button } from "@/components/ui/button";
import { useState } from "react";
import { Link, useLocation } from "wouter";

export default function Header() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [location] = useLocation();

  return (
    <header className="sticky top-0 z-10 bg-surface border-b border-border px-4 lg:px-6">
      <div className="container mx-auto py-4 flex justify-between items-center">
        <div className="flex items-center space-x-2">
          <Link href="/">
            <div className="flex items-center cursor-pointer">
              <i className="ri-quill-pen-line text-primary text-2xl mr-2"></i>
              <h1 className="font-heading text-xl font-bold text-foreground">TextRefine</h1>
            </div>
          </Link>
          <span className="bg-primary text-xs text-white px-2 py-0.5 rounded-full">Beta</span>
        </div>
        
        <div className="hidden md:flex items-center space-x-6">
          <Link href="#features">
            <span className="text-muted-foreground hover:text-foreground transition-colors cursor-pointer">Features</span>
          </Link>
          <Link href="#pricing">
            <span className="text-muted-foreground hover:text-foreground transition-colors cursor-pointer">Pricing</span>
          </Link>
          <Link href="#about">
            <span className="text-muted-foreground hover:text-foreground transition-colors cursor-pointer">About</span>
          </Link>
          <Link href="#faq">
            <span className="text-muted-foreground hover:text-foreground transition-colors cursor-pointer">FAQ</span>
          </Link>
        </div>
        
        <div className="flex items-center space-x-3">
          <button className="text-muted-foreground hover:text-foreground transition-colors">
            <i className="ri-settings-4-line text-xl"></i>
          </button>
          
          {/* Authentication Links */}
          <div className="hidden md:flex items-center space-x-3">
            <Link href="/login">
              <Button variant="ghost">Log In</Button>
            </Link>
            <Link href="/signup">
              <Button variant="default">Sign Up</Button>
            </Link>
          </div>
          
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
              <span className="text-muted-foreground hover:text-foreground py-2 transition-colors cursor-pointer">Features</span>
            </Link>
            <Link href="#pricing">
              <span className="text-muted-foreground hover:text-foreground py-2 transition-colors cursor-pointer">Pricing</span>
            </Link>
            <Link href="#about">
              <span className="text-muted-foreground hover:text-foreground py-2 transition-colors cursor-pointer">About</span>
            </Link>
            <Link href="#faq">
              <span className="text-muted-foreground hover:text-foreground py-2 transition-colors cursor-pointer">FAQ</span>
            </Link>
            
            {/* Mobile Authentication Links */}
            <div className="py-2 flex flex-col space-y-2">
              <Link href="/login">
                <Button variant="outline" className="w-full">Log In</Button>
              </Link>
              <Link href="/signup">
                <Button variant="default" className="w-full">Sign Up</Button>
              </Link>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
