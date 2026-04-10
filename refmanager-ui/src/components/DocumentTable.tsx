import { useMemo } from 'react'
import { Badge } from '@/components/ui/badge'
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
}

const labelMap: Record<ColumnKey, string> = {
  title: 'Title',
  authors: 'Authors',
  year: 'Year',
  status: 'Status',
  doi: 'DOI',
  journal: 'Journal',
}

export function DocumentTable({
  columns = ['title', 'authors', 'year', 'status'],
  defaultSort = '-year',
}: DocumentTableProps) {
  const query = useRefManagerStore((state) => state.filters.query)
  const setQuery = useRefManagerStore((state) => state.setQuery)
  const status = useRefManagerStore((state) => state.filters.status)
  const setStatus = useRefManagerStore((state) => state.setStatus)
  const sort = useRefManagerStore((state) => state.filters.sort)
  const setSort = useRefManagerStore((state) => state.setSort)

  const { documents, pagination, isLoading, error } = useDocuments({
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
        <Badge variant="secondary" className="ml-auto">{sortLabel}</Badge>
      </div>

      {error ? <p className="text-sm text-destructive">{error}</p> : null}

      <div className="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              {columns.map((column) => (
                <TableHead key={column}>{labelMap[column]}</TableHead>
              ))}
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={columns.length + 1} className="text-center text-muted-foreground">
                  Loading documents...
                </TableCell>
              </TableRow>
            ) : documents.length === 0 ? (
              <TableRow>
                <TableCell colSpan={columns.length + 1} className="text-center text-muted-foreground">
                  No documents found.
                </TableCell>
              </TableRow>
            ) : (
              documents.map((document) => (
                <TableRow key={document.id}>
                  <TableCell>{document.id}</TableCell>
                  {columns.map((column) => (
                    <TableCell key={`${document.id}-${column}`}>
                      {column === 'title' && <span className="font-medium">{document.title}</span>}
                      {column === 'authors' && (document.authors?.map((author) => author.full_name).join(', ') || '-')}
                      {column === 'year' && (document.year ?? '-')}
                      {column === 'status' && <Badge variant="outline">{document.status}</Badge>}
                      {column === 'doi' && (document.doi ?? '-')}
                      {column === 'journal' && (document.journal ?? '-')}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <span>Total: {pagination?.total ?? documents.length}</span>
        <span>Page: {pagination?.current_page ?? 1}</span>
        <span>Per page: {pagination?.per_page ?? 10}</span>
      </div>
    </div>
  )
}

