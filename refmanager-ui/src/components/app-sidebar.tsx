import { BookOpen, FolderGit2, LayoutGrid } from 'lucide-react'
import AppLogo from '@/components/app-logo'
import { NavFooter } from '@/components/nav-footer'
import { NavMain } from '@/components/nav-main'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar'
import type { NavItem } from '@/types/navigation'

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '#',
    icon: LayoutGrid,
    isActive: true,
  },
]

const footerNavItems: NavItem[] = [
  {
    title: 'Repository',
    href: 'https://github.com/nexus-scholar/refmanager',
    icon: FolderGit2,
  },
  {
    title: 'Documentation',
    href: 'https://laravel.com/docs/starter-kits#react',
    icon: BookOpen,
  },
]

export function AppSidebar() {
  return (
    <Sidebar collapsible="icon">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <a href="#">
                <AppLogo />
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={mainNavItems} />
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItems} className="mt-auto" />
      </SidebarFooter>
    </Sidebar>
  )
}

