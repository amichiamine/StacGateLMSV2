import { Link } from "wouter";
import { Button } from "@/components/ui/button";

export default function Navigation() {
  return (
    <nav className="bg-white shadow-sm border-b border-neutral-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <Link href="/">
                <span className="text-2xl font-bold text-gradient-primary">
                  StacGateLMS
                </span>
              </Link>
            </div>
            <div className="hidden md:block ml-10">
              <div className="flex items-baseline space-x-8">
                <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300">
                  Cours
                </a>
                <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300">
                  À propos
                </a>
                <a href="#" className="text-neutral-500 hover:text-primary transition-colors duration-300">
                  Contact
                </a>
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-4">
            <Link href="/login">
              <Button variant="ghost" className="text-neutral-500 hover:text-primary">
                Connexion
              </Button>
            </Link>
            <Link href="/login">
              <Button className="bg-primary text-white hover:bg-primary/90">
                Commencer
              </Button>
            </Link>
          </div>
        </div>
      </div>
    </nav>
  );
}
