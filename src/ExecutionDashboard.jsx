import React, { useState, useEffect } from 'react';
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Activity, Clock, TrendingUp, TrendingDown, Users, Search, X } from 'lucide-react';

const ExecutionDashboard = () => {
  const [supabaseUrl] = useState(process.env.REACT_APP_SUPABASE_URL || '');
  const [apiKey] = useState(process.env.REACT_APP_SUPABASE_ANON_KEY || '');
  const [isConnected, setIsConnected] = useState(false);
  const [totalExecutions, setTotalExecutions] = useState(0);
  const [dailyData, setDailyData] = useState([]);
  const [hourlyData, setHourlyData] = useState([]);
  const [lastExecutionTime, setLastExecutionTime] = useState(null);
  const [timeSinceLast, setTimeSinceLast] = useState('');
  const [growthPercent, setGrowthPercent] = useState(null);
  const [todayCount, setTodayCount] = useState(0);
  const [yesterdayCount, setYesterdayCount] = useState(0);
  const [uniqueUsers, setUniqueUsers] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Search state
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchError, setSearchError] = useState('');
  const [hasSearched, setHasSearched] = useState(false);

  // Update "time since last execution" every second
  useEffect(() => {
    const timer = setInterval(() => {
      if (lastExecutionTime) {
        const diff = Math.floor((new Date() - new Date(lastExecutionTime)) / 1000);
        if (diff < 60) setTimeSinceLast(`${diff}s ago`);
        else if (diff < 3600) setTimeSinceLast(`${Math.floor(diff / 60)}m ago`);
        else setTimeSinceLast(`${Math.floor(diff / 3600)}h ago`);
      }
    }, 1000);
    return () => clearInterval(timer);
  }, [lastExecutionTime]);

  const searchUsername = async () => {
    if (!searchQuery.trim()) return;
    setSearchLoading(true);
    setSearchError('');
    setHasSearched(true);

    try {
      const headers = {
        'Authorization': `Bearer ${apiKey}`,
        'apikey': apiKey,
        'Content-Type': 'application/json',
      };

      const res = await fetch(
        `${supabaseUrl}/rest/v1/unique_users?username=ilike.*${encodeURIComponent(searchQuery.trim())}*&select=username,created_at&limit=20`,
        { headers }
      );
      const data = await res.json();
      if (Array.isArray(data)) {
        setSearchResults(data);
      } else {
        setSearchError('No results found.');
        setSearchResults([]);
      }
    } catch (err) {
      setSearchError('Search failed. Try again.');
      setSearchResults([]);
    } finally {
      setSearchLoading(false);
    }
  };

  const clearSearch = () => {
    setSearchQuery('');
    setSearchResults([]);
    setHasSearched(false);
    setSearchError('');
  };

  const fetchData = async () => {
    if (!supabaseUrl || !apiKey) {
      setError('Supabase credentials not configured.');
      return;
    }

    setLoading(true);
    try {
      const headers = {
        'Authorization': `Bearer ${apiKey}`,
        'apikey': apiKey,
        'Content-Type': 'application/json',
      };

      // Fetch all game_executions rows
      let allData = [];
      let from = 0;
      const pageSize = 1000;
      while (true) {
        const res = await fetch(
          `${supabaseUrl}/rest/v1/game_executions?select=count,last_executed_at&offset=${from}&limit=${pageSize}`,
          { headers }
        );
        const page = await res.json();
        if (!Array.isArray(page) || page.length === 0) break;
        allData = [...allData, ...page];
        if (page.length < pageSize) break;
        from += pageSize;
      }

      const total = allData.reduce((sum, row) => sum + (parseInt(row.count) || 0), 0);
      setTotalExecutions(total);

      const sorted = [...allData].sort((a, b) => new Date(b.last_executed_at) - new Date(a.last_executed_at));
      if (sorted.length > 0) setLastExecutionTime(sorted[0].last_executed_at);

      const now = new Date();
      const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const startOfYesterday = new Date(startOfToday.getTime() - 24 * 60 * 60 * 1000);

      let todayTotal = 0;
      let yesterdayTotal = 0;
      allData.forEach(row => {
        const d = new Date(row.last_executed_at);
        const c = parseInt(row.count) || 0;
        if (d >= startOfToday) todayTotal += c;
        else if (d >= startOfYesterday) yesterdayTotal += c;
      });

      setTodayCount(todayTotal);
      setYesterdayCount(yesterdayTotal);

      if (yesterdayTotal > 0) {
        const growth = ((todayTotal - yesterdayTotal) / yesterdayTotal) * 100;
        setGrowthPercent(growth.toFixed(1));
      } else if (todayTotal > 0) {
        setGrowthPercent(100);
      } else {
        setGrowthPercent(0);
      }

      const uniqueRes = await fetch(
        `${supabaseUrl}/rest/v1/unique_users?select=*`,
        { method: 'HEAD', headers: { ...headers, 'Prefer': 'count=exact' } }
      );
      const cr = uniqueRes.headers.get('Content-Range');
      if (cr) setUniqueUsers(parseInt(cr.split('/')[1]) || 0);

      const dailyGrouped = {};
      allData.forEach(row => {
        const d = new Date(row.last_executed_at);
        const key = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        if (!dailyGrouped[key]) dailyGrouped[key] = { count: 0, ts: d };
        dailyGrouped[key].count += parseInt(row.count) || 0;
      });

      const dailyChartData = Object.entries(dailyGrouped)
        .map(([date, { count, ts }]) => ({ date, count, ts }))
        .sort((a, b) => a.ts - b.ts)
        .slice(-30)
        .map(({ date, count }) => ({ date, count }));
      setDailyData(dailyChartData);

      const oneDayAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000);
      const hourlyGrouped = {};
      for (let i = 0; i < 24; i++) {
        const hour = new Date(oneDayAgo.getTime() + i * 60 * 60 * 1000);
        const hourKey = hour.toLocaleTimeString('en-US', { hour: '2-digit', hour12: true });
        hourlyGrouped[hourKey] = 0;
      }
      allData.forEach(row => {
        const itemDate = new Date(row.last_executed_at);
        if (itemDate >= oneDayAgo) {
          const hourKey = itemDate.toLocaleTimeString('en-US', { hour: '2-digit', hour12: true });
          if (hourlyGrouped[hourKey] !== undefined) {
            hourlyGrouped[hourKey] += parseInt(row.count) || 0;
          }
        }
      });
      setHourlyData(Object.entries(hourlyGrouped).map(([time, count]) => ({ time, count })));
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

  const isPositiveGrowth = parseFloat(growthPercent) >= 0;

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
          <div className="mb-12 flex items-center justify-between">
            <div>
              <h1 className="text-4xl font-light text-white mb-2 tracking-tight">Execution Analytics</h1>
              <p className="text-zinc-400 text-sm">Real-time execution count tracking</p>
            </div>
            {lastExecutionTime && (
              <div className="flex items-center gap-2 bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2">
                <span className="relative flex h-2 w-2">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <Clock size={14} className="text-zinc-400" />
                <span className="text-zinc-300 text-sm">Last execution <span className="text-white font-medium">{timeSinceLast}</span></span>
              </div>
            )}
          </div>

          <div className="grid grid-cols-1 gap-8">

            {/* Stat cards */}
            <div className="grid grid-cols-1 gap-4" style={{gridTemplateColumns: 'repeat(3, 1fr)'}}>
              <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-6">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-zinc-400 text-sm font-medium">Total Executions</p>
                    <p className="text-4xl font-light text-white mt-2 tracking-tight">{totalExecutions.toLocaleString()}</p>
                  </div>
                  <div className="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <Activity size={20} className="text-blue-400" />
                  </div>
                </div>
              </div>

              <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-6">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-zinc-400 text-sm font-medium">Growth vs Yesterday</p>
                    <p className={`text-4xl font-light mt-2 tracking-tight ${isPositiveGrowth ? 'text-green-400' : 'text-red-400'}`}>
                      {growthPercent !== null ? `${isPositiveGrowth ? '+' : ''}${growthPercent}%` : '—'}
                    </p>
                    <p className="text-zinc-500 text-xs mt-1">Today: {todayCount.toLocaleString()} · Yesterday: {yesterdayCount.toLocaleString()}</p>
                  </div>
                  <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${isPositiveGrowth ? 'bg-green-500/20' : 'bg-red-500/20'}`}>
                    {isPositiveGrowth ? <TrendingUp size={20} className="text-green-400" /> : <TrendingDown size={20} className="text-red-400" />}
                  </div>
                </div>
              </div>

              <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-6">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-zinc-400 text-sm font-medium">Unique Users</p>
                    <p className="text-4xl font-light text-white mt-2 tracking-tight">{uniqueUsers.toLocaleString()}</p>
                  </div>
                  <div className="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                    <Users size={20} className="text-purple-400" />
                  </div>
                </div>
              </div>
            </div>

            {/* Username Search */}
            <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-8">
              <h2 className="text-lg font-light text-white mb-6 flex items-center gap-2">
                <Search size={18} className="text-zinc-400" />
                Username Search
              </h2>
              <div className="flex gap-3 mb-6">
                <div className="relative flex-1">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={e => setSearchQuery(e.target.value)}
                    onKeyDown={e => e.key === 'Enter' && searchUsername()}
                    placeholder="Search username..."
                    className="w-full bg-zinc-900 border border-zinc-600 text-white placeholder-zinc-500 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500 transition"
                  />
                  {searchQuery && (
                    <button onClick={clearSearch} className="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white">
                      <X size={14} />
                    </button>
                  )}
                </div>
                <button
                  onClick={searchUsername}
                  disabled={searchLoading || !searchQuery.trim()}
                  className="px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-zinc-700 text-white text-sm font-medium rounded-lg transition"
                >
                  {searchLoading ? 'Searching...' : 'Search'}
                </button>
              </div>

              {/* Results */}
              {hasSearched && (
                <div>
                  {searchError && <p className="text-red-400 text-sm">{searchError}</p>}
                  {!searchError && searchResults.length === 0 && (
                    <p className="text-zinc-500 text-sm">No users found matching <span className="text-zinc-300">"{searchQuery}"</span></p>
                  )}
                  {searchResults.length > 0 && (
                    <div>
                      <p className="text-zinc-500 text-xs mb-3">{searchResults.length} result{searchResults.length !== 1 ? 's' : ''} found</p>
                      <div className="space-y-2">
                        {searchResults.map((user, i) => (
                          <div key={i} className="flex items-center justify-between bg-zinc-900 border border-zinc-700 rounded-lg px-4 py-3">
                            <div className="flex items-center gap-3">
                              <div className="w-8 h-8 bg-blue-500/20 rounded-full flex items-center justify-center">
                                <span className="text-blue-400 text-xs font-medium">{user.username.charAt(0).toUpperCase()}</span>
                              </div>
                              <span className="text-white text-sm font-medium">{user.username}</span>
                            </div>
                            <span className="text-zinc-500 text-xs">
                              {new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Daily Chart */}
            <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-8">
              <h2 className="text-lg font-light text-white mb-6">Daily Executions (Last 30 Days)</h2>
              <ResponsiveContainer width="100%" height={350}>
                <BarChart data={dailyData} margin={{ top: 10, right: 10, left: 0, bottom: 10 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#27272a" />
                  <XAxis dataKey="date" stroke="#71717a" style={{ fontSize: '12px' }} />
                  <YAxis stroke="#71717a" style={{ fontSize: '12px' }} />
                  <Tooltip contentStyle={{ backgroundColor: '#27272a', border: '1px solid #3f3f46', borderRadius: '8px' }} labelStyle={{ color: '#fafafa' }} />
                  <Bar dataKey="count" fill="#3b82f6" radius={[8, 8, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>

            {/* Hourly Chart */}
            <div className="bg-gradient-to-br from-zinc-800 to-zinc-850 border border-zinc-700 rounded-xl p-8">
              <h2 className="text-lg font-light text-white mb-6">Hourly Executions (Last 24 Hours)</h2>
              <ResponsiveContainer width="100%" height={350}>
                <LineChart data={hourlyData} margin={{ top: 10, right: 10, left: 0, bottom: 10 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#27272a" />
                  <XAxis dataKey="time" stroke="#71717a" style={{ fontSize: '12px' }} />
                  <YAxis stroke="#71717a" style={{ fontSize: '12px' }} />
                  <Tooltip contentStyle={{ backgroundColor: '#27272a', border: '1px solid #3f3f46', borderRadius: '8px' }} labelStyle={{ color: '#fafafa' }} />
                  <Line type="monotone" dataKey="count" stroke="#3b82f6" dot={{ fill: '#3b82f6', r: 4 }} activeDot={{ r: 6 }} strokeWidth={2} />
                </LineChart>
              </ResponsiveContainer>
            </div>

            {/* Refresh */}
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
