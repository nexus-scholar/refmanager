import { useQuery } from '@tanstack/react-query'
import { documentDetailsSchema } from '@/schemas/document'
import { useRefManagerStore } from '@/stores/ref-manager-store'

export function useDocumentDetails(documentId: number | null) {
  const apiBaseUrl = useRefManagerStore((state) => state.apiBaseUrl)

  const query = useQuery({
    queryKey: ['document-details', apiBaseUrl, documentId],
    enabled: documentId !== null,
    queryFn: async () => {
      const response = await fetch(`${apiBaseUrl}/documents/${documentId}`, {
        headers: { Accept: 'application/json' },
      })

      if (!response.ok)
        throw new Error(`Failed to load document details (${response.status})`)

      const payload = await response.json()
      return documentDetailsSchema.parse(payload.data)
    },
    staleTime: 60_000,
  })

  return {
    document: query.data ?? null,
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    error: query.error instanceof Error ? query.error.message : null,
    refetch: query.refetch,
  }
}

