# CekPDP

CekPDP checks a privacy policy against the transparency and disclosure requirements of Indonesia's Personal Data Protection Law, [UU No. 27 Tahun 2022](https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022). It returns a checklist of likely gaps, each with quoted evidence from the policy and a legal reference.

> This tool gives an automated initial review and is not legal advice. Consult a qualified professional before making compliance decisions.

**Status:** Milestone 2 of 5. You can paste a policy, answer the context questions and get a full analysis from the offline mock provider (keyword matching, no AI). The Claude provider comes in Milestone 3; uploads, exports and the full results interface in Milestone 4. A complete README (architecture, evaluation results, limitations, screenshots) comes in Milestone 5.

## What's here so far

- **Ruleset 1.0.0** in `resources/rulesets/uu-pdp-27-2022/1.0.0.yaml`: 31 rules (21 legal requirements, 10 best practices) built on 46 provisions, each verified word for word against the official copy. See [`docs/legal/SOURCES.md`](docs/legal/SOURCES.md).
- **The analysis pipeline**: text cleaning, language detection, sections with stable IDs, the offline mock provider, response validation with one retry, quote verification and the Policy Coverage score.
- **The web app** in Bahasa Indonesia (default) and English: the form with context questions and the AI-disclosure notice, a progress page, and a basic results page.
- **Privacy safeguards**: policy text is encrypted at rest and deleted 30 minutes after the analysis finishes (or at once with "Delete now"); it never enters the session, the queue payload or the logs.
- **Evaluation dataset** in `evaluation/`: 12 original fictional policies with the expected status of every rule. Four of them are offered as samples on the home page.
- **The plan** in [`docs/plan/PLAN.md`](docs/plan/PLAN.md).

## Requirements

PHP 8.4+ (with the `intl`, `mbstring`, `pdo_sqlite` and `zip` extensions), Composer 2, Node.js 20+, and SQLite for local development (MySQL in production).

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run build
composer dev
```

`composer dev` starts the web server, the queue worker that runs analyses, the scheduler that deletes expired analyses, and Vite. Open <http://localhost:8000>, pick a sample policy or paste your own, tick the notice and submit.

Running the pieces separately also works: `php artisan serve`, `php artisan queue:work` and `php artisan schedule:work`. Without a queue worker, analyses stay "waiting" and are deleted after an hour.

## Checks

```bash
php artisan cekpdp:ruleset:validate   # ruleset against its JSON Schema and semantic checks
composer test                          # Pest test suite
composer analyse                       # Larastan (PHPStan level 8)
composer lint                          # Pint code style
```
