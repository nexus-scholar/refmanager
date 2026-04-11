# @nexus/refmanager-ui

React + Tailwind + shadcn UI toolkit for `nexus/refmanager`.

## What is included

- Vite + React + TypeScript scaffold
- Tailwind CSS v4 + `@tailwindcss/vite`
- State/query/validation stack:
  - `zustand`
  - `@tanstack/react-query`
  - `zod`
- shadcn initialized with:
  - preset: `bZ6`
  - template: `vite`
  - LTR currently enabled (`rtl` can be switched later)
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

## Local development workflows

### Option A: npm link

```bash
cd C:\Users\mouadh\Desktop\projects\nexus\refmanager\refmanager-ui
npm link

cd <your-host-app>
npm link @nexus/refmanager-ui
```

### Option B: workspace reference

Point your host app/workspace dependency to the local path:

```json
{
  "dependencies": {
    "@nexus/refmanager-ui": "file:../refmanager/refmanager-ui"
  }
}
```

## Host Tailwind content configuration

Ensure the host Tailwind scanner includes UI package files:

```ts
// tailwind.config.ts
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.{js,ts,jsx,tsx}',
    './node_modules/@nexus/refmanager-ui/**/*.{js,ts,jsx,tsx}',
  ],
}
```

## Current structure

- `src/providers/ref-manager-provider.tsx` - API config provider
- `src/hooks/use-documents.ts` - documents query hook
- `src/hooks/use-import.ts` - import mutation hook with progress
- `src/hooks/use-deduplication.ts` - dedup scan/resolve hook
- `src/stores/ref-manager-store.ts` - global state store
- `src/schemas/*.ts` - runtime API validation schemas
- `src/components/ui/*` - shadcn primitives
- `src/components/DocumentTable.tsx` - core table component
- `src/components/ImportDropzone.tsx` - import UI component
- `src/components/NexusSearchImportPanel.tsx` - OpenAlex search + import panel
- `src/components/DeduplicationReview.tsx` - dedup workflow component
- `src/components/DocumentMetadataPanel.tsx` - full paper metadata side panel
- `src/types/document.ts` - shared API types
- `src/App.tsx` - runnable demo dashboard

## UX model (current)

- Import and deduplication workflows open in dialogs/panels from the workspace header.
- OpenAlex search-import runs from a dedicated panel (`/api/refmanager/nexus/search-import`).
- `DocumentTable` is optimized for screening with richer evidence signals in each row.
- Selecting a row opens detailed bibliographic metadata in a side panel.

## Integration note for host apps

When consumed from another app, ensure Tailwind scans package sources (or distributed build output) so component utility classes are generated.

## Dev API proxy (important)

When running `refmanager-ui` with its own Vite dev server, relative API calls like
`/api/refmanager/documents` go to the UI origin by default (e.g. `http://127.0.0.1:5174`).

This project is configured to proxy `/api/*` to your Laravel backend.

1) Copy env template:

```bash
cp .env.example .env
```

2) Set backend URL in `.env` (if different from default):

```dotenv
VITE_BACKEND_URL=http://127.0.0.1:8000
```

3) Run UI dev server:

```bash
npm run dev
```

Now `/api/refmanager/*` from the UI is forwarded to your Laravel app.
