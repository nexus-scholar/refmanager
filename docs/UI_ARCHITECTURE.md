# RefManager UI/UX Architecture

This document outlines the architectural strategy for building a modern React/Shadcn UI for the `refmanager` Laravel package.

## The Strategy: Headless API + UI Toolkit (The Enterprise Approach)

To ensure maximum flexibility, composability, and a seamless developer experience, `refmanager` adopts a **Headless API + NPM UI Toolkit** architecture.

This separates the backend logic (PHP/Laravel) from the frontend presentation (React/Tailwind), allowing developers to build complex Systematic Literature Review (SLR) dashboards, peer-review interfaces, or simple citation pickers seamlessly into their own applications.

exus/refmanager)
### Part 1: The PHP Package (`nexus/refmanager`)
The Laravel package is strictly headless. It provides the database schema, Eloquent models, Actions, and a robust JSON API, but zero HTML or CSS.

**Key Responsibilities:**
1. **API Routes:** Expose RESTful endpoints for the React components to consume.
    * GET /api/refmanager/documents (List, filter, paginate, sort)
    * POST /api/refmanager/import (Upload RIS/BibTeX)
    * POST /api/refmanager/deduplicate (Run the tiered duplicate detector)
    * PUT /api/refmanager/documents/{id}/status (Update PRISMA status)
2. **Configuration:** Allow the host application to prefix these routes or apply middleware (e.g., `auth:sanctum`).

    ```php
    // config/refmanager.php
    'api' => [
        'prefix' => 'api/refmanager',
        'middleware' => ['web', 'auth'],
    ],
    ```
3. **Data Contracts:** Ensure every endpoint returns clean, predictable JSON resources (e.g., DocumentResource).

### Part 2: The NPM Package (@nexus/refmanager-ui)
This is a standalone NPM package containing modular React components built with Shadcn UI and Tailwind CSS.

**Key Responsibilities:**
1. **The Component Library:** Provide modular, self-contained components.
    * <DocumentTable />: A data table with sorting, filtering, and pagination.
    * <ImportDropzone />: A drag-and-drop area for RIS/BibTeX files.
    * <DeduplicationReview />: A specialized UI for reviewing Tier 3 fuzzy matches.
    * <PrismaFlowchart />: A visual component showing the inclusion/exclusion funnel.
2. **API Integration (Hooks):** Provide custom React hooks (React Query) to interact with the PHP API, with Zod runtime validation.

    ```ts
    // Example: src/hooks/use-documents.ts
    const { data, isLoading, error } = useQuery({
      queryKey: ['documents', filters],
      queryFn: async () => paginatedDocumentsSchema.parse(await fetchJson('/api/refmanager/documents')),
    })
    ```
3. **Styling (Tailwind Integration):**
    Because the components use Tailwind CSS, the host application's Tailwind compiler must scan the package's files to generate the necessary CSS classes. This ensures the components perfectly inherit the host app's theme (colors, fonts, border-radius) without CSS conflicts.

---

## The Developer Experience (DX)

Integrating the Reference Manager into a host Laravel/Inertia/React app is designed to be frictionless.

### 1. Installation
The developer installs both the backend engine and the frontend toolkit:

```bash
# Install the backend engine
composer require nexus/refmanager
php artisan refmanager:install

# Install the frontend toolkit
npm install @nexus/refmanager-ui
```

### 2. Tailwind Configuration
The developer adds the UI package to their `tailwind.config.js`/`tailwind.config.ts` so the compiler generates the required utility classes:

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

### 3. Usage
The developer can compose a custom dashboard instantly by importing the components:

```tsx
import { DocumentTable, ImportDropzone, RefManagerProvider } from '@nexus/refmanager-ui';
import AppLayout from '@/Layouts/AppLayout';

export default function SLRWorkspace() {
    return (
        <AppLayout>
            <div className="max-w-7xl mx-auto py-10">
                <h1 className="text-2xl font-bold mb-6">Literature Review Workspace</h1>
                
                {/* Provider sets global config like the API base URL */}
                <RefManagerProvider apiBaseUrl="/api/refmanager">
                    <div className="grid grid-cols-3 gap-6">
                        <div className="col-span-1">
                            <ImportDropzone onSuccess={() => alert('Imported successfully!')} />
                        </div>
                        <div className="col-span-2">
                            <DocumentTable 
                                columns={['title', 'authors', 'year', 'status']}
                                defaultSort="-year"
                            />
                        </div>
                    </div>
                </RefManagerProvider>
            </div>
        </AppLayout>
    );
}
```

## Why this approach wins:
* **No CSS Conflicts:** Shadcn components inherit the host app's CSS variables and design system.
* **Extreme Composability:** Developers aren't locked into a rigid dashboard layout.
* **Independent Versioning:** Backend API changes and frontend UI tweaks can be versioned and released independently via Composer and NPM respectively.
