import { ChevronDown, ChevronUp, GripVertical } from 'lucide-react'
import type { DragEndEvent } from '@dnd-kit/core'
import { DndContext, PointerSensor, useSensor, useSensors } from '@dnd-kit/core'
import {
  SortableContext,
  arrayMove,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { cn } from '@/lib/utils'

interface ReorderListProps<T> {
  items: T[]
  getKey: (item: T, index: number) => string | number
  onReorder: (items: T[]) => void
  renderItem: (
    item: T,
    context: {
      index: number
      isDragging: boolean
      handle: React.ReactNode
      controls: React.ReactNode
    }
  ) => React.ReactNode
  className?: string
  itemClassName?: string
  disabled?: boolean
}

function SortableItem<T>({
  item,
  index,
  total,
  disabled,
  itemClassName,
  getKey,
  onReorder,
  items,
  renderItem,
}: {
  item: T
  index: number
  total: number
  disabled?: boolean
  itemClassName?: string
  getKey: (item: T, index: number) => string | number
  onReorder: (items: T[]) => void
  items: T[]
  renderItem: ReorderListProps<T>['renderItem']
}) {
  const { attributes, listeners, setNodeRef, transform, isDragging } = useSortable({
    id: getKey(item, index),
    disabled: disabled ?? false,
  })

  const style = {
    transform: CSS.Transform.toString(transform),
  }

  const handle = disabled ? null : (
    <button
      aria-label="Drag to reorder"
      title="Drag to reorder"
      className="flex items-center justify-center h-11 w-11 shrink-0 cursor-grab active:cursor-grabbing text-text-muted hover:text-text-primary hover:bg-surface-muted rounded-lg touch-none"
      {...listeners}
      {...attributes}
    >
      <GripVertical size={20} />
    </button>
  )

  const controls = disabled ? null : (
    <div className="flex flex-col items-center">
      <button
        aria-label="Move up"
        title="Move up"
        disabled={index === 0}
        onClick={() => onReorder(arrayMove(items, index, index - 1))}
        className="h-11 w-11 flex items-center justify-center rounded-lg text-text-muted hover:text-text-primary hover:bg-surface-muted disabled:opacity-30 disabled:pointer-events-none transition-colors"
      >
        <ChevronUp size={18} />
      </button>
      <button
        aria-label="Move down"
        title="Move down"
        disabled={index === total - 1}
        onClick={() => onReorder(arrayMove(items, index, index + 1))}
        className="h-11 w-11 flex items-center justify-center rounded-lg text-text-muted hover:text-text-primary hover:bg-surface-muted disabled:opacity-30 disabled:pointer-events-none transition-colors"
      >
        <ChevronDown size={18} />
      </button>
    </div>
  )

  return (
    <div ref={setNodeRef} style={style} className={cn(itemClassName, isDragging && 'dragging')}>
      {renderItem(item, {
        index,
        isDragging,
        handle,
        controls,
      })}
    </div>
  )
}

export function ReorderList<T>({
  items,
  getKey,
  onReorder,
  renderItem,
  className,
  itemClassName,
  disabled,
}: ReorderListProps<T>) {
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  )

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event

    if (over && active.id !== over.id) {
      const oldIndex = items.findIndex(item => getKey(item, items.indexOf(item)) === active.id)
      const newIndex = items.findIndex(item => getKey(item, items.indexOf(item)) === over.id)

      if (oldIndex !== -1 && newIndex !== -1) {
        onReorder(arrayMove(items, oldIndex, newIndex))
      }
    }
  }

  return (
    <DndContext sensors={sensors} onDragEnd={handleDragEnd}>
      <SortableContext
        items={items.map((item, index) => getKey(item, index))}
        strategy={verticalListSortingStrategy}
        disabled={disabled ?? false}
      >
        <div className={className}>
          {items.map((item, index) => (
            <SortableItem
              key={getKey(item, index)}
              item={item}
              index={index}
              total={items.length}
              disabled={disabled}
              itemClassName={itemClassName}
              getKey={getKey}
              onReorder={onReorder}
              items={items}
              renderItem={renderItem}
            />
          ))}
        </div>
      </SortableContext>
    </DndContext>
  )
}
