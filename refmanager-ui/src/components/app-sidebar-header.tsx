import { SidebarTrigger } from '@/components/ui/sidebar'
import type { BreadcrumbItem } from '@/types/navigation'

export function AppSidebarHeader({
  breadcrumbs = [],
}: {
  breadcrumbs?: BreadcrumbItem[]
}) {
  return (
    <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 px-6 md:px-4">
      <div className="flex items-center gap-2">
        <SidebarTrigger className="-ml-1" />
        <div className="hidden items-center gap-2 text-sm text-muted-foreground sm:flex">
          {breadcrumbs.length === 0 ? (
            <span>Workspace</span>
          ) : (
            breadcrumbs.map((item, index) => (
              <div key={`${item.title}-${index}`} className="flex items-center gap-2">
                {index > 0 ? <span className="text-muted-foreground/50">/</span> : null}
                <span>{item.title}</span>
              </div>
            ))
          )}
        </div>
      </div>
    </header>
  )
}

