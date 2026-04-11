# PDF URL Implementation Plan

## Goal

Add a first-class `pdf_url` field to RefManager documents so:
- OpenAlex-powered imports can persist full-text PDF links when available.
- Users can provide or edit PDF links manually.
- API and UI can expose/open PDF links consistently.

---

## Scope

### In scope
- Database column (`documents.pdf_url`)
- Model + API resource support
- OpenAlex search-import mapping
- Manual update support (`PATCH /documents/{id}`)
- Optional canonical import mapping (CSL/other format paths)
- Tests for persistence and endpoint behavior

### Out of scope (for first pass)
- PDF file upload/storage pipeline (binary file hosting)
- Fulltext download/caching service
- Export format changes (RIS/BibTeX embedding of PDF URL)

---

## Phased Plan

## Phase 1: Persistence Foundation

- Add migration: `add_pdf_url_to_documents_table`.
  - nullable string/text column, indexed only if needed after profiling.
- Update `Nexus\RefManager\Models\Document`:
  - add `pdf_url` to `$fillable`.

### Acceptance
- Migration runs cleanly up/down.
- `Document::create([... 'pdf_url' => ...])` persists correctly.

---

## Phase 2: API Surface

- Update `DocumentResource` to include `pdf_url`.
- Update `DocumentsController@update` validation:
  - `pdf_url` => `sometimes|nullable|url`.

### Acceptance
- `GET /api/refmanager/documents` includes `pdf_url`.
- `GET /api/refmanager/documents/{id}` includes `pdf_url`.
- `PATCH /api/refmanager/documents/{id}` accepts/updates/clears `pdf_url`.

---

## Phase 3: OpenAlex Mapping (Nexus Search Import)

- In `NexusSearchImportController`, extract PDF link from OpenAlex payload if available.
- Persist mapped value into `pdf_url` during `updateOrCreate`.
- Keep null-safe behavior for records without a PDF URL.

### Candidate mapping strategy
- Prefer a direct OA PDF link if present.
- Fall back to known OpenAlex location fields when direct key is missing.

### Acceptance
- `POST /api/refmanager/nexus/search-import` stores `pdf_url` when provided by OpenAlex.
- Records without a PDF URL keep `pdf_url = null`.

---

## Phase 4: Manual/Import Path Alignment

- Ensure `ReferenceImporter` can hydrate `pdf_url` from canonical payload if provided.
- Add/adjust parser mapping where possible (CSL first, then format-specific fields if available).

### Acceptance
- File/string import with canonical `pdf_url` persists value.

---

## Phase 5: Dedup/Merge Policy

- Keep dedup matching keys unchanged (DOI/PMID/title-year-author).
- During merge resolution:
  - if primary `pdf_url` empty and candidate has one, promote candidate value.

### Acceptance
- Dedup precision unchanged.
- Merge retains best available `pdf_url`.

---

## Testing Plan

- Integration: document API returns and updates `pdf_url`.
- Integration: nexus search-import persists `pdf_url`.
- Unit/integration: importer hydrates `pdf_url` from canonical input.
- Integration: dedup resolve merge keeps/promotes `pdf_url` correctly.

---

## Open Questions for Discussion

1. **Validation strictness**
   - Keep `url` rule only, or restrict to `http/https` schemes explicitly?

2. **Column type**
   - `string` (255) vs `text` for long signed URLs?

3. **UI behavior**
   - Show `Open PDF` action only when `pdf_url` exists?
   - Add an inline PDF URL editor in metadata panel now or later?

4. **Canonical key naming**
   - Enforce one key (`pdf_url`) or support aliases (`pdfUrl`, `PDF_URL`) at ingest boundary?

---

## Proposed Implementation Order

1. Migration + model + resource + `PATCH` validation
2. OpenAlex mapping in nexus search-import
3. Tests for API + search-import
4. Canonical importer mapping + tests
5. Merge enrichment for `pdf_url`

