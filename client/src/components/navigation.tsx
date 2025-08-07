import { Link } from "wouter";
import { Button } from "@/components/ui/button";

import { useState } from "react";
import { Menu, X } from "lucide-react";

export default function Navigation() {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <nav className="bg-white shadow-sm border-b border-neutral-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <Link href="/">
                <span className="text-xl sm:text-2xl font-bold text-gradient-primary">
                  StacGateLMS
                </span>
              </Link>
            </div>
            
            {/* Desktop Navigation */}
            <div className="hidden lg:block ml-8 xl:ml-10">
              <div className="flex items-baseline space-x-6 xl:space-x-8">
                <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300 text-sm font-medium">
                  Cours
                </a>
                <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300 text-sm font-medium">
                  À propos
                </a>
                <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300 text-sm font-medium">
                  Contact
                </a>
              </div>
            </div>
          </div>

          {/* Desktop Actions */}
          <div className="hidden md:flex items-center space-x-3 lg:space-x-4">
            <Link href="/login">
              <Button variant="ghost" className="text-neutral-500 hover:text-primary text-sm px-3 lg:px-4">
                Connexion
              </Button>
            </Link>
            <Link href="/login">
              <Button className="bg-primary text-white hover:bg-primary/90 text-sm px-4 lg:px-6">
                Commencer
              </Button>
            </Link>
          </div>

          {/* Mobile Menu Button */}
          <div className="md:hidden">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="text-neutral-500 hover:text-primary"
            >
              {isMobileMenuOpen ? <X size={20} /> : <Menu size={20} />}
            </Button>
          </div>
        </div>

        {/* Mobile Menu */}
        {isMobileMenuOpen && (
          <div className="md:hidden py-4 border-t border-neutral-100">
            <div className="flex flex-col space-y-3">
              {/* Mobile Navigation Links */}
              <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300 px-3 py-2 text-sm font-medium">
                Cours
              </a>
              <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300 px-3 py-2 text-sm font-medium">
                À propos
              </a>
              <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300 px-3 py-2 text-sm font-medium">
                Contact
              </a>
              
              {/* Mobile Actions */}
              <div className="flex flex-col space-y-2 px-3 pt-4 border-t border-neutral-100">
                <Link href="/login">
                  <Button variant="ghost" className="w-full justify-start text-neutral-500 hover:text-primary">
                    Connexion
                  </Button>
                </Link>
                <Link href="/login">
                  <Button className="w-full bg-primary text-white hover:bg-primary/90">
                    Commencer
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        )}
      </div>
    </nav>
  );
}
