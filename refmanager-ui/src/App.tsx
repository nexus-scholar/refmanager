import { DeduplicationReview } from '@/components/DeduplicationReview'
import { DocumentMetadataPanel } from '@/components/DocumentMetadataPanel'
import { DocumentTable } from '@/components/DocumentTable'
import { ImportDropzone } from '@/components/ImportDropzone'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import AppLayout from '@/layouts/app-layout'
import { RefManagerProvider } from '@/providers/ref-manager-provider'
import { useRefManagerStore } from '@/stores/ref-manager-store'

function Workspace() {
  const activeDocumentId = useRefManagerStore((state) => state.activeDocumentId)
  const setActiveDocumentId = useRefManagerStore((state) => state.setActiveDocumentId)

  const isImportDialogOpen = useRefManagerStore((state) => state.isImportDialogOpen)
  const setImportDialogOpen = useRefManagerStore((state) => state.setImportDialogOpen)

  const isDedupDialogOpen = useRefManagerStore((state) => state.isDedupDialogOpen)
  const setDedupDialogOpen = useRefManagerStore((state) => state.setDedupDialogOpen)

  return (
    <AppLayout breadcrumbs={[{ title: 'RefManager Workspace', href: '#' }]}>
      <main className="mx-auto max-w-7xl space-y-6 p-6 md:p-8">
        <header className="rounded-xl border bg-card p-4">
          <div className="flex flex-wrap items-center gap-3">
            <div>
              <h1 className="text-xl font-semibold">Systematic Review Workspace</h1>
              <p className="text-sm text-muted-foreground">
                Screen evidence, inspect metadata, and resolve duplicate records efficiently.
              </p>
            </div>
            <div className="ml-auto flex gap-2">
              <Button onClick={() => setImportDialogOpen(true)}>Import References</Button>
              <Button variant="outline" onClick={() => setDedupDialogOpen(true)}>Review Duplicates</Button>
            </div>
          </div>
        </header>

        <section className="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
          <div className="space-y-4">
            <DocumentTable
              defaultSort="-year"
              columns={['title', 'authors', 'year', 'status']}
              onSelectDocument={setActiveDocumentId}
            />
          </div>
          <div>
            <DocumentMetadataPanel documentId={activeDocumentId} />
          </div>
        </section>
      </main>

      <Dialog open={isImportDialogOpen} onOpenChange={setImportDialogOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Import References</DialogTitle>
            <DialogDescription>
              Upload source files and ingest bibliographic data into your workspace.
            </DialogDescription>
          </DialogHeader>
          <ImportDropzone onSuccess={() => setImportDialogOpen(false)} />
        </DialogContent>
      </Dialog>

      <Dialog open={isDedupDialogOpen} onOpenChange={setDedupDialogOpen}>
        <DialogContent className="h-screen max-w-[720px] translate-x-0 translate-y-0 top-0 start-auto end-0 rounded-none border-l p-6 sm:max-w-[720px]">
          <DialogHeader>
            <DialogTitle>Deduplication Review</DialogTitle>
            <DialogDescription>
              Scan title/year similarity and curate merge decisions.
            </DialogDescription>
          </DialogHeader>
          <div className="overflow-y-auto">
            <DeduplicationReview />
          </div>
        </DialogContent>
      </Dialog>
    </AppLayout>
  )
}

function App() {
  return (
    <RefManagerProvider apiBaseUrl="/api/refmanager">
      <Workspace />
    </RefManagerProvider>
  )
}

export default App
