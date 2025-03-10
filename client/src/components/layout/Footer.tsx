import { Link } from "wouter";

export default function Footer() {
  return (
    <footer className="bg-surface border-t border-border py-6 px-4 lg:px-6">
      <div className="container mx-auto">
        <div className="flex flex-col md:flex-row justify-between items-center">
          <div className="flex items-center space-x-2 mb-4 md:mb-0">
            <i className="ri-quill-pen-line text-primary text-xl"></i>
            <span className="font-heading font-bold text-foreground">TextRefine</span>
          </div>
          <div className="flex flex-wrap justify-center gap-x-6 gap-y-2 mb-4 md:mb-0">
            <Link href="/terms">
              <a className="text-sm text-muted-foreground hover:text-foreground transition-colors">Terms of Service</a>
            </Link>
            <Link href="/privacy">
              <a className="text-sm text-muted-foreground hover:text-foreground transition-colors">Privacy Policy</a>
            </Link>
            <Link href="/help">
              <a className="text-sm text-muted-foreground hover:text-foreground transition-colors">Help Center</a>
            </Link>
            <Link href="/contact">
              <a className="text-sm text-muted-foreground hover:text-foreground transition-colors">Contact Us</a>
            </Link>
          </div>
          <div className="flex space-x-4">
            <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" className="text-muted-foreground hover:text-foreground transition-colors">
              <i className="ri-twitter-x-line text-xl"></i>
            </a>
            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" className="text-muted-foreground hover:text-foreground transition-colors">
              <i className="ri-facebook-circle-line text-xl"></i>
            </a>
            <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" className="text-muted-foreground hover:text-foreground transition-colors">
              <i className="ri-linkedin-box-line text-xl"></i>
            </a>
            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" className="text-muted-foreground hover:text-foreground transition-colors">
              <i className="ri-instagram-line text-xl"></i>
            </a>
          </div>
        </div>
        <div className="text-center mt-6 text-xs text-muted-foreground">
          © {new Date().getFullYear()} TextRefine. All rights reserved.
        </div>
      </div>
    </footer>
  );
}
