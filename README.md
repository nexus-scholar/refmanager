# Shabakah Reference Manager — Package Blueprint

> **Laravel package** for managing bibliographic references with full import/export support for Zotero, EndNote, and Mendeley.

---

## Table of Contents

1. [Overview](#overview)
2. [Package Identity](#package-identity)
3. [Feature Scope](#feature-scope)
4. [Architecture](#architecture)
5. [Directory Structure](#directory-structure)
6. [Data Model](#data-model)
7. [Format Support Matrix](#format-support-matrix)
8. [Quick Start](#quick-start)
9. [API Reference](#api-reference)
10. [Extension Guide](#extension-guide)

---

## Overview

`shabakah/refmanager` is a Laravel package that provides a complete reference management layer — import, export, deduplication, and collection management — designed to integrate with the existing Shabakah systematic literature review ecosystem.

It builds on top of the `Document` + `Author` Eloquent models already present in your system and wraps them with a clean adapter-based I/O layer that speaks the native file formats of every major reference manager.

---

## Package Identity

| Property     | Value                              |
|--------------|------------------------------------|
| Package name | `shabakah/refmanager`              |
| Namespace    | `Shabakah\RefManager`              |
| Laravel      | 11.x / 12.x                        |
| PHP          | 8.2+                               |
| License      | MIT                                |

---

## Feature Scope

### Core Features
- **Import** RIS, BibTeX, CSL-JSON, and EndNote XML files into `Document` + `Author` models
- **Export** document collections to all four formats with a streaming response helper
- **Deduplication** by DOI fingerprint and title+year fuzzy fallback
- **Collection management** — named reference lists, tagging, notes
- **Filtered export** — export only screened/included documents post-SLR
- **Batch operations** — merge duplicates, bulk tag, bulk export

### Out of Scope (v1)
- Citation style rendering (CSL processing) — use `seboettg/citeproc-php` separately
- Zotero/Mendeley cloud API sync (planned v2)
- PDF attachment management

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     User / Controller                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
          ┌────────────▼────────────┐
          │     FormatManager       │  ← resolves format by ext/MIME
          └────────┬────────────────┘
         ┌─────────▼──────────┐
         │  ReferenceFormat   │  ← interface (parse / serialize)
         └────────────────────┘
              │         │
   ┌──────────▼──┐  ┌───▼──────────┐
   │  Importer   │  │   Exporter   │
   └──────┬──────┘  └──────┬───────┘
          │                │
   ┌──────▼────────────────▼───────┐
   │       Document Collection     │  ← Eloquent models
   │   Document ↔ Author (pivot)   │
   └───────────────────────────────┘
```

### Design Principles

1. **Adapter pattern** — every format is a swappable implementation of `ReferenceFormat`
2. **No format lock-in** — the canonical internal representation is a plain PHP array (CSL-JSON schema)
3. **Non-destructive imports** — always return hydrated models, never auto-save; the caller decides persistence
4. **Event-driven** — every import/export fires events so host apps can hook in (logging, notifications, post-processing)
5. **Streaming exports** — large collections stream directly to response without buffering in memory

