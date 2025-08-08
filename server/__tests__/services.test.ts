import { describe, it, expect, beforeEach, vi } from 'vitest'
import { AnalyticsService } from '../services/AnalyticsService'
import { ExportService } from '../services/ExportService'
import type { DatabaseStorage } from '../storage'

// Mock storage
const mockStorage = {
  getCourseEnrollmentStats: vi.fn(),
  getUserActivityStats: vi.fn(),
  getEstablishmentStats: vi.fn(),
  getCourses: vi.fn(),
  getUsers: vi.fn(),
  getEnrollments: vi.fn(),
} as unknown as DatabaseStorage

describe('AnalyticsService', () => {
  let analyticsService: AnalyticsService

  beforeEach(() => {
    analyticsService = new AnalyticsService(mockStorage)
    vi.clearAllMocks()
  })

  describe('getDashboardStats', () => {
    it('should return dashboard statistics', async () => {
      const mockStats = {
        totalStudents: 100,
        totalCourses: 20,
        averageProgress: 75,
        completionRate: 85
      }

      vi.mocked(mockStorage.getCourseEnrollmentStats).mockResolvedValue(mockStats)

      const result = await analyticsService.getDashboardStats('establishment-1')
      
      expect(result).toEqual(mockStats)
      expect(mockStorage.getCourseEnrollmentStats).toHaveBeenCalledWith('establishment-1')
    })

    it('should handle errors gracefully', async () => {
      vi.mocked(mockStorage.getCourseEnrollmentStats).mockRejectedValue(new Error('Database error'))

      await expect(analyticsService.getDashboardStats('establishment-1')).rejects.toThrow('Database error')
    })
  })
})

describe('ExportService', () => {
  let exportService: ExportService

  beforeEach(() => {
    exportService = new ExportService(mockStorage)
    vi.clearAllMocks()
  })

  describe('exportCourses', () => {
    it('should export courses in CSV format', async () => {
      const mockCourses = [
        {
          id: '1',
          title: 'Course 1',
          description: 'Description 1',
          type: 'synchrone' as const,
          establishment_id: '1',
          instructor_id: '1',
          is_active: true,
          created_at: new Date(),
          updated_at: new Date(),
        }
      ]

      vi.mocked(mockStorage.getCourses).mockResolvedValue(mockCourses)

      const result = await exportService.exportCourses('establishment-1', 'csv')
      
      expect(result).toBeDefined()
      expect(result.type).toBe('csv')
      expect(result.data).toContain('Course 1')
      expect(mockStorage.getCourses).toHaveBeenCalledWith('establishment-1')
    })

    it('should export courses in JSON format', async () => {
      const mockCourses = [
        {
          id: '1',
          title: 'Course 1',
          description: 'Description 1',
          type: 'synchrone' as const,
          establishment_id: '1',
          instructor_id: '1',
          is_active: true,
          created_at: new Date(),
          updated_at: new Date(),
        }
      ]

      vi.mocked(mockStorage.getCourses).mockResolvedValue(mockCourses)

      const result = await exportService.exportCourses('establishment-1', 'json')
      
      expect(result).toBeDefined()
      expect(result.type).toBe('json')
      
      const parsedData = JSON.parse(result.data)
      expect(parsedData).toHaveLength(1)
      expect(parsedData[0].title).toBe('Course 1')
    })
  })
})