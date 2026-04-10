export { RefManagerProvider, useRefManagerConfig } from '@/providers/ref-manager-provider'
export { useDocuments } from '@/hooks/use-documents'
export { useImport } from '@/hooks/use-import'
export { useDeduplication } from '@/hooks/use-deduplication'
export { useRefManagerStore } from '@/stores/ref-manager-store'

export type { Author, DocumentRecord, PaginatedResponse } from '@/schemas/document'
export type { ImportResult } from '@/schemas/import'
export type {
  DedupPair,
  DedupResolveResult,
  DedupScanResult,
} from '@/schemas/deduplication'

export { DocumentTable } from '@/components/DocumentTable'
export { ImportDropzone } from '@/components/ImportDropzone'
export { DeduplicationReview } from '@/components/DeduplicationReview'

export { Button, buttonVariants } from '@/components/ui/button'
export { Badge } from '@/components/ui/badge'
export {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
export {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableFooter,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
export {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
