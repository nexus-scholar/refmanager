import type { ReactNode } from 'react'
import { SidebarProvider } from '@/components/ui/sidebar'

type Props = {
  children: ReactNode
}

export function AppShell({ children }: Props) {
  return <SidebarProvider defaultOpen>{children}</SidebarProvider>
}

