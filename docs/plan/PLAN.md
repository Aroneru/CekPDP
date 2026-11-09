# CekPDP: plan for review

**Status:** awaiting your approval. No application code has been written yet. · **Date:** 5 November 2026

Companion documents:

- [`docs/legal/SOURCES.md`](../legal/SOURCES.md): legal sources, verification log, status of the law, court decisions and implementing regulations.
- [`docs/plan/ruleset-draft.yaml`](ruleset-draft.yaml): the draft ruleset (31 rules, 46 verified provisions).

The decisions I need from you are in [§15](#15-decisions-needed). Each one has a recommended default.

---

## 1. Research findings that shape the design

| Finding | Consequence for CekPDP |
| --- | --- |
| UU 27/2022 has been in force since 17 Oct 2022. Its 2-year transition (Pasal 74) ended 17 Oct 2024. The official copy is a **scanned** PDF whose OCR layer has errors (mostly dropped words) in at least 10 of the provisions I checked. | Every quoted provision in the ruleset was verified against the page images, not the OCR text. |
| Only one Constitutional Court ruling changes the text: **151/PUU-XXII/2024** (30 Jul 2025). It makes the DPO-appointment criteria in Pasal 53(1) alternative ("dan/atau"). Other petitions were rejected or not accepted. | Reflected in rule PDP-DPO-01 and in the provision catalogue. |
| **PP 33/2026** (the implementing regulation) is reported as promulgated in July 2026 but **not in force until about January 2027**, and no official government copy could be found. | Kept out of the v1.0 checklist. The UI will show an "upcoming regulation" notice, and a separate v1.1 module is planned once the official copy is verified. |
| The PDP institution (*lembaga*) hasn't been formed. Its Perpres is still a draft. | Rules that mention the institution say so. The tool doesn't point users to a complaint channel that doesn't exist yet. |
| Pasal 21(1) lists exactly what must be conveyed, but only **for consent-based processing**. Pasal 5 (right to information) and Pasal 16(2)(f)/27 (transparency principles) apply more generally. | A context question asks whether consent is relied on, and the requirement type adjusts automatically (see §3.3). |
| The law has no official English translation. | English text is labelled as an unofficial translation everywhere it appears. |
| Pasal.id offers an MCP server and a metadata API. Its terms limit the API to metadata, frame MCP as an assistant reading on a user's behalf, and ban bulk extraction or building datasets from its content. | Used as a maintenance aid and for reader links only. It is never used in the analysis path and never as a rule's legal basis (see §9). |

## 2. Scope

**CekPDP does:** compare a privacy policy's text with a versioned checklist of transparency and disclosure items under UU 27/2022. For each item it reports a status, a reason, verified quotes from the policy, the legal reference, a next step, and (for gaps) draft wording.

**CekPDP does not:** assess whether an organisation actually complies, review sector regulations (finance, health, telco), apply PP 33/2026, or give legal advice. Every page and report shows this notice:

> This tool gives an automated initial review and is not legal advice. Consult a qualified professional before making compliance decisions.
>
> *Alat ini memberikan tinjauan awal otomatis dan bukan nasihat hukum. Konsultasikan dengan profesional yang berkualifikasi sebelum mengambil keputusan terkait kepatuhan.*

## 3. Ruleset design

### 3.1 Requirement types

A rule is a **legal requirement** only if a verified provision expressly does one of these:

1. obliges the controller to inform or notify data subjects of the item (Pasal 16(2)(f), 21(1), 21(2), 27 with its Elucidation);
2. gives data subjects a right to information about the item (Pasal 5); or
3. grants a data-subject right (Pasal 6–13), which Pasal 21(1)(g) requires the controller to inform them about.

Everything else is a **best practice**, even when it relates to a real obligation. For example, the law requires breach notification when an incident occurs (Pasal 46), but not a promise in the privacy policy. Best-practice rules cite their provisions as "related", never as the basis.

Legal rules also carry a **legal mapping**:

- `direct`: the provision's words cover the item.
- `interpretive`: the link needs a reasoning step. Interpretive rules start as `needs_review` and stay **out of the active checklist** until you approve them. There are two: PDP-ID-02 and PDP-DATA-04.

### 3.2 Result statuses

| Status | Meaning | Shown with |
| --- | --- | --- |
| Addressed | The policy clearly gives the information the rule asks for. | ✓ icon + label |
| Partially addressed | The topic is covered but key elements are missing or vague, or a statement appears inconsistent with the law (for example, a 30-day response time where the law says 3×24 hours). | ◐ icon + label |
| Not found | No relevant statement in the policy. | ○ icon + label |
| Needs human review | Ambiguous or conflicting text, a quote that failed verification, or an evaluation error for that rule. | ? icon + label |

Rules that don't apply because of the context answers are listed separately as "not applicable", with the reason.

### 3.3 Context questions

These are optional and default to "Not sure". "Not sure" is treated like "Yes" for applicability, which is the conservative choice.

| Context | Affects |
| --- | --- |
| Relies on consent? | If "No", rules based only on Pasal 21 become best practices, and PDP-RIGHT-04 and PDP-CONSENT-01 become not applicable. |
| Specific (sensitive) data, per Pasal 4(2)? | PDP-DATA-03 |
| Children's data? | PDP-CHILD-01, and PDP-DATA-03 (children's data is specific data under Pasal 4(2)(e)) |
| Transfers outside Indonesia? | PDP-XFER-01, PDP-XFER-02 |
| Solely automated decisions with legal or significant effects? | PDP-RIGHT-05 |

