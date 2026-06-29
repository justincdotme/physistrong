import 'react'

declare module 'react' {
  interface HTMLAttributes<T> {
    dusk?: string
  }
}
