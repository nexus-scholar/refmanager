import { useMemo, useState } from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useDocuments } from '@/hooks/use-documents'
import { useRefManagerStore } from '@/stores/ref-manager-store'

type ColumnKey = 'title' | 'authors' | 'year' | 'status' | 'doi' | 'journal'

type DocumentTableProps = {
  columns?: ColumnKey[]
  defaultSort?: string
  onSelectDocument?: (id: number) => void
}

function nextSort(currentSort: string, ascKey: string, descKey: string): string {
  if (currentSort === descKey)
    return ascKey

  if (currentSort === ascKey)
    return descKey

  return descKey
}

function sortIndicator(currentSort: string, ascKey: string, descKey: string): string {
  if (currentSort === descKey)
    return ' ↓'

  if (currentSort === ascKey)
    return ' ↑'

  return ''
}

export function DocumentTable({
  columns = ['title', 'authors', 'year', 'status'],
  defaultSort = '-year',
  onSelectDocument,
}: DocumentTableProps) {
  const query = useRefManagerStore((state) => state.filters.query)
  const setQuery = useRefManagerStore((state) => state.setQuery)
  const status = useRefManagerStore((state) => state.filters.status)
  const setStatus = useRefManagerStore((state) => state.setStatus)
  const sort = useRefManagerStore((state) => state.filters.sort)
  const setSort = useRefManagerStore((state) => state.setSort)
  const page = useRefManagerStore((state) => state.filters.page)
  const perPage = useRefManagerStore((state) => state.filters.perPage)
  const setPage = useRefManagerStore((state) => state.setPage)
  const setPerPage = useRefManagerStore((state) => state.setPerPage)
  const apiBaseUrl = useRefManagerStore((state) => state.apiBaseUrl)
  const activeDocumentId = useRefManagerStore((state) => state.activeDocumentId)
  const setActiveDocumentId = useRefManagerStore((state) => state.setActiveDocumentId)
  const density = useRefManagerStore((state) => state.tableDensity)
  const setTableDensity = useRefManagerStore((state) => state.setTableDensity)
  const [selectedDocumentIds, setSelectedDocumentIds] = useState<number[]>([])
  const [bulkStatus, setBulkStatus] = useState('')
  const [isBulkApplying, setIsBulkApplying] = useState(false)

  const { documents, pagination, isLoading, error, refetch } = useDocuments({
    sort: sort || defaultSort,
  })

  const sortLabel = useMemo(() => {
    if (sort === '-year')
      return 'Newest first'

    if (sort === 'year')
      return 'Oldest first'

    if (sort === 'title')
      return 'Title A-Z'

    if (sort === '-title')
      return 'Title Z-A'

    return 'Sort'
  }, [sort])

  const showCompactColumns = columns.length > 0
  const columnCount = 2 + (showCompactColumns ? 1 : 0) + 1
  const isCompact = density === 'compact'

  const visibleDocumentIds = useMemo(() => documents.map((document) => document.id), [documents])

  const allVisibleSelected =
    visibleDocumentIds.length > 0
    && visibleDocumentIds.every((id) => selectedDocumentIds.includes(id))

  const selectedCount = selectedDocumentIds.length

  const currentPage = pagination?.current_page ?? page
  const lastPage = pagination?.last_page ?? 1

  async function applyBulkStatusUpdate() {
    if (selectedDocumentIds.length === 0 || bulkStatus === '')
      return

    setIsBulkApplying(true)

    try {
      await Promise.all(
        selectedDocumentIds.map(async (id) => {
          const response = await fetch(`${apiBaseUrl}/documents/${id}`, {
            method: 'PATCH',
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: bulkStatus }),
          })

          if (!response.ok)
            throw new Error(`Bulk update failed for document ${id}`)
        }),
      )

      setSelectedDocumentIds([])
      setBulkStatus('')
      await refetch()
    } finally {
      setIsBulkApplying(false)
    }
  }

  function toggleSelectAllVisible(checked: boolean) {
    if (checked) {
      setSelectedDocumentIds((prev) => Array.from(new Set([...prev, ...visibleDocumentIds])))
      return
    }

    setSelectedDocumentIds((prev) => prev.filter((id) => !visibleDocumentIds.includes(id)))
  }

  function toggleSelectDocument(documentId: number, checked: boolean) {
    setSelectedDocumentIds((prev) => {
      if (checked)
        return prev.includes(documentId) ? prev : [...prev, documentId]

      return prev.filter((id) => id !== documentId)
    })
  }

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center gap-2">
        <input
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Search title, abstract, DOI, journal..."
          className="h-9 min-w-64 rounded-md border border-input bg-background px-3 text-sm"
        />
        <select
          value={status}
          onChange={(event) => setStatus(event.target.value)}
          className="h-9 rounded-md border border-input bg-background px-3 text-sm"
        >
          <option value="">All statuses</option>
          <option value="imported">Imported</option>
          <option value="included">Included</option>
          <option value="excluded">Excluded</option>
          <option value="title_abstract_screened">Title/Abstract Screened</option>
          <option value="full_text_screened">Full Text Screened</option>
        </select>
        <select
          value={sort || defaultSort}
          onChange={(event) => setSort(event.target.value)}
          className="h-9 rounded-md border border-input bg-background px-3 text-sm"
        >
          <option value="-year">Newest first</option>
          <option value="year">Oldest first</option>
          <option value="title">Title A-Z</option>
          <option value="-title">Title Z-A</option>
        </select>
        <div className="inline-flex items-center gap-1 rounded-md border border-input p-0.5">
          <Button
            type="button"
            size="xs"
            variant={isCompact ? 'default' : 'ghost'}
            onClick={() => setTableDensity('compact')}
          >
            Compact
          </Button>
          <Button
            type="button"
            size="xs"
            variant={!isCompact ? 'default' : 'ghost'}
            onClick={() => setTableDensity('comfortable')}
          >
            Comfortable
          </Button>
        </div>
        {selectedCount > 0 ? (
          <>
            <Badge variant="outline">Selected: {selectedCount}</Badge>
            <select
              value={bulkStatus}
              onChange={(event) => setBulkStatus(event.target.value)}
              className="h-9 rounded-md border border-input bg-background px-2 text-sm"
            >
              <option value="">Bulk action...</option>
              <option value="imported">Mark Imported</option>
              <option value="included">Mark Included</option>
              <option value="excluded">Mark Excluded</option>
              <option value="title_abstract_screened">Mark Title/Abstract Screened</option>
              <option value="full_text_screened">Mark Full Text Screened</option>
            </select>
            <Button
              type="button"
              size="xs"
              disabled={bulkStatus === '' || isBulkApplying}
              onClick={() => void applyBulkStatusUpdate()}
            >
              {isBulkApplying ? 'Applying...' : 'Apply'}
            </Button>
          </>
        ) : null}
        <Badge variant="secondary" className="ml-auto">{sortLabel}</Badge>
      </div>

      {error ? <p className="text-sm text-destructive">{error}</p> : null}

      <div className="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className={isCompact ? 'w-[44px]' : 'w-[56px]'}>
                <input
                  type="checkbox"
                  checked={allVisibleSelected}
                  onChange={(event) => toggleSelectAllVisible(event.target.checked)}
                  aria-label="Select all visible papers"
                />
              </TableHead>
              <TableHead>
                <button
                  type="button"
                  className="text-left font-medium"
                  onClick={() => setSort(nextSort(sort || defaultSort, 'title', '-title'))}
                >
                  Paper{sortIndicator(sort || defaultSort, 'title', '-title')}
                </button>
              </TableHead>
              {showCompactColumns ? (
                <TableHead className={isCompact ? 'w-[120px]' : 'w-[140px]'}>
                  <button
                    type="button"
                    className="text-left font-medium"
                    onClick={() => setSort(nextSort(sort || defaultSort, 'year', '-year'))}
                  >
                    Signals{sortIndicator(sort || defaultSort, 'year', '-year')}
                  </button>
                </TableHead>
              ) : null}
              <TableHead className={isCompact ? 'w-[120px]' : 'w-[140px]'}>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={columnCount} className="text-center text-muted-foreground">
                  Loading documents...
                </TableCell>
              </TableRow>
            ) : documents.length === 0 ? (
              <TableRow>
                <TableCell colSpan={columnCount} className="text-center text-muted-foreground">
                  No documents found.
                </TableCell>
              </TableRow>
            ) : (
              documents.map((document) => (
                <TableRow
                  key={document.id}
                  className={isCompact ? 'cursor-pointer [&_td]:py-2' : 'cursor-pointer'}
                  data-state={activeDocumentId === document.id ? 'selected' : undefined}
                  onClick={() => {
                    setActiveDocumentId(document.id)
                    onSelectDocument?.(document.id)
                  }}
                >
                  <TableCell className="align-top" onClick={(event) => event.stopPropagation()}>
                    <input
                      type="checkbox"
                      checked={selectedDocumentIds.includes(document.id)}
                      onChange={(event) => toggleSelectDocument(document.id, event.target.checked)}
                      aria-label={`Select ${document.title}`}
                    />
                  </TableCell>
                  <TableCell className="align-top">
                    <div className={isCompact ? 'space-y-1.5' : 'space-y-2'}>
                      <p className={isCompact ? 'text-xs font-semibold leading-tight' : 'text-sm font-semibold leading-tight'}>{document.title}</p>
                      <p className={
                        isCompact
                          ? 'line-clamp-1 text-[12px] text-muted-foreground whitespace-normal wrap-break-word'
                          : 'line-clamp-2 text-xs text-muted-foreground whitespace-normal wrap-break-word'
                      }>
                        {document.abstract?.trim() || 'No abstract available.'}
                      </p>
                      <div className={isCompact ? 'flex flex-wrap gap-1.5 text-[9px] font-semibold tracking-wide text-muted-foreground' : 'flex flex-wrap gap-2 text-xs text-muted-foreground'}>
                        <span>{document.authors?.map((author) => author.full_name).join(', ') || 'Unknown authors'}</span>
                        <span>•</span>
                        <span>{document.journal || 'Unknown journal'}</span>
                        <span>•</span>
                        <span>{document.year ?? 'n/a'}</span>
                        {document.doi ? (
                          <>
                            <span>•</span>
                            <span className="font-mono">{document.doi}</span>
                          </>
                        ) : null}
                      </div>
                    </div>
                  </TableCell>
                  {showCompactColumns ? (
                    <TableCell className="align-top">
                      <div className={isCompact ? 'flex flex-wrap gap-1' : 'flex flex-wrap gap-1.5'}>
                        <Badge variant="outline" className={isCompact ? 'px-1 text-[10px]' : 'text-[11px]'}>{document.status}</Badge>
                        <Badge variant="secondary" className={isCompact ? 'px-1 text-[10px]' : 'text-[11px]'}>{document.document_type}</Badge>
                        {document.doi ? <Badge variant="secondary" className={isCompact ? 'px-1 text-[10px]' : 'text-[11px]'}>DOI</Badge> : null}
                        {document.pubmed_id ? <Badge variant="secondary" className={isCompact ? 'px-1 text-[10px]' : 'text-[11px]'}>PubMed</Badge> : null}
                      </div>
                    </TableCell>
                  ) : null}
                  <TableCell className="align-top">
                    <div className={isCompact ? 'flex flex-wrap gap-1' : 'flex flex-wrap gap-1.5'} onClick={(event) => event.stopPropagation()}>
                      <Button
                        size={isCompact ? 'icon-xs' : 'xs'}
                        onClick={() => {
                          setActiveDocumentId(document.id)
                          onSelectDocument?.(document.id)
                        }}
                      >
                        {isCompact ? 'V' : 'View'}
                      </Button>
                      {document.url ? (
                        <Button size={isCompact ? 'icon-xs' : 'xs'} variant="outline" asChild>
                          <a href={document.url} target="_blank" rel="noopener noreferrer">{isCompact ? 'O' : 'Open'}</a>
                        </Button>
                      ) : null}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <span>Total: {pagination?.total ?? documents.length}</span>
        <span>Page: {currentPage} / {lastPage}</span>
        <span>Per page:</span>
        <select
          value={perPage}
          onChange={(event) => setPerPage(Number(event.target.value))}
          className="h-8 rounded-md border border-input bg-background px-2 text-xs"
        >
          <option value={10}>10</option>
          <option value={25}>25</option>
          <option value={50}>50</option>
          <option value={100}>100</option>
        </select>
        <Button
          type="button"
          size="xs"
          variant="outline"
          disabled={currentPage <= 1}
          onClick={() => setPage(currentPage - 1)}
        >
          Previous
        </Button>
        <Button
          type="button"
          size="xs"
          variant="outline"
          disabled={currentPage >= lastPage}
          onClick={() => setPage(currentPage + 1)}
        >
          Next
        </Button>
      </div>
    </div>
  )
}
