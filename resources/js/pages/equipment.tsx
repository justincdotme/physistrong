import { useState } from 'react'
import { Dumbbell, Plus, Trash2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { Sheet } from '@/components/ui/sheet'
import { EmptyState } from '@/components/ui/empty-state'
import { useEquipment } from '@/hooks/use-equipment'
import { useExercises } from '@/hooks/use-exercises'
import { useApp } from '@/lib/store'
import { equipmentUsageCount } from '@/lib/domain'
import type { EquipmentType } from '@/api/types'

export function EquipmentPage() {
  const { data: equipment } = useEquipment()
  const { data: exercises } = useExercises()
  const { addEquipment, deleteEquipment, toast } = useApp()
  const [addOpen, setAddOpen] = useState(false)
  const [name, setName] = useState('')
  const [deleteTarget, setDeleteTarget] = useState<EquipmentType | null>(null)

  const handleCreate = () => {
    const trimmed = name.trim()
    if (!trimmed) return
    addEquipment(trimmed)
    setName('')
    setAddOpen(false)
    toast('Equipment added.')
  }

  const handleDelete = () => {
    if (!deleteTarget) return
    deleteEquipment(deleteTarget.id)
    setDeleteTarget(null)
    toast('Equipment deleted.')
  }

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && name.trim()) {
      handleCreate()
    }
  }

  return (
    <>
      <PageHeader
        title="Equipment"
        subtitle={`${equipment.length} types`}
        actions={
          <Button
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
        <div className="flex flex-col gap-2.5">
          {equipment.map(eq => {
            const usage = equipmentUsageCount(exercises, eq.id)
            const inUse = usage > 0
            const ariaLabel = inUse
              ? `${eq.name} in use by ${usage} exercises`
              : `Delete ${eq.name}`
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
                    if (!inUse) setDeleteTarget(eq)
                  }}
                  disabled={inUse}
                  title={
                    inUse
                      ? `${eq.name} in use by ${usage} exercise${usage === 1 ? '' : 's'} and cannot be deleted.`
                      : 'Delete equipment'
                  }
                  aria-label={ariaLabel}
                  className={`h-10 w-10 flex items-center justify-center rounded-lg shrink-0 transition-colors ${
                    inUse
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
    </>
  )
}
