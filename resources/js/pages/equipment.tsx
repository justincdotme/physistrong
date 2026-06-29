import { useState, type KeyboardEvent } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { isAxiosError } from 'axios'
import { Dumbbell, Plus, Trash2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { Sheet } from '@/components/ui/sheet'
import { EmptyState } from '@/components/ui/empty-state'
import { useApp } from '@/lib/use-app'
import { listEquipment, createEquipment, deleteEquipment } from '@/api/equipment'
import type { EquipmentType } from '@/api/types'

export function EquipmentPage() {
  const queryClient = useQueryClient()
  const { toast } = useApp()
  const { data: equipment = [], isLoading } = useQuery({
    queryKey: ['equipment'],
    queryFn: listEquipment,
  })

  const createMutation = useMutation({
    mutationFn: createEquipment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['equipment'] })
      toast('Equipment added.')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: deleteEquipment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['equipment'] })
      toast('Equipment deleted.')
    },
    onError: error => {
      if (isAxiosError(error) && error.response?.status === 409) {
        toast(error.response.data?.message ?? 'Equipment is in use.')
      } else {
        toast('Could not delete. Try again.')
      }
    },
  })

  const [addOpen, setAddOpen] = useState(false)
  const [name, setName] = useState('')
  const [deleteTarget, setDeleteTarget] = useState<EquipmentType | null>(null)

  const handleCreate = () => {
    const trimmed = name.trim()
    if (!trimmed) return
    createMutation.mutate(trimmed)
    setName('')
    setAddOpen(false)
  }

  const handleDelete = () => {
    if (!deleteTarget) return
    deleteMutation.mutate(deleteTarget.id)
    setDeleteTarget(null)
  }

  const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && name.trim()) {
      handleCreate()
    }
  }

  if (isLoading) {
    return (
      <>
        <PageHeader title="Equipment" />
        <p className="text-text-secondary text-sm">Loading...</p>
      </>
    )
  }

  return (
    <div dusk="equipment-page">
      <PageHeader
        title="Equipment"
        subtitle={`${equipment.length} types`}
        actions={
          <Button
            dusk="create-equipment-btn"
            size="sm"
            onClick={() => {
              setName('')
              setAddOpen(true)
            }}
            icon={<Plus size={16} />}
          >
            Add
          </Button>
        }
      />

      {equipment.length === 0 ? (
        <EmptyState
          title="No equipment yet"
          action={
            <Button size="sm" onClick={() => setAddOpen(true)}>
              Add Equipment
            </Button>
          }
        >
          Start by adding the equipment types you use.
        </EmptyState>
      ) : (
        <div dusk="equipment-list" className="flex flex-col gap-2.5">
          {equipment.map(eq => {
            const canDelete = !eq.isSystem
            return (
              <Card key={eq.id} className="p-4 flex items-center gap-3">
                <span
                  className="h-9 w-9 rounded-lg flex items-center justify-center shrink-0"
                  style={{
                    background: 'var(--color-surface-muted)',
                    color: 'var(--color-text-secondary)',
                  }}
                >
                  <Dumbbell size={18} />
                </span>
                <div className="min-w-0 flex-1">
                  <div className="font-semibold text-sm truncate">{eq.name}</div>
                </div>
                <Badge tone={eq.isSystem ? 'neutral' : 'primary'}>
                  {eq.isSystem ? 'System' : 'Custom'}
                </Badge>
                <button
                  onClick={() => {
                    if (canDelete) setDeleteTarget(eq)
                  }}
                  disabled={!canDelete}
                  title={
                    eq.isSystem ? 'System equipment types cannot be deleted.' : 'Delete equipment'
                  }
                  aria-label={eq.isSystem ? `${eq.name} is a system type` : `Delete ${eq.name}`}
                  className={`h-10 w-10 flex items-center justify-center rounded-lg shrink-0 transition-colors ${
                    !canDelete
                      ? 'text-text-muted opacity-50 cursor-not-allowed'
                      : 'text-text-muted hover:text-destructive hover:bg-surface-muted'
                  }`}
                >
                  <Trash2 size={17} />
                </button>
              </Card>
            )
          })}
        </div>
      )}

      <Sheet
        open={addOpen}
        onClose={() => setAddOpen(false)}
        title="Add Equipment Type"
        footer={
          <Button full disabled={!name.trim()} onClick={handleCreate}>
            Add Equipment
          </Button>
        }
      >
        <label htmlFor="equipment-name" className="label-caps text-text-secondary block mb-1.5">
          Name
        </label>
        <input
          id="equipment-name"
          value={name}
          onChange={e => setName(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="e.g. Sled"
          className="ps-input w-full px-3 py-2.5 text-sm"
        />
      </Sheet>

      <ConfirmDialog
        open={!!deleteTarget}
        title="Delete equipment?"
        message={deleteTarget ? `"${deleteTarget.name}" will be removed.` : ''}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
      />
    </div>
  )
}
