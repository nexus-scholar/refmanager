import { useMutation, useQueryClient } from '@tanstack/react-query'
import { nexusSearchImportSchema, type NexusSearchImportResult } from '@/schemas/nexus-search'
import { useRefManagerStore } from '@/stores/ref-manager-store'

type NexusSearchImportPayload = {
  query: string
  yearMin?: number
  yearMax?: number
  maxResults?: number
  offset?: number
  language?: string
  deduplicate?: boolean
  projectId?: number
  collectionId?: number
  useCache?: boolean
}

export function useNexusSearchImport() {
  const apiBaseUrl = useRefManagerStore((state) => state.apiBaseUrl)
  const queryClient = useQueryClient()

  const mutation = useMutation({
    mutationFn: async (payload: NexusSearchImportPayload): Promise<NexusSearchImportResult> => {
      const response = await fetch(`${apiBaseUrl}/nexus/search-import`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          query: payload.query,
          year_min: payload.yearMin,
          year_max: payload.yearMax,
          max_results: payload.maxResults,
          offset: payload.offset,
          language: payload.language,
          deduplicate: payload.deduplicate,
          project_id: payload.projectId,
          collection_id: payload.collectionId,
          use_cache: payload.useCache,
        }),
      })

      if (!response.ok)
        throw new Error(`Nexus search import failed (${response.status}).`)

      const json = await response.json()
      return nexusSearchImportSchema.parse(json)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['documents'] })
      await queryClient.invalidateQueries({ queryKey: ['duplicates'] })
    },
  })

  return mutation
}

export type { NexusSearchImportPayload }

