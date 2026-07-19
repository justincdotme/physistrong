import { queryOptions } from '@tanstack/react-query'
import { api } from './client'
import { toEquipmentType, type RawEquipmentType } from './transformers'
import type { EquipmentType } from './types'

export const equipmentQueries = {
  base: ['equipment'] as const,
  list: () => queryOptions({ queryKey: ['equipment'] as const, queryFn: listEquipment }),
}

export async function listEquipment(): Promise<EquipmentType[]> {
  const { data } = await api.get('/equipment-types')
  return (data.data as RawEquipmentType[]).map(toEquipmentType)
}

export async function createEquipment(name: string): Promise<EquipmentType> {
  const { data } = await api.post('/equipment-types', { name })
  return toEquipmentType(data.data)
}

export async function deleteEquipment(id: string): Promise<void> {
  await api.delete(`/equipment-types/${id}`)
}
