/* eslint-disable react-refresh/only-export-components */

import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useEffect, useState, type PropsWithChildren } from 'react'
import { useRefManagerStore } from '@/stores/ref-manager-store'

type RefManagerConfig = {
  apiBaseUrl: string
}

export function RefManagerProvider({
  apiBaseUrl,
  children,
}: PropsWithChildren<RefManagerConfig>) {
  const [queryClient] = useState(() => new QueryClient())
  const setApiBaseUrl = useRefManagerStore((state) => state.setApiBaseUrl)

  useEffect(() => {
    setApiBaseUrl(apiBaseUrl)
  }, [apiBaseUrl, setApiBaseUrl])

  return (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  )
}

export function useRefManagerConfig(): RefManagerConfig {
  const apiBaseUrl = useRefManagerStore((state) => state.apiBaseUrl)

  return { apiBaseUrl }
}
