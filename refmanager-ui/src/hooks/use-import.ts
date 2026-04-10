import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { importResultSchema, type ImportResult } from '@/schemas/import'
import { useRefManagerStore } from '@/stores/ref-manager-store'

type ImportPayload = {
  file?: File
  content?: string
  format?: string
  save?: boolean
  deduplicate?: boolean
  projectId?: number
  collectionId?: number
}

function postImportWithProgress(
  endpoint: string,
  payload: ImportPayload,
  onProgress: (progress: number) => void,
): Promise<ImportResult> {
  return new Promise((resolve, reject) => {
    const formData = new FormData()

    if (payload.file)
      formData.append('file', payload.file)

    if (payload.content)
      formData.append('content', payload.content)

    if (payload.format)
      formData.append('format', payload.format)

    if (typeof payload.save === 'boolean')
      formData.append('save', payload.save ? '1' : '0')

    if (typeof payload.deduplicate === 'boolean')
      formData.append('deduplicate', payload.deduplicate ? '1' : '0')

    if (typeof payload.projectId === 'number')
      formData.append('project_id', String(payload.projectId))

    if (typeof payload.collectionId === 'number')
      formData.append('collection_id', String(payload.collectionId))

    const request = new XMLHttpRequest()
    request.open('POST', endpoint)
    request.setRequestHeader('Accept', 'application/json')

    request.upload.onprogress = (event) => {
      if (!event.lengthComputable)
        return

      const progress = Math.round((event.loaded / event.total) * 100)
      onProgress(progress)
    }

    request.onerror = () => reject(new Error('Import request failed.'))

    request.onload = () => {
      if (request.status < 200 || request.status >= 300) {
        reject(new Error(`Import failed (${request.status}).`))
        return
      }

      const json = JSON.parse(request.responseText)
      resolve(importResultSchema.parse(json))
    }

    request.send(formData)
  })
}

export function useImport() {
  const apiBaseUrl = useRefManagerStore((state) => state.apiBaseUrl)
  const queryClient = useQueryClient()
  const [progress, setProgress] = useState(0)

  const mutation = useMutation({
    mutationFn: async (payload: ImportPayload) => {
      setProgress(0)

      const result = await postImportWithProgress(
        `${apiBaseUrl}/import`,
        payload,
        setProgress,
      )

      setProgress(100)
      return result
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['documents'] })
    },
  })

  return {
    ...mutation,
    progress,
  }
}

export type { ImportPayload }

