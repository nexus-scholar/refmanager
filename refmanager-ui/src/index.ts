export { RefManagerProvider, useRefManagerConfig } from '@/providers/ref-manager-provider'
export { useDocuments } from '@/hooks/use-documents'
export { useDocumentDetails } from '@/hooks/use-document-details'
export { useImport } from '@/hooks/use-import'
export { useDeduplication } from '@/hooks/use-deduplication'
export { useRefManagerStore } from '@/stores/ref-manager-store'

export type {
  Author,
  DocumentDetails,
  DocumentRecord,
  PaginatedResponse,
} from '@/schemas/document'
export type { ImportResult } from '@/schemas/import'
export type {
  DedupPair,
  DedupResolveResult,
  DedupScanResult,
} from '@/schemas/deduplication'

export { DocumentTable } from '@/components/DocumentTable'
export { DocumentMetadataPanel } from '@/components/DocumentMetadataPanel'
export { ImportDropzone } from '@/components/ImportDropzone'
export { DeduplicationReview } from '@/components/DeduplicationReview'
export { AppSidebar } from '@/components/app-sidebar'
export { AppSidebarHeader } from '@/components/app-sidebar-header'
export { AppShell } from '@/components/app-shell'
export { AppContent } from '@/components/app-content'
export { NavMain } from '@/components/nav-main'
export { NavFooter } from '@/components/nav-footer'
export { default as AppLayout } from '@/layouts/app-layout'

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
export {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger,
  useSidebar,
} from '@/components/ui/sidebar'

export type { BreadcrumbItem, NavItem } from '@/types/navigation'
