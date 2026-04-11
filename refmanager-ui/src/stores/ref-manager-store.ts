import { create } from 'zustand'

type DocumentFilters = {
  page: number
  perPage: number
  query: string
  status: string
  sort: string
}

type TableDensity = 'comfortable' | 'compact'

type RefManagerState = {
  apiBaseUrl: string
  filters: DocumentFilters
  activeDocumentId: number | null
  isImportDialogOpen: boolean
  isDedupDialogOpen: boolean
  isNexusSearchDialogOpen: boolean
  tableDensity: TableDensity
  setApiBaseUrl: (apiBaseUrl: string) => void
  setFilters: (filters: Partial<DocumentFilters>) => void
  setStatus: (status: string) => void
  setSort: (sort: string) => void
  setQuery: (query: string) => void
  setPage: (page: number) => void
  setPerPage: (perPage: number) => void
  setActiveDocumentId: (id: number | null) => void
  setImportDialogOpen: (open: boolean) => void
  setDedupDialogOpen: (open: boolean) => void
  setNexusSearchDialogOpen: (open: boolean) => void
  setTableDensity: (density: TableDensity) => void
  resetFilters: () => void
}

const initialFilters: DocumentFilters = {
  page: 1,
  perPage: 10,
  query: '',
  status: '',
  sort: '-year',
}

export const useRefManagerStore = create<RefManagerState>((set) => ({
  apiBaseUrl: '/api/refmanager',
  filters: initialFilters,
  activeDocumentId: null,
  isImportDialogOpen: false,
  isDedupDialogOpen: false,
  isNexusSearchDialogOpen: false,
  tableDensity: 'compact',
  setApiBaseUrl: (apiBaseUrl) => set({ apiBaseUrl }),
  setFilters: (filters) =>
    set((state) => ({
      filters: { ...state.filters, ...filters },
    })),
  setStatus: (status) =>
    set((state) => ({
      filters: { ...state.filters, status, page: 1 },
    })),
  setSort: (sort) =>
    set((state) => ({
      filters: { ...state.filters, sort, page: 1 },
    })),
  setQuery: (query) =>
    set((state) => ({
      filters: { ...state.filters, query, page: 1 },
    })),
  setPage: (page) =>
    set((state) => ({
      filters: { ...state.filters, page: Math.max(page, 1) },
    })),
  setPerPage: (perPage) =>
    set((state) => ({
      filters: { ...state.filters, perPage: Math.max(perPage, 1), page: 1 },
    })),
  setActiveDocumentId: (activeDocumentId) => set({ activeDocumentId }),
  setImportDialogOpen: (isImportDialogOpen) => set({ isImportDialogOpen }),
  setDedupDialogOpen: (isDedupDialogOpen) => set({ isDedupDialogOpen }),
  setNexusSearchDialogOpen: (isNexusSearchDialogOpen) => set({ isNexusSearchDialogOpen }),
  setTableDensity: (tableDensity) => set({ tableDensity }),
  resetFilters: () => set({ filters: initialFilters }),
}))

