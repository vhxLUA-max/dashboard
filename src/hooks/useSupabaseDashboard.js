import { useState, useEffect, useCallback } from 'react'
import { supabase } from '../lib/supabase'

export const useSupabaseDashboard = (dateRange = '7d') => {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [realtimeExecutions, setRealtimeExecutions] = useState([])

  // Fetch initial data
  const fetchData = useCallback(async () => {
    try {
      setLoading(true)
      setError(null)

      // Calculate date range
      const rangeMap = {
        '24h': 1,
        '7d': 7,
        '30d': 30,
        '90d': 90
      }
      const days = rangeMap[dateRange] || 7
      const startDate = new Date()
      startDate.setDate(startDate.getDate() - days)

      // Get executions
      const { data: executions, error: execError } = await supabase
        .from('executions')
        .select('*')
        .gte('created_at', startDate.toISOString())
        .order('created_at', { ascending: false })

      if (execError) throw execError

      // Calculate metrics
      const total = executions.length
      const successful = executions.filter(e => e.status === 'success').length
      const failed = executions.filter(e => e.status === 'failed').length
      const successRate = total > 0 ? (successful / total * 100).toFixed(1) : 0
      const avgDuration = total > 0 
        ? (executions.reduce((acc, e) => acc + (e.duration_ms || 0), 0) / total).toFixed(0)
        : 0

      // Get unique users
      const uniqueUsers = [...new Set(executions.map(e => e.user_id))].length

      // Daily breakdown for charts
      const dailyData = getDailyBreakdown(executions, days)

      // Hourly distribution
      const hourlyData = getHourlyDistribution(executions)

      setData({
        totalExecutions: total,
        uniqueUsers,
        successRate,
        avgDuration: `${(avgDuration / 1000).toFixed(1)}s`,
        successful,
        failed,
        dailyData,
        hourlyData,
        recentExecutions: executions.slice(0, 10).map(e => ({
          id: e.id,
          user: e.username || e.user_id?.slice(0, 8) || 'Unknown',
          status: e.status,
          time: formatRelativeTime(e.created_at),
          duration: `${((e.duration_ms || 0) / 1000).toFixed(1)}s`
        }))
      })
    } catch (err) {
      console.error('Supabase error:', err)
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }, [dateRange])

  // Real-time subscription
  useEffect(() => {
    fetchData()

    // Subscribe to changes
    const subscription = supabase
      .channel('executions_channel')
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'executions'
        },
        (payload) => {
          const newExecution = payload.new
          
          // Add to realtime list
          setRealtimeExecutions(prev => [newExecution, ...prev].slice(0, 5))
          
          // Refresh data
          fetchData()
        }
      )
      .subscribe()

    return () => {
      subscription.unsubscribe()
    }
  }, [fetchData])

  const insertExecution = async (executionData) => {
    const { data, error } = await supabase
      .from('executions')
      .insert([executionData])
      .select()
    
    if (error) throw error
    return data
  }

  return {
    data,
    loading,
    error,
    realtimeExecutions,
    refresh: fetchData,
    insertExecution
  }
}

// Helper functions
function getDailyBreakdown(executions, days) {
  const data = []
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date()
    date.setDate(date.getDate() - i)
    const dateStr = date.toISOString().split('T')[0]
    
    const dayExecs = executions.filter(e => 
      e.created_at.startsWith(dateStr)
    )
    
    data.push({
      date: dateStr,
      successful: dayExecs.filter(e => e.status === 'success').length,
      failed: dayExecs.filter(e => e.status === 'failed').length
    })
  }
  return data
}

function getHourlyDistribution(executions) {
  const hours = Array(24).fill(0)
  executions.forEach(e => {
    const hour = new Date(e.created_at).getHours()
    hours[hour]++
  })
  return hours.map((count, hour) => ({ hour, count }))
}

function formatRelativeTime(dateString) {
  const date = new Date(dateString)
  const seconds = Math.floor((new Date() - date) / 1000)
  
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}
