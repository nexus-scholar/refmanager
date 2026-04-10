import { z } from 'zod'
import { documentSchema } from '@/schemas/document'

export const importDuplicateSchema = z.object({
  is_duplicate: z.boolean(),
  confidence: z.number(),
  matched_by: z.string(),
  existing_document_id: z.number().nullable(),
})

export const importResultSchema = z.object({
  data: z.object({
    total: z.number(),
    imported_count: z.number(),
    duplicates_count: z.number(),
    failed_count: z.number(),
    imported: z.array(documentSchema),
    duplicates: z.array(importDuplicateSchema),
    failed: z.array(z.record(z.string(), z.unknown())),
  }),
})

export type ImportResult = z.infer<typeof importResultSchema>

