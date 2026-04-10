import { useMemo } from 'react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useDocuments } from '@/hooks/use-documents'
import { RefManagerProvider } from '@/providers/ref-manager-provider'
import { useRefManagerStore } from '@/stores/ref-manager-store'

function DocumentsPreview() {
  const statusFilter = useRefManagerStore((state) => state.filters.status)
  const setStatus = useRefManagerStore((state) => state.setStatus)
  const { documents, pagination, isLoading, error } = useDocuments()

  const stats = useMemo(() => {
    const included = documents.filter((doc) => doc.status === 'included').length
    const excluded = documents.filter((doc) => doc.status === 'excluded').length

    return { included, excluded }
  }, [documents])

  return (
    <main className="mx-auto max-w-6xl space-y-6 p-6 md:p-8">
      <Card>
        <CardHeader>
          <CardTitle>RefManager UI Toolkit (LTR)</CardTitle>
          <CardDescription>
            Connected to your package API using Zustand state + TanStack Query + Zod.
          </CardDescription>
        </CardHeader>
        <CardContent className="flex flex-wrap items-center gap-2">
          <Button variant={statusFilter === '' ? 'default' : 'outline'} onClick={() => setStatus('')}>
            All
          </Button>
          <Button
            variant={statusFilter === 'included' ? 'default' : 'outline'}
            onClick={() => setStatus('included')}
          >
            Included
          </Button>
          <Button
            variant={statusFilter === 'excluded' ? 'default' : 'outline'}
            onClick={() => setStatus('excluded')}
          >
            Excluded
          </Button>
          <div className="ml-auto flex gap-2">
            <Badge variant="secondary">Included: {stats.included}</Badge>
            <Badge variant="secondary">Excluded: {stats.excluded}</Badge>
            <Badge>Total: {pagination?.total ?? documents.length}</Badge>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Documents</CardTitle>
          <CardDescription>
            Source: /api/refmanager/documents
          </CardDescription>
        </CardHeader>
        <CardContent>
          {error ? <p className="text-sm text-destructive">{error}</p> : null}
          <div className="overflow-x-auto rounded-lg border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>ID</TableHead>
                  <TableHead>Title</TableHead>
                  <TableHead>Year</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Authors</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {isLoading ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center text-muted-foreground">
                      Loading documents...
                    </TableCell>
                  </TableRow>
                ) : documents.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center text-muted-foreground">
                      No documents found.
                    </TableCell>
                  </TableRow>
                ) : (
                  documents.map((document) => (
                    <TableRow key={document.id}>
                      <TableCell>{document.id}</TableCell>
                      <TableCell className="font-medium">{document.title}</TableCell>
                      <TableCell>{document.year ?? '-'}</TableCell>
                      <TableCell>
                        <Badge variant="outline">{document.status}</Badge>
                      </TableCell>
                      <TableCell>
                        {document.authors?.map((author) => author.full_name).join(', ') || '-'}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </main>
  )
}

function App() {
  return (
    <RefManagerProvider apiBaseUrl="/api/refmanager">
      <DocumentsPreview />
    </RefManagerProvider>
  )
}

export default App
