import { Router } from 'express'
import { monitoringService } from '../../middleware/monitoring'

const router = Router()

/**
 * @swagger
 * /api/system/health:
 *   get:
 *     summary: Get system health status
 *     tags: [System]
 *     responses:
 *       200:
 *         description: System health status
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 status:
 *                   type: string
 *                   enum: [healthy, warning, critical]
 *                 checks:
 *                   type: object
 *                   properties:
 *                     responseTime:
 *                       type: object
 *                       properties:
 *                         status:
 *                           type: string
 *                         value:
 *                           type: number
 *                     errorRate:
 *                       type: object
 *                       properties:
 *                         status:
 *                           type: string
 *                         value:
 *                           type: number
 *                     requestVolume:
 *                       type: object
 *                       properties:
 *                         status:
 *                           type: string
 *                         value:
 *                           type: number
 */
router.get('/health', (req, res) => {
  try {
    const healthStatus = monitoringService.getHealthStatus()
    res.json({
      success: true,
      data: healthStatus
    })
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to get health status'
    })
  }
})

/**
 * @swagger
 * /api/system/metrics:
 *   get:
 *     summary: Get system metrics
 *     tags: [System]
 *     responses:
 *       200:
 *         description: System metrics
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 totalRequests:
 *                   type: number
 *                 averageResponseTime:
 *                   type: number
 *                 errorRate:
 *                   type: number
 *                 requestsPerMinute:
 *                   type: number
 *                 topEndpoints:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       path:
 *                         type: string
 *                       count:
 *                         type: number
 */
router.get('/metrics', (req, res) => {
  try {
    const metrics = monitoringService.getMetrics()
    res.json({
      success: true,
      data: metrics
    })
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to get metrics'
    })
  }
})

export default router