If the policy text clearly contradicts an answer (for example, the user says "no transfers" but the policy mentions servers in Singapore), the results show a hint. The answers themselves are never changed automatically.

### 3.4 Draft rule list (v1.0.0-draft.1)

| ID | Item | Type | Applies | Basis (verified) |
| --- | --- | --- | --- | --- |
| PDP-ID-01 | Controller identity | Legal · direct | always | Pasal 5 |
| PDP-ID-02 | Contact point and how to submit requests | Legal · **interpretive** | always | Pasal 5 ("akuntabilitas"); Pasal 14 related |
| PDP-DATA-01 | Personal data collected | Legal · direct | always | Pasal 21(1)(c),(e) + Elucidation; Pasal 27 + Elucidation |
| PDP-DATA-02 | Why each type of data is needed (relevance) | Legal · direct † | always | Pasal 21(1)(c) |
| PDP-DATA-03 | Specific (sensitive) personal data | Legal · direct | specific data / children | Elucidation of Pasal 21(1)(e); Pasal 27 Elucidation |
| PDP-DATA-04 | Sources and methods of collection | Legal · **interpretive** | always | Pasal 16(1)(a) + 16(2)(f) |
| PDP-PURP-01 | Purposes of processing | Legal · direct | always | Pasal 5; 16(2)(f); 21(1)(b) |
| PDP-BASIS-01 | Legal basis for processing | Legal · direct | always | Pasal 5; 21(1)(a); Pasal 20 related |
| PDP-RET-01 | Retention period | Legal · direct † | always | Pasal 21(1)(d) |
| PDP-RET-02 | Processing period | Legal · direct † | always | Pasal 21(1)(f) + Elucidation |
| PDP-SHARE-01 | Disclosure and transfer to other parties | Legal · direct | always | Pasal 16(1)(e) + 16(2)(f) |
| PDP-XFER-01 | Transfers outside Indonesia | Legal · direct | transfers | Pasal 16(1)(e) + 16(2)(f); Pasal 56 related |
| PDP-RIGHT-01 … 08 | Rights: correct, access/copy, end/delete/destroy, withdraw consent, object to automated decisions, delay/restrict, sue for compensation, portability | Legal · direct † | always (04: consent; 05: automated decisions) | Pasal 6–13 + Pasal 21(1)(g) |
| PDP-CHG-01 | Notice **before** changes take effect | Legal · direct † | always | Pasal 21(2) |
| PDP-XFER-02 | Safeguards for overseas transfers | Best practice | transfers | Pasal 56 related |
| PDP-RIGHT-09 | Response times not exceeding 3×24 h | Best practice | always | Pasal 30(1), 32(2), 40(2), 41(1) related |
| PDP-CONSENT-01 | How explicit consent is obtained (flags "by using you agree") | Best practice | consent | Pasal 20(2)(a), 22, 23, 24 related |
| PDP-SEC-01 | Security measures | Best practice | always | Pasal 16(2)(e), 35, 39(1) related |
| PDP-BREACH-01 | Breach notification commitment | Best practice | always | Pasal 46 + Elucidation related |
| PDP-CHILD-01 | Parent or guardian consent | Best practice | children | Pasal 25 related |
| PDP-DPO-01 | DPO contact | Best practice | always | Pasal 53(1) (as modified by MK 151/PUU-XXII/2024), 54(1)(d) related |
| PDP-CORP-01 | Notice of data transfer in mergers and similar | Best practice | always | Pasal 48 related |
| PDP-META-01 | Effective or last-updated date | Best practice | always | Pasal 21(2) related |
| PDP-LANG-01 | Available in Bahasa Indonesia | Best practice | always | Pasal 27 Elucidation; Pasal 22(4)(c) + Elucidation related |

