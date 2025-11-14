'use client'

import { useEffect, useState } from 'react'
import { createClient } from '@/lib/supabase/client'
import { Team } from '@/types/database'
import { useUser } from './useUser'

export function useTeams() {
  const [teams, setTeams] = useState<Team[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const { profile } = useUser()

  useEffect(() => {
    if (!profile) return

    const fetchTeams = async () => {
      try {
        const supabase = createClient()
        let query = supabase.from('teams').select('*')

        // Managers see only their teams
        if (profile.role === 'manager') {
          query = query.eq('manager_id', profile.id)
        }
        // Employees see teams they're members of
        else if (profile.role === 'employee') {
          const { data: teamMembers } = await supabase
            .from('team_members')
            .select('team_id')
            .eq('employee_id', profile.id)

          if (teamMembers && teamMembers.length > 0) {
            const teamIds = teamMembers.map((tm) => tm.team_id)
            query = query.in('id', teamIds)
          } else {
            setTeams([])
            setLoading(false)
            return
          }
        }
        // Admins see all teams (no filter needed)

        const { data, error } = await query

        if (error) throw error
        setTeams(data || [])
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to fetch teams')
      } finally {
        setLoading(false)
      }
    }

    fetchTeams()
  }, [profile])

  return { teams, loading, error }
}
