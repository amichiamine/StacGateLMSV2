import { Button } from "@/components/ui/button";
import { X } from "lucide-react";

interface PagePreviewProps {
  pageData: any;
  onExitPreview: () => void;
}

export function PagePreview({ pageData, onExitPreview }: PagePreviewProps) {
  const renderComponent = (component: any) => {
    const { type, data } = component;

    switch (type) {
      case "hero":
        return (
          <div
            className="relative py-20 px-4 text-center"
            style={{
              backgroundImage: data.backgroundImage ? `url(${data.backgroundImage})` : undefined,
              backgroundSize: "cover",
              backgroundPosition: "center",
              backgroundColor: data.backgroundImage ? undefined : "#f3f4f6",
            }}
          >
            <div className="max-w-4xl mx-auto">
              {data.title && (
                <h1 className={`text-4xl md:text-6xl font-bold mb-6 text-${data.textAlign || 'center'}`}>
                  {data.title}
                </h1>
              )}
              {data.subtitle && (
                <h2 className={`text-xl md:text-2xl mb-6 text-${data.textAlign || 'center'} opacity-80`}>
                  {data.subtitle}
                </h2>
              )}
              {data.description && (
                <p className={`text-lg mb-8 text-${data.textAlign || 'center'} max-w-2xl mx-auto`}>
                  {data.description}
                </p>
              )}
              {data.buttonText && (
                <a
                  href={data.buttonUrl || "#"}
                  className="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                >
                  {data.buttonText}
                </a>
              )}
            </div>
          </div>
        );

      case "features":
        return (
          <div className="py-16 px-4">
            <div className="max-w-6xl mx-auto">
              {data.title && (
                <h2 className="text-3xl font-bold text-center mb-4">{data.title}</h2>
              )}
              {data.subtitle && (
                <p className="text-xl text-center mb-12 text-gray-600">{data.subtitle}</p>
              )}
              
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {(data.features || []).map((feature: any, index: number) => (
                  <div key={index} className="text-center">
                    <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <span className="text-2xl">
                        {feature.icon === 'star' && '⭐'}
                        {feature.icon === 'heart' && '❤️'}
                        {feature.icon === 'shield' && '🛡️'}
                        {feature.icon === 'rocket' && '🚀'}
                        {feature.icon === 'trophy' && '🏆'}
                        {feature.icon === 'users' && '👥'}
                      </span>
                    </div>
                    <h3 className="text-xl font-semibold mb-2">{feature.title}</h3>
                    <p className="text-gray-600">{feature.description}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        );

      case "stats":
        return (
          <div className="py-16 px-4 bg-gray-50">
            <div className="max-w-6xl mx-auto">
              {data.title && (
                <h2 className="text-3xl font-bold text-center mb-12">{data.title}</h2>
              )}
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                {(data.stats || []).map((stat: any, index: number) => (
                  <div key={index} className="text-center">
                    <div className="text-4xl font-bold text-blue-600 mb-2">{stat.number}</div>
                    <div className="text-lg text-gray-600">{stat.label}</div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        );

      case "text":
        return (
          <div className="py-8 px-4">
            <div className="max-w-4xl mx-auto">
              <div 
                className={`text-${data.textAlign || 'left'} text-${data.fontSize || 'base'}`}
                style={{ whiteSpace: 'pre-wrap' }}
              >
                {data.content}
              </div>
            </div>
          </div>
        );

      case "image":
        return (
          <div className="py-8 px-4">
            <div className={`max-w-4xl mx-auto text-${data.align || 'center'}`}>
              {data.src && (
                <img
                  src={data.src}
                  alt={data.alt || ""}
                  className="mx-auto rounded-lg shadow-lg"
                  style={{ width: data.width || "100%" }}
                />
              )}
              {data.caption && (
                <p className="text-sm text-gray-600 mt-2 italic">{data.caption}</p>
              )}
            </div>
          </div>
        );

      case "navigation":
        return (
          <nav className="bg-white shadow-sm border-b">
            <div className="max-w-6xl mx-auto px-4">
              <div className="flex items-center justify-between h-16">
                <div className="flex items-center space-x-4">
                  {data.logo && (
                    <img src={data.logo} alt="Logo" className="h-8 w-auto" />
                  )}
                  {data.logoText && (
                    <span className="text-xl font-bold">{data.logoText}</span>
                  )}
                </div>
                
                <div className="hidden md:flex space-x-8">
                  {(data.menuItems || []).map((item: any, index: number) => (
                    <a
                      key={index}
                      href={item.url}
                      className="text-gray-700 hover:text-blue-600 transition-colors"
                    >
                      {item.label}
                    </a>
                  ))}
                </div>
              </div>
            </div>
          </nav>
        );

      case "footer":
        return (
          <footer className="bg-gray-900 text-white py-12 px-4">
            <div className="max-w-6xl mx-auto">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                  <h3 className="text-lg font-semibold mb-4">Liens rapides</h3>
                  <div className="space-y-2">
                    {(data.links || []).map((link: any, index: number) => (
                      <a
                        key={index}
                        href={link.url}
                        className="block text-gray-300 hover:text-white transition-colors"
                      >
                        {link.label}
                      </a>
                    ))}
                  </div>
                </div>
                
                <div>
                  <h3 className="text-lg font-semibold mb-4">Contact</h3>
                  <p className="text-gray-300">
                    Contactez-nous pour plus d'informations
                  </p>
                </div>
                
                <div>
                  <h3 className="text-lg font-semibold mb-4">Suivez-nous</h3>
                  <div className="flex space-x-4">
                    {(data.socialLinks || []).map((social: any, index: number) => (
                      <a
                        key={index}
                        href={social.url}
                        className="text-gray-300 hover:text-white transition-colors"
                      >
                        {social.platform}
                      </a>
                    ))}
                  </div>
                </div>
              </div>
              
              <div className="border-t border-gray-800 mt-8 pt-8 text-center">
                <p className="text-gray-400">{data.copyright}</p>
              </div>
            </div>
          </footer>
        );

      default:
        return (
          <div className="py-8 px-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div className="text-center">
              <p className="text-yellow-800">
                Aperçu du composant "{type}" non disponible
              </p>
              <p className="text-sm text-yellow-600 mt-1">
                Le composant sera affiché correctement sur le site final
              </p>
            </div>
          </div>
        );
    }
  };

  const renderSection = (section: any) => {
    console.log(`[PagePreview] Rendering section: ${section.type} with ${(section.components || []).length} components`);
    return (
      <div key={section.type} className={`section-${section.type}`}>
        {(section.components || []).map((component: any, index: number) => {
          console.log(`[PagePreview] Rendering component ${index}: ${component.type}`, component);
          return (
            <div key={component.id || index}>
              {renderComponent(component)}
            </div>
          );
        })}
      </div>
    );
  };

  return (
    <div className="h-screen bg-white">
      {/* Barre d'outils d'aperçu */}
      <div className="bg-gray-900 text-white p-4 flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold">Aperçu de la page</h2>
          <p className="text-sm text-gray-300">{pageData.pageTitle}</p>
        </div>
        
        <Button
          variant="ghost"
          size="sm"
          onClick={onExitPreview}
          className="text-white hover:bg-gray-800"
        >
          <X className="w-4 h-4 mr-2" />
          Fermer l'aperçu
        </Button>
      </div>

      {/* Contenu de la page */}
      <div className="h-full overflow-auto">
        {/* Rendu des sections */}
        {(pageData.layout?.sections || []).map((section: any) => renderSection(section))}
        
        {/* Message si aucune section */}
        {(!pageData.layout?.sections || pageData.layout.sections.length === 0) && (
          <div className="flex items-center justify-center h-full">
            <div className="text-center text-gray-500">
              <p className="text-xl mb-2">Page vide</p>
              <p>Ajoutez des composants pour voir l'aperçu</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default PagePreview;