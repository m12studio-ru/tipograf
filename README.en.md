# Типограф: висячие предлоги (Typograph: hanging prepositions)

[Русский](README.md) · **English**

A WordPress plugin that fixes hanging prepositions and conjunctions in Russian texts: short words, particles, dashes and units stay on the same line as the neighbouring word.

A hanging preposition is a short word left at the end of a line, cut off from the word it belongs to: «в», «на», «не», «и». Russian typography rules say such words should move to the next line together with that word. The plugin joins them with a non-breaking space, so the browser wraps them as one unit.

## What it does

- **Short words.** Prepositions, conjunctions and short pronouns stay with the next word: «с&nbsp;вами», «на&nbsp;сайт», «не&nbsp;всегда». The word list can be edited in the settings.
- **Particles.** Же, ли, бы stay with the previous word: «он&nbsp;же», «был&nbsp;ли».
- **Dash.** A dash never starts a line: «Москва&nbsp;— столица».
- **Numbers and abbreviations:** «10&nbsp;м», «5&nbsp;шт», «№&nbsp;5», «г.&nbsp;Москва».

Each rule can be turned on and off separately in **Settings → Typograph**.

## How it works

- Processes the whole final page when it is served: post content, headings, menus, custom fields, theme template strings.
- Touches only visible text. Tags, attributes, links, scripts, styles, `<head>`, `<pre>` and `<code>` stay untouched.
- Texts in the database are not changed: deactivate the plugin and everything is back as it was.
- With a page cache (WP Super Cache, WP Rocket, LiteSpeed) a page is processed once, when it goes into the cache. The cache is cleared automatically on activation, deactivation and when the settings change.

## Requirements

- WordPress 6.5 or later
- PHP 7.4 or later

## Installation

1. Download the repository archive and unpack the `tipograf` folder into `wp-content/plugins/`.
2. Activate the plugin on the **Plugins** screen.
3. Go to **Settings → Typograph**, choose the rules and edit the short word list if needed.

The admin interface is in English and switches to Russian on Russian-language sites.

## Content loaded without a page reload

"Load more" buttons, catalog filters and live search often load posts through AJAX. The plugin processes these responses on its own, with no setup: plain HTML as well as JSON that carries HTML. Plain strings inside JSON are left alone, since they may be data: URLs, keys, field values.

Not processed:

- requests from the admin area, so non-breaking spaces never get saved back to the database;
- the REST API, RSS feeds, robots.txt, sitemaps and other non-HTML responses;
- pages in other languages on multilingual sites (TranslatePress, WPML, Polylang): translations are matched by the original text;
- pages open in the TranslatePress translation editor or in a visual page builder (Elementor, Divi, Beaver Builder, Oxygen, Bricks, WPBakery, Thrive, Brizy): text from there is saved back to the database.

To turn processing off on particular pages, use a filter:

```php
add_filter( 'tipograf_enabled', function ( $enabled ) {
	return $enabled && ! is_page( 'contacts' );
} );
```

## Tests

```bash
php tests/run.php
```

## Credits

The rules are based on [Typograph by Evgeny Muravjev](https://mdash.ru) (public domain), rewritten for modern PHP.

## License

[GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html). Made by [m12studio](https://m12studio.ru).
