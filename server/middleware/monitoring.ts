import type { Request, Response, NextFunction } from 'express'

interface RequestMetrics {
  timestamp: number
  method: string
  path: string
  statusCode: number
  responseTime: number
  userAgent?: string
  ip: string
  userId?: string
  establishmentId?: string
}

class MonitoringService {
  private metrics: RequestMetrics[] = []
  private maxMetrics = 10000 // Keep last 10k requests in memory

  /**
   * Log request metrics
   */
  logRequest(metrics: RequestMetrics): void {
    this.metrics.push(metrics)
    
    // Keep only the last maxMetrics entries
    if (this.metrics.length > this.maxMetrics) {
      this.metrics = this.metrics.slice(-this.maxMetrics)
    }
  }

  /**
   * Get metrics summary
   */
  getMetrics(): {
    totalRequests: number
    averageResponseTime: number
    errorRate: number
    requestsPerMinute: number
    topEndpoints: { path: string; count: number }[]
    recentErrors: RequestMetrics[]
  } {
    const now = Date.now()
    const oneMinuteAgo = now - 60 * 1000
    const oneHourAgo = now - 60 * 60 * 1000
    
    const recentMetrics = this.metrics.filter(m => m.timestamp > oneHourAgo)
    const lastMinuteMetrics = this.metrics.filter(m => m.timestamp > oneMinuteAgo)
    
    const totalRequests = recentMetrics.length
    const averageResponseTime = totalRequests > 0 
      ? recentMetrics.reduce((sum, m) => sum + m.responseTime, 0) / totalRequests 
      : 0
    
    const errorRequests = recentMetrics.filter(m => m.statusCode >= 400)
    const errorRate = totalRequests > 0 ? (errorRequests.length / totalRequests) * 100 : 0
    
    const requestsPerMinute = lastMinuteMetrics.length
    
    // Top endpoints by request count
    const endpointCounts = new Map<string, number>()
    recentMetrics.forEach(m => {
      const count = endpointCounts.get(m.path) || 0
      endpointCounts.set(m.path, count + 1)
    })
    
    const topEndpoints = Array.from(endpointCounts.entries())
      .map(([path, count]) => ({ path, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 10)
    
    const recentErrors = errorRequests
      .filter(m => m.timestamp > oneMinuteAgo)
      .slice(-20) // Last 20 errors
    
    return {
      totalRequests,
      averageResponseTime: Math.round(averageResponseTime * 100) / 100,
      errorRate: Math.round(errorRate * 100) / 100,
      requestsPerMinute,
      topEndpoints,
      recentErrors
    }
  }

  /**
   * Get health status
   */
  getHealthStatus(): {
    status: 'healthy' | 'warning' | 'critical'
    checks: {
      responseTime: { status: string; value: number }
      errorRate: { status: string; value: number }
      requestVolume: { status: string; value: number }
    }
  } {
    const metrics = this.getMetrics()
    
    // Health thresholds
    const responseTimeThreshold = 1000 // ms
    const errorRateThreshold = 5 // %
    const requestVolumeThreshold = 100 // requests per minute
    
    const responseTimeStatus = metrics.averageResponseTime < responseTimeThreshold ? 'healthy' : 'warning'
    const errorRateStatus = metrics.errorRate < errorRateThreshold ? 'healthy' : 'critical'
    const requestVolumeStatus = metrics.requestsPerMinute < requestVolumeThreshold ? 'healthy' : 'warning'
    
    const checks = {
      responseTime: { status: responseTimeStatus, value: metrics.averageResponseTime },
      errorRate: { status: errorRateStatus, value: metrics.errorRate },
      requestVolume: { status: requestVolumeStatus, value: metrics.requestsPerMinute }
    }
    
    const hasWarning = Object.values(checks).some(check => check.status === 'warning')
    const hasCritical = Object.values(checks).some(check => check.status === 'critical')
    
    const status = hasCritical ? 'critical' : hasWarning ? 'warning' : 'healthy'
    
    return { status, checks }
  }
}

const monitoringService = new MonitoringService()

/**
 * Request monitoring middleware
 */
export function requestMonitoring(req: Request, res: Response, next: NextFunction): void {
  const startTime = Date.now()
  
  // Override res.end to capture response time
  const originalEnd = res.end.bind(res)
  res.end = function(chunk: any, encoding?: any): Response {
    const endTime = Date.now()
    const responseTime = endTime - startTime
    
    // Extract user info from session if available
    const user = (req as any).user
    
    const metrics: RequestMetrics = {
      timestamp: startTime,
      method: req.method,
      path: req.path,
      statusCode: res.statusCode,
      responseTime,
      userAgent: req.get('User-Agent'),
      ip: req.ip || req.connection.remoteAddress || 'unknown',
      userId: user?.id,
      establishmentId: user?.establishment_id
    }
    
    monitoringService.logRequest(metrics)
    
    // Call original end method
    return originalEnd.call(this, chunk, encoding)
  }
  
  next()
}

/**
 * Export monitoring service for use in routes
 */
export { monitoringService }