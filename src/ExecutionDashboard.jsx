import React, { useState, useEffect } from 'react';
import { 
  Activity, 
  Users, 
  Clock, 
  CheckCircle, 
  XCircle, 
  TrendingUp,
  Zap
} from 'lucide-react';
import Layout from './components/Layout/Layout';
import MetricCard from './components/Dashboard/MetricCard';
import EmptyState from './components/Dashboard/EmptyState';
import DateRangePicker from './components/common/DateRangePicker';
import { SkeletonCard, SkeletonChart } from './components/common/LoadingSpinner';

// Mock data generator - replace with your actual API calls
const generateMockData = () => ({
  totalExecutions: 1247,
  uniqueUsers: 89,
  successRate: 94.5,
  avgDuration: '2.3s',
  growth: '+12.5%',
  recentExecutions: [
    { id: 1, user: 'john@example.com', status: 'success', time: '2 min ago', duration: '1.2s' },
    { id: 2, user: 'sarah@example.com', status: 'failed', time: '5 min ago', duration: '3.1s' },
    { id: 3, user: 'mike@example.com', status: 'success', time: '12 min ago', duration: '0.8s' },
  ]
});

const Dashboard = () => {
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState(null);
  const [dateRange, setDateRange] = useState('7d');
  const [hasData, setHasData] = useState(false);

  useEffect(() => {
    // Simulate API call
    const fetchData = async () => {
      setLoading(true);
      await new Promise(resolve => setTimeout(resolve, 1000));
      const mockData = generateMockData();
      setData(mockData);
      setHasData(mockData.totalExecutions > 0);
      setLoading(false);
    };

    fetchData();
  }, [dateRange]);

  const metrics = [
    {
      title: 'Total Executions',
      value: data?.totalExecutions?.toLocaleString() || '0',
      subtitle: 'All time executions',
      trend: 'up',
      trendValue: data?.growth || '0%',
      icon: Zap,
      color: 'blue'
    },
    {
      title: 'Unique Users',
      value: data?.uniqueUsers?.toString() || '0',
      subtitle: 'Active this month',
      trend: 'up',
      trendValue: '+5.2%',
      icon: Users,
      color: 'purple'
    },
    {
      title: 'Success Rate',
      value: `${data?.successRate || 0}%`,
      subtitle: 'Last 30 days',
      trend: 'up',
      trendValue: '+2.1%',
      icon: CheckCircle,
      color: 'green'
    },
    {
      title: 'Avg Duration',
      value: data?.avgDuration || '0s',
      subtitle: 'Per execution',
      trend: 'down',
      trendValue: '-0.3s',
      icon: Clock,
      color: 'orange'
    }
  ];

  return (
    <Layout>
      {/* Header Section */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h2 className="text-2xl font-bold text-white mb-1">Dashboard Overview</h2>
          <p className="text-gray-400">Monitor your execution metrics and performance</p>
        </div>
        <DateRangePicker value={dateRange} onChange={setDateRange} />
      </div>

      {/* Metrics Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {loading ? (
          <>
            <SkeletonCard />
            <SkeletonCard />
            <SkeletonCard />
            <SkeletonCard />
          </>
        ) : (
          metrics.map((metric, index) => (
            <MetricCard key={index} {...metric} />
          ))
        )}
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {/* Main Chart */}
        <div className="lg:col-span-2 rounded-2xl bg-gray-900 border border-gray-800 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-white">Execution Trends</h3>
            <div className="flex items-center gap-2">
              <span className="flex items-center gap-1 text-sm text-gray-400">
                <span className="w-2 h-2 rounded-full bg-blue-500" />
                Successful
              </span>
              <span className="flex items-center gap-1 text-sm text-gray-400">
                <span className="w-2 h-2 rounded-full bg-red-500" />
                Failed
              </span>
            </div>
          </div>
          
          {loading ? (
            <SkeletonChart />
          ) : !hasData ? (
            <EmptyState 
              title="No execution data"
              description="Start running executions to see trends and analytics here."
              actionLabel="Run First Execution"
              onAction={() => console.log('Run execution')}
            />
          ) : (
            <div className="h-64 flex items-end gap-2">
              {/* Replace with your actual chart library (recharts, chart.js, etc.) */}
              {[...Array(30)].map((_, i) => {
                const height = Math.random() * 60 + 20;
                return (
                  <div key={i} className="flex-1 flex flex-col gap-1 group cursor-pointer">
                    <div 
                      className="w-full bg-blue-600/80 rounded-t-sm hover:bg-blue-500 transition-colors relative"
                      style={{ height: `${height}%` }}
                    >
                      <div className="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                        {Math.floor(Math.random() * 100)} executions
                      </div>
                    </div>
                    <div 
                      className="w-full bg-red-500/60 rounded-t-sm hover:bg-red-400 transition-colors"
                      style={{ height: `${height * 0.2}%` }}
                    />
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Recent Activity */}
        <div className="rounded-2xl bg-gray-900 border border-gray-800 p-6">
          <h3 className="text-lg font-semibold text-white mb-6">Recent Activity</h3>
          
          {loading ? (
            <div className="space-y-4">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="flex items-center gap-3 animate-pulse">
                  <div className="w-10 h-10 rounded-full bg-gray-800" />
                  <div className="flex-1">
                    <div className="w-32 h-4 rounded bg-gray-800 mb-2" />
                    <div className="w-20 h-3 rounded bg-gray-800" />
                  </div>
                </div>
              ))}
            </div>
          ) : !hasData ? (
            <EmptyState 
              title="No recent activity"
              description="Recent executions will appear here."
              icon={Activity}
            />
          ) : (
            <div className="space-y-4">
              {data?.recentExecutions?.map((execution) => (
                <div 
                  key={execution.id} 
                  className="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/50 transition-colors group cursor-pointer"
                >
                  <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
                    execution.status === 'success' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'
                  }`}>
                    {execution.status === 'success' ? <CheckCircle size={20} /> : <XCircle size={20} />}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors">
                      {execution.user}
                    </p>
                    <p className="text-xs text-gray-500">{execution.time} • {execution.duration}</p>
                  </div>
                  <div className={`text-xs font-medium px-2 py-1 rounded-full ${
                    execution.status === 'success' 
                      ? 'bg-emerald-500/10 text-emerald-400' 
                      : 'bg-red-500/10 text-red-400'
                  }`}>
                    {execution.status}
                  </div>
                </div>
              ))}
              
              <button className="w-full py-3 text-sm text-blue-400 hover:text-blue-300 font-medium hover:bg-blue-500/10 rounded-xl transition-colors">
                View all executions →
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Hourly Distribution */}
      <div className="rounded-2xl bg-gray-900 border border-gray-800 p-6">
        <h3 className="text-lg font-semibold text-white mb-6">Hourly Distribution (24h)</h3>
        {loading ? (
          <SkeletonChart />
        ) : !hasData ? (
          <EmptyState 
            title="No hourly data"
            description="Execution patterns by hour will appear here once you have data."
            icon={Clock}
          />
        ) : (
          <div className="h-48 flex items-end gap-1">
            {[...Array(24)].map((_, i) => {
              const height = Math.random() * 80 + 10;
              const isPeak = height > 70;
              return (
                <div key={i} className="flex-1 flex flex-col items-center gap-1 group">
                  <div 
                    className={`w-full rounded-t transition-all duration-300 ${
                      isPeak ? 'bg-purple-500' : 'bg-gray-700 hover:bg-gray-600'
                    }`}
                    style={{ height: `${height}%` }}
                  />
                  <span className="text-[10px] text-gray-600 group-hover:text-gray-400">
                    {i}:00
                  </span>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </Layout>
  );
};

export default Dashboard;
