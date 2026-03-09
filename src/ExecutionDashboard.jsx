import React, { useState, useEffect, useCallback } from 'react';
import {
  BarChart, Bar, LineChart, Line, AreaChart, Area,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer
} from 'recharts';
import {
  Activity, Users, Search, X, TrendingUp, TrendingDown,
  Clock, Zap, LayoutDashboard, UserSearch, RefreshCw,
  ChevronRight, AlertCircle, Database, Globe
} from 'lucide-react';

// ── Skeleton loader ──────────────────────────────────────────────
const Skeleton = ({ className = '' }) => (
  <div className={`animate-pulse bg-white/5 rounded-lg ${className}`} />
);

// ── Stat card ────────────────────────────────────────────────────
const StatCard = ({ label, value, sub, icon: Icon, color, loading }) => {
  const colors = {
    blue:   { bg: 'bg-blue-500/10',   text: 'text-blue-400',   border: 'border-blue-500/20' },
    green:  { bg: 'bg-emerald-500/10', text: 'text-emerald-400', border: 'border-emerald-500/20' },
    purple: { bg: 'bg-violet-500/10', text: 'text-violet-400', border: 'border-violet-500/20' },
    amber:  { bg: 'bg-amber-500/10',  text: 'text-amber-400',  border: 'border-amber-500/20' },
    red:    { bg: 'bg-red-500/10',    text: 'text-red-400',    border: 'border-red-500/20' },
  };
  const c = colors[color] || colors.blue;

  return (
    <div className={`relative rounded-2xl border bg-[#0f1117] p-6 overflow-hidden transition-all duration-300 hover:scale-[1.02] ${c.border}`}
      style={{ boxShadow: '0 0 0 1px rgba(255,255,255,0.03), 0 4px 24px rgba(0,0,0,0.4)' }}>
      {/* Glow */}
      <div className={`absolute -top-8 -right-8 w-24 h-24 rounded-full blur-2xl opacity-30 ${c.bg}`} />
      <div className="relative">
        <div className="flex items-center justify-between mb-4">
          <span className="text-xs font-mono uppercase tracking-widest text-white/30">{label}</span>
          <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${c.bg}`}>
            <Icon size={15} className={c.text} />
          </div>
        </div>
        {loading ? (
          <>
            <Skeleton className="h-9 w-32 mb-2" />
            <Skeleton className="h-3 w-24" />
          </>
        ) : (
          <>
            <p className={`text-3xl font-light tracking-tight text-white mb-1`}>{value}</p>
            {sub && <p className="text-xs text-white/30 font-mono">{sub}</p>}
          </>
        )}
      </div>
    </div>
  );
};

// ── Empty state ───────────────────────────────────────────────────
const EmptyState = ({ title, desc }) => (
  <div className="flex flex-col items-center justify-center h-48 gap-3">
    <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center">
      <Database size={20} className="text-white/20" />
    </div>
    <p className="text-white/40 text-sm font-medium">{title}</p>
    <p className="text-white/20 text-xs text-center max-w-xs">{desc}</p>
  </div>
);

// ── Custom tooltip ────────────────────────────────────────────────
const CustomTooltip = ({ active, payload, label }) => {
  if (!active || !payload?.length) return null;
  return (
    <div className="bg-[#1a1d27] border border-white/10 rounded-xl px-4 py-3 shadow-2xl">
      <p className="text-white/50 text-xs font-mono mb-1">{label}</p>
      <p className="text-white font-light text-lg">{payload[0]?.value?.toLocaleString()}</p>
    </div>
  );
};

// ── Main dashboard ────────────────────────────────────────────────
const ExecutionDashboard = () => {
  const [supabaseUrl] = useState(process.env.REACT_APP_SUPABASE_URL || '');
  const [apiKey]      = useState(process.env.REACT_APP_SUPABASE_ANON_KEY || '');

  const [activeTab, setActiveTab]   = useState('overview');
  const [loading, setLoading]       = useState(true);
  const [lastFetch, setLastFetch]   = useState(null);
  const [error, setError]           = useState('');

  // Metrics
  const [totalExecutions, setTotalExecutions] = useState(0);
  const [uniqueUsers, setUniqueUsers]         = useState(0);
  const [todayCount, setTodayCount]           = useState(0);
  const [yesterdayCount, setYesterdayCount]   = useState(0);
  const [growthPercent, setGrowthPercent]     = useState(null);
  const [lastExecTime, setLastExecTime]       = useState(null);
  const [timeSinceLast, setTimeSinceLast]     = useState('');
  const [peakHour, setPeakHour]               = useState('—');

  // Charts
  const [dailyData, setDailyData]   = useState([]);
  const [hourlyData, setHourlyData] = useState([]);

  // Search
  const [searchQuery, setSearchQuery]     = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searchLoading, setSearchLoading] = useState(false);
  const [hasSearched, setHasSearched]     = useState(false);

  // Live timer
  useEffect(() => {
    const t = setInterval(() => {
      if (!lastExecTime) return;
      const diff = Math.floor((Date.now() - new Date(lastExecTime)) / 1000);
      if (diff < 60) setTimeSinceLast(`${diff}s ago`);
      else if (diff < 3600) setTimeSinceLast(`${Math.floor(diff / 60)}m ago`);
      else setTimeSinceLast(`${Math.floor(diff / 3600)}h ago`);
    }, 1000);
    return () => clearInterval(t);
  }, [lastExecTime]);

  const fetchData = useCallback(async () => {
    if (!supabaseUrl || !apiKey) { setError('Missing Supabase credentials.'); return; }
    setLoading(true);
    try {
      const h = { 'Authorization': `Bearer ${apiKey}`, 'apikey': apiKey, 'Content-Type': 'application/json' };

      // Paginate game_executions
      let all = [], from = 0;
      while (true) {
        const r = await fetch(`${supabaseUrl}/rest/v1/game_executions?select=count,last_executed_at&offset=${from}&limit=1000`, { headers: h });
        const p = await r.json();
        if (!Array.isArray(p) || !p.length) break;
        all = [...all, ...p];
        if (p.length < 1000) break;
        from += 1000;
      }

      const total = all.reduce((s, r) => s + (parseInt(r.count) || 0), 0);
      setTotalExecutions(total);

      // Last execution
      const sorted = [...all].sort((a, b) => new Date(b.last_executed_at) - new Date(a.last_executed_at));
      if (sorted[0]) setLastExecTime(sorted[0].last_executed_at);

      // Today vs yesterday
      const now = new Date();
      const startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const startYest  = new Date(startToday - 86400000);
      let tod = 0, yest = 0;
      all.forEach(row => {
        const d = new Date(row.last_executed_at), c = parseInt(row.count) || 0;
        if (d >= startToday) tod += c;
        else if (d >= startYest) yest += c;
      });
      setTodayCount(tod);
      setYesterdayCount(yest);
      setGrowthPercent(yest > 0 ? ((tod - yest) / yest * 100).toFixed(1) : tod > 0 ? 100 : 0);

      // Unique users count
      const ur = await fetch(`${supabaseUrl}/rest/v1/unique_users?select=*`, { method: 'HEAD', headers: { ...h, 'Prefer': 'count=exact' } });
      const cr = ur.headers.get('Content-Range');
      if (cr) setUniqueUsers(parseInt(cr.split('/')[1]) || 0);

      // Daily chart (last 30 days)
      const daily = {};
      all.forEach(row => {
        const d = new Date(row.last_executed_at);
        const k = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        if (!daily[k]) daily[k] = { count: 0, ts: d };
        daily[k].count += parseInt(row.count) || 0;
      });
      setDailyData(Object.entries(daily).map(([date, { count, ts }]) => ({ date, count, ts })).sort((a, b) => a.ts - b.ts).slice(-30).map(({ date, count }) => ({ date, count })));

      // Hourly chart (last 24h)
      const ago24 = new Date(now - 86400000);
      const hourly = {};
      for (let i = 0; i < 24; i++) {
        const hk = new Date(ago24.getTime() + i * 3600000).toLocaleTimeString('en-US', { hour: '2-digit', hour12: true });
        hourly[hk] = 0;
      }
      all.forEach(row => {
        const d = new Date(row.last_executed_at);
        if (d >= ago24) {
          const hk = d.toLocaleTimeString('en-US', { hour: '2-digit', hour12: true });
          if (hourly[hk] !== undefined) hourly[hk] += parseInt(row.count) || 0;
        }
      });
      const hourlyArr = Object.entries(hourly).map(([time, count]) => ({ time, count }));
      setHourlyData(hourlyArr);

      // Peak hour
      const peak = hourlyArr.reduce((a, b) => b.count > a.count ? b : a, { count: 0, time: '—' });
      setPeakHour(peak.count > 0 ? peak.time : '—');

      setLastFetch(new Date());
      setError('');
    } catch (e) {
      console.error(e);
      setError('Failed to fetch data.');
    } finally {
      setLoading(false);
    }
  }, [supabaseUrl, apiKey]); // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => {
    fetchData();
    const iv = setInterval(fetchData, 30000);
    return () => clearInterval(iv);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const searchUsername = async () => {
    if (!searchQuery.trim()) return;
    setSearchLoading(true);
    setHasSearched(true);
    try {
      const r = await fetch(
        `${supabaseUrl}/rest/v1/unique_users?username=ilike.*${encodeURIComponent(searchQuery.trim())}*&select=username,created_at&limit=20`,
        { headers: { 'Authorization': `Bearer ${apiKey}`, 'apikey': apiKey } }
      );
      const d = await r.json();
      setSearchResults(Array.isArray(d) ? d : []);
    } catch { setSearchResults([]); }
    finally { setSearchLoading(false); }
  };

  const isUp = parseFloat(growthPercent) >= 0;

  // ── Sidebar ────────────────────────────────────────────────────
  const nav = [
    { id: 'overview', label: 'Overview',  icon: LayoutDashboard },
    { id: 'users',    label: 'Users',     icon: UserSearch },
  ];

  return (
    <div className="flex min-h-screen bg-[#080a0f] text-white" style={{ fontFamily: "'DM Mono', 'Fira Mono', monospace" }}>

      {/* Sidebar */}
      <aside className="w-56 shrink-0 border-r border-white/5 flex flex-col" style={{ background: 'linear-gradient(180deg, #0d1018 0%, #080a0f 100%)' }}>
        {/* Logo */}
        <div className="px-6 py-6 border-b border-white/5">
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 rounded-lg bg-blue-500 flex items-center justify-center">
              <Zap size={14} className="text-white" fill="white" />
            </div>
            <span className="text-sm font-medium tracking-tight text-white">vhx<span className="text-blue-400">analytics</span></span>
          </div>
        </div>

        {/* Nav */}
        <nav className="flex-1 px-3 py-4 space-y-1">
          {nav.map(({ id, label, icon: Icon }) => (
            <button key={id} onClick={() => setActiveTab(id)}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-200 ${
                activeTab === id
                  ? 'bg-blue-500/15 text-blue-400 border border-blue-500/20'
                  : 'text-white/30 hover:text-white/60 hover:bg-white/5 border border-transparent'
              }`}>
              <Icon size={14} />
              {label}
              {activeTab === id && <ChevronRight size={12} className="ml-auto" />}
            </button>
          ))}
        </nav>

        {/* Status */}
        <div className="px-4 py-4 border-t border-white/5">
          <div className="flex items-center gap-2 mb-1">
            <span className="relative flex h-1.5 w-1.5">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
              <span className="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500" />
            </span>
            <span className="text-xs text-white/20">live · 30s refresh</span>
          </div>
          {lastFetch && <p className="text-xs text-white/15 pl-3.5">Updated {lastFetch.toLocaleTimeString()}</p>}
        </div>
      </aside>

      {/* Main */}
      <main className="flex-1 overflow-auto">
        {/* Topbar */}
        <div className="sticky top-0 z-10 px-8 py-4 border-b border-white/5 flex items-center justify-between"
          style={{ background: 'rgba(8,10,15,0.8)', backdropFilter: 'blur(12px)' }}>
          <div>
            <h1 className="text-sm font-medium text-white capitalize">{activeTab}</h1>
            {lastExecTime && (
              <p className="text-xs text-white/25 mt-0.5 flex items-center gap-1">
                <Clock size={10} /> Last exec {timeSinceLast}
              </p>
            )}
          </div>
          <button onClick={fetchData} disabled={loading}
            className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-white/50 hover:text-white text-xs transition-all border border-white/5">
            <RefreshCw size={12} className={loading ? 'animate-spin' : ''} />
            {loading ? 'Syncing...' : 'Refresh'}
          </button>
        </div>

        <div className="p-8">
          {error && (
            <div className="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
              <AlertCircle size={16} /> {error}
            </div>
          )}

          {/* ── OVERVIEW ── */}
          {activeTab === 'overview' && (
            <div className="space-y-6">
              {/* Stat cards */}
              <div className="grid grid-cols-2 gap-4" style={{ gridTemplateColumns: 'repeat(4,1fr)' }}>
                <StatCard label="Total Executions" value={totalExecutions.toLocaleString()} icon={Activity} color="blue" loading={loading} />
                <StatCard label="Unique Users" value={uniqueUsers.toLocaleString()} icon={Users} color="purple" loading={loading} />
                <StatCard
                  label="Growth vs Yesterday"
                  value={growthPercent !== null ? `${isUp ? '+' : ''}${growthPercent}%` : '—'}
                  sub={`Today ${todayCount.toLocaleString()} · Yesterday ${yesterdayCount.toLocaleString()}`}
                  icon={isUp ? TrendingUp : TrendingDown}
                  color={isUp ? 'green' : 'red'}
                  loading={loading}
                />
                <StatCard label="Peak Hour (24h)" value={peakHour} icon={Clock} color="amber" loading={loading} />
              </div>

              {/* Daily chart */}
              <div className="rounded-2xl border border-white/5 bg-[#0f1117] p-6" style={{ boxShadow: '0 0 0 1px rgba(255,255,255,0.03)' }}>
                <div className="flex items-center justify-between mb-6">
                  <div>
                    <h2 className="text-sm font-medium text-white">Daily Executions</h2>
                    <p className="text-xs text-white/25 mt-0.5">Last 30 days</p>
                  </div>
                  <Globe size={14} className="text-white/20" />
                </div>
                {loading ? (
                  <Skeleton className="h-64 w-full" />
                ) : dailyData.length === 0 ? (
                  <EmptyState title="No daily data yet" desc="Executions will appear here once your Lua script starts logging." />
                ) : (
                  <ResponsiveContainer width="100%" height={260}>
                    <AreaChart data={dailyData} margin={{ top: 5, right: 5, left: -20, bottom: 5 }}>
                      <defs>
                        <linearGradient id="blueGrad" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stopColor="#3b82f6" stopOpacity={0.3} />
                          <stop offset="100%" stopColor="#3b82f6" stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)" />
                      <XAxis dataKey="date" stroke="rgba(255,255,255,0.15)" tick={{ fontSize: 10, fontFamily: 'monospace' }} />
                      <YAxis stroke="rgba(255,255,255,0.15)" tick={{ fontSize: 10, fontFamily: 'monospace' }} />
                      <Tooltip content={<CustomTooltip />} />
                      <Area type="monotone" dataKey="count" stroke="#3b82f6" strokeWidth={2} fill="url(#blueGrad)" dot={false} activeDot={{ r: 4, fill: '#3b82f6' }} />
                    </AreaChart>
                  </ResponsiveContainer>
                )}
              </div>

              {/* Hourly chart */}
              <div className="rounded-2xl border border-white/5 bg-[#0f1117] p-6" style={{ boxShadow: '0 0 0 1px rgba(255,255,255,0.03)' }}>
                <div className="flex items-center justify-between mb-6">
                  <div>
                    <h2 className="text-sm font-medium text-white">Hourly Executions</h2>
                    <p className="text-xs text-white/25 mt-0.5">Last 24 hours</p>
                  </div>
                  <Activity size={14} className="text-white/20" />
                </div>
                {loading ? (
                  <Skeleton className="h-64 w-full" />
                ) : hourlyData.every(d => d.count === 0) ? (
                  <EmptyState title="No activity in last 24h" desc="Hourly data will populate as executions come in." />
                ) : (
                  <ResponsiveContainer width="100%" height={260}>
                    <BarChart data={hourlyData} margin={{ top: 5, right: 5, left: -20, bottom: 5 }}>
                      <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)" />
                      <XAxis dataKey="time" stroke="rgba(255,255,255,0.15)" tick={{ fontSize: 10, fontFamily: 'monospace' }} />
                      <YAxis stroke="rgba(255,255,255,0.15)" tick={{ fontSize: 10, fontFamily: 'monospace' }} />
                      <Tooltip content={<CustomTooltip />} />
                      <Bar dataKey="count" fill="#6366f1" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                )}
              </div>
            </div>
          )}

          {/* ── USERS ── */}
          {activeTab === 'users' && (
            <div className="space-y-6">
              <div className="rounded-2xl border border-white/5 bg-[#0f1117] p-6" style={{ boxShadow: '0 0 0 1px rgba(255,255,255,0.03)' }}>
                <h2 className="text-sm font-medium text-white mb-1">Username Lookup</h2>
                <p className="text-xs text-white/25 mb-6">Search any Roblox username that has executed your scripts</p>

                <div className="flex gap-3 mb-6">
                  <div className="relative flex-1">
                    <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-white/25" />
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={e => setSearchQuery(e.target.value)}
                      onKeyDown={e => e.key === 'Enter' && searchUsername()}
                      placeholder="Enter username..."
                      className="w-full bg-white/5 border border-white/10 text-white placeholder-white/20 rounded-xl pl-9 pr-9 py-2.5 text-sm focus:outline-none focus:border-blue-500/50 transition font-mono"
                    />
                    {searchQuery && (
                      <button onClick={() => { setSearchQuery(''); setSearchResults([]); setHasSearched(false); }}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-white/25 hover:text-white/60">
                        <X size={14} />
                      </button>
                    )}
                  </div>
                  <button onClick={searchUsername} disabled={searchLoading || !searchQuery.trim()}
                    className="px-5 py-2.5 bg-blue-500/15 hover:bg-blue-500/25 disabled:opacity-30 border border-blue-500/20 text-blue-400 text-sm rounded-xl transition font-mono">
                    {searchLoading ? 'Searching...' : 'Search'}
                  </button>
                </div>

                {/* Results */}
                {!hasSearched && (
                  <EmptyState title="Search for a username" desc="Type any full or partial username above to find users who have executed your scripts." />
                )}
                {hasSearched && searchResults.length === 0 && (
                  <EmptyState title={`No results for "${searchQuery}"`} desc="This username hasn't executed any of your scripts yet." />
                )}
                {searchResults.length > 0 && (
                  <div>
                    <p className="text-xs text-white/25 font-mono mb-3">{searchResults.length} user{searchResults.length !== 1 ? 's' : ''} found</p>
                    <div className="space-y-2">
                      {searchResults.map((user, i) => (
                        <div key={i} className="flex items-center justify-between bg-white/3 border border-white/5 rounded-xl px-4 py-3 hover:bg-white/5 transition">
                          <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-xl bg-blue-500/15 border border-blue-500/20 flex items-center justify-center">
                              <span className="text-blue-400 text-xs font-mono font-medium">{user.username.charAt(0).toUpperCase()}</span>
                            </div>
                            <span className="text-white text-sm font-mono">{user.username}</span>
                          </div>
                          <span className="text-white/25 text-xs font-mono">
                            {new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Unique users stat */}
              <div className="grid gap-4" style={{ gridTemplateColumns: 'repeat(2,1fr)' }}>
                <StatCard label="Total Unique Users" value={uniqueUsers.toLocaleString()} icon={Users} color="purple" loading={loading} />
                <StatCard label="Today's Executions" value={todayCount.toLocaleString()} icon={Activity} color="blue" loading={loading} />
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
};

export default ExecutionDashboard;
