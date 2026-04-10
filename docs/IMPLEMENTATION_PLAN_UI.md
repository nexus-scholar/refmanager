# RefManager UI/UX Implementation Plan

This document outlines the step-by-step implementation of the full-stack UI architecture for the `refmanager` package, as defined in `docs/UI_ARCHITECTURE.md`.

## Architecture Overview
- **Backend**: Laravel-based Headless API (REST/JSON) within the existing `refmanager` package.
- **Frontend**: Standalone React toolkit `@nexus/refmanager-ui` using Shadcn UI and Tailwind CSS.
- **Structure**: Monorepo approach. The UI package resides in `refmanager/ui` or a sibling directory.

---

## Phase 1: API Layer (Laravel Backend)
Transform the headless package into a data provider.

### 1.1 Foundation & Routing
- [ ] Create `refmanager/routes/api.php` for internal package routes.
- [ ] Update `RefManagerServiceProvider` to:
    - Load routes with configurable prefix and middleware.
    - Add `api` configuration key to `config/refmanager.php`.
- [ ] Implement `Http/Resources` for consistent JSON output:
        - [x] Create `refmanager/routes/api.php` for internal package routes.
        - [x] Update `RefManagerServiceProvider` to:
            - Load routes with configurable prefix and middleware.
            - Add `api` configuration key to `config/refmanager.php`.
        - [x] Implement `Http/Resources` for consistent JSON output:
            - `DocumentResource`
            - `AuthorResource`
            - `CollectionResource` (for search/import results)
    - `DocumentResource`
    - `AuthorResource`
    - `CollectionResource` (for search/import results)

### 1.2 Core Endpoints
- [x] **Documents API**: `GET /documents`, `GET /documents/{id}`, `PATCH /documents/{id}`, `DELETE /documents/{id}`.
    - Support filtering by status (PRISMA), year, and search query.
- [x] **Import API**: `POST /import` - handles binary/text file upload and routes to `ReferenceImporter`.
- [x] **Deduplication API**:
    - `POST /deduplicate/scan`: Trigger fuzzy matching.
    - `GET /duplicates`: List identified clusters for review.
    - `POST /duplicates/resolve`: Merge or ignore candidate duplicates.

---

## Phase 2: UI Toolkit (React Frontend)
Build the modular component library in `refmanager/ui`.

### 2.1 Project Setup
- [x] Initialize Vite + TypeScript + Tailwind project.
- [x] Configure `shadcn/ui` and install base primitives (Table, Button, Dialog, Badge, Card).
- [x] Setup `axios` or `fetch` client with configurable `baseURL`.

### 2.2 Core Components
- [x] **`<RefManagerProvider />`**: Context provider for API config and global state.
- [x] **`<DocumentTable />`**:
    - Server-side pagination and sorting.
    - Status badges (Screened, Included, Excluded).
- [x] **`<ImportDropzone />`**:
    - File upload UI with progress tracking.
    - Validation error reporting (from `ParseException`).
- [x] **`<DeduplicationReview />`**:
    - Side-by-side comparison of duplicate candidates.
    - "Merge" and "Keep Both" actions.

### 2.3 Integration Hooks
- [x] `useDocuments()`: SWR/React-Query hook for library management.
- [x] `useImport()`: Mutation hook for running imports.
- [x] `useDeduplication()`: Hook for managing the dedup workflow.

---

## Phase 3: Integration & Distribution
### 3.1 Local Development Workflow
- [x] Setup `npm link` or workspace reference for testing the UI inside a host Laravel app.
- [x] Document Tailwind "Content" path configuration for host applications.

### 3.2 Installation Commands
- [x] Add `refmanager:ui-install` Artisan command to scaffold a basic dashboard in a Laravel/Inertia project.

---

## Technical Notes
- **Namespaces**: `Nexus\RefManager\Http\Controllers`, `Nexus\RefManager\Http\Resources`.
- **CSS Strategy**: Components will ship as uncompiled TypeScript/JSX to allow the host application's Tailwind to scan and theme them (Zero CSS runtime).
- **Git Strategy**: Keep as a monorepo folder to maintain atomic commits between API and UI.

---

## Progress Checklist
- [x] Phase 1: API Layer [100%]
- [x] Phase 2: UI Toolkit [100%]
- [x] Phase 3: Integration [100%]

