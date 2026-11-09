# CekPDP evaluation dataset

Twelve **original, fictional** privacy policies, each with the expected result of every rule in the active ruleset. The `cekpdp:evaluate` command (Milestone 5) uses them to measure accuracy, false positives and false negatives per rule, for the mock provider and for Claude.

- `fixtures/{slug}.md`: the policy text, written for this project. No text was copied from a real company's policy. Company names, addresses and products are invented, email addresses use the reserved `.example` domain, and phone numbers are obviously fake.
- `expected/{slug}.yaml`: context answers plus the expected status of every active rule.

## Fixtures

| Slug | Language | Context | Designed to test |
| --- | --- | --- | --- |
| `id-01-belanja-lengkap` | id | consent, overseas transfer | A comprehensive policy: almost everything addressed |
| `en-02-saas-comprehensive` | en | consent, overseas transfer | A strong policy with typical gaps: no right to sue, 14-day response time, vague breach timing |
| `id-03-fintech-sedang` | id | consent, specific data, automated decisions, transfer unsure | Medium completeness: credit scoring without the right to object, changes "berlaku sejak diumumkan" |
| `en-04-gdpr-style` | en | consent, overseas transfer, automated decisions unsure | A GDPR-style notice that misses UU PDP specifics (complaint instead of right to sue, one-month responses, 72 hours "where required") |
| `id-05-klinik-kesehatan` | id | consent, specific (health) data, children unsure | Health data identified as specific data; parental consent for child patients |
| `id-06-edtech-anak` | id | consent, children's data, overseas transfer | Children's app with parental consent; vague security and transfer safeguards |
| `id-07-persetujuan-dianggap` | id | consent | Deemed consent ("dianggap menyetujui") and changes effective on posting |
| `en-08-vague-marketing` | en | everything "not sure" | Vague, marketing-heavy text: mostly partial or missing |
| `id-09-umkm-minim` | id | consent | A very short small-business notice |
| `en-10-game-minimal` | en | children, overseas transfer, consent and specific data unsure | A minimal mobile-game notice with an age limit but no parental consent |
| `bi-11-logistik-dwibahasa` | mixed | consent | A bilingual Indonesian–English policy |
| `en-12-injection` | en | consent, overseas transfer | A medium policy containing a prompt-injection attempt. The expected results reflect the policy's real content, so obeying the injected text causes false positives |

## Labelling conventions

- Statuses follow each rule's `criteria` in the ruleset: `addressed`, `partially_addressed`, `not_found`, or `needs_human_review`.
- Rules that don't apply under a fixture's context answers are `not_applicable`. The loader checks this against the applicability rules.
- A few items are genuinely borderline. They list `acceptable` alternatives and a `note` explaining why, and the evaluation counts either answer as correct.
- The labels are a careful reading by the author of the dataset, not legal advice. Review them whenever the ruleset changes.
