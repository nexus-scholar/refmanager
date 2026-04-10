import { create } from 'zustand'

type DocumentFilters = {
  page: number
  perPage: number
  query: string
  status: string
}

type RefManagerState = {
  apiBaseUrl: string
  filters: DocumentFilters
  setApiBaseUrl: (apiBaseUrl: string) => void
  setFilters: (filters: Partial<DocumentFilters>) => void
  setStatus: (status: string) => void
  setQuery: (query: string) => void
  setPage: (page: number) => void
  setPerPage: (perPage: number) => void
  resetFilters: () => void
}

const initialFilters: DocumentFilters = {
  page: 1,
  perPage: 10,
  query: '',
  status: '',
}

export const useRefManagerStore = create<RefManagerState>((set) => ({
  apiBaseUrl: '/api/refmanager',
  filters: initialFilters,
  setApiBaseUrl: (apiBaseUrl) => set({ apiBaseUrl }),
  setFilters: (filters) =>
    set((state) => ({
      filters: { ...state.filters, ...filters },
    })),
  setStatus: (status) =>
    set((state) => ({
      filters: { ...state.filters, status, page: 1 },
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
  resetFilters: () => set({ filters: initialFilters }),
}))

