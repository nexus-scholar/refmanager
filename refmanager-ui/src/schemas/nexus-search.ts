import { z } from 'zod'
import { documentSchema } from '@/schemas/document'

export const nexusSearchImportSchema = z.object({
  data: z.object({
    provider: z.string(),
    searched_count: z.number(),
    imported_count: z.number(),
    duplicates_count: z.number(),
    failed_count: z.number(),
    imported: z.array(documentSchema),
    duplicates: z.array(
      z.object({
        matched_by: z.string(),
        confidence: z.number(),
        existing_document_id: z.number().nullable(),
      }),
    ),
    failed: z.array(z.record(z.string(), z.unknown())),
  }),
})

export type NexusSearchImportResult = z.infer<typeof nexusSearchImportSchema>

