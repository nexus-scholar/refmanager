import { Slot } from '@radix-ui/react-slot'
import { cva, type VariantProps } from 'class-variance-authority'
import { PanelLeftCloseIcon, PanelLeftOpenIcon } from 'lucide-react'
import * as React from 'react'

import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const SIDEBAR_WIDTH = '16rem'
const SIDEBAR_WIDTH_ICON = '3.5rem'

type SidebarContextValue = {
  open: boolean
  state: 'expanded' | 'collapsed'
  isMobile: boolean
  setOpen: (open: boolean) => void
  toggleSidebar: () => void
}

const SidebarContext = React.createContext<SidebarContextValue | null>(null)

function useSidebar() {
  const context = React.useContext(SidebarContext)
  if (!context)
    throw new Error('useSidebar must be used within SidebarProvider.')

  return context
}

function SidebarProvider({
  defaultOpen = true,
  open: openProp,
  onOpenChange,
  className,
  style,
  children,
  ...props
}: React.ComponentProps<'div'> & {
  defaultOpen?: boolean
  open?: boolean
  onOpenChange?: (open: boolean) => void
}) {
  const [internalOpen, setInternalOpen] = React.useState(defaultOpen)
  const open = openProp ?? internalOpen

  const setOpen = React.useCallback((next: boolean) => {
    onOpenChange?.(next)
    if (openProp === undefined)
      setInternalOpen(next)
  }, [onOpenChange, openProp])

  const toggleSidebar = React.useCallback(() => setOpen(!open), [open, setOpen])

  const context = React.useMemo<SidebarContextValue>(() => ({
    open,
    state: open ? 'expanded' : 'collapsed',
    isMobile: false,
    setOpen,
    toggleSidebar,
  }), [open, setOpen, toggleSidebar])

  return (
    <SidebarContext.Provider value={context}>
      <div
        data-slot="sidebar-wrapper"
        data-state={context.state}
        style={{
          '--sidebar-width': SIDEBAR_WIDTH,
          '--sidebar-width-icon': SIDEBAR_WIDTH_ICON,
          ...style,
        } as React.CSSProperties}
        className={cn('group/sidebar-wrapper flex min-h-screen w-full bg-background', className)}
        {...props}
      >
        {children}
      </div>
    </SidebarContext.Provider>
  )
}

function Sidebar({
  className,
  children,
  collapsible = 'icon',
  ...props
}: React.ComponentProps<'aside'> & {
  collapsible?: 'icon' | 'none'
}) {
  const { state } = useSidebar()

  return (
    <aside
      data-slot="sidebar"
      data-state={state}
      data-collapsible={collapsible}
      className={cn(
        'hidden border-r border-sidebar-border bg-sidebar text-sidebar-foreground md:flex md:h-screen md:flex-col md:sticky md:top-0 transition-[width] duration-200',
        state === 'collapsed' && collapsible === 'icon' ? 'w-(--sidebar-width-icon)' : 'w-(--sidebar-width)',
        className,
      )}
      {...props}
    >
      {children}
    </aside>
  )
}

function SidebarInset({ className, ...props }: React.ComponentProps<'main'>) {
  return (
    <main
      data-slot="sidebar-inset"
      className={cn('min-h-screen flex-1 bg-background', className)}
      {...props}
    />
  )
}

function SidebarTrigger({ className, ...props }: React.ComponentProps<typeof Button>) {
  const { state, toggleSidebar } = useSidebar()

  return (
    <Button
      data-slot="sidebar-trigger"
      variant="ghost"
      size="icon-sm"
      className={cn('h-8 w-8', className)}
      onClick={toggleSidebar}
      {...props}
    >
      {state === 'collapsed' ? <PanelLeftOpenIcon /> : <PanelLeftCloseIcon />}
      <span className="sr-only">Toggle sidebar</span>
    </Button>
  )
}

function SidebarHeader({ className, ...props }: React.ComponentProps<'div'>) {
  return <div data-slot="sidebar-header" className={cn('p-2', className)} {...props} />
}

function SidebarContent({ className, ...props }: React.ComponentProps<'div'>) {
  return <div data-slot="sidebar-content" className={cn('flex-1 overflow-auto p-2', className)} {...props} />
}

function SidebarFooter({ className, ...props }: React.ComponentProps<'div'>) {
  return <div data-slot="sidebar-footer" className={cn('p-2', className)} {...props} />
}

function SidebarGroup({ className, ...props }: React.ComponentProps<'div'>) {
  return <div data-slot="sidebar-group" className={cn('mb-2', className)} {...props} />
}

function SidebarGroupLabel({ className, ...props }: React.ComponentProps<'div'>) {
  const { state } = useSidebar()

  return (
    <div
      data-slot="sidebar-group-label"
      className={cn('px-2 pb-2 text-xs font-medium text-sidebar-foreground/70', state === 'collapsed' ? 'hidden' : 'block', className)}
      {...props}
    />
  )
}

function SidebarGroupContent({ className, ...props }: React.ComponentProps<'div'>) {
  return <div data-slot="sidebar-group-content" className={cn('space-y-1', className)} {...props} />
}

function SidebarMenu({ className, ...props }: React.ComponentProps<'ul'>) {
  return <ul data-slot="sidebar-menu" className={cn('space-y-1', className)} {...props} />
}

function SidebarMenuItem({ className, ...props }: React.ComponentProps<'li'>) {
  return <li data-slot="sidebar-menu-item" className={cn('', className)} {...props} />
}

const sidebarMenuButtonVariants = cva(
  'flex w-full items-center gap-2 rounded-md px-2 py-2 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
  {
    variants: {
      isActive: {
        true: 'bg-sidebar-primary text-sidebar-primary-foreground',
        false: '',
      },
      size: {
        default: 'h-9',
        lg: 'h-11',
      },
    },
    defaultVariants: {
      isActive: false,
      size: 'default',
    },
  },
)

function SidebarMenuButton({
  className,
  asChild = false,
  isActive,
  size,
  ...props
}: React.ComponentProps<'button'> &
  VariantProps<typeof sidebarMenuButtonVariants> & {
    asChild?: boolean
    tooltip?: { children: React.ReactNode }
  }) {
  const Comp = asChild ? Slot : 'button'
  const { state } = useSidebar()

  return (
    <Comp
      data-slot="sidebar-menu-button"
      data-active={isActive ? 'true' : undefined}
      className={cn(
        sidebarMenuButtonVariants({ isActive, size }),
        state === 'collapsed' ? 'justify-center [&>span]:hidden' : '',
        className,
      )}
      {...props}
    />
  )
}

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
}

