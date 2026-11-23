# Tide Table

Events kept in [SquidForm](https://github.com/ChaoticFish/squidform), shown on your site by a module in six layouts. By Chaotic Fish Design. An optional SquidForm add-on ([all add-ons](https://github.com/ChaoticFish/squidform#add-ons)).

- **Version:** 1.0.0 (2026-11-20)
- **Needs:** Joomla 5 or 6, PHP 8.2 or later, **SquidForm 1.0.3 or later** (install or update SquidForm first)
- **Licence:** GPL v2 or later
- Works offline: no CDN, no outside calls.

## What's in the package

| Part | What it does |
|---|---|
| `plg_squidform_tidetable` | Makes two SquidForm forms on install: **Tide Table: Event categories** and **Tide Table: Events**. Offers them as builder starters, fills the Events form from an old event for **Copy**, and offers the categories as a choice list (`tidetable.categories`). |
| `plg_task_tidetable` | A scheduled task, **Tide Table: archive past events**, added on install (daily). |
| `mod_tidetable` | The module: upcoming events from SquidForm or from a JSON, XML or CSV file, in six layouts. |

## Set it up

1. Install SquidForm 1.0.3, then `pkg_tidetable-1.0.0.zip` (System → Install → Extensions). The install makes the two forms (published), switches both plugins on and adds the scheduled task. Installing again never touches forms that already exist.
2. **Categories first.** The categories form is for Special access. Add each category on a page showing it (a SquidForm Form menu item, or `{squidform tidetable-categories}` in an article), or import them (step 4).
   - **Name** shows on the filter buttons. **Short code** (`tour`) is what events store. **Colour**: teal, gold, purple, orange, blue, green, red or gray. **Order**: lower first. **Show this category** off hides it and its events without deleting anything.
3. **A page for the Events form**: a menu item of type SquidForm → Form, form "Tide Table: Events". The module's Add an event and Copy links go there.
4. **Moving from the old Analemma module:** in SquidForm → Submissions → Import submissions (CSV), import `tidetable-categories.csv` into "Tide Table: Event categories", then `tidetable-events.csv` into "Tide Table: Events". The column names are the field keys, so they match themselves. Both files are made from `events.10.29.2026.json`. Planets and Solar Viewing are in the categories file (switched off), so the old events that use them still import.
5. **The module:** Content → Site Modules → New → Tide Table. Pick a layout and a position, and set Adding events → Page with the Events form.

## Who can add events

- **Visitors** (anyone who can see the Events form; it's Public at install) send an event. It waits for an admin in SquidForm's approvals and shows once it's approved. They get an email when it's decided.
- **Administrators and Super Users** skip the check: their events go up straight away. (The form's one approval step has the condition "the sender isn't in Administrator or Super Users".) To change who skips it, edit that step in the builder.
- To let only signed-in people add events, set the Events form's access to Registered.

## The layouts

| Layout | Looks like | Good for |
|---|---|---|
| **Default** | A list by month, a date block beside each event, a coloured edge per category | Most pages |
| **Analemma** | The Analemma site's timeline, with its old class names (`.analemma-events-module`, `.timeline-item`, `.ti-*`), so its theme CSS still applies. New classes: `.ti-fees`, `.ti-reg`, `.ti-copy`, `.ti-add`, `.ti-heading`, `.timeline-group`, `.timeline-list` | Analemma |
| **Tidechart** | A timeline down the middle, events either side (one side on phones), a gold star on special events | An events page |
| **Portofcall** | Cards with a big date and a full-width button | A home page, with Most events set to 3 to 6 |
| **Shipslog** | A compact table: date, time, event, cost (stacks on phones) | A sidebar |
| **Mooncalendar** | A month grid with a moon on days with events, that month's events under it, Previous / Next month | Seeing what's when |

Every layout shows upcoming events only (today on, in the site's time zone), soonest first, then by the first time in the Time text ("1:17 – 2:26 PM" sorts as 1:17 PM). The category buttons appear when there's more than one category. Fees show as "Fee: $13", "Youth: $10 · Adult: $15", or "Free" for $0.

**Accessibility** (Section 508 / WCAG 2.1 AA; axe in a light and a dark template, and at phone width):
- Real headings (Heading level sets the month level), lists, and a table with a caption.
- Filter buttons say which is on (`aria-pressed`), and the count is announced.
- Colour never carries meaning alone: each category is named, and special events say "Special event" to screen readers.
- Links to other sites say they open in a new tab.
- The calendar's day links say the day and how many events, and move focus to them.
- Everything works without JavaScript: the filter buttons just don't appear, and the calendar shows every month.

## Copy

Signed-in people in **Copy for** (Administrator and Super Users by default) get a **Copy** link on each event. It opens the Events form filled in from that event, with the date empty and the contact email not carried over. Only events that went up (sent or approved, archived ones too) can be copied; drafts and events waiting for approval can't.

## Archiving

Once a day the scheduled task archives every event dated before today (site time zone), so an event that runs past midnight stays up until the next day. **Archive after (days)** waits longer. Archived events stay in SquidForm (Submissions, filtered to Archived), and Restore brings one back. The task uses Joomla's scheduler, which runs on site visits (the "lazy scheduler", on by default). With that off, run it from cron: `php cli/joomla.php scheduler:run`.

## A file instead of SquidForm

Set **Events from** to a file: the path from the site's root (`files/events.json`) or the full server path.
- JSON, XML and CSV, in the old Analemma module's format, plus `enrollment_fee_youth`, `enrollment_fee_adult` and `registration_info`.
- A category with `"disabled": true` (CSV: a `category_disabled` column) is hidden with its events.
- Samples: `media/mod_tidetable/samples/events-sample.json`, `.xml` and `.csv`.
- Files over 5 MB, other extensions, and anything that isn't a readable file are refused. Admins see why on the page; visitors see "No upcoming events right now."
- There's no Copy or Add an event with a file.

## For developers

The module reads SquidForm through its reader (`SquidForm\Component\SquidForm\Administrator\Api\Submissions`, SquidForm 1.0.3), never its tables. Layout overrides: copy `modules/mod_tidetable/tmpl/<layout>.php` to `templates/<template>/html/mod_tidetable/`. The shared pieces (fees, links, Copy, the filter) are in `src/Helper/Show.php`, so an override can reuse them.

## Build

```bash
./build.sh                       # lints PHP, refuses outside URLs in media/, checks the forms parse,
                                 # writes dist/pkg_tidetable-<version>.zip and its .sha256
PHPCS=/path/to/phpcs ./build.sh  # also PHP 8.2–8.5 compatibility
python3 mincss.py src-css/tidetable.css mod_tidetable/media/css/tidetable.min.css
                                 # CSS: minified, properties alphabetical, @media left expanded
```

Tests: `tests/e2e-tidetable.mjs` (Playwright and axe) installs the package, imports the Analemma data, and checks every layout, approvals, Copy, the archive task, file sources and Share as choices: `BASE=… ADMIN_PASS=… DB=… node tests/e2e-tidetable.mjs`.

## Test checklist (by hand)

1. Install SquidForm 1.0.3, then Tide Table. The message says it made the two forms and the scheduled task. System → Scheduled Tasks lists "Tide Table: archive past events".
2. Import the two CSV files (categories first). Submissions shows 9 categories and 98 events.
3. Make the Events form page and a module (Default layout) on a test page. Past events, and Intro to Astronomy, Night Sky Tours and Planet Adventure Night (switched off), don't show. Fees and registration details do.
4. Click a category button: only its events show. Tab to the buttons and press Enter: same.
5. Switch the module through the other five layouts. On a phone (or a narrow window), nothing scrolls sideways.
6. Signed out, add an event: it doesn't show. Approve it in SquidForm: it shows. Signed in as an admin, add one: it shows straight away.
7. As an admin, press Copy on an event: the form opens filled in, date empty.
8. System → Scheduled Tasks → Tide Table: archive past events → Run Test. The past events are archived (Submissions → Archived).
9. Switch the module to a file: `media/mod_tidetable/samples/events-sample.json`. The two sample events show.
10. In a dark template (or your site's dark mode), every layout is readable.

## Changelog

### 1.0.0 (2026-11-20)
- First release, replacing the Analemma Events module (`mod_analemma_events` 1.1.0), whose database source was never finished.
- Events and categories kept in SquidForm (two forms made on install), with approval for visitors' events and none for admins'.
- Six layouts: Default, Analemma, Tidechart, Portofcall, Shipslog, Mooncalendar.
- Fees (youth and adult) and registration details shown.
- Copy an event; past events archived daily by a scheduled task.
- JSON, XML and CSV files still work as a source.
