# How CekPDP works

CekPDP reads a privacy policy and reports, rule by rule, whether it discloses what Indonesia's Personal Data Protection Law (UU No. 27 Tahun 2022) asks a privacy notice to disclose. For each rule you get a status, a reason, quotes from the policy that back it up, the legal basis, and a suggested next step. It assesses only what the policy *says*, never whether the organisation actually complies.

This document describes the app as of Milestone 2. Analyses are made by the offline **mock** analyser (keyword matching, no AI); Claude takes over that step in Milestone 3, and nothing else in the flow changes.

## Contents

1. [The journey, as a visitor sees it](#1-the-journey-as-a-visitor-sees-it)
2. [What happens behind the scenes](#2-what-happens-behind-the-scenes)
3. [The analysis pipeline, step by step](#3-the-analysis-pipeline-step-by-step)
4. [The ruleset](#4-the-ruleset)
5. [The Policy Coverage score](#5-the-policy-coverage-score)
6. [How the offline mock decides](#6-how-the-offline-mock-decides)
7. [Privacy and security](#7-privacy-and-security)
8. [Where the code lives](#8-where-the-code-lives)
9. [What comes next](#9-what-comes-next)

---

## 1. The journey, as a visitor sees it

1. **Home page.** The visitor pastes a policy (or picks one of four fictional samples), answers up to five optional context questions, reads the notice about where the text goes and how long it is kept, ticks the box, and submits.
2. **Progress page.** The analysis runs in the background. The page shows six stages and a progress bar, and updates itself every two seconds.
3. **Results page.** A report with:
   - the **Policy Coverage** figure and the arithmetic behind it;
   - a strip with one square per rule, coloured and shaped by status (each square jumps to its rule);
   - every rule as a row that expands to show the reason, the quotes, what was looked for, the legal basis with links to the official text, and, for gaps, a next step and draft wording;
   - the rules that don't apply, and why;
   - the context answers used.
4. **Deletion.** After 30 minutes the policy text and results are deleted automatically. "Delete now" removes them at once.

The interface is in Bahasa Indonesia by default, with an English switch.

---

## 2. What happens behind the scenes

```mermaid
flowchart TD
    A[Browser: form POST] --> B[AnalysisController@store]
    B -->|validate, clean, rate-limit| C[(analyses table<br/>text encrypted)]
    B -->|queue the ID only| D[AnalyzePolicyJob]
    D --> E[AnalysisRunner]
    E --> F[AnalysisPipeline]
    F --> G[(rule_results table<br/>reasons and quotes encrypted)]
    H[Progress page<br/>Livewire, polls every 2 s] -.reads status.-> C
    I[Results page<br/>ReportBuilder] -.reads.-> G
    J[Scheduler, every minute<br/>cekpdp:purge-expired] -.deletes expired.-> C
```

### Submitting (`AnalysisController@store`)

1. **Validate.** Text is required, as is the notice checkbox. Context answers must be Yes, No or Not sure.
2. **Clean the text** (`TextCleaner`): normalise Unicode and line endings, remove invisible characters, collapse extra spaces, and rejoin lines that were broken mid-sentence (common when copying from a PDF). No word is ever dropped.
3. **Check the size** of the cleaned text: 100 to 300,000 characters. Text that is too long is rejected with its exact size and is never cut short.
4. **If validation fails**, the form is shown again *directly*, with the text still in the box. The text is never put in the session, because the session lives in a browser cookie.
5. **Rate limit.** Each visitor gets 10 *accepted* analyses per hour. A visitor is identified by a keyed hash of their IP address, never the IP itself. There is also a burst limit of 20 form posts per minute.
6. **Clean up.** Expired analyses are deleted, as a safety net in case the scheduler isn't running.
7. **Store the analysis.** It is saved with:
   - the encrypted text and the context answers;
   - the ruleset version and the provider;
   - an `expires_at` 60 minutes ahead, so an analysis that never runs is still deleted;
   - an *owner hash*: the browser's session cookie holds a random 64-character token, and only its SHA-256 hash is stored.
8. **Queue `AnalyzePolicyJob`** with only the analysis ID, then redirect to the progress page.

### The background job (`AnalyzePolicyJob` → `AnalysisRunner`)

- The job carries only the ID, so policy text never appears in the queue or in the failed-jobs table.
- The runner loads the analysis, marks it *processing*, and runs the pipeline (next section). As each stage starts, it writes the stage and a percentage to the row.
- On success, everything is stored in one database transaction:
  - one row per rule, with reasons and quotes encrypted;
  - the sections and the detected language;
  - the score.

  The analysis is then marked *completed* and `expires_at` is set to 30 minutes from now.
- On failure, the analysis is marked *failed* with an error code (for example `invalid_provider_output`), and the visitor sees a friendly message. The log records the analysis ID, the error code and where it happened, never the text.
- If the visitor deletes the analysis while it is running, the results are thrown away.
- The job runs once (no automatic queue retries) and has a 10-minute limit.

### Progress and results

- **Progress page:** the `AnalysisProgress` Livewire component polls every 2 seconds and reads only status columns, never the encrypted text. When the analysis finishes, it reloads the page. Without JavaScript, the page refreshes itself every 5 seconds.
- **Results page:** `ReportBuilder` combines the stored results with the *same ruleset version* the analysis used, so old analyses keep their meaning even after the ruleset is updated.
- **Access:** every page re-checks the owner hash. Another browser gets a 404 that doesn't reveal whether the analysis exists.

---

## 3. The analysis pipeline, step by step

`AnalysisPipeline::run()` takes the cleaned text, the ruleset and the context answers. It runs these steps:

| # | Step | Class | What it does |
| --- | --- | --- | --- |
| 1 | Structure | `LanguageDetector`, `Sectionizer` | Detects Indonesian, English, bilingual ("mixed") or unknown by counting common function words of each language. Splits the text into sections with stable IDs (S01, S02, …): a heading plus the paragraphs under it, or one section per paragraph if there are no headings. |
| 2 | Applicability | `ApplicabilityResolver` | Uses the context answers to decide which rules apply and whether each is a legal requirement or a best practice (see [§4](#4-the-ruleset)). |
| 3 | Evaluate | `EvaluationService` + a `PolicyEvaluator` | The language rule (PDP-LANG-01) is decided by language detection where possible. The other rules go to the provider in batches of 8. Every answer is checked against a JSON Schema and against the request (each requested rule exactly once, no quotes on "Not found"). An invalid answer is requested once more; if it is still invalid, the analysis fails cleanly rather than showing partial results. |
| 4 | Verify quotes | `EvidenceVerifier` | Every quote must appear word for word in the policy: first in the section the provider cited, then anywhere (the section is corrected). If any quote can't be found, or a "found" result has no quote at all, the result becomes **Needs human review**. The provider's original answer stays visible. |
| 5 | Score | `CoverageScorer` | Calculates Policy Coverage deterministically (see [§5](#5-the-policy-coverage-score)). |

The four statuses:

| Status | Meaning |
| --- | --- |
| Tercakup / Addressed | The policy clearly gives the information the rule asks for. |
| Tercakup sebagian / Partially addressed | The topic is covered, but key elements are missing or vague, or a statement may conflict with the law (for example, "changes take effect once posted"). |
| Tidak ditemukan / Not found | Nothing relevant in the policy. |
| Perlu tinjauan manusia / Needs human review | A quote couldn't be verified, or the item can't be judged with confidence. |

The pipeline uses only the policy text, and it is the only code that reads it. Instructions inside a pasted policy can't change what the app does: providers return only statuses and quotes for the requested rules, quotes must exist in the text, and scoring is plain code.

---

## 4. The ruleset

The checklist is data, not code. It lives in `resources/rulesets/uu-pdp-27-2022/1.0.0.yaml`.

- **46 provisions:** quoted articles and Elucidation passages of UU 27/2022. Each was verified word for word against the official scanned copy (JDIH BPK), with its PDF page and verification date. See `docs/legal/SOURCES.md`.
- **31 rules.** Each has:
  - an ID (for example `PDP-RET-01`), a bilingual title and explanation;
  - its legal references and what to look for;
  - what each status means for this rule;
  - a next step and example wording;
  - keyword *signals* used only by the offline mock.
- **Legal requirement vs best practice.** A rule is a legal requirement only when a verified provision obliges the organisation to inform people of that item, or gives people a right they must be told about. Everything else is a best practice, which is reported but never counted in the score. Two legal rules rest on an interpretation and say so in the results.
- **Context questions** (consent, specific data, children, transfers abroad, automated decisions):
  - Not sure counts as Yes.
  - Some rules apply only when relevant; for example, the children's rule needs Yes or Not sure to "children's data".
  - If the organisation does **not** rely on consent, rules based only on Pasal 21 become best practices.
- **Safety checks:**
  - The ruleset is validated against a JSON Schema plus consistency checks (references resolve, legal rules cite a basis, and so on) before it can be used. `php artisan cekpdp:ruleset:validate` runs the same checks.
  - Validated data is cached, keyed by a hash of the file, so any edit is re-validated.
- **Versioning:** a released version is never edited; a change means a new version file. Analyses record which version they used.

---

## 5. The Policy Coverage score

Only **applicable legal requirements** count, and every rule has equal weight:

```text
Policy Coverage = (Addressed + 0.5 × Partially addressed) ÷ (Addressed + Partially addressed + Not found)
```

- **Example:** 9 addressed, 6 partially addressed and 6 not found gives `(9 + 0.5 × 6) ÷ 21 = 57%`. The results page always shows this arithmetic.
- **Needs human review** results are left out of the formula. If they make up **more than 20%** of the applicable legal requirements, no score is shown at all, and the page lists what to review.
- **Best practices** are reported as counts and never change the figure.
- The figure measures **disclosure, not compliance**. It is shown in neutral ink, with no traffic-light colours or grades.

---

## 6. How the offline mock decides

The mock (`MockEvaluator`) needs no API key and sends nothing anywhere. It exists so the whole app, the tests and the evaluation dataset work offline, and so there is a baseline to compare Claude against. For each rule, it uses the rule's keyword signals:

1. Sentences matching a **topic** keyword are "about" the rule. If there are none, the rule is **Not found**.
2. A list item is read together with the sentence that introduces its list (for example, "Anda berhak:" followed by the rights).
3. If every **complete** keyword group appears in those sentences, the rule is **Addressed**; otherwise it is **Partially addressed**.
4. If a **cap** phrase appears (a statement that may conflict with the law, such as "deemed to agree"), the result is capped at **Partially addressed** and that sentence is quoted first.
5. The quotes are whole sentences from the policy, so they always pass verification.

On the 12 fictional policies in `evaluation/`, the mock agrees with the expected status on 288 of 340 checks (85%). It is a rough baseline. Its keyword lists also miss some Indonesian word forms: for example, "simpan" (store) doesn't match "menyimpan".

---

## 7. Privacy and security

| Concern | What CekPDP does |
| --- | --- |
| Stored text | Encrypted at rest (with `APP_KEY`), as are reasons and quotes. Deleted 30 minutes after the analysis finishes, 60 minutes after submission if it never runs, or at once with "Delete now". The scheduler purges every minute. |
| Sessions | An encrypted cookie, so the server keeps no session records (no IP or browser logs). There are no accounts; ownership is the hashed token described above. |
| Queue and logs | The job carries only the ID. Logs carry IDs and error codes, never policy text. |
| Caching | Pages that can contain policy text are sent with `Cache-Control: no-store`. |
| Abuse | CSRF protection on every form, plus hourly and burst rate limits keyed by an HMAC of the IP. |
| Third parties | Fonts are self-hosted. In mock mode nothing leaves the server. Before Claude is used (Milestone 3), the notice says the text goes to Anthropic and warns against submitting confidential information. |
| Output | Everything from the policy is escaped when shown. External links send no referrer, so the analysis URL isn't leaked. |

---

## 8. Where the code lives

| Area | Location |
| --- | --- |
| Routes and the purge schedule | `routes/web.php`, `routes/console.php` |
| Web flow (form, submit, results, delete) | `app/Http/Controllers/AnalysisController.php` |
| Ownership, rate-limit key, locale, no-store | `app/Http/Support/`, `app/Http/Middleware/` |
| Background job and persistence | `app/Jobs/AnalyzePolicyJob.php`, `app/Services/` |
| Progress component | `app/Livewire/AnalysisProgress.php` |
| Text cleaning | `app/Domain/Extraction/` |
| Language, sections, sentences | `app/Domain/Document/` |
| Ruleset loading, validation, applicability | `app/Domain/Rules/` |
| Providers, validation, retry | `app/Domain/Ai/` |
| Quote verification | `app/Domain/Evidence/` |
| Score | `app/Domain/Scoring/` |
| The pipeline itself | `app/Domain/Analysis/AnalysisPipeline.php` |
| Report for the results page | `app/Domain/Reporting/` |
| Pages and components | `resources/views/` |
| Interface text (id, en) | `lang/id/`, `lang/en/` |
| Ruleset and its schema | `resources/rulesets/` |
| Provider answer schema | `resources/schemas/evaluation-response.schema.json` |
| Evaluation dataset | `evaluation/fixtures/` (policies), `evaluation/expected/` (expected statuses) |

`app/Domain` holds the logic and never uses HTTP classes, Livewire or Laravel facades. An architecture test enforces this.

---

## 9. What comes next

- **Milestone 3: Claude as the analyser.**
  - The `ClaudeEvaluator` provider, with structured outputs and prompt caching.
  - More tolerant quote matching (typographic quotes, dashes, whitespace).
  - Detection of prompt-injection text.
  - Splitting very long policies into parts.
  - Tests that logs contain no policy text.
- **Milestone 4: the full results experience.**
  - PDF and DOCX upload.
  - Filters and a side-by-side view that highlights quotes in the policy.
  - Markdown and PDF export.
  - Security headers.
  - A keyboard and screen-reader pass.
- **Milestone 5: finishing.**
  - The `cekpdp:evaluate` command with published accuracy numbers.
  - Comparing two policy versions.
  - CekPDP's own privacy policy.
  - The Pasal.id source check.
  - Docker deployment and the full README.

To run it locally, see the README: `composer dev` starts the web server, the queue worker, the scheduler and Vite.
