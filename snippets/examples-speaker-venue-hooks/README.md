# Speaker & Venue Hook Examples

Example snippets showing how to customize Sugar Calendar Pro speaker and venue
page layouts using the plugin's action/filter hook system.

Sugar Calendar Pro does **not** support theme-based template overrides
(`single-sc_speakers.php`, etc.) for speakers and venues. Instead, the plugin
renders these pages through action hooks, which you can use to reorder, replace,
or extend the default output.

## How to use

1. Copy any snippet file from this folder into the parent `snippets/` directory.
2. Go to **Settings > Sugar Snippets** and activate the snippet.

> **Important:** Only snippets in the top-level `snippets/` folder are loaded.
> Files inside this `examples-speaker-venue-hooks/` subfolder are reference-only.

## Speaker hooks

| Hook (type) | Priority | Description |
|---|---|---|
| `sc_speaker_details_before` (action) | — | Fires above the `.sc-speaker-details` wrapper |
| `sc_speaker_details` (action) | 10 | Renders inside `.sc-speaker-details-inner` |
| `sc_speaker_details_after` (action) | — | Fires below the wrapper (related events here) |
| `sc_speaker_details_data_order` (filter) | — | Reorder detail fields (title, website, email, phone, social_links) |
| `sc_speaker_details_data_key_pair` (filter) | — | Customize field labels and meta keys |
| `sc_speaker_details_social_links` (filter) | — | Add/remove social link icons |

## Venue hooks

| Hook (type) | Priority | Description |
|---|---|---|
| `sc_venue_details_before` (action) | — | Fires above the `.sc-venue-details` wrapper |
| `sc_venue_details` (action) | 10 | Renders inside `.sc-venue-details-inner` |
| `sc_venue_details_after` (action) | 10 | Fires below the wrapper (related events here) |

> Venue fields have no reorder filter -- to change field order, remove the
> default `sc_venue_details` action and add your own (see `venue-custom-details.php`).

## Snippets in this folder

| File | What it does |
|---|---|
| `speaker-featured-image-first.php` | Shows the speaker photo above the detail fields |
| `speaker-reorder-details.php` | Changes the detail field order via filter |
| `speaker-custom-field-labels.php` | Renames field labels (e.g. "Title" to "Role") |
| `speaker-add-content-before.php` | Adds a banner above the speaker block |
| `speaker-card-layout.php` | **Full replacement** — modern card layout with hero image, pill-style contact links, social badges, and divider-separated bio |
| `venue-featured-image-first.php` | Shows the venue photo above the address fields |
| `venue-custom-details.php` | Replaces the default venue renderer with a custom layout |
| `venue-add-content-before-after.php` | Adds a "Get Directions" link below venue details |
