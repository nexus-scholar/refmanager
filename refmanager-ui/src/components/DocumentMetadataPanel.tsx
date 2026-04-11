import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useDocumentDetails } from '@/hooks/use-document-details'

type DocumentMetadataPanelProps = {
  documentId: number | null
}

function MetadataRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="grid grid-cols-[140px_1fr] gap-2 border-b py-2 last:border-b-0">
      <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>
      <span className="text-sm break-words">{value}</span>
    </div>
  )
}

export function DocumentMetadataPanel({ documentId }: DocumentMetadataPanelProps) {
  const { document, isLoading, error } = useDocumentDetails(documentId)

  return (
    <Card className="h-full">
      <CardHeader>
        <CardTitle>Paper Metadata</CardTitle>
        <CardDescription>
          Scientific details for quick screening and synthesis.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {!documentId ? (
          <p className="text-sm text-muted-foreground">Select a paper row to inspect full metadata.</p>
        ) : null}

        {isLoading ? <p className="text-sm text-muted-foreground">Loading metadata...</p> : null}
        {error ? <p className="text-sm text-destructive">{error}</p> : null}

        {document ? (
          <div className="space-y-3">
            <div className="space-y-2">
              <h3 className="text-base font-semibold leading-tight">{document.title}</h3>
              <div className="flex flex-wrap gap-2">
                <Badge variant="outline">{document.status}</Badge>
                <Badge variant="secondary">{document.document_type}</Badge>
                {document.year ? <Badge variant="secondary">{document.year}</Badge> : null}
              </div>
            </div>

            <div className="rounded-md border p-3 text-sm text-muted-foreground">
              {document.abstract?.trim() ? document.abstract : 'No abstract available.'}
            </div>

            <div className="rounded-md border px-3">
              <MetadataRow label="Authors" value={document.authors.map((author) => author.full_name).join(', ') || '-'} />
              <MetadataRow label="Journal" value={document.journal || '-'} />
              <MetadataRow label="DOI" value={document.doi || '-'} />
              <MetadataRow label="URL" value={document.url || '-'} />
              <MetadataRow label="PubMed ID" value={document.pubmed_id || '-'} />
              <MetadataRow label="Provider" value={document.provider || '-'} />
              <MetadataRow label="Provider ID" value={document.provider_id || '-'} />
              <MetadataRow
                label="Keywords"
                value={document.keywords?.length ? document.keywords.join(', ') : '-'}
              />
              <MetadataRow label="Exclusion Reason" value={document.exclusion_reason || '-'} />
              <MetadataRow label="Created" value={document.created_at || '-'} />
              <MetadataRow label="Updated" value={document.updated_at || '-'} />
            </div>
          </div>
        ) : null}
      </CardContent>
    </Card>
  )
}

