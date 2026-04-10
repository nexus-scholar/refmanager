import { DeduplicationReview } from '@/components/DeduplicationReview'
import { DocumentTable } from '@/components/DocumentTable'
import { ImportDropzone } from '@/components/ImportDropzone'
import { RefManagerProvider } from '@/providers/ref-manager-provider'

function Workspace() {
  return (
    <main className="mx-auto max-w-7xl space-y-6 p-6 md:p-8">
      <section className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-1">
          <ImportDropzone />
        </div>
        <div className="lg:col-span-2">
          <DocumentTable defaultSort="-year" columns={['title', 'authors', 'year', 'status']} />
        </div>
      </section>
      <section>
        <DeduplicationReview />
      </section>
    </main>
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
