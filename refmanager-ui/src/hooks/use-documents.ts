import { useQuery } from '@tanstack/react-query'
import { paginatedDocumentsSchema } from '@/schemas/document'
import { useRefManagerStore } from '@/stores/ref-manager-store'

type UseDocumentsOptions = {
  page?: number
  perPage?: number
  query?: string
  status?: string
}

export function useDocuments(options: UseDocumentsOptions = {}) {
  const apiBaseUrl = useRefManagerStore((state) => state.apiBaseUrl)
  const storeFilters = useRefManagerStore((state) => state.filters)

  const filters = {
    page: options.page ?? storeFilters.page,
    perPage: options.perPage ?? storeFilters.perPage,
    query: options.query ?? storeFilters.query,
    status: options.status ?? storeFilters.status,
  }

  const queryResult = useQuery({
    queryKey: ['documents', apiBaseUrl, filters],
    queryFn: async () => {
      const params = new URLSearchParams()

      if (filters.page > 0)
        params.set('page', String(filters.page))

      if (filters.perPage > 0)
        params.set('per_page', String(filters.perPage))

      if (filters.query)
        params.set('q', filters.query)

      if (filters.status)
        params.set('status', filters.status)

      const endpoint = `${apiBaseUrl}/documents${params.toString() ? `?${params.toString()}` : ''}`
      const response = await fetch(endpoint, {
        headers: {
          Accept: 'application/json',
        },
      })

      if (!response.ok)
        throw new Error(`Failed to load documents (${response.status})`)

      const payload = await response.json()
      return paginatedDocumentsSchema.parse(payload)
    },
    staleTime: 30_000,
  })

  return {
    documents: queryResult.data?.data ?? [],
    pagination: queryResult.data?.meta,
    links: queryResult.data?.links,
    isLoading: queryResult.isLoading,
    isFetching: queryResult.isFetching,
    error: queryResult.error instanceof Error ? queryResult.error.message : null,
    refetch: queryResult.refetch,
  }
}
