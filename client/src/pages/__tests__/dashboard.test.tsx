import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@/test/test-utils'
import Dashboard from '../dashboard'

// Mock useAuth hook
vi.mock('@/hooks/useAuth', () => ({
  useAuth: vi.fn(),
}))

// Mock useQuery
vi.mock('@tanstack/react-query', async () => {
  const actual = await vi.importActual('@tanstack/react-query')
  return {
    ...actual,
    useQuery: vi.fn(),
  }
})

import { useAuth } from '@/hooks/useAuth'
import { useQuery } from '@tanstack/react-query'

describe('Dashboard Page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders welcome message for authenticated user', () => {
    vi.mocked(useAuth).mockReturnValue({
      user: {
        id: '1',
        first_name: 'Jean',
        last_name: 'Dupont',
        email: 'jean@example.com',
        role: 'apprenant',
        establishment_id: '1',
      },
      isLoading: false,
    })

    vi.mocked(useQuery).mockReturnValue({
      data: { courses: [], recentActivity: [] },
      isLoading: false,
      error: null,
    } as any)

    render(<Dashboard />)
    
    expect(screen.getByText(/bienvenue, jean/i)).toBeInTheDocument()
  })

  it('shows loading state when user data is loading', () => {
    vi.mocked(useAuth).mockReturnValue({
      user: null,
      isLoading: true,
    })

    vi.mocked(useQuery).mockReturnValue({
      data: undefined,
      isLoading: true,
      error: null,
    } as any)

    render(<Dashboard />)
    
    expect(screen.getByText(/chargement/i)).toBeInTheDocument()
  })

  it('shows redirect message for unauthenticated user', () => {
    vi.mocked(useAuth).mockReturnValue({
      user: null,
      isLoading: false,
    })

    vi.mocked(useQuery).mockReturnValue({
      data: undefined,
      isLoading: false,
      error: null,
    } as any)

    render(<Dashboard />)
    
    expect(screen.getByText(/redirection vers la page de connexion/i)).toBeInTheDocument()
  })
})