† Becomes a best practice if the organisation says it doesn't rely on consent.

**Considered but left out of v1:** a standalone cookie rule (covered by PDP-DATA-04), automated-decision disclosure as its own rule, persons with disabilities (Pasal 26), joint controllers (Pasal 18), CCTV signage (Pasal 17), complaints to the PDP institution (it doesn't exist yet), and "clear language" scoring (too subjective to evidence with quotes).

### 3.5 File format and versioning

- `resources/rulesets/uu-pdp-27-2022/1.0.0.yaml` contains a provision catalogue and the rules. Every rule has the fields you listed (ID, bilingual title and explanation, article references, source URL, verification date, requirement type, applicable contexts, what to look for, example wording), plus criteria per status, a next step, and mock-provider keyword signals.
- A JSON Schema (`resources/rulesets/ruleset.schema.json`) plus `php artisan cekpdp:ruleset:validate` check structure, unique IDs, resolvable references, the legal-versus-basis consistency, and verification dates.
- Semantic versioning. MAJOR: a rule is removed or its meaning changes. MINOR: a rule is added. PATCH: wording fixes. Old versions are kept so past analyses can be shown with the ruleset they used. The UI and reports show the version and `last_reviewed` date.

## 4. Architecture

### 4.1 Components

These map to the separation you asked for:

| Area | Location | Responsibility |
| --- | --- | --- |
| Text extraction and cleaning | `app/Domain/Extraction` | PDF (smalot/pdfparser, pure PHP), DOCX (own ZipArchive + DOM reader that keeps heading styles and is safe against zip bombs and XXE), paste. Unicode normalisation, line-break repair, structure kept. Detects scanned or encrypted PDFs. |
| Document structure | `app/Domain/Document` | Language detection (deterministic id/en stopword model: `id`, `en`, or `mixed`), sectioning with stable IDs (`S01`, `S02`, … in document order), chunking. |
| Rules | `app/Domain/Rules` | Loading, schema validation, applicability and type overrides for a context. |
| AI provider and response validation | `app/Domain/Ai` | `PolicyEvaluator` interface, `ClaudeEvaluator`, `MockEvaluator`, prompt builder, JSON Schema validation, retry once. |
| Evidence verification | `app/Domain/Evidence` | Verbatim quote matching with an offset map back to the displayed text, and downgrades. |
| Scoring | `app/Domain/Scoring` | Deterministic Policy Coverage calculation and its explanation. |
| Reports | `app/Domain/Reporting` | Report model → Markdown and PDF, and comparisons. |
| UI | `app/Livewire`, `resources/views` | Livewire 4 class-based components for progress, results and comparison. The input form is a plain Blade form (see §8). |

### 4.2 Analysis pipeline

```text
Browser ──POST /analyses (CSRF, rate-limited)──► AnalysisController
   │  text or file, context answers, AI-disclosure acknowledgement
   │  file → extracted in-request from PHP's temp upload (never written to app storage)
   ▼
analyses row (status=queued, text encrypted at rest) ──► AnalyzePolicyJob(analysisId)   [queue: database]
   1  Extract & clean ........ TextCleaner                       progress 10%
   2  Structure .............. LanguageDetector → Sectionizer      progress 20%
   3  Evaluate ............... ApplicabilityResolver → rule batches → PolicyEvaluator
                               (Claude or mock) → JSON Schema check → retry once   20–85%
   4  Verify evidence ........ EvidenceVerifier → downgrade failures to Needs Human Review
   5  Score .................. CoverageScorer (deterministic)       progress 95%
   6  Persist ................ rule_results; set expires_at       progress 100%
   ▼
Livewire progress view (wire:poll) → results view → exports (streamed, not stored)
Scheduler: cekpdp:purge-expired every 5 minutes
```

The job receives only the analysis ID, so policy text never appears in queue payloads or `failed_jobs`. If the AI output is still invalid after one retry, the analysis fails with a clear, retryable error. No partial result is shown as if it were complete.

### 4.3 Data model (MySQL in production, SQLite locally and in tests)

- **`analyses`**: ULID, owner token hash (ties the analysis to the browser session; there are no accounts), status and stage, progress, input kind and metadata (characters, words, pages, language, sections, chunks), context answers, ruleset ID and version, provider and **actual** model used, policy text and sections (encrypted, nullable), SHA-256 of the text, injection flags, score JSON (all inputs to the formula), error code, timestamps, `expires_at`, `purged_at`.
- **`rule_results`**: analysis FK (cascade delete), rule ID, effective requirement type, applicability and reason, final status, status as returned by the model before verification, bilingual reasons (encrypted), evidence (encrypted JSON: section ID, quote, offsets, verified flag).
- Laravel defaults: `jobs`, `failed_jobs`, `cache`, `sessions`.

## 5. AI layer

### 5.1 Provider interface

```php
interface PolicyEvaluator
{
    /** Evaluates one batch of rules against the sectioned policy. Returns raw, unvalidated output. */
    public function evaluate(EvaluationRequest $request): ProviderResponse;

    public function name(): string;          // 'claude' | 'mock'
    public function maxInputTokens(): int;   // drives chunking
}
```

`EvaluationService` wraps any provider with schema validation, the single retry, merging of batches and chunks, and error mapping. Providers stay thin and swappable.

### 5.2 Claude provider

- Uses the official **`anthropic-ai/sdk`** for PHP (currently v1.5.0). Key and model come from `ANTHROPIC_API_KEY` and `ANTHROPIC_MODEL`. The model defaults to `claude-opus-5-5` and can be changed by setting the variable.
- **Structured outputs** (`outputConfig.format` with a JSON Schema). The same schema file is used for local validation, with extra local-only checks: every requested rule ID appears exactly once, quotes are length-limited, and "Not found" has no evidence.
- Opus 5.5 always uses adaptive thinking. Effort is configurable (`ANTHROPIC_EFFORT`, default `high` because accuracy matters most here). Sampling parameters and assistant prefill are not used because the current API rejects them.
- **Prompt caching:** the system prompt and the sectioned policy come first, with `cacheControl`, so each rule batch (default 8 rules per call) reuses the cached policy.
- **Refusal fallback:** opted in by default per Anthropic's guidance for this model (server-side fallback beta). The analysis records which model actually answered. The exact PHP binding will be confirmed against the SDK in Milestone 3.
- Errors: typed SDK exceptions are mapped to friendly, non-leaky messages. Rate-limit and overload errors use the SDK's built-in retries. Logs record only the status code and request ID.
- **Rough cost estimate:** about US$0.30–0.60 per analysis at `high` effort. I'll measure the real figure in Milestone 3 before quoting it anywhere.

### 5.3 Mock provider

A deterministic evaluator driven by each rule's bilingual keyword signals. It returns real verbatim sentences as evidence, so the full app, every test and the evaluation command work without an API key. It also gives a baseline that Claude's accuracy is compared against. Test switches can simulate invalid JSON, timeouts and partial output to exercise the retry and error paths.

### 5.4 Evidence verification

- The model must quote contiguous text from the policy, with no ellipses.
- Both sides are normalised (NFKC, quote and dash variants, whitespace). Matching is otherwise exact and case-sensitive.
- The quote is looked up in the cited section first, then in the whole document (the section ID is corrected and noted).
- An offset map ties each match back to the displayed text for highlighting.
- Any unverifiable quote, or an Addressed or Partially addressed result with no quote, becomes **Needs human review**. The model's original status and the failed quote stay visible for transparency.

### 5.5 Treating policy text as untrusted

1. The system prompt separates instructions from data. The policy is supplied as delimited, section-tagged data, with an explicit statement that it may contain instructions and that they must not be followed.
2. The model has no tools. Its output is limited by the schema to enumerated statuses for the requested rule IDs.
3. Everything that matters afterwards is deterministic: quotes must exist, scoring and reports are code, and the model can't change the ruleset, the score or the UI.
4. A deterministic detector flags instruction-like text ("ignore previous instructions", "abaikan instruksi sebelumnya", …). It shows a warning banner and includes the flag in the report.
5. All output is escaped (Blade `{{ }}`, escaped Markdown export, dompdf with PHP and remote loading disabled).
6. A prompt-injection fixture in the evaluation set measures how well this holds.

### 5.6 Long documents

- Hard limit `CEKPDP_MAX_INPUT_CHARS` (default 300,000 characters, about 75k tokens). Anything larger gets a clear error with the actual size. Text is never silently truncated.
- Above the provider's chunk budget, the document is split on section boundaries (paragraph boundaries for very long sections). Every chunk is evaluated against every rule. Merging per rule is conservative: Addressed > Partially > Needs human review > Not found, with evidence combined. Results say how many parts the document was analysed in.
- With Claude's 1M context, chunking rarely triggers at the default limit, so tests run it with a small chunk budget.

## 6. Scoring: "Policy Coverage"

Only **applicable legal-requirement rules** count. Best practices are reported separately as "X of Y addressed".

```text
Policy Coverage = (Addressed + 0.5 × Partially addressed) ÷ (Addressed + Partially addressed + Not found)
```

- Every rule has equal weight. No hidden weights.
- Needs-human-review results are excluded from the formula, and if they make up **more than 20%** of applicable legal rules, **no score is shown**. The UI says why and lists the items to review.
- The UI always shows the arithmetic with the real numbers, for example `(12 + 0.5 × 3) ÷ 17 = 79%`, plus the ruleset version. There are no grades, colours-as-verdicts, or "compliant" wording, and the label always says this is coverage of disclosures, not compliance.

## 7. UI and UX

- **Pages:**
  - Home: paste box, PDF/DOCX upload, sample policies, optional context questions, and the AI-provider disclosure checkbox.
  - Progress: staged, with an `aria-live` region.
  - Results: summary and score card, filters by status and type, an expandable card per rule, a side-by-side view with highlighted evidence, and draft wording labelled "Draft — needs legal review".
  - Compare two versions.
  - Ruleset browser: every rule with its verified legal text.
  - Methodology, the app's own privacy policy, and a language switch (Bahasa Indonesia / English).
- **Accessibility (target WCAG 2.2 AA):**
  - Statuses always pair an icon and a text label with colour.
  - Cards are real buttons with `aria-expanded`. Filters use `aria-pressed`.
  - "Show in policy" moves focus to the highlighted passage and "Back to result" returns it.
  - Visible focus styles, `prefers-reduced-motion` respected, and contrast checked.
- **Mobile:** the side-by-side view becomes two tabs (Results | Policy). There's no horizontal scrolling and touch targets are at least 44px.
- **Look:** a calm, document-like legal-tech style with neutral slate and one deep accent colour. Fonts are **self-hosted**, with no Google Fonts or other third-party requests (the app practises what it checks). Tailwind CSS v4 handles styling.

## 8. Privacy and security

- **Storage:** uploaded files are never written to app storage. They are parsed from PHP's temporary upload during the request and discarded when the request ends. Extracted text is encrypted at rest and deleted automatically (default **30 minutes after completion**, see decision 4), with a "Delete now" button. Results are deleted at the same time. Exports are streamed with `Cache-Control: no-store`.
- **No input before consent.** The input form is a plain form submission rather than a Livewire upload. Livewire uploads files to a temporary folder as soon as a file is picked, which would be before the user has acknowledged the notice.
- **Before analysis**, the user must acknowledge that the text is sent to the configured AI provider (Anthropic) and that they shouldn't submit confidential information. In mock mode the notice says no external AI is used.
- **Logs:** policy text and keys never appear.
  - Sensitive parameters use `#[\SensitiveParameter]`, and policy text is wrapped in a value object whose debug output is redacted.
  - `dontFlash` covers the text field, and a Monolog redaction processor is added.
  - SDK errors are logged without request or response bodies.
  - A test asserts that log output contains neither fixture text nor a fake key.
- **Abuse controls:** per-IP rate limits (default 10 analyses per hour), a global daily cap on analyses when Claude is the provider (protects API spend), CSRF on all forms, and upload checks (extension, MIME type, magic bytes, size, page count, DOCX entry count and compression ratio, encrypted PDFs).
- **Headers:** strict security headers (CSP, `frame-ancestors 'none'`, `nosniff`, Referrer-Policy, Permissions-Policy, HSTS in production). Secure, `HttpOnly`, `SameSite=Lax` session cookies.
- **CekPDP's own privacy policy** is bilingual. It covers controller identity (placeholders for you to fill in), data processed, purposes, legal bases, Anthropic as a recipient and the overseas transfer, the 30-minute retention, rights and contact, prior notice of changes, and an effective date. **It is run through CekPDP itself as an evaluation fixture.**

## 9. Pasal.id integration (your request)

| What | How | Why this shape |
| --- | --- | --- |
| **Claude Code MCP connection** (done now) | `.mcp.json` registers `pasal-id` → `https://mcp.pasal.id/mcp`. You approve it on the next Claude Code start and log in with your free Pasal.id account via `/mcp`. | Gives maintainers `search_legal`, `read_law`, `get_law_context` and `search_court_decisions` for ruleset reviews. I couldn't use it in this session because it needs your browser login. |
| **Reader links** in the UI and reports | Each legal reference shows "Official text (JDIH BPK, PDF p. N)" first, then "Read on Pasal.id (unofficial)" using the verified URL pattern `/peraturan/uu/uu-no-27-tahun-2022/pasal-N`. | Convenient reading with no runtime API calls. Its terms don't address linking. |
| **Source monitor command** | `php artisan cekpdp:sources:check` (optional, needs `PASAL_ID_API_TOKEN`). Calls the Discovery API to check UU 27/2022's status and flags, and to search for new PDP-related PP or Perpres. Writes a "needs human review" report and **never edits the ruleset**. Can run weekly in CI. | Low-volume metadata checks are within the API's stated scope. It helps catch PP 33/2026, the Perpres and new amendments early. |
| **Ruleset review procedure** | Documented steps for a maintainer working with Claude Code and the MCP: cross-check each provision's text, amendments and MK decisions. | Any disagreement is settled against the **official page image**, never by trusting Pasal.id. |
| **Deliberately not done** | No policy text is ever sent to Pasal.id. Pasal.id isn't called during analyses. Its text isn't copied into the ruleset. The app doesn't call the MCP itself. | Privacy, the "evaluate using only the policy text" requirement, and its terms (no bulk extraction or competing datasets; MCP is for assistants acting for a user). |

## 10. Quality: tests, static checks, evaluation

- **Tooling:** Pest 5 (needs PHP 8.4; you have 8.4.27), Larastan (level 8 or higher), Pint, `declare(strict_types=1)` in all app code, and `npm run build` (Vite + Tailwind 4). After each milestone I run tests, static analysis, Pint and the build, and report anything I couldn't verify.
- **Unit tests** cover:
  - cleaning and extraction (PDF and DOCX fixtures generated in tests, including scanned, encrypted and zip-bomb cases);
  - language detection, sectioning and chunking;
  - ruleset schema, applicability and overrides;
  - schema validation and the retry path;
  - evidence matching edge cases (typographic quotes, whitespace, wrong section, fabricated quotes);
  - scoring, including withholding;
  - Markdown escaping.
- **Feature tests** cover the full mock-provider flow, upload errors, rate limiting, CSRF, ownership checks, purging, the "no text in logs" check, the Claude provider against a faked client, and the Livewire components.
- **Evaluation dataset:** **12 original fictional policies** (6 Indonesian, 5 English, 1 bilingual), plus CekPDP's own policy:
  - complete, average and minimal versions;
  - a health app (specific data), an edtech service for children, a SaaS with overseas transfers;
  - a GDPR-style policy that misses UU PDP specifics (3×24-hour limits, right to sue);
  - a "deemed consent / changes effective on posting" policy;
  - a vague marketing-heavy policy;
  - a long policy to exercise chunking;
  - a prompt-injection policy.

  Each fixture has context answers and an expected status for **every** rule, with an optional list of acceptable alternatives for genuinely borderline items. No real company text is used.
- **`php artisan cekpdp:evaluate --provider=mock|claude`** prints per-rule and overall metrics and writes JSON and Markdown to `evaluation/results/`:
  - status accuracy;
  - false positives (said found, expected not found);
  - false negatives;
  - Needs-human-review (abstention) rate;
  - evidence-verification failure rate.

  Claude runs cost money and ask for confirmation.

## 11. Ruleset update process

1. **Trigger:** the `sources:check` report, news of an amendment or MK decision, a quarterly review, and specifically PP 33/2026 taking effect.
2. Get the official copy (JDIH BPK, JDIH Setneg or peraturan.go.id) and record its URL and SHA-256.
3. Verify changed provisions against the **page images**. Update the provision catalogue (`text_id`, `text_en`, `pdf_page`, `verified_on`). Optionally cross-check with the Pasal.id MCP.
4. Edit the rules and bump the version (semver). Add a new file and keep the old one.
5. Update `CHANGELOG.md` and `SOURCES.md`. Run `cekpdp:ruleset:validate`, the tests and `cekpdp:evaluate`, adjusting expected results only when rule semantics changed.
6. The owner approves: set `reviewed_by` and `last_reviewed`, then switch the active version in config.

## 12. File structure

```text
CekPDP/
├── .mcp.json                         # pasal-id MCP for Claude Code (added now)
├── app/
│   ├── Console/Commands/             # Evaluate, PurgeExpiredAnalyses, RulesetValidate, SourcesCheck
│   ├── Domain/
│   │   ├── Extraction/               # TextExtractor contract, PdfExtractor, DocxExtractor, TextCleaner, ExtractionException
│   │   ├── Document/                 # LanguageDetector, Sectionizer, Section, PolicyDocument, Chunker
│   │   ├── Rules/                    # RulesetRepository, Ruleset, Rule, Provision, ContextProfile, ApplicabilityResolver
│   │   ├── Ai/                       # PolicyEvaluator contract, ClaudeEvaluator, MockEvaluator, PromptBuilder,
│   │   │                             #   ResponseValidator, EvaluationService, InjectionDetector
│   │   ├── Evidence/                 # EvidenceVerifier, MatchNormalizer, OffsetMap
│   │   ├── Scoring/                  # CoverageScorer, CoverageScore
│   │   ├── Reporting/                # ReportBuilder, MarkdownRenderer, PdfRenderer, ComparisonBuilder
│   │   ├── Evaluation/               # DatasetLoader, MetricsCalculator
│   │   └── Sources/                  # PasalIdClient (Discovery API), SourceCheckReport
│   ├── Enums/                        # RuleStatus, RequirementType, AnalysisStatus, AnalysisStage
│   ├── Http/                         # AnalysisController, ExportController, LocaleController; middleware: SecurityHeaders, SetLocale
│   ├── Jobs/AnalyzePolicyJob.php
│   ├── Livewire/                     # AnalysisProgress, AnalysisResults, CompareAnalyses
│   ├── Models/                       # Analysis, RuleResult
│   └── Support/                      # SensitiveText value object, log redaction processor
├── config/cekpdp.php
├── database/{migrations,factories}/
├── docs/
│   ├── legal/SOURCES.md
│   ├── plan/{PLAN.md, ruleset-draft.yaml}
│   └── architecture.md, ruleset-update-process.md   # Milestone 5
├── evaluation/
│   ├── fixtures/                     # *.md fictional policies
│   ├── expected/                     # *.yaml expected status per rule + context answers
│   └── results/                      # generated evaluation reports
├── lang/{id,en}/
├── resources/
│   ├── rulesets/{ruleset.schema.json, uu-pdp-27-2022/1.0.0.yaml, uu-pdp-27-2022/CHANGELOG.md}
│   ├── schemas/evaluation-response.schema.json
│   ├── css/app.css, js/app.js
│   └── views/{layouts, livewire, pages, reports}/
├── tests/{Unit,Feature}/
├── Dockerfile, docker-compose.yml    # Milestone 5
├── .env.example
└── README.md
```

## 13. Milestones

At the end of each one I run tests, Larastan, Pint and the build, fix what fails, and report what I couldn't verify. Then I stop for you to test.

| # | Deliverable | Done when |
| --- | --- | --- |
| **1** | Laravel 13 + Livewire 4 + Tailwind 4 scaffold (strict types, Pint, Larastan, Pest). Final ruleset 1.0.0 (your decisions applied, mock signals added) with JSON Schema, loader and `cekpdp:ruleset:validate`. Migrations, models and factories. 12+ fixtures with expected results. `.env.example`. | The ruleset validates. Every fixture has an expected status for every rule. Tests are green. |
| **2** | Paste input, context questions, disclosure acknowledgement, queue job, progress view, mock provider, cleaning, language detection, sectioning, scoring, a basic results page, purge scheduler, rate limits. | A pasted sample runs end to end offline with a correct, explained score. |
| **3** | Claude provider (SDK, structured outputs, caching, effort, fallback), schema validation and retry, friendly errors, evidence verification and downgrades, injection detector, chunking, log redaction tests. | Faked-client tests pass. One real smoke test if you provide a key, otherwise clearly reported as not run. |
| **4** | Full results UI (filters, cards, side-by-side highlighting, draft wording), PDF/DOCX upload and every friendly error, Markdown and PDF export, security headers. | Keyboard-only walkthrough works, mobile layout works, and the export escaping tests pass. |
| **5** | `cekpdp:evaluate` and results in the README, version comparison, complete id/en translations, privacy policy page, `cekpdp:sources:check`, Docker deployment (+ your chosen platform notes), README with screenshot placeholders. | Evaluation numbers are published honestly (mock, plus Claude if a key is available) and a fresh clone runs by following the README. |

## 14. Risks and limitations

- AI judgement can be wrong even when quotes verify (a real quote can be misread). The evaluation set measures this, and borderline cases go to Needs human review.
- The interpretive rules and the expected labels in the dataset are my legal reading. They need your review, and ideally a qualified lawyer's.
- PP 33/2026 may add or refine disclosure duties from about January 2027. v1.0 will say clearly that it isn't included.
- Scanned PDFs aren't supported (no OCR in v1). Users get a clear message and can paste the text instead.
- The analysis sends text to a third-party AI provider, which is disclosed before submission. Mock mode avoids this but is far less accurate.

## 15. Decisions needed

Reply with "go with the recommendations" or name any you want changed.

1. **Interpretive rules PDP-ID-02 (contact/requests) and PDP-DATA-04 (sources of data).**
   **Recommended:** activate both as legal requirements, shown with an "interpretation" label and the reasoning. Alternatives: make them best practices, or exclude them.
2. **Consent context.**
   **Recommended:** ask the question, and turn rules based only on Pasal 21 into best practices when the answer is "No". Alternative: always treat the policy as consent-based.
3. **Rights granularity.**
   **Recommended:** eight separate rules (clear, actionable gaps). When consent is relied on, rights then make up 7–8 of the 16–21 applicable legal rules (the totals depend on the context answers and decision 1), so they weigh heavily in the score. Alternative: one grouped rule.
4. **Text retention.**
   **Recommended:** encrypted at rest, auto-deleted 30 minutes after the analysis finishes, plus a "Delete now" button. This keeps the side-by-side view, exports and comparison working. Alternative: delete the moment analysis completes. The side-by-side view then works only until the page is refreshed, and comparison uses stored results only.
5. **PP 33/2026.**
   **Recommended:** leave it out of v1.0 with an "upcoming regulation" notice, and plan v1.1 once an official copy is verified. If you can get the official copy (JDIH Setneg / LN 2026 No. 88), I'll verify it.
6. **Deployment and repository.**
   **Recommended:** a portable Docker image (app + queue worker + scheduler, MySQL), with notes for one platform of your choice (Laravel Cloud, Railway, Fly.io or a VPS), and `git init` with a commit per milestone. Which platform?
7. **Default UI language.**
   **Recommended:** Bahasa Indonesia, with an English toggle. Alternative: English first.
8. **Pasal.id scope.**
   **Recommended:** all four integrations in §9. Do you want to create a Pasal.id API token for `cekpdp:sources:check`? It's optional, and the command is skipped without one.
9. **API key and budget.**
   Will you provide an Anthropic API key for Milestone 3 smoke tests and the Milestone 5 Claude evaluation? A full run of about 13 fixtures is estimated at roughly US$5–8, to be measured before I run it. I'll confirm before each paid run.
