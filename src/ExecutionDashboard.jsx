import React, { useState, useEffect } from 'react';
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Activity } from 'lucide-react';

const ExecutionDashboard = () => {
  const [supabaseUrl] = useState(process.env.REACT_APP_SUPABASE_URL || '');
  const [apiKey] = useState(process.env.REACT_APP_SUPABASE_ANON_KEY || '');
  const [isConnected, setIsConnected] = useState(false);
  const [totalExecutions, setTotalExecutions] = useState(0);
  const [dailyData, setDailyData] = useState([]);
  const [hourlyData, setHourlyData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const fetchData = async () => {
    if (!supabaseUrl || !apiKey) {
      setError('Supabase credentials not configured. Set REACT_APP_SUPABASE_URL and REACT_APP_SUPABASE_ANON_KEY environment variables.');
      return;
    }

    setLoading(true);
    try {
      const headers = {
        'Authorization': `Bearer ${apiKey}`,
        'apikey': apiKey,
        'Content-Type': 'application/json',
      };

      // Get exact total count via HEAD request (avoids row limit issues)
      const countRes = await fetch(`${supabaseUrl}/rest/v1/executions?select=*`, {
        method: 'HEAD',
        headers: { ...headers, 'Prefer': 'count=exact' },
      });
      const contentRange = countRes.headers.get('Content-Range');
      const total = contentRange ? parseInt(contentRange.split('/')[1]) : 0;
      setTotalExecutions(total);

      // Paginate through all rows for charts (Supabase caps at 1000 rows/page)
      let allData = [];
      let from = 0;
      const pageSize = 1000;
      while (true) {
        const res = await fetch(
          `${supabaseUrl}/rest/v1/executions?select=executed_at&offset=${from}&limit=${pageSize}`,
          { headers }
        );
        const page = await res.json();
        if (!Array.isArray(page) || page.length === 0) break;
        allData = [...allData, ...page];
        if (page.length < pageSize) break;
        from += pageSize;
      }

      // Process daily chart data
      const dailyGrouped = {};
      allData.forEach(item => {
        const d = new Date(item.executed_at);
        const key = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        if (!dailyGrouped[key]) dailyGrouped[key] = { count: 0, ts: d };
        dailyGrouped[key].count++;
      });

      const dailyChartData = Object.entries(dailyGrouped)
        .map(([date, { count, ts }]) => ({ date, count, ts }))
        .sort((a, b) => a.ts - b.ts)
        .slice(-30)
        .map(({ date, count }) => ({ date, count }));

      setDailyData(dailyChartData);

      // Process hourly chart data (last 24 hours)
      const now = new Date();
      const oneDayAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000);

      const hourlyGrouped = {};
      for (let i = 0; i < 24; i++) {
        const hour = new Date(oneDayAgo.getTime() + i * 60 * 60 * 1000);
        const hourKey = hour.toLocaleTimeString('en-US', { hour: '2-digit', hour12: true });
        hourlyGrouped[hourKey] = 0;
      }

      allData.forEach(item => {
        const itemDate = new Date(item.executed_at);
        if (itemDate >= oneDayAgo) {
          const hourKey = itemDate.toLocaleTimeString('en-US', { hour: '2-digit', hour12: true });
          if (hourlyGrouped[hourKey] !== undefined) {
            hourlyGrouped[hourKey] += 1;
          }
        }
      });

      const hourlyChartData = Object.entries(hourlyGrouped)
        .map(([time, count]) => ({ time, count }));

      setHourlyData(hourlyChartData);
      setIsConnected(true);
    } catch (err) {
      console.error('Error fetching data:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (supabaseUrl && apiKey) {
      fetchData();
      setIsConnected(true);
      const interval = setInterval(fetchData, 30000);
      return () => clearInterval(interval);
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div className="min-h-screen bg-gradient-to-br from-zinc-950 via-zinc-900 to-zinc-950">
      {error && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
          <div className="bg-red-900 rounded-xl p-8 max-w-md w-full border border-red-800 shadow-2xl">
            <h2 className="text-2xl font-light text-white mb-4 tracking-tight">Configuration Error</h2>
            <p className="text-red-100 text-sm mb-6">{error}</p>
            <p className="text-red-200 text-xs">Configure environment variables in Railway to get started.</p>
          </div>
        </div>
      )}

      {isConnected && (
        <div className="p-8">
          <div className="mb-12">
            <h1 className="text-4xl font-light text-white mb-2 tracking-tight">Execution Analytics</h1>
            <p className="text-zinc-400 text-sm">Real-time execution count tracking</p>
          </div>

          <div className="grid grid-cols-1 gap-8">
            <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-8">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-zinc-400 text-sm font-medium">Total Executions</p>
                  <p className="text-5xl font-light text-white mt-2 tracking-tight">{totalExecutions.toLocaleString()}</p>
                </div>
                <div className="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                  <Activity size={24} className="text-blue-400" />
                </div>
              </div>
            </div>

            <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-8">
              <h2 className="text-lg font-light text-white mb-6">Daily Executions (Last 30 Days)</h2>
              <ResponsiveContainer width="100%" height={350}>
                <BarChart data={dailyData} margin={{ top: 10, right: 10, left: 0, bottom: 10 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#27272a" />
                  <XAxis dataKey="date" stroke="#71717a" style={{ fontSize: '12px' }} />
                  <YAxis stroke="#71717a" style={{ fontSize: '12px' }} />
                  <Tooltip
                    contentStyle={{ backgroundColor: '#27272a', border: '1px solid #3f3f46', borderRadius: '8px' }}
                    labelStyle={{ color: '#fafafa' }}
                  />
                  <Bar dataKey="count" fill="#3b82f6" radius={[8, 8, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>

            <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-8">
              <h2 className="text-lg font-light text-white mb-6">Hourly Executions (Last 24 Hours)</h2>
              <ResponsiveContainer width="100%" height={350}>
                <LineChart data={hourlyData} margin={{ top: 10, right: 10, left: 0, bottom: 10 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#27272a" />
                  <XAxis dataKey="time" stroke="#71717a" style={{ fontSize: '12px' }} />
                  <YAxis stroke="#71717a" style={{ fontSize: '12px' }} />
                  <Tooltip
                    contentStyle={{ backgroundColor: '#27272a', border: '1px solid #3f3f46', borderRadius: '8px' }}
                    labelStyle={{ color: '#fafafa' }}
                  />
                  <Line
                    type="monotone"
                    dataKey="count"
                    stroke="#3b82f6"
                    dot={{ fill: '#3b82f6', r: 4 }}
                    activeDot={{ r: 6 }}
                    strokeWidth={2}
                  />
                </LineChart>
              </ResponsiveContainer>
            </div>

            <div className="flex justify-center">
              <button
                onClick={fetchData}
                disabled={loading}
                className="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-zinc-700 text-white font-medium rounded-lg transition duration-200"
              >
                {loading ? 'Refreshing...' : 'Refresh'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ExecutionDashboard;
