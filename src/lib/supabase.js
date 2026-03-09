import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseKey = import.meta.env.VITE_SUPABASE_ANON_KEY

export const supabase = createClient(supabaseUrl, supabaseKey)

export const getDashboardStats = async (dateRange = '7d') => {
  const { data, error } = await supabase
    .rpc('get_dashboard_stats', { date_range: dateRange })
  
  if (error) throw error
  return data
}
