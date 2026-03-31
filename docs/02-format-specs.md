# Format Specifications

## Format Support Matrix

| Format | Extension | MIME Type | Import | Export | Universal Compat |
|--------|-----------|-----------|--------|--------|-----------------|
| RIS | `.ris` | `application/x-research-info-systems` | ✅ | ✅ | Zotero, EndNote, Mendeley, JabRef |
| BibTeX | `.bib` | `application/x-bibtex` | ✅ | ✅ | Zotero, Mendeley, JabRef, LaTeX |
| CSL-JSON | `.json` | `application/vnd.citationstyles.csl+json` | ✅ | ✅ | Zotero, Pandoc, citeproc-js |
| EndNote XML | `.xml` | `application/xml` | ✅ | ✅ | EndNote (native), Zotero |

> **Recommendation**: Use **RIS** for cross-tool round-trips and **CSL-JSON** as the canonical internal format.

---

## RIS Format

### Specification Notes
- Line-based, each line: `TAG  - value` (2-space-dash-space pattern)
- Record separator: `ER  - ` (end of record)
- Multi-value fields (AU, KW) repeat the tag on new lines
- Abstract continuation: some exporters use `N2` instead of `AB` — handle both

### Full Tag Mapping

| RIS Tag | Meaning | Document Field |
|---------|---------|----------------|
| `TY` | Reference type | `type` (mapped) |
| `TI` / `T1` | Title | `title` |
| `AU` | Author (Last, First) | `authors` relation |
| `AB` / `N2` | Abstract | `abstract` |
| `DO` | DOI | `doi` |
| `UR` | URL | `url` |
| `PY` / `Y1` | Year | `year` |
| `JO` / `JF` / `T2` | Journal/Container | `journal` |
| `VL` | Volume | `volume` |
| `IS` | Issue | `issue` |
| `SP` | Start page | `pages` (combined) |
| `EP` | End page | `pages` (combined) |
| `PB` | Publisher | `publisher` |
| `CY` | City | `publisher_place` |
| `LA` | Language | `language` |
| `KW` | Keyword | `keywords` array |
| `SN` | ISSN/ISBN | `issn` |
| `ER` | End of record | — |

### RIS Type Mapping

| RIS `TY` | CSL Type |
|----------|----------|
| `JOUR` | `article-journal` |
| `CONF` | `paper-conference` |
| `BOOK` | `book` |
| `CHAP` | `chapter` |
| `THES` | `thesis` |
| `RPRT` | `report` |
| `ELEC` | `webpage` |
| `GEN` | `article` |

### Sample RIS Record
```
TY  - JOUR
TI  - Deep Learning for Plant Disease Detection
AU  - Smith, John A.
AU  - Doe, Jane B.
AB  - This study presents a convolutional neural network approach...
DO  - 10.1016/j.compag.2024.001
PY  - 2024
JO  - Computers and Electronics in Agriculture
VL  - 210
IS  - 3
SP  - 107890
EP  - 107901
KW  - plant disease
KW  - deep learning
KW  - CNN
ER  -
```

---

## BibTeX Format

### Parser Dependency
Use `RenanBr\BibTexParser` via Composer — handles nested braces, special chars, `@string` macros:
```
composer require renanbr/bibtex-parser
```

### Field Mapping

| BibTeX Key | CSL/Document Field | Notes |
|------------|--------------------|-------|
| `title` | `title` | Strip `{}` wrappers |
| `author` | `authors` | `Last, First and Last, First` format |
| `abstract` | `abstract` | |
| `doi` | `doi` | |
| `url` | `url` | |
| `year` | `year` | |
| `journal` | `journal` | For `@article` |
| `booktitle` | `container-title` | For `@inproceedings` |
| `volume` | `volume` | |
| `number` | `issue` | |
| `pages` | `pages` | `123--145` → `123-145` |
| `publisher` | `publisher` | |
| `address` | `publisher_place` | |
| `keywords` | `keywords` | Comma-separated string |
| `issn` | `issn` | |

### Author Parsing Rule
```
"Smith, John A. and Doe, Jane B." → split on " and "
"John A. Smith and Jane B. Doe"  → detect by comma absence
Each → ['family' => ..., 'given' => ...]
```

### Entry Type Mapping

| BibTeX Entry | CSL Type |
|--------------|----------|
| `@article` | `article-journal` |
| `@inproceedings` / `@conference` | `paper-conference` |
| `@book` | `book` |
| `@incollection` | `chapter` |
| `@phdthesis` / `@mastersthesis` | `thesis` |
| `@techreport` | `report` |
| `@misc` | `article` |

---

## CSL-JSON Format

### Notes
- This IS the canonical internal format — import is schema validation + array normalization
- Full spec: https://citeproc-js.readthedocs.io/en/latest/csl-json/markup.html
- Date format: `{"date-parts": [[2024, 3, 15]]}` — year, month, day (month/day optional)
- Export: `json_encode($collection->map->toCanonical()->values(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)`

### Validation Checklist for Import
- [ ] `type` field is present (default to `"article"` if missing)
- [ ] `author` entries have at least `family` OR `literal`
- [ ] `issued.date-parts` is a nested array `[[year, month?, day?]]`
- [ ] `DOI` is uppercase key per spec

---

## EndNote XML Format

### Structure
```xml
<?xml version="1.0" encoding="UTF-8"?>
<xml>
  <records>
    <record>
      <database name="My Library">My Library.enl</database>
      <ref-type name="Journal Article">17</ref-type>
      <contributors>
        <authors>
          <author>Smith, John A.</author>
          <author>Doe, Jane B.</author>
        </authors>
      </contributors>
      <titles>
        <title>Deep Learning for Plant Disease Detection</title>
        <secondary-title>Computers and Electronics in Agriculture</secondary-title>
      </titles>
      <periodical>
        <full-title>Computers and Electronics in Agriculture</full-title>
      </periodical>
      <pages>107890-107901</pages>
      <volume>210</volume>
      <number>3</number>
      <dates>
        <year>2024</year>
      </dates>
      <electronic-resource-num>10.1016/j.compag.2024.001</electronic-resource-num>
      <abstract>This study presents...</abstract>
      <keywords>
        <keyword>plant disease</keyword>
        <keyword>deep learning</keyword>
      </keywords>
    </record>
  </records>
</xml>
```

### Parsing Strategy
Use PHP's built-in `SimpleXMLElement`. No external dependency needed.
- `<ref-type name="...">` → map name string to CSL type
- `<titles><title>` → `title`
- `<titles><secondary-title>` → `container-title`
- `<contributors><authors><author>` → `authors` array
- `<electronic-resource-num>` → `doi`

