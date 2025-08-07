import { useState, useEffect, createContext, useContext } from 'react';

export type ColorScheme = 'purple' | 'blue' | 'green' | 'orange' | 'red' | 'azur';
export type ThemeMode = 'light' | 'dark';
export type FontSize = 'sm' | 'base' | 'lg';

export interface ThemeConfig {
  colorScheme: ColorScheme;
  mode: ThemeMode;
  fontSize: FontSize;
}

const defaultTheme: ThemeConfig = {
  colorScheme: 'purple',
  mode: 'light',
  fontSize: 'base',
};

export const ThemeContext = createContext<{
  theme: ThemeConfig;
  setColorScheme: (scheme: ColorScheme) => void;
  setMode: (mode: ThemeMode) => void;
  setFontSize: (size: FontSize) => void;
  toggleMode: () => void;
}>({
  theme: defaultTheme,
  setColorScheme: () => {},
  setMode: () => {},
  setFontSize: () => {},
  toggleMode: () => {},
});

export const useTheme = () => {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
};

export const useThemeHook = () => {
  const [theme, setTheme] = useState<ThemeConfig>(() => {
    if (typeof window !== 'undefined') {
      const savedTheme = localStorage.getItem('stacgate-theme');
      if (savedTheme) {
        try {
          return JSON.parse(savedTheme);
        } catch {
          return defaultTheme;
        }
      }
    }
    return defaultTheme;
  });

  useEffect(() => {
    const root = document.documentElement;
    
    // Apply color scheme
    root.className = root.className.replace(/theme-\w+/g, '');
    root.classList.add(`theme-${theme.colorScheme}`);
    
    // Apply dark mode
    if (theme.mode === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
    
    // Apply font size
    root.style.setProperty('--font-size-current', `var(--font-size-${theme.fontSize})`);
    
    // Save to localStorage
    localStorage.setItem('stacgate-theme', JSON.stringify(theme));
  }, [theme]);

  const setColorScheme = (scheme: ColorScheme) => {
    setTheme(prev => ({ ...prev, colorScheme: scheme }));
  };

  const setMode = (mode: ThemeMode) => {
    setTheme(prev => ({ ...prev, mode }));
  };

  const setFontSize = (fontSize: FontSize) => {
    setTheme(prev => ({ ...prev, fontSize }));
  };

  const toggleMode = () => {
    setTheme(prev => ({ 
      ...prev, 
      mode: prev.mode === 'light' ? 'dark' : 'light' 
    }));
  };

  return {
    theme,
    setColorScheme,
    setMode,
    setFontSize,
    toggleMode,
  };
};