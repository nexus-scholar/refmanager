import type { LucideIcon } from 'lucide-react'

export type BreadcrumbItem = {
  title: string
  href?: string
}

export type NavItem = {
  title: string
  href: string
  icon?: LucideIcon | null
  isActive?: boolean
}

