import { useState } from 'react'

export function useDeleteConfirm<T>(onDelete: (target: T) => void) {
  const [target, setTarget] = useState<T | null>(null)

  return {
    target,
    request: setTarget,
    dialogProps: {
      open: target !== null,
      onConfirm: () => {
        if (target) {
          onDelete(target)
          setTarget(null)
        }
      },
      onCancel: () => setTarget(null),
    },
  }
}
