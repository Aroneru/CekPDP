# CekPDP

CekPDP checks a privacy policy against the transparency and disclosure requirements of Indonesia's Personal Data Protection Law, [UU No. 27 Tahun 2022](https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022). It returns a checklist of likely gaps, each with quoted evidence from the policy and a legal reference.

> This tool gives an automated initial review and is not legal advice. Consult a qualified professional before making compliance decisions.

**Status:** Milestone 1 of 5 (reviewed ruleset, data model and evaluation dataset). There is no user interface yet. A full README (architecture, environment variables, evaluation results, limitations, screenshots) comes in Milestone 5.

## What's here so far

- **Ruleset 1.0.0** in `resources/rulesets/uu-pdp-27-2022/1.0.0.yaml`: 31 rules (21 legal requirements, 10 best practices) built on 46 provisions. Each provision was verified word for word against the official copy. See [`docs/legal/SOURCES.md`](docs/legal/SOURCES.md).
- **Evaluation dataset** in `evaluation/`: 12 original fictional policies (Indonesian, English and bilingual) with the expected status of every rule.
- **Data model** for analyses and rule results. Policy text, reasons and evidence are encrypted at rest.
- **The plan** in [`docs/plan/PLAN.md`](docs/plan/PLAN.md).

## Requirements

PHP 8.4+ (Pest 5 needs 8.4; the app itself runs on 8.3+), Composer 2, Node.js 20+, and SQLite for local development (MySQL in production).

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run build
```

## Checks

```bash
php artisan cekpdp:ruleset:validate   # ruleset against its JSON Schema and semantic checks
composer test                          # Pest test suite
composer analyse                       # Larastan (PHPStan level 8)
composer lint                          # Pint code style
```
