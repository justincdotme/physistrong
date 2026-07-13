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
import { useDeleteConfirm } from '@/hooks/use-delete-confirm'
import { useApp } from '@/lib/use-app'
import { equipmentQueries, createEquipment, deleteEquipment } from '@/api/equipment'
import type { EquipmentType } from '@/api/types'

export function EquipmentPage() {
  const queryClient = useQueryClient()
  const { toast } = useApp()
  const { data: equipment = [], isLoading } = useQuery(equipmentQueries.list())

  const createMutation = useMutation({
    mutationFn: createEquipment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: equipmentQueries.base })
      toast('Equipment added.')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: deleteEquipment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: equipmentQueries.base })
      toast('Equipment deleted.')
    },
    onError: error => {
      if (isAxiosError(error) && error.response?.status === 409) {
        toast(error.response.data?.message ?? 'Equipment is in use.', 'error')
      } else {
        toast('Could not delete. Try again.', 'error')
      }
    },
  })

  const deleteConfirm = useDeleteConfirm<EquipmentType>(eq => deleteMutation.mutate(eq.id))

  const [addOpen, setAddOpen] = useState(false)
  const [name, setName] = useState('')

  const handleCreate = () => {
    const trimmed = name.trim()
    if (!trimmed) return
    createMutation.mutate(trimmed)
    setName('')
    setAddOpen(false)
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
            const canDelete = !eq.isSystem && eq.usageCount === 0
            const deleteReason = eq.isSystem
              ? 'System equipment types cannot be deleted.'
              : eq.usageCount > 0
                ? `In use by ${eq.usageCount} exercise(s)`
                : 'Delete equipment'
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
                    if (canDelete) deleteConfirm.request(eq)
                  }}
                  disabled={!canDelete}
                  title={deleteReason}
                  aria-label={canDelete ? `Delete ${eq.name}` : deleteReason}
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
        <label htmlFor="equipment-name" className="form-label">
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
        {...deleteConfirm.dialogProps}
        title="Delete equipment?"
        message={deleteConfirm.target ? `"${deleteConfirm.target.name}" will be removed.` : ''}
      />
    </div>
  )
}
