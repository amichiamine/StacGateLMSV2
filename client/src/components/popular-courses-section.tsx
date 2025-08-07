import { Button } from "@/components/ui/button";
import { Star, Code, BarChart3, Megaphone } from "lucide-react";

const courses = [
  {
    icon: Code,
    title: "HTML, CSS & JavaScript",
    category: "Développement Web",
    description: "Maîtrisez les fondamentaux du développement web moderne",
    price: "49€",
    rating: "4.8",
    gradient: "from-primary to-secondary",
    categoryColor: "bg-accent/10 text-accent"
  },
  {
    icon: BarChart3,
    title: "Analyse de Données",
    category: "Data Science",
    description: "Apprenez à analyser et visualiser les données efficacement",
    price: "79€",
    rating: "4.9",
    gradient: "from-secondary to-accent",
    categoryColor: "bg-secondary/10 text-secondary"
  },
  {
    icon: Megaphone,
    title: "Marketing Digital",
    category: "Marketing",
    description: "Stratégies et outils pour réussir en ligne",
    price: "59€",
    rating: "4.7",
    gradient: "from-accent to-primary",
    categoryColor: "bg-primary/10 text-primary"
  }
];

export default function PopularCoursesSection() {
  return (
    <section className="py-20 bg-neutral-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-bold text-neutral-900 mb-4">
            Cours Populaires
          </h2>
          <p className="text-xl text-neutral-600">
            Découvrez nos cours les plus appréciés par la communauté
          </p>
        </div>
        
        <div className="grid md:grid-cols-3 gap-8">
          {courses.map((course, index) => (
            <div 
              key={index}
              className="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2"
            >
              <div className={`h-48 bg-gradient-to-br ${course.gradient} flex items-center justify-center`}>
                <course.icon className="text-white text-4xl w-16 h-16" />
              </div>
              <div className="p-6">
                <div className="flex items-center justify-between mb-3">
                  <span className={`${course.categoryColor} px-3 py-1 rounded-full text-sm font-medium`}>
                    {course.category}
                  </span>
                  <div className="flex items-center text-yellow-500">
                    <Star className="w-4 h-4 fill-current" />
                    <span className="ml-1 text-neutral-600">{course.rating}</span>
                  </div>
                </div>
                <h3 className="text-xl font-semibold text-neutral-900 mb-3">{course.title}</h3>
                <p className="text-neutral-600 mb-4">{course.description}</p>
                <div className="flex items-center justify-between">
                  <span className="text-2xl font-bold text-primary">{course.price}</span>
                  <Button className="bg-primary text-white hover:bg-primary/90">
                    Découvrir
                  </Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
