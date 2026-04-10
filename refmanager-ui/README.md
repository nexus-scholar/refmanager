# @nexus/refmanager-ui

React + Tailwind + shadcn UI toolkit for `nexus/refmanager`.

## What is included

- Vite + React + TypeScript scaffold
- Tailwind CSS v4 + `@tailwindcss/vite`
- shadcn initialized with:
  - preset: `bZ6`
  - template: `vite`
  - RTL enabled
- Base toolkit wiring:
  - `RefManagerProvider`
  - `useDocuments` hook
  - shadcn primitives (`Button`, `Card`, `Table`, `Badge`, `Dialog`)

## Quick start

```bash
npm install
npm run dev
```

Open the local Vite URL and ensure your Laravel API is available at `/api/refmanager`.

## Build check

```bash
npm run build
```

## Current structure

- `src/providers/ref-manager-provider.tsx` - API config provider
- `src/hooks/use-documents.ts` - documents fetch hook
- `src/components/ui/*` - shadcn primitives
- `src/types/document.ts` - shared API types
- `src/App.tsx` - runnable demo dashboard

## Integration note for host apps

When consumed from another app, ensure Tailwind scans package sources (or distributed build output) so component utility classes are generated.
