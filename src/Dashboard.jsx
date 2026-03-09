import React, { useState } from 'react'
import { Activity, Users, Clock, CheckCircle, XCircle, Zap, Plus, RefreshCw } from 'lucide-react'
import { useSupabaseDashboard } from './hooks/useSupabaseDashboard'

// Components (create these or inline them)
const MetricCard = ({ title, value, subtitle, icon: Icon, color }) => {
  const colors = {
    blue: 'from-blue-500 to-blue-600',
    green: 'from-emerald-500 to-emerald-600',
    purple: 'from-purple-500 to-purple-600',
    orange: 'from-orange-500 to-orange-600'
  }

  return (
    <div className="rounded-2xl bg-gray-900 border border-gray-800 p-6 hover:border-gray-700 transition-all">
      <div className={`w-12 h-12 rounded-xl bg-gradient-to-br ${colors[color]} bg-opacity-20 flex items-center justify-center mb-4`}>
        <Icon size={24} className="text-white" />
      </div>
      <h3 className="text-gray-400 text-sm mb-1">{title}</h3>
      <div className="text-3xl font-bold text-white mb-1">{value}</div>
      <p className="text-gray-500 text-sm">{subtitle}</p>
    </div>
  )
}

const EmptyState = ({ onAction }) => (
  <div className="flex flex-col items-center justify-center py-16 text-center">
    <div className="w-20 h-20 rounded-2xl bg-gray-800 flex items-center justify-center mb-4">
      <Activity size={32} className="text-gray-400" />
    </div>
    <h3 className="text-xl font-semibold text-white mb-2">No executions yet</h3>
    <p className="text-gray-400 mb-6">Start by creating a test execution</p>
    <button
      onClick={onAction}
      className="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-medium transition-colors"
    >
      <Plus size={20} />
      Create Test Execution
    </button>
  </div>
)

function App() {
  const [dateRange, setDateRange] = useState('7d')
  const { data, loading, error, realtimeExecutions, refresh, insertExecution } = useSupabaseDashboard(dateRange)

  const handleTestExecution = async () => {
    try {
      await insertExecution({
        username: 'test-user',
        status: Math.random() > 0.3 ? 'success' : 'failed',
        duration_ms: Math.floor(Math.random() * 5000),
        metadata: { test: true }
      })
    } catch (err) {
      alert('Error: ' + err.message)
    }
  }

  const metrics = [
    { title: 'Total Executions', value: data?.totalExecutions || 0, subtitle: 'All time', icon: Zap, color: 'blue' },
    { title: 'Unique Users', value: data?.uniqueUsers || 0, subtitle: 'Active users', icon: Users, color: 'purple' },
    { title: 'Success Rate', value: `${data?.successRate || 0}%`, subtitle: `${data?.successful || 0} passed`, icon: CheckCircle, color: 'green' },
    { title: 'Avg Duration', value: data?.avgDuration || '0s', subtitle: 'Per execution', icon: Clock, color: 'orange' }
  ]

  if (error) {
    return (
      <div className="min-h-screen bg-gray-950 text-white flex items-center justify-center">
        <div className="text-center">
          <XCircle size={48} className="text-red-500 mx-auto mb-4" />
          <h2 className="text-xl font-bold mb-2">Connection Error</h2>
          <p className="text-gray-400 mb-4">{error}</p>
          <button 
            onClick={refresh}
            className="flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg mx-auto"
          >
            <RefreshCw size={16} />
            Retry
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-950 text-white p-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold mb-1">Dashboard</h1>
          <p className="text-gray-400">Monitor your executions in real-time</p>
        </div>
        <div className="flex items-center gap-3">
          <button
            onClick={handleTestExecution}
            className="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-medium transition-colors"
          >
            <Plus size={18} />
            Test Execution
          </button>
          <select 
            value={dateRange} 
            onChange={(e) => setDateRange(e.target.value)}
            className="px-4 py-2 bg-gray-900 border border-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="24h">Last 24 hours</option>
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="90d">Last 90 days</option>
          </select>
        </div>
      </div>

      {/* Real-time indicator */}
      {realtimeExecutions.length > 0 && (
        <div className="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center gap-3">
          <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
          <span className="text-emerald-400">
            {realtimeExecutions.length} new execution{realtimeExecutions.length > 1 ? 's' : ''}
          </span>
        </div>
      )}

      {/* Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {loading ? (
          <>
            {[...Array(4)].map((_, i) => (
              <div key={i} className="h-32 bg-gray-900 rounded-2xl animate-pulse" />
            ))}
          </>
        ) : (
          metrics.map((metric, index) => (
            <MetricCard key={index} {...metric} />
          ))
        )}
      </div>

      {/* Main Content */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Chart Area */}
        <div className="lg:col-span-2 rounded-2xl bg-gray-900 border border-gray-800 p-6">
          <h3 className="text-lg font-semibold mb-6">Recent Activity</h3>
          
          {loading ? (
            <div className="h-64 bg-gray-800 rounded-xl animate-pulse" />
          ) : data?.recentExecutions?.length === 0 ? (
            <EmptyState onAction={handleTestExecution} />
          ) : (
            <div className="space-y-3">
              {data.recentExecutions.map((execution) => (
                <div 
                  key={execution.id} 
                  className="flex items-center gap-4 p-4 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors"
                >
                  <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
                    execution.status === 'success' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'
                  }`}>
                    {execution.status === 'success' ? <CheckCircle size={20} /> : <XCircle size={20} />}
                  </div>
                  <div className="flex-1">
                    <p className="font-medium">{execution.user}</p>
                    <p className="text-sm text-gray-400">{execution.time} • {execution.duration}</p>
                  </div>
                  <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                    execution.status === 'success' 
                      ? 'bg-emerald-500/10 text-emerald-400' 
                      : 'bg-red-500/10 text-red-400'
                  }`}>
                    {execution.status}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Stats */}
        <div className="rounded-2xl bg-gray-900 border border-gray-800 p-6">
          <h3 className="text-lg font-semibold mb-6">Quick Stats</h3>
          <div className="space-y-4">
            <div className="flex justify-between items-center p-4 bg-gray-800/50 rounded-xl">
              <span className="text-gray-400">Success Rate</span>
              <span className="text-2xl font-bold text-emerald-400">{data?.successRate || 0}%</span>
            </div>
            <div className="flex justify-between items-center p-4 bg-gray-800/50 rounded-xl">
              <span className="text-gray-400">Failed</span>
              <span className="text-2xl font-bold text-red-400">{data?.failed || 0}</span>
            </div>
            <div className="flex justify-between items-center p-4 bg-gray-800/50 rounded-xl">
              <span className="text-gray-400">Total</span>
              <span className="text-2xl font-bold text-white">{data?.totalExecutions || 0}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default App
