import { z } from 'zod'
import { documentSchema } from '@/schemas/document'

export const dedupPairSchema = z.object({
  confidence: z.number(),
  matched_by: z.string(),
  primary: documentSchema,
  candidate: documentSchema,
})

export const dedupScanResultSchema = z.object({
  data: z.object({
    threshold: z.number(),
    count: z.number(),
    pairs: z.array(dedupPairSchema),
  }),
})

export const dedupResolveResultSchema = z.object({
  data: z.object({
    action: z.enum(['merge', 'keep_both']),
    primary_id: z.number(),
    resolved_count: z.number(),
  }),
})

export type DedupPair = z.infer<typeof dedupPairSchema>
export type DedupScanResult = z.infer<typeof dedupScanResultSchema>
export type DedupResolveResult = z.infer<typeof dedupResolveResultSchema>

