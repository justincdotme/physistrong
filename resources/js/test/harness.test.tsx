import { describe, it, expect } from 'vitest'
import { http, HttpResponse } from 'msw'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/api/client'
import { screen } from '@testing-library/react'
import { renderWithProviders } from './render'
import { server } from './server'
import equipmentList from './mocks/fixtures/equipment/list.json'

interface EquipmentType {
  id: number
  name: string
  is_system: boolean
  user_id: null | number
  created_at: string
  updated_at: string
  usage_count: number
}

interface ListResponse {
  data: EquipmentType[]
}

function EquipmentListComponent() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['equipment-types'],
    queryFn: async () => {
      const response = await api.get<ListResponse>('/equipment-types')
      return response.data
    },
  })

  if (isLoading) {
    return <div>Loading equipment...</div>
  }

  if (error) {
    return <div>Error: {error.message}</div>
  }

  const first = data?.data[0]
  if (!first) {
    return <div>No equipment found</div>
  }

  return <div>{first.name}</div>
}

describe('MSW infrastructure smoke test', () => {
  it('renders component with real data-fetching path through MSW', async () => {
    renderWithProviders(<EquipmentListComponent />)

    expect(screen.getByText('Loading equipment...')).toBeInTheDocument()

    const firstEquipment = equipmentList.data[0]
    if (!firstEquipment) throw new Error('equipment fixture is empty')
    expect(await screen.findByText(firstEquipment.name)).toBeInTheDocument()
  })

  it('allows per-test handler overrides via server.use()', async () => {
    server.use(
      http.get('/api/v1/equipment-types', () => {
        return HttpResponse.json({ data: [] })
      })
    )

    renderWithProviders(<EquipmentListComponent />)

    const noEquipmentText = await screen.findByText('No equipment found')
    expect(noEquipmentText).toBeInTheDocument()
  })
})
