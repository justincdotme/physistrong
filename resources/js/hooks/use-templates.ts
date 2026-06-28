import { useApp } from '@/lib/store'
import type { WorkoutTemplate } from '@/api/types'

interface UseTemplatesResult {
  data: WorkoutTemplate[]
  loading: boolean
  error: null
}

export function useTemplates(): UseTemplatesResult {
  const { templates } = useApp()
  return { data: templates, loading: false, error: null }
}

interface UseTemplateResult {
  data: WorkoutTemplate | null
  loading: boolean
  error: null
}

export function useTemplate(id: string): UseTemplateResult {
  const { templates } = useApp()
  return { data: templates.find(t => t.id === id) ?? null, loading: false, error: null }
}
