import { z } from 'zod'

export const authorSchema = z.object({
  id: z.number(),
  given_name: z.string().nullable(),
  family_name: z.string(),
  full_name: z.string(),
  orcid: z.string().nullable(),
})

export const documentSchema = z.object({
  id: z.number(),
  title: z.string(),
  abstract: z.string().nullable(),
  doi: z.string().nullable(),
  url: z.string().nullable(),
  journal: z.string().nullable(),
  year: z.number().nullable(),
  status: z.string(),
  document_type: z.string(),
  authors: z.array(authorSchema).default([]),
})

export const paginationLinksSchema = z
  .object({
    first: z.string().nullable().optional(),
    last: z.string().nullable().optional(),
    prev: z.string().nullable().optional(),
    next: z.string().nullable().optional(),
  })
  .optional()

export const paginationMetaSchema = z
  .object({
    current_page: z.number().optional(),
    from: z.number().nullable().optional(),
    last_page: z.number().optional(),
    path: z.string().optional(),
    per_page: z.number().optional(),
    to: z.number().nullable().optional(),
    total: z.number().optional(),
  })
  .optional()

export const paginatedDocumentsSchema = z.object({
  data: z.array(documentSchema),
  links: paginationLinksSchema,
  meta: paginationMetaSchema,
})

export type Author = z.infer<typeof authorSchema>
export type DocumentRecord = z.infer<typeof documentSchema>
export type PaginatedResponse<T> = {
  data: T[]
  links?: z.infer<typeof paginationLinksSchema>
  meta?: z.infer<typeof paginationMetaSchema>
}

