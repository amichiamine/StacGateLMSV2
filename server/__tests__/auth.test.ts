import { describe, it, expect, beforeEach, vi } from 'vitest'
import { AuthService } from '../services/AuthService'

// Mock storage
vi.mock('../storage', () => ({
  storage: {
    getUserByEmail: vi.fn(),
    createUser: vi.fn(),
    updateUser: vi.fn(),
    updateUserLastLogin: vi.fn(),
    getUser: vi.fn(),
  },
}))

// Mock bcrypt
vi.mock('bcryptjs', () => ({
  default: {
    compare: vi.fn(),
    hash: vi.fn(),
  },
}))

describe('AuthService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('authenticateUser', () => {
    it('should return user when credentials are valid', async () => {
      const mockUser = {
        id: '1',
        email: 'test@example.com',
        password: '$2a$10$hashedpassword',
        first_name: 'Test',
        last_name: 'User',
        role: 'apprenant' as const,
        establishment_id: '1',
        is_active: true,
        created_at: new Date(),
        updated_at: new Date(),
      }

      const { storage } = await import('../storage')
      const bcrypt = await import('bcryptjs')
      
      vi.mocked(storage.getUserByEmail).mockResolvedValue(mockUser)
      vi.mocked(bcrypt.default.compare).mockResolvedValue(true)
      vi.mocked(storage.updateUserLastLogin).mockResolvedValue()

      const result = await AuthService.authenticateUser('test@example.com', 'password', '1')
      
      expect(result).toBeDefined()
      expect(result?.email).toBe('test@example.com')
      expect(storage.updateUserLastLogin).toHaveBeenCalledWith('1')
    })

    it('should return null when user not found', async () => {
      const { storage } = await import('../storage')
      
      vi.mocked(storage.getUserByEmail).mockResolvedValue(undefined)

      const result = await AuthService.authenticateUser('test@example.com', 'password', '1')
      
      expect(result).toBeNull()
    })

    it('should return null when password is invalid', async () => {
      const mockUser = {
        id: '1',
        email: 'test@example.com',
        password: '$2a$10$hashedpassword',
        first_name: 'Test',
        last_name: 'User',
        role: 'apprenant' as const,
        establishment_id: '1',
        is_active: true,
        created_at: new Date(),
        updated_at: new Date(),
      }

      const { storage } = await import('../storage')
      const bcrypt = await import('bcryptjs')
      
      vi.mocked(storage.getUserByEmail).mockResolvedValue(mockUser)
      vi.mocked(bcrypt.default.compare).mockResolvedValue(false)

      const result = await AuthService.authenticateUser('test@example.com', 'wrongpassword', '1')
      
      expect(result).toBeNull()
    })
  })

  describe('hashPassword', () => {
    it('should hash password correctly', async () => {
      const bcrypt = await import('bcryptjs')
      vi.mocked(bcrypt.default.hash).mockResolvedValue('hashedpassword')

      const result = await AuthService.hashPassword('password')
      
      expect(result).toBe('hashedpassword')
      expect(bcrypt.default.hash).toHaveBeenCalledWith('password', 12)
    })
  })
})