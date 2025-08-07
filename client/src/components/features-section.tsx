import { Brain, Users, Award } from "lucide-react";

const features = [
  {
    icon: Brain,
    title: "Apprentissage Adaptatif",
    description: "Notre IA personnalise votre parcours d'apprentissage selon vos besoins et votre rythme.",
    gradient: "from-primary to-secondary"
  },
  {
    icon: Users,
    title: "Communauté Active",
    description: "Échangez avec d'autres apprenants, participez à des forums et collaborez sur des projets.",
    gradient: "from-secondary to-accent"
  },
  {
    icon: Award,
    title: "Certifications",
    description: "Obtenez des certificats reconnus pour valoriser vos compétences sur le marché du travail.",
    gradient: "from-accent to-primary"
  }
];

export default function FeaturesSection() {
  return (
    <section className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-bold text-neutral-900 mb-4">
            Pourquoi choisir StacGateLMS ?
          </h2>
          <p className="text-xl text-neutral-600 max-w-3xl mx-auto">
            Une plateforme conçue pour maximiser votre apprentissage avec des outils modernes 
            et une approche pédagogique innovante.
          </p>
        </div>
        
        <div className="grid md:grid-cols-3 gap-8">
          {features.map((feature, index) => (
            <div 
              key={index}
              className="bg-gradient-to-br from-neutral-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-neutral-100"
            >
              <div className={`w-16 h-16 bg-gradient-to-br ${feature.gradient} rounded-2xl flex items-center justify-center mb-6`}>
                <feature.icon className="text-white text-2xl w-8 h-8" />
              </div>
              <h3 className="text-xl font-semibold text-neutral-900 mb-4">{feature.title}</h3>
              <p className="text-neutral-600 leading-relaxed">
                {feature.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
