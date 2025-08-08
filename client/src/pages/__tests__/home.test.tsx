import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@/test/test-utils'
import Home from '../home'

// Mock useAuth hook
vi.mock('@/hooks/useAuth', () => ({
  useAuth: () => ({
    user: null,
    isLoading: false,
  }),
}))

describe('Home Page', () => {
  it('renders hero section', () => {
    render(<Home />)
    
    expect(screen.getByText(/bienvenue sur stacgatelms/i)).toBeInTheDocument()
    expect(screen.getByText(/plateforme d'apprentissage moderne/i)).toBeInTheDocument()
  })

  it('renders navigation links', () => {
    render(<Home />)
    
    expect(screen.getByText(/commencer maintenant/i)).toBeInTheDocument()
    expect(screen.getByText(/découvrir les fonctionnalités/i)).toBeInTheDocument()
  })

  it('renders features section', () => {
    render(<Home />)
    
    expect(screen.getByText(/fonctionnalités principales/i)).toBeInTheDocument()
    expect(screen.getByText(/cours interactifs/i)).toBeInTheDocument()
    expect(screen.getByText(/collaboration temps réel/i)).toBeInTheDocument()
  })

  it('renders popular courses section', () => {
    render(<Home />)
    
    expect(screen.getByText(/cours populaires/i)).toBeInTheDocument()
  })
})