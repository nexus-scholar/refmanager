import { useState } from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { useDeduplication } from '@/hooks/use-deduplication'

export function DeduplicationReview() {
  const [threshold, setThreshold] = useState(0.92)
  const {
    duplicates,
    isLoading,
    scanning,
    resolving,
    error,
    scan,
    resolve,
  } = useDeduplication({ threshold })

  async function handleScan() {
    await scan({ threshold })
  }

  async function handleMerge(primaryId: number, candidateId: number) {
    await resolve({
      action: 'merge',
      primaryId,
      candidateIds: [candidateId],
    })
  }

  async function handleKeepBoth(primaryId: number, candidateId: number) {
    await resolve({
      action: 'keep_both',
      primaryId,
      candidateIds: [candidateId],
    })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Deduplication Review</CardTitle>
        <CardDescription>
          Review fuzzy matches and decide whether to merge or keep both.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex items-center gap-2">
          <label className="text-sm text-muted-foreground" htmlFor="threshold">Threshold</label>
          <input
            id="threshold"
            type="number"
            min={0}
            max={1}
            step={0.01}
            value={threshold}
            onChange={(event) => setThreshold(Number(event.target.value))}
            className="h-9 w-28 rounded-md border border-input bg-background px-3 text-sm"
          />
          <Button disabled={scanning} onClick={() => void handleScan()}>
            {scanning ? 'Scanning...' : 'Run Scan'}
          </Button>
        </div>

        {error ? <p className="text-sm text-destructive">{error}</p> : null}

        {isLoading ? (
          <p className="text-sm text-muted-foreground">Loading clusters...</p>
        ) : duplicates.length === 0 ? (
          <p className="text-sm text-muted-foreground">No duplicate candidates found.</p>
        ) : (
          <div className="space-y-3">
            {duplicates.map((pair) => (
              <div key={`${pair.primary.id}-${pair.candidate.id}`} className="rounded-lg border p-3">
                <div className="mb-2 flex flex-wrap gap-2">
                  <Badge variant="secondary">Confidence: {pair.confidence.toFixed(2)}</Badge>
                  <Badge variant="outline">{pair.matched_by}</Badge>
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                  <div className="rounded-md border p-3">
                    <p className="text-xs text-muted-foreground">Primary</p>
                    <p className="font-medium">{pair.primary.title}</p>
                    <p className="text-sm text-muted-foreground">{pair.primary.year ?? '-'}</p>
                  </div>
                  <div className="rounded-md border p-3">
                    <p className="text-xs text-muted-foreground">Candidate</p>
                    <p className="font-medium">{pair.candidate.title}</p>
                    <p className="text-sm text-muted-foreground">{pair.candidate.year ?? '-'}</p>
                  </div>
                </div>
                <div className="mt-3 flex gap-2">
                  <Button
                    disabled={resolving}
                    onClick={() => void handleMerge(pair.primary.id, pair.candidate.id)}
                  >
                    Merge
                  </Button>
                  <Button
                    variant="outline"
                    disabled={resolving}
                    onClick={() => void handleKeepBoth(pair.primary.id, pair.candidate.id)}
                  >
                    Keep Both
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}

