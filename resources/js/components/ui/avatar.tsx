export interface AvatarProps {
  name?: string
  size?: number
  onClick?: () => void
}

export function Avatar({ name, size = 36, onClick }: AvatarProps) {
  const initials = (name || '')
    .split(' ')
    .map(p => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()

  const el = (
    <span
      className="inline-flex items-center justify-center rounded-full font-semibold text-white shrink-0"
      style={{
        width: size,
        height: size,
        fontSize: size * 0.38,
        backgroundImage: 'var(--gradient-primary)',
      }}
    >
      {initials}
    </span>
  )

  if (onClick) {
    return (
      <button onClick={onClick} aria-label="Profile" className="rounded-full">
        {el}
      </button>
    )
  }

  return el
}
