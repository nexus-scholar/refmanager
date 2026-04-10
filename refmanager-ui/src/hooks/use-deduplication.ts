import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  dedupResolveResultSchema,
  dedupScanResultSchema,
  type DedupPair,
} from '@/schemas/deduplication'
import { useRefManagerStore } from '@/stores/ref-manager-store'

type ScanPayload = {
  year?: number
  threshold?: number
}

type ResolvePayload = {
  action: 'merge' | 'keep_both'
  primaryId: number
  candidateIds: number[]
}

async function fetchJson<T>(url: string, init: RequestInit, parser: (input: unknown) => T): Promise<T> {
  const response = await fetch(url, {
    ...init,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(init.headers ?? {}),
    },
  })

  if (!response.ok)
    throw new Error(`Request failed (${response.status})`)

  const payload = await response.json()
  return parser(payload)
}

export function useDeduplication(scanPayload: ScanPayload = {}) {
  const apiBaseUrl = useRefManagerStore((state) => state.apiBaseUrl)
  const queryClient = useQueryClient()

  const duplicatesQuery = useQuery({
    queryKey: ['duplicates', apiBaseUrl, scanPayload],
    queryFn: async () => {
      const params = new URLSearchParams()

      if (typeof scanPayload.year === 'number')
        params.set('year', String(scanPayload.year))

      if (typeof scanPayload.threshold === 'number')
        params.set('threshold', String(scanPayload.threshold))

      const endpoint = `${apiBaseUrl}/duplicates${params.toString() ? `?${params.toString()}` : ''}`

      const result = await fetchJson(endpoint, { method: 'GET' }, (input) =>
        dedupScanResultSchema.parse(input),
      )

      return result.data
    },
  })

  const scanMutation = useMutation({
    mutationFn: async (payload: ScanPayload) => {
      const result = await fetchJson(
        `${apiBaseUrl}/deduplicate/scan`,
        {
          method: 'POST',
          body: JSON.stringify(payload),
        },
        (input) => dedupScanResultSchema.parse(input),
      )

      return result.data
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['duplicates'] })
    },
  })

  const resolveMutation = useMutation({
    mutationFn: async (payload: ResolvePayload) => {
      const result = await fetchJson(
        `${apiBaseUrl}/duplicates/resolve`,
        {
          method: 'POST',
          body: JSON.stringify({
            action: payload.action,
            primary_id: payload.primaryId,
            candidate_ids: payload.candidateIds,
          }),
        },
        (input) => dedupResolveResultSchema.parse(input),
      )

      return result.data
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['duplicates'] })
      await queryClient.invalidateQueries({ queryKey: ['documents'] })
    },
  })

  return {
    duplicates: duplicatesQuery.data?.pairs ?? ([] as DedupPair[]),
    threshold: duplicatesQuery.data?.threshold,
    count: duplicatesQuery.data?.count ?? 0,
    isLoading: duplicatesQuery.isLoading,
    isFetching: duplicatesQuery.isFetching,
    error: duplicatesQuery.error instanceof Error ? duplicatesQuery.error.message : null,
    scan: scanMutation.mutateAsync,
    scanning: scanMutation.isPending,
    resolve: resolveMutation.mutateAsync,
    resolving: resolveMutation.isPending,
  }
}

export type { ScanPayload, ResolvePayload }

