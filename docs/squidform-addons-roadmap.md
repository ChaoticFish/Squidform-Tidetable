# SquidForm add-ons roadmap

Status (2026-11-20). Add-ons stay out of the main package and hook into SquidForm. Anything that goes online, runs other programs or brings a big library is an add-on, so the main package stays offline. SquidForm 1.0.3 added the hooks they need (DEVELOPER.md → "Which hook for which planned add-on").

## Done

| Add-on | Status | Notes |
|---|---|---|
| **Scanner tools** (`plg_squidform_scannertools` 1.0.2) | Released, jDownloads 16 | Runs ClamAV's program on uploads, EICAR check. Stays off the work network. |
| **Tide Table** (`pkg_tidetable` 1.0.0) | Built, waiting for Fishie's hand test | Events kept in SquidForm, module with six layouts, archive task, Copy. Needs SquidForm 1.0.3. Full notes: `claude/tide-table-roadmap.md`. |

## Planned (no order chosen yet)

| Add-on | What | Hooks it uses (1.0.3) |
|---|---|---|
| **Surveys** | Results pages with charts. Study SurveyMonkey, Google Forms, Typeform, Qualtrics, LimeSurvey and do what they do, better. Charts bundled locally, GPL-compatible: Chart.js (MIT) or Apache ECharts (Apache 2.0). | Reader (`Api\Submissions`) for counts, `onSquidformSubmissionsLinks` (Results button), `onSquidformFieldTypes`, `onSquidformStarters` (survey templates) |
| **Connectors** | For sites that can reach the internet: send answers to a web address or Google, online captchas. | `onSquidformActionTypes`; online captchas are Joomla captcha plugins (already supported) |
| **Payments** | Pay before a submission counts. | **Not covered yet**: needs its own status ("waiting for payment") and a return page. Design when it's planned. |
| **Fancy PDFs** | TCPDF writer for PDFs that need to look the part (certificates). Built-in writer stays the default. | `onSquidformPdfRenderer` |
| **Quill editor** | Quill (BSD) instead of TinyMCE for Formatted text; bundled, needs toolbar labels, keyboard handling and an accessibility test. TinyMCE stays the default. | `onSquidformEditor` |
| **Address formats** | Import a country's address layout from a small file (no code or HTML); shareable; ships with a JSON schema, a México sample and an AI prompt (`claude/squidform-address-format-prompt.md`). | `onSquidformAddressFormats` |
| **More languages** | More visitor language packs plus a "make your own translation" tool that writes the pack file. Until then the manual says languages can be added on request. | `onSquidformLanguages` |
| **Pecking Order link-up** | Org chart from a form's answers; people or teams as choices in forms. | Reader, `onSquidformOptionSources` |
| **Delete data action** | Only if needed. | `onSquidformActionTypes` |

## Rules for every add-on
- Offline unless the add-on's whole point is going online (then it's clearly named as such).
- No server code stored in the database; Read/Write allow-lists stay as they are.
- Section 508 / WCAG 2.1 AA, light and dark, tested with axe.
- Read answers through `Api\Submissions`, never the form tables.
- Its own zip named with the version, README.md, changelog, language strings, a short test checklist, and the CFD update-server tag.
