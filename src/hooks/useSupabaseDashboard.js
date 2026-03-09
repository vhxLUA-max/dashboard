import { useState, useEffect, useCallback } from 'react'
import { supabase } from '../lib/supabase'

export const useSupabaseDashboard = (dateRange = '7d') => {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [realtimeExecutions, setRealtimeExecutions] = useState([])

  const fetchData = useCallback(async () => {
    try {
      setLoading(true)
      setError(null)

      const rangeMap = {
        '24h': 1,
        '7d': 7,
        '30d': 30,
        '90d': 90
      }
      const days = rangeMap[dateRange] || 7
      const startDate = new Date()
      startDate.setDate(startDate.getDate() - days)

      const { data: executions, error: execError } = await supabase
        .from('executions')
        .select('*')
        .gte('created_at', startDate.toISOString())
        .order('created_at', { ascending: false })

      if (execError) throw execError

      const total = executions.length
      const successful = executions.filter(e => e.status === 'success').length
      const failed = executions.filter(e => e.status === 'failed').length
      const successRate = total > 0 ? (successful / total * 100).toFixed(1) : 0
      const avgDuration = total > 0 
        ? (executions.reduce((acc, e) => acc + (e.duration_ms || 0), 0) / total).toFixed(0)
        : 0

      const uniqueUsers = [...new Set(executions.map(e => e.user_id))].length

      setData({
        totalExecutions: total,
        uniqueUsers,
        successRate,
        avgDuration: `${(avgDuration / 1000).toFixed(1)}s`,
        successful,
        failed,
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

  useEffect(() => {
    fetchData()

    const subscription = supabase
      .channel('executions_channel')
      .on(
        'postgres_changes',
        { event: 'INSERT', schema: 'public', table: 'executions' },
        (payload) => {
          setRealtimeExecutions(prev => [payload.new, ...prev].slice(0, 5))
          fetchData()
        }
      )
      .subscribe()

    return () => subscription.unsubscribe()
  }, [fetchData])

  const insertExecution = async (executionData) => {
    const { data, error } = await supabase
      .from('executions')
      .insert([executionData])
      .select()
    
    if (error) throw error
    return data
  }

  return { data, loading, error, realtimeExecutions, refresh: fetchData, insertExecution }
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
