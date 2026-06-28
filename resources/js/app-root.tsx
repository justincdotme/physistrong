import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Route, Routes } from 'react-router-dom'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 1000 * 60 * 5, gcTime: 1000 * 60 * 10, retry: false },
  },
})

function Placeholder() {
  return (
    <div className="flex h-full items-center justify-center">
      <p className="text-text-secondary">Physistrong</p>
    </div>
  )
}

export function AppRoot() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="/*" element={<Placeholder />} />
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  )
}
