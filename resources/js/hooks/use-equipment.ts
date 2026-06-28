import { useApp } from '@/lib/use-app'
import type { EquipmentType } from '@/api/types'

interface UseEquipmentResult {
  data: EquipmentType[]
  loading: boolean
  error: null
}

export function useEquipment(): UseEquipmentResult {
  const { equipment } = useApp()
  return { data: equipment, loading: false, error: null }
}
