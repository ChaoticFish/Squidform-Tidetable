# Changelog: Tide Table (pkg_tidetable)

Started on 2026-11-23 when the source moved to GitHub, from the 1.0.0 package as shipped.

## 1.0.0 (2026-11-20): first release
- Events kept in SquidForm (needs SquidForm 1.0.3 or later): the install makes two forms, **Tide Table: Event categories** and **Tide Table: Events**, offers them as builder starters, and offers the categories as a choice list (`tidetable.categories`).
- Admins' events go live at once; other people's wait for approval. Past events are archived (never deleted) by a daily scheduled task, and can be brought back. **Copy** fills a new event from an old one.
- The module shows upcoming events from SquidForm or from a JSON, XML or CSV file, in six layouts (including one after the Analemma design), each with options to switch parts off.
- Works offline: no CDN, no outside calls. Import files for moving events in from Analemma are in `data/`.
