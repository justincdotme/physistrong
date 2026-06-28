import { createRoot } from 'react-dom/client'
import '@fontsource/plus-jakarta-sans/400.css'
import '@fontsource/plus-jakarta-sans/500.css'
import '@fontsource/plus-jakarta-sans/600.css'
import '@fontsource/plus-jakarta-sans/700.css'
import { AppRoot } from './app-root'
import '../css/app.css'

const root = document.getElementById('app')
if (!root) {
  throw new Error('Root element not found')
}

createRoot(root).render(<AppRoot />)
