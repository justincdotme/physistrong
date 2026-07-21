import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Trash2, Layers } from 'lucide-react'
import type { WorkoutTemplateListItem } from '@/api/types'
import { templateQueries, createTemplate, deleteTemplate } from '@/api/templates'
import { useDeleteConfirm } from '@/hooks/use-delete-confirm'
import { useApp } from '@/lib/use-app'
import { PageHeader } from '@/components/ui/page-header'
import { EmptyState } from '@/components/ui/empty-state'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Sheet } from '@/components/ui/sheet'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'

export function TemplatesPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useApp()

  const [createOpen, setCreateOpen] = useState(false)
  const [name, setName] = useState('')

  const { data: templates = [], isLoading } = useQuery(templateQueries.list())

  const createMutation = useMutation({
    mutationFn: (n: string) => createTemplate({ name: n }),
    onSuccess: tpl => {
      queryClient.invalidateQueries({ queryKey: templateQueries.base })
      setCreateOpen(false)
      setName('')
      toast('Template created.')
      navigate(`/templates/${tpl.id}`)
    },
    onError: () => toast('Could not create template. Try again.', 'error'),
  })

  const deleteMutation = useMutation({
    mutationFn: deleteTemplate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: templateQueries.base })
      toast('Template deleted.')
    },
    onError: () => toast('Could not delete template. Try again.', 'error'),
  })

  const deleteConfirm = useDeleteConfirm<WorkoutTemplateListItem>(tpl =>
    deleteMutation.mutate(tpl.id)
  )

  if (isLoading) {
    return (
      <>
        <PageHeader title="Templates" back onBack={() => navigate('/workouts')} />
        <p className="text-text-secondary text-sm">Loading...</p>
      </>
    )
  }

  return (
    <div dusk="templates-page">
      <PageHeader
        title="Templates"
        subtitle={`${templates.length} saved`}
        back
        onBack={() => navigate('/workouts')}
        actions={
          <Button
            size="sm"
            icon={<Plus size={16} />}
            onClick={() => setCreateOpen(true)}
            dusk="create-template-btn"
          >
            Create
          </Button>
        }
      />

      {templates.length ? (
        <div className="flex flex-col gap-2.5" dusk="template-list">
          {templates.map(tpl => (
            <Card
              key={tpl.id}
              className="p-4 flex items-center gap-3 cursor-pointer"
              onClick={() => navigate(`/templates/${tpl.id}`)}
            >
              <span
                className="h-9 w-9 rounded-lg flex items-center justify-center shrink-0"
                style={{
                  background: 'color-mix(in srgb, var(--color-primary) 12%, transparent)',
                  color: 'var(--color-primary)',
                }}
              >
                <Layers size={18} />
              </span>
              <div className="min-w-0 flex-1">
                <div className="font-semibold text-sm truncate">{tpl.name}</div>
                <div className="text-[12px] text-text-secondary">
                  {tpl.exercises.length} exercise{tpl.exercises.length !== 1 ? 's' : ''}
                </div>
              </div>
              <button
                onClick={e => {
                  e.stopPropagation()
                  deleteConfirm.request(tpl)
                }}
                aria-label={`Delete ${tpl.name}`}
                title="Delete template"
                dusk="delete-template-btn"
                className="h-10 w-10 flex items-center justify-center rounded-lg shrink-0 text-text-muted hover:text-destructive hover:bg-surface-muted"
              >
                <Trash2 size={17} />
              </button>
            </Card>
          ))}
        </div>
      ) : (
        <EmptyState
          title="No templates yet"
          action={
            <Button onClick={() => setCreateOpen(true)} icon={<Plus size={18} />}>
              Create Template
            </Button>
          }
        >
          Save a routine once and start future workouts from it.
        </EmptyState>
      )}

      <Sheet
        open={createOpen}
        onClose={() => {
          setCreateOpen(false)
          setName('')
        }}
        title="Create Template"
        footer={
          <Button
            full
            disabled={!name.trim() || createMutation.isPending}
            onClick={() => createMutation.mutate(name.trim())}
            dusk="submit-template-btn"
          >
            {createMutation.isPending ? 'Creating...' : 'Create'}
          </Button>
        }
      >
        <div>
          <label htmlFor="template-name-input" className="form-label">
            Template name
          </label>
          <input
            id="template-name-input"
            value={name}
            onChange={e => setName(e.target.value)}
            placeholder="e.g. Push Day"
            className="ps-input w-full px-3 py-2.5 text-sm"
          />
        </div>
      </Sheet>

      <ConfirmDialog
        {...deleteConfirm.dialogProps}
        title="Delete template?"
        message={
          deleteConfirm.target
            ? `"${deleteConfirm.target.name}" will be removed from your templates.`
            : ''
        }
      />
    </div>
  )
}
