import { Link } from "wouter";
import { Button } from "@/components/ui/button";

import { useState } from "react";
import { Menu, X } from "lucide-react";

export default function Navigation() {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  return (
    <>
      {/* Glassmorphism Navigation */}
      <nav className="fixed top-0 w-full z-50 backdrop-blur-md bg-white/20 border-b border-white/30 shadow-2xl glassmorphism">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* Logo */}
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <Link href="/">
                  <span className="text-xl sm:text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent hover:from-purple-600 hover:to-blue-600 transition-all duration-300">
                    StacGateLMS
                  </span>
                </Link>
              </div>
              
              {/* Desktop Navigation */}
              <div className="hidden lg:block ml-8 xl:ml-10">
                <div className="flex items-baseline space-x-6 xl:space-x-8">
                  <a href="#" className="text-blue-900 hover:text-blue-600 transition-colors duration-300 text-sm font-medium relative group">
                    Cours
                    <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                  </a>
                  <a href="#" className="text-blue-900 hover:text-blue-600 transition-colors duration-300 text-sm font-medium relative group">
                    À propos
                    <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                  </a>
                  <a href="#" className="text-blue-900 hover:text-blue-600 transition-colors duration-300 text-sm font-medium relative group">
                    Contact
                    <span className="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                  </a>
                </div>
              </div>
            </div>

            {/* Desktop Actions */}
            <div className="hidden md:flex items-center space-x-3 lg:space-x-4">
              <Link href="/login">
                <Button variant="ghost" className="text-blue-900 hover:text-blue-600 hover:bg-blue-50/50 text-sm px-3 lg:px-4 transition-all duration-300">
                  Connexion
                </Button>
              </Link>
              <Link href="/login">
                <Button className="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-sm px-4 lg:px-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                  Commencer
                </Button>
              </Link>
            </div>

            {/* Mobile Menu Button - Version plus visible */}
            <div className="md:hidden">
              <button
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="relative w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center transform hover:scale-105 border-2 border-white/20"
                data-testid="mobile-menu-button"
              >
                <div className="flex flex-col justify-center items-center w-6 h-6">
                  <span className={`block h-1 w-6 bg-white rounded-full transition-all duration-300 ${
                    isMobileMenuOpen ? 'rotate-45 translate-y-2' : ''
                  }`}></span>
                  <span className={`block h-1 w-6 bg-white rounded-full transition-all duration-300 mt-1 ${
                    isMobileMenuOpen ? 'opacity-0' : ''
                  }`}></span>
                  <span className={`block h-1 w-6 bg-white rounded-full transition-all duration-300 mt-1 ${
                    isMobileMenuOpen ? '-rotate-45 -translate-y-2' : ''
                  }`}></span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Mobile Menu avec Glassmorphism */}
      <div className={`fixed inset-0 z-40 transition-all duration-300 ${isMobileMenuOpen ? 'visible opacity-100' : 'invisible opacity-0'}`}>
        {/* Overlay */}
        <div 
          className="absolute inset-0 bg-black/20 backdrop-blur-sm"
          onClick={() => setIsMobileMenuOpen(false)}
        ></div>
        
        {/* Menu Panel */}
        <div className={`absolute top-16 right-0 left-0 mx-4 transform transition-all duration-300 ${
          isMobileMenuOpen ? 'translate-y-0 scale-100' : '-translate-y-4 scale-95'
        }`}>
          <div className="glass-mobile-menu rounded-2xl border border-white/30 shadow-2xl p-6">
            {/* Mobile Navigation Links */}
            <div className="flex flex-col space-y-4 mb-6">
              <a 
                href="#" 
                className="text-blue-900 hover:text-blue-600 transition-colors duration-300 text-lg font-medium py-2 px-4 rounded-lg hover:bg-white/30 backdrop-blur-sm"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Cours
              </a>
              <a 
                href="#" 
                className="text-blue-900 hover:text-blue-600 transition-colors duration-300 text-lg font-medium py-2 px-4 rounded-lg hover:bg-white/30 backdrop-blur-sm"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                À propos
              </a>
              <a 
                href="#" 
                className="text-blue-900 hover:text-blue-600 transition-colors duration-300 text-lg font-medium py-2 px-4 rounded-lg hover:bg-white/30 backdrop-blur-sm"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                Contact
              </a>
            </div>
            
            {/* Mobile Actions */}
            <div className="flex flex-col space-y-3 pt-4 border-t border-white/30">
              <Link href="/login">
                <Button 
                  variant="ghost" 
                  className="w-full justify-center text-blue-900 hover:text-blue-600 hover:bg-white/30 py-3 text-lg font-medium transition-all duration-300"
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Connexion
                </Button>
              </Link>
              <Link href="/login">
                <Button 
                  className="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 text-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-[1.02]"
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Commencer
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </div>
      
      {/* Spacer for fixed navigation */}
      <div className="h-16"></div>
    </>
  );
}
