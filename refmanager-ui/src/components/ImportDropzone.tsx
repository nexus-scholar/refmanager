import { useMemo, useState, type DragEvent } from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useImport } from '@/hooks/use-import'

type ImportDropzoneProps = {
  onSuccess?: () => void
}

const allowedExtensions = ['.ris', '.bib', '.bibtex', '.json', '.xml', '.jsonl', '.ndjson']

export function ImportDropzone({ onSuccess }: ImportDropzoneProps) {
  const [file, setFile] = useState<File | null>(null)
  const [deduplicate, setDeduplicate] = useState(true)
  const { mutateAsync, isPending, progress, data, error } = useImport()

  const extensionValid = useMemo(() => {
    if (!file)
      return true

    const lowerName = file.name.toLowerCase()
    return allowedExtensions.some((extension) => lowerName.endsWith(extension))
  }, [file])

  const onDrop = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault()

    const dropped = event.dataTransfer.files?.[0]
    if (dropped)
      setFile(dropped)
  }

  async function submitImport() {
    if (!file || !extensionValid)
      return

    await mutateAsync({
      file,
      save: true,
      deduplicate,
    })

    setFile(null)
    onSuccess?.()
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Import References</CardTitle>
        <CardDescription>
          Upload RIS, BibTeX, JSON, XML or JSONL files.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        <div
          onDrop={onDrop}
          onDragOver={(event) => event.preventDefault()}
          className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
          <p>Drag and drop a file here, or choose one manually.</p>
          <input
            type="file"
            className="mt-3 block w-full text-xs"
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
          />
          {file ? <p className="mt-2 text-foreground">Selected: {file.name}</p> : null}
        </div>

        {!extensionValid ? (
          <p className="text-sm text-destructive">
            Unsupported file extension. Allowed: {allowedExtensions.join(', ')}
          </p>
        ) : null}

        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={deduplicate}
            onChange={(event) => setDeduplicate(event.target.checked)}
          />
          Enable duplicate detection during import
        </label>

        {isPending ? (
          <div className="space-y-1">
            <p className="text-sm text-muted-foreground">Uploading... {progress}%</p>
            <div className="h-2 rounded bg-muted">
              <div className="h-2 rounded bg-primary" style={{ width: `${progress}%` }} />
            </div>
          </div>
        ) : null}

        {error instanceof Error ? <p className="text-sm text-destructive">{error.message}</p> : null}

        {data ? (
          <div className="flex flex-wrap gap-2">
            <Badge variant="secondary">Parsed: {data.data.total}</Badge>
            <Badge variant="secondary">Imported: {data.data.imported_count}</Badge>
            <Badge variant="secondary">Duplicates: {data.data.duplicates_count}</Badge>
            <Badge variant="secondary">Failed: {data.data.failed_count}</Badge>
          </div>
        ) : null}

        <Button disabled={!file || !extensionValid || isPending} onClick={() => void submitImport()}>
          {isPending ? 'Importing...' : 'Start Import'}
        </Button>
      </CardContent>
    </Card>
  )
}

