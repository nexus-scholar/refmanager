import { useState } from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useNexusSearchImport } from '@/hooks/use-nexus-search-import'

type NexusSearchImportPanelProps = {
  onSuccess?: () => void
}

export function NexusSearchImportPanel({ onSuccess }: NexusSearchImportPanelProps) {
  const [query, setQuery] = useState('')
  const [yearMin, setYearMin] = useState<string>('')
  const [yearMax, setYearMax] = useState<string>('')
  const [maxResults, setMaxResults] = useState<string>('25')
  const [deduplicate, setDeduplicate] = useState(true)

  const { mutateAsync, isPending, data, error } = useNexusSearchImport()

  async function submit() {
    const trimmed = query.trim()
    if (trimmed === '')
      return

    await mutateAsync({
      query: trimmed,
      yearMin: yearMin ? Number(yearMin) : undefined,
      yearMax: yearMax ? Number(yearMax) : undefined,
      maxResults: maxResults ? Number(maxResults) : undefined,
      deduplicate,
      useCache: true,
    })

    onSuccess?.()
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Search OpenAlex + Import</CardTitle>
        <CardDescription>
          Run a Nexus search using the OpenAlex provider and persist results in RefManager.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-1">
          <label className="text-sm font-medium" htmlFor="nexus-query">Query</label>
          <input
            id="nexus-query"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="e.g., machine learning in agriculture"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
          />
        </div>

        <div className="grid gap-3 sm:grid-cols-3">
          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="nexus-year-min">Year Min</label>
            <input
              id="nexus-year-min"
              value={yearMin}
              onChange={(event) => setYearMin(event.target.value)}
              type="number"
              className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="nexus-year-max">Year Max</label>
            <input
              id="nexus-year-max"
              value={yearMax}
              onChange={(event) => setYearMax(event.target.value)}
              type="number"
              className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            />
          </div>
          <div className="space-y-1">
            <label className="text-sm font-medium" htmlFor="nexus-max-results">Max Results</label>
            <input
              id="nexus-max-results"
              value={maxResults}
              onChange={(event) => setMaxResults(event.target.value)}
              type="number"
              min={1}
              max={500}
              className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            />
          </div>
        </div>

        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={deduplicate}
            onChange={(event) => setDeduplicate(event.target.checked)}
          />
          Deduplicate while importing
        </label>

        {error instanceof Error ? (
          <p className="text-sm text-destructive">{error.message}</p>
        ) : null}

        {data ? (
          <div className="flex flex-wrap gap-2">
            <Badge variant="secondary">Searched: {data.data.searched_count}</Badge>
            <Badge variant="secondary">Imported: {data.data.imported_count}</Badge>
            <Badge variant="secondary">Duplicates: {data.data.duplicates_count}</Badge>
            <Badge variant="secondary">Failed: {data.data.failed_count}</Badge>
          </div>
        ) : null}

        <Button disabled={isPending || query.trim() === ''} onClick={() => void submit()}>
          {isPending ? 'Searching & Importing...' : 'Run OpenAlex Search'}
        </Button>
      </CardContent>
    </Card>
  )
}

