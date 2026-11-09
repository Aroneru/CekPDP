# Legal sources and verification log

**Verified on:** 5 November 2026 · **Status re-checked:** 9 November 2026 · **Prepared by:** Claude (AI assistant), for review by the project owner · **This is research for a software tool, not legal advice.**

This log records where every legal reference in the CekPDP ruleset comes from, how it was verified, and what is still unverified. Update it whenever the ruleset changes (see the ruleset update process in `docs/plan/PLAN.md`, section 11).

**Used by:** ruleset 1.0.0 (`resources/rulesets/uu-pdp-27-2022/1.0.0.yaml`). Its provision texts are byte-identical to the reviewed draft verified here.

**Re-check on 9 November 2026:** a news search found no new MK decision on UU 27/2022, and no report that the Perpres establishing the PDP institution has been signed (most recent: [Bisnis.com, 13 September 2026](https://teknologi.bisnis.com/read/20260913/84/2003698/pembentukan-badan-pdp-dipercepat-implementasi-uu-pdp-dinilai-belum-efektif)). PP 33/2026 is still not in force. Nothing in the ruleset changed.

---

## 1. Primary source: UU No. 27 Tahun 2022 (Personal Data Protection Law)

| Item | Value | Where verified |
| --- | --- | --- |
| Title | Undang-Undang Republik Indonesia Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi | p. 1 |
| Enacted (*disahkan*) | 17 October 2022, Jakarta, by President Joko Widodo | p. 34 |
| Promulgated (*diundangkan*) | 17 October 2022, by Minister of State Secretary Pratikno | p. 34 |
| State Gazette | Lembaran Negara RI Tahun 2022 Nomor 196; Tambahan Lembaran Negara RI Nomor 6820 | pp. 34, 50 |
| Entry into force | On promulgation, 17 October 2022 (Pasal 76) | p. 33 |
| Transition period | Controllers, processors and others had to comply within 2 years of promulgation (Pasal 74), so by 17 October 2024 | p. 33 |
| Status on JDIH BPK | "Berlaku" (in force) | detail page |
| Official copy used | JDIH BPK RI detail page: <https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022> · PDF: <https://peraturan.bpk.go.id/Download/224884/UU%20Nomor%2027%20Tahun%202022.pdf> | — |
| Type of copy | Scan of the "Salinan sesuai dengan aslinya" certified by Kementerian Sekretariat Negara (Deputi Bidang Perundang-undangan dan Administrasi Hukum). 50 pages: body pp. 1–34, Elucidation (*Penjelasan*) pp. 35–50 | pp. 34, 35 |
| SHA-256 of the PDF | `ed952dea04b87d14ecf037f9f324a65d50b396ddd34fc28ddf9ff014391971f6` | — |
| Cross-check | JDIH Kemenkeu publishes a byte-identical file (same SHA-256): <https://jdih.kemenkeu.go.id/download/46c17b53-4a45-411c-9b76-c6799e5769fc/UU_Nomor_27_Tahun_2022%20Perlindungan%20Data%20Pribadi.pdf> | — |

Page numbers in this log and in the ruleset are **PDF page numbers**. For the body they match the printed page numbers ("- 5 -" is PDF page 5). Elucidation page "- 1 -" is PDF page 35.

### 1.1 Verification method

1. The PDF is a scanned image with an OCR text layer. The OCR layer has errors, so it was used **only to find provisions**, never as the source of quoted text.
2. Every provision quoted in the draft ruleset was compared word for word with the **page image**. Pages reviewed visually: body pp. 5–20, 22–24, 33–34; Elucidation pp. 39–41, 43–44, 46.
3. English text in the ruleset is **our own unofficial translation**. There is no official English translation of this law. The Indonesian text is authoritative.

### 1.2 OCR errors found (why the page images matter)

Quoting the OCR text directly would have put errors like these into the ruleset:

| Provision | OCR layer | Page image (official text) |
| --- | --- | --- |
| Pasal 4(3)(c) | *(missing)* | "kewarganegaraan;" |
| Pasal 11 | "…pemrosesan Data Pribadi secara sesual dengan tujuan…" | "…secara **proporsional** sesuai dengan tujuan…" |
| Pasal 13(2) | "berhak dan Data Pribadi…" | "berhak **menggunakan** dan **mengirimkan** Data Pribadi…" |
| Pasal 16(2)(a) | "a. Data Pribadi dilakukan secara terbatas…" | "a. **pengumpulan** Data Pribadi dilakukan secara terbatas…" |
| Pasal 16(2)(e) | "…pengubahan yang tidak sah, perusakan…" | "…pengubahan yang tidak sah, **penyalahgunaan**, perusakan…" |
| Pasal 21(1)(e) | "rincian mengenai Informasi yang" | "rincian mengenai Informasi yang **dikumpulkan**;" |
| Pasal 21(1)(g) | "C. hak Subjek Data Pribadi." | "g. hak Subjek Data Pribadi." |
| Pasal 25(1) | "Pemrosesan Data Pribadi anak khusus." | "…anak **diselenggarakan secara** khusus." |
| Pasal 46(1) | "…wajib pemberitahuan secara tertulis…" | "…wajib **menyampaikan** pemberitahuan secara tertulis…" |
| Pasal 48(1) | "…badan hukum wajib pemberitahuan pengalihan…" | "…wajib **menyampaikan** pemberitahuan pengalihan…" |

These checks are also why the app's own evidence verification must compare quotes against the exact text the user submitted (see PLAN.md §5.4).

### 1.3 Provisions delegated to implementing regulations

The law delegates details to a Government Regulation (*Peraturan Pemerintah*, PP) in Pasal 10(2), 12(2), 13(3), 16(3), 34(3), 48(5), 54(3) and 56(5). All of these were confirmed on the page images. Pasal 57(5) and Pasal 61 (PP) and Pasal 58(5) (Presidential Regulation, *Perpres*, on the PDP institution) were found in the OCR layer only; their page images (pp. 25–28) were not reviewed because no rule relies on them.

---

## 2. Constitutional Court (Mahkamah Konstitusi, MK) decisions on UU 27/2022

| Case | Provision challenged | Outcome | Date | How verified |
| --- | --- | --- | --- | --- |
| **151/PUU-XXII/2024** | The word "dan" at the end of Pasal 53(1)(b) | **Granted.** "dan" is conditionally unconstitutional and not binding unless read as "dan/atau". The three DPO-appointment criteria in Pasal 53(1)(a)–(c) are therefore alternative: meeting any one of them is enough. | Decided 16 July 2025; pronounced **30 July 2025** | Operative ruling (*amar*) read in the decision text, p. 159. Copy linked from JDIH BPK: <https://peraturan.bpk.go.id/DownloadUjiMateri/218/putusan_mkri_151_PUU-XXII_2024.pdf> (SHA-256 `6dbbdb8092625df850707a86592e240379c4edb7a28cb98b99ff8ac077815e47`) |
| **133/PUU-XXIV/2026** | Pasal 62(2) (international cooperation) | **Rejected entirely.** No change to the text. | Pronounced 25 May 2026 | Operative ruling read in the decision text: <https://www.mkri.id/public/content/persidangan/putusan/putusan_mkri_14323_1779697116.pdf> (SHA-256 `6ef761a83011181d685975890ea396108553b615643119d3bed6696664250b70`) |
| 284/PUU-XXIII/2025 | Pasal 20(2)(a) ("persetujuan yang sah") | Rejected (reported) | 2 March 2026 (reported) | Secondary only: MK news <https://www.mkri.id/berita/pemrosesan-data-pribadi-hanya-dapat-dilakukan-dengan-persetujuan-yang-sah-24702>, <https://pasal.id/peraturan/putusan-mk/puu-mk-284-2025>. Decision text not reviewed. |
| 102/PUU-XXIV/2026 | Pasal 20(2)(f) | Not accepted (*tidak dapat diterima*) (reported) | — | Secondary only: <https://www.mkri.id/berita/permohonan-uji-uu-pdp-tidak-dapat-diterima-24954> |
| 153/PUU-XXIV/2026 | Pasal 58(5) and 61 (forming the PDP institution) | Not accepted (reported) | 17 June 2026 (reported) | Secondary only: <https://www.mkri.id/berita/uji-uu-pdp-menyoal-kekosongan-lembaga-pengawas-independen-tidak-dapat-diterima-25202> |
| 135/PUU-XXIII/2025 | Pasal 65(2) jo. 67(2) | Rejected (reported) | — | Secondary only: <https://aji.or.id/informasi/mk-tolak-uji-materiil-uu-pdp-hak-publik-atas-informasi-kian-terancam> |
| 108/PUU-XX/2022, 110/PUU-XX/2022 | Various | Rejected (reported) | April 2023 (reported) | Secondary only: <https://www.antaranews.com/berita/3488898/mk-tolak-2-uji-materi-undang-undang-pelindungan-data-pribadi>, <https://pasal.id/peraturan/putusan-mk/puu-mk-110-2022> |

**Effect on the ruleset:** only 151/PUU-XXII/2024 changes a provision the tool cites, and only in a best-practice rule (PDP-DPO-01). JDIH BPK lists only that decision under "Uji Materi". Before each ruleset release, search the MK case register again for new decisions on UU 27/2022.

---

## 3. Implementing regulations: status on 5 November 2026

| Instrument | Status | In the active checklist? | Sources |
| --- | --- | --- | --- |
| **PP No. 33 Tahun 2026** tentang Peraturan Pelaksanaan Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi | **Promulgated, not yet in force.** Reported as enacted and promulgated on 16 July 2026 (one database says 15 July 2026), in Lembaran Negara 2026 No. 88, with 225 articles in 12 chapters. Its final article reportedly sets entry into force at 6 months after promulgation, which is about **16 January 2027** (or 15 January if promulgated on 15 July). **No official government copy was found** on JDIH BPK, JDIH Setneg or peraturan.go.id. | **No.** It isn't in force, and its text hasn't been verified against an official copy. | Secondary only: Hukumonline <https://www.hukumonline.com/pusatdata/detail/lt6a9164bf1e96a/peraturan-pemerintah-nomor-33-tahun-2026/>; Datahukum <https://www.datahukum.com/peraturan/PP_33_Tahun_2026.html>; Depositobpr <https://depositobpr.id/blog/pp-33-2026-aturan-turunan-uu-pelindungan-data-pribadi>; FWP <https://fwp.co.id/pp-33-tahun-2026-pelindungan-data-pribadi/> |
| **Perpres on the PDP institution** (*lembaga*, Pasal 58(3)–(5)) | **Not enacted.** The draft was reportedly submitted to the President on 20 May 2026. News on 4 November 2026 says it is in its final stage. Komdigi's Directorate General of Digital Space Supervision reportedly handles PDP functions in the meantime. | Not applicable. Rules that mention the *lembaga* (e.g., breach notification under Pasal 46(1)(b)) note that it hasn't been formed yet. | Secondary only: Kompas, 27 July 2026 <https://nasional.kompas.com/read/2026/07/27/15423511/komdigi-ungkap-perpres-pembentukan-badan-perlindungan-data-pribadi-sedang>; Kompas, 4 September 2026 <https://nasional.kompas.com/read/2026/09/04/17150021/segera-bentuk-badan-pelindungan-data-pribadi>; Harian Jogja, 4 November 2026 <https://news.harianjogja.com/r-1277616/kemkomdigi-siapkan-satgas-jelang-pembentukan-badan-pengawas-data-pribadi> |

**Action needed before January 2027:** get an official copy of PP 33/2026 (JDIH Setneg or the State Gazette), verify it, and decide which privacy-notice requirements it adds. Those would go into a separate, clearly labelled ruleset module (proposed as v1.1.0) with its own effective date. They must not be mixed into the UU 27/2022 rules.

## 4. Other regulations: out of scope for v1

- **Regulations kept in force by Pasal 75.** Existing regulations on personal data protection stay in force to the extent they don't conflict with UU 27/2022 (Pasal 75, p. 33, verified). This includes sector rules for finance, health, telecommunications and electronic systems. They are **not covered** by v1 and were not researched. The UI and README will say so.
- **Criminal provisions (Pasal 67–73)** have nothing to do with privacy-policy disclosure and were not reviewed for later changes, such as alignment with the new Criminal Code.

## 5. Pasal.id: secondary reader and maintenance aid

[Pasal.id](https://pasal.id) is a third-party database of Indonesian legislation and MK decisions. It offers a remote MCP server (`https://mcp.pasal.id/mcp`, with full text, free account, 300 full-text reads per account per day) and a Discovery API (`https://pasal.id/api/v1`, metadata, status and links only, no regulation text). Setup page: <https://pasal.id/hubungkan>.

**Cross-check on 5 November 2026** (public web pages; the MCP server wasn't used because it needs the owner's login):

- UU 27/2022 page: <https://pasal.id/peraturan/uu/uu-no-27-tahun-2022>. Status "Berlaku". Verification tier "Sesuai naskah acuan". Its methodology defines this tier as reproducing a locked reference text that was **drafted with AI assistance**, which confirms repeatability, not human review. Listed source: <https://peraturan.go.id/id/uu-no-27-tahun-2022> (not reviewed by us).
- Pasal 53 is annotated with MK 151/PUU-XXII/2024, which matches §2.
- Five provisions where the official scan's OCR layer was wrong (Pasal 11, 13(2), 16(2)(a), 16(2)(e), 21(1)(e)) were compared with Pasal.id's text. All five matched the official page image, although Pasal.id showed only short excerpts.
- No page for PP 33/2026 was found at the URL patterns tried.

**How the project may use it** (Pasal.id Terms of Service, effective 5 October 2026: <https://pasal.id/ketentuan>):

- The Discovery API is metadata only, so low-volume status checks fit its stated scope.
- The MCP server is described as an AI assistant reading regulations on a user's behalf, within the daily per-account limit.
- The terms prohibit automated bulk extraction and building a competing database or dataset from Pasal.id content without a written licence. They also claim rights in Pasal.id's structured data. CekPDP therefore **won't copy Pasal.id text into the ruleset**. Ruleset texts come only from our own verification of the official copy, and Pasal.id is never the basis of a rule.

## 6. Secondary sources

Used only to find primary sources or to learn the status of documents that aren't available officially. They are never the basis of an active rule.

- PP 33/2026 commentary: <https://veritask.ai/id/artikel/pengaturan-teknis-pelindungan-data-pribadi-dan-kewajiban-pengendali-serta-prosesor-akhirnya-terbit-lewat-pp-33-2026>, <https://www.kres.id/pp-33-2026-aturan-pelaksana-uu-pdp>, <https://justisio.com/blog/pp-pdp-disahkan-jangan-sampai-kena-sanksi>
- Lembaga PDP: <https://www.kompas.id/artikel/lembaga-perlindungan-data-pribadi-tak-kunjung-dibentuk-uu-pdp-dibawa-ke-mk>, <https://jatim.antaranews.com/berita/1031282/kemkomdigi-targetkan-pembentukan-badan-pdp-rampung-2026>
- MK decisions: <https://www.metrotvnews.com/read/KYVC4M41-uji-uu-pdp-dikabulkan-mk-negara-wajib-melindungi-data-pribadi-warga-secara-maksimal>, <https://www.nu.or.id/nasional/mk-kabulkan-uji-materiil-uu-pdp-perlindungan-data-pribadi-makin-diperkuat-ckmEB>
