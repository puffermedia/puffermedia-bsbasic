# PufferMedia Bootstrap Basic (puffermedia-bsbasic)

A straightforward Bootstrap 5 site template for Joomla 6, written in SCSS and
built from scratch. It uses Joomla's own bundled Bootstrap, keeps every module
position of the core Cassiopeia template, and puts colours, fonts, layout and
behaviour in Template Style params, so most sites need no code changes.

The repo also holds a small companion plugin (Open Graph fields on articles,
per-page Bootstrap components on menu items) and the scripts that build,
package and copy the template.

Current version: see `<version>` in `base-template/templateDetails.xml`.
History: [CHANGELOG.md](CHANGELOG.md). Working on the code (repository layout,
building, packaging, Bootstrap internals, what each param emits):
[DEVELOPING.md](DEVELOPING.md).

## Features

- **Bootstrap 5 from Joomla itself.** The page loads Joomla's precompiled
  `media/vendor/bootstrap/` copy. No extra framework download, no Node.js.
- **Same module positions as Cassiopeia.** Switch a site from Cassiopeia and
  every module assignment stays in place.
- **Configured in the admin.** Logo, colours, fonts, header and footer
  schemes, layout width, sidebars, sticky header, animations, social sharing
  tags and custom code are all Template Style params.
- **No external requests.** System font stacks only; Font Awesome (optional)
  comes from Joomla's bundled copy.
- **Light on output.** At default params the template adds no inline `<style>`
  block, and each script (back-to-top, sticky header, animations, sidebar
  menu) loads only when its param is on.
- **"Modern editorial" look.** Ink-on-paper palette with one restrained accent,
  serif headings over a system sans body, a light header, reading-width
  articles and a dark footer.

## Requirements

- Joomla 6 (developed on 6.2).
- To build from source: Git and a Bash shell. SCSS is compiled with the
  standalone [Dart Sass](https://sass-lang.com/dart-sass/) binary, which a
  script downloads; no Node.js or npm.

## Installing

Build the zips (see [Building packages](DEVELOPING.md#building-packages) in DEVELOPING.md), then in Joomla go
to **System → Install → Extensions → Upload Package File**.

| Zip | Contains |
|---|---|
| `dist/puffermedia-bsbasic-<version>.zip` | The template only |
| `dist/pkg_puffermedia_bsbasic-<version>.zip` | Template and companion plugin in one upload. Joomla installs plugins disabled, so enable **System - BS Basic Open Graph** in System → Plugins afterwards |

Then make it the default under **System → Site Template Styles**.

**Upgrading:** install a newer zip over the old one. The manifest uses
`method="upgrade"`, so every Template Style setting is kept.

**Display name vs. technical name.** The manifest `<name>` must stay
`puffermedia-bsbasic`: Joomla matches upgrades on it, and a different name
installs a separate template without your settings. The name you see in the
admin is the style's **Style Name** field (System → Site Template Styles →
click the style), which you can change freely.

## Module positions

All of Cassiopeia's positions, in the same places:

| Position | Location |
|---|---|
| `topbar` | Utility bar above the header |
| `below-top` | Below the top bar, above the header |
| `menu` | Inside the header navbar |
| `search` | Inside the header navbar, right side |
| `banner` | Below the header (full-bleed hero with the Banner layout, see below) |
| `top-a` | Above the main content area |
| `top-b` | Above the main content area (second row) |
| `main-top` | Top of the main content column |
| `main-bottom` | Bottom of the main content column |
| `breadcrumbs` | Above the component output |
| `sidebar-left` | Left sidebar |
| `sidebar-right` | Right sidebar |
| `bottom-a` | Below the main content area |
| `bottom-b` | Below the main content area (same row as `bottom-a`) |
| `footer` | Footer |
| `error-403` | Error page, 403 only |
| `error-404` | Error page, 404 only |
| `debug` | Joomla debug output |

The main column narrows automatically for each populated sidebar, by the
Sidebar Width param (3 of 12 columns by default). With no sidebar, articles
are centred in a reading-width column.

Module chromes the template defines: `none`, `html5` and `card` (Bootstrap
`.card` / `.card-header` / `.card-body`; Joomla core has no `card` chrome for
site templates, so the template ships its own in
`html/layouts/chromes/card.php`).

## Template Style params

Params are grouped in tabs, each with titled sections. Every default
reproduces the template's standard look. Moving a field between tabs never
affects saved values, because Joomla stores params by name.

### Details tab (Branding)

| Param | Notes |
|---|---|
| Logo Image | Falls back to the site name as text |
| Logo Alt Text | Defaults to the site name |
| Tagline | Shown next to the logo from the md breakpoint up, hidden on phones |
| Copyright Text | Footer line. `{year}` and `{sitename}` are replaced at render time |

### Appearance tab

Sections: Colors, Bootstrap Theme Colors, Typography, Shape, Animation.

| Param | Default | Notes |
|---|---|---|
| Primary Color | follows Accent | Bootstrap primary: links, `.btn-primary`, active pagination, every `*-primary` utility through the full `--bs-primary-*` set |
| Accent Color | `#ff0000` | Hover and pressed states, nav underline and current item, focus rings, selection, borders and rules. A 15% darker hover shade is derived automatically |
| Link Color | Primary | Resting text-link colour; hover uses the dark Accent shade |
| Body Background Color | white | Page background (also `--bs-body-bg`) |
| Container Background Color | — | One colour behind the main content area (top-a through bottom-b, including sidebars), with the Body Background showing around it. Most visible with a Boxed layout |
| Bootstrap Theme Colors | Bootstrap's | Secondary, Success, Info, Warning, Danger, Light, Dark. Each colour set emits the full `--bs-*` set plus the button, outline, `.text-bg-*` and link rules Bootstrap hard-codes, with hover shades computed the way Bootstrap does |
| Body Font | system sans | Default, Classic Sans (Helvetica/Arial), Humanist (Segoe UI/Verdana), Serif (Georgia/Times), Monospace |
| Body Text Color | ink | General page text, blockquotes and inline code. Titles follow it unless Title Font Color is set. Header, footer and top bar keep their own colours |
| Article Text Color | soft ink | Article reading text, tag pills and form labels |
| Secondary Text Color | grey | Article info, image captions, table headings, `card` module titles and breadcrumbs |
| Title Font | Georgia serif | Same options, for h1–h6 |
| Title Font Color | Body Text Color | Headings and blog item titles |
| H1 Color … H6 Color | — | One colour per heading level (h1–h6 and `.h1`–`.h6`); blog item titles follow H2. Blank = Title Font Color |
| Border Radius | Soft (`.5rem`) | Square / Soft / Round. Buttons and inputs use 80% of it |
| Tile Hover Effect | Lift | None / Lift / Zoom image / Lift and zoom image, on article and feed tiles |
| Entrance Animation | None | Fade in / Slide up / Zoom in as content scrolls into view. Loads `js/animate.js` only when set; honours `prefers-reduced-motion` |
| Animate | Cards, tiles and sections | Which elements take the entrance animation (the footer never does) |
| Blog Items | Entrance only | Entrance and hover also adds hover effects to blog items |
| Animation Speed | 600 ms | 200–2000 ms slider |
| Stagger | On | Cards revealed together animate one after another |

### Header & Footer tab

Sections: Top Bar, Header, Menu & Sticky Header, Footer.

| Param | Default | Notes |
|---|---|---|
| Top Bar Background | ink | Background of the `topbar` position band |
| Top Bar Text Color | white | Text and links in the top bar (resting text was 80% white; a picked colour applies at full strength) |
| Header Color | Light | Light / Dark / Primary / Accent. Drop-down panels stay light |
| Header Font Color | follows scheme | Resting menu text colour |
| Header Spacing | Default | Compact / Default / Roomy navbar padding |
| Header Shadow | Subtle | None removes it, including on the sticky header |
| Menu Collapse Breakpoint | Large (992px) | Medium 768 / Large 992 / Extra large 1200px |
| Menu Alignment | End | End / Center / Start |
| Menu Style | Template | Template (animated accent underline) / Pills / Underline |
| Mobile Menu | Collapse | Collapse, or an offcanvas panel from the right or left |
| Sticky Header | No | Keeps the header at the top; in-page anchor links stop below it |
| Sticky Header Behavior | Fixed | Fixed / Shrink on scroll / Hide on scroll down (comes back on scroll up or keyboard focus) |
| Footer Color | Dark | Dark / Light / Primary / Accent |
| Footer Font Color | follows scheme | Footer links and menu items |

### Layout tab

Sections: Page Width, Content.

| Param | Default | Notes |
|---|---|---|
| Layout Width | Fluid | Fluid (`container-fluid`) or Boxed (`container`) |
| Boxed Max Width | 1320px | 1140 / 1320 / 1440 / 1600px (Boxed only) |
| Boxed From | All widths | Full width below md / lg / xl / xxl, boxed from there (Boxed only) |
| Top Bar, Header and Footer Width | Match page width | Full width keeps those bands edge to edge on a boxed page |
| Sidebar Breakpoint | lg | md / lg / xl; below it the columns stack |
| Content First on Small Screens | No | Puts the article above the left sidebar when stacked |
| Grid Gutter | Default | Bootstrap gutter 0–5 for the main and bottom rows |
| Reading Width | Default (46rem) | Narrow 40 / Default 46 / Wide 54 / Extra wide 64rem, for articles with no sidebar |
| Sidebar Width | 3 | 2 / 3 / 4 of 12 columns per sidebar |
| Sidebar Menu | Standard | Collapsible hides submenus in the sidebars behind toggle buttons (`js/sidebar-menu.js`); without JS the menu stays open |
| Sidebar Menu Dividers | Off | Hairlines between top-level sidebar menu items |
| Content Indent | Default (5rem) | None / Small / Default / Large left indent for header, page headings, blog items and bands, from 992px up |
| Container Padding | Default (1.5rem) | None / Small 2.5rem / Default / Large 5rem left and right padding inside the Container Background Color box; no effect while that colour is blank |

### Bootstrap tab

Sections: Extra Classes, Tables & List Groups, JavaScript & Icons, Developer.
Aimed at developers who know Bootstrap; every default leaves the page
unchanged.

| Param | Default | Notes |
|---|---|---|
| Extra Classes | — | Your own classes on Body, Header, Navbar, Main Area, Main Column, Sidebars, Bottom Row and Footer (letters, digits, `_`, `-`, `:` and spaces) |
| Tables follow Theme Colors | No | Recomputes `.table-*` variants from your palette, using Bootstrap's own `table-variant()` math |
| List Groups follow Theme Colors | Yes | `.list-group-item-*` follow the palette; No restores Bootstrap's stock colours there |
| Bootstrap JS Components | none | Alert, Carousel, Dropdown, Modal, Offcanvas, Popover, Scrollspy, Tab, Toast, Tooltip, loaded through Joomla's asset manager. Tooltips, popovers and toasts are also initialized. With the companion plugin enabled, menu items can add components for their own pages |
| Load Font Awesome | No | Joomla's bundled Font Awesome Free; no external request |
| Breakpoint Indicator | No | A small xs–xxl badge, shown only while System Debug is on |

### Advanced tab

Sections: Back-to-Top Button, Browser & Sharing, Custom Code.

| Param | Default | Notes |
|---|---|---|
| Back-to-Top Button | Yes | Floating button |
| Back-to-Top Position | Right | Right / Left |
| Back-to-Top Appears After | 300px | 100 / 300 / 600 / 1000px of scrolling |
| Favicon | — | Any ico, png, svg, gif, jpg or webp. Blank leaves Joomla's default |
| Browser Theme Color | None | None / Primary / Accent / Custom, as `<meta name="theme-color">` |
| Social Sharing Tags | No | Open Graph tags (`og:site_name`, `og:type`, `og:title`, `og:description`, `og:url`, `og:image`). Tags set by another extension are kept |
| Default Share Image | — | About 1200 × 630px; used when a page has no image of its own |
| Twitter / X Card | No | `summary_large_image` with a share image, otherwise `summary` |
| Custom CSS | — | Per-style CSS added last, so it wins over the template and params |
| Custom Code (Head) | — | Inserted before `</head>` |
| Custom Code (Before `</body>`) | — | Inserted before `</body>` |

## Companion plugin

`plugins/plg_system_bsbasicog/` (**System - BS Basic Open Graph**) does what a
template cannot: add tabs to edit forms. It ships in the bundle zip and stores
everything in existing params, so it adds no database tables.

- **Open Graph tab on articles.** Title, description and image per article.
  They replace the template's tags on that article's page; empty fields
  change nothing. Needs Social Sharing Tags on in the template.
- **Article dates.** On article pages it sets `og:type` to `article`, adds
  `article:published_time` / `article:modified_time`, and fills
  `datePublished` / `dateModified` in Joomla's schema.org data.
- **Bootstrap tab on site menu items.** Pick Bootstrap JS components for the
  pages a menu item opens, on top of the style-wide list. Disabling the plugin
  turns these off again.

## Customizing

Most changes need no code: use the params, or the **Custom CSS** param for
small overrides.

For site-side CSS that survives upgrades, create
`templates/puffermedia-bsbasic/css/user.css` on the site. If it exists it loads
after `template.css`; it is never in the repo or the zip, so upgrades leave it
alone. Colours and fonts are CSS custom properties, so overriding them is
usually enough:

```css
:root {
  --bsbasic-accent: #0a6e5c;
  --bsbasic-body-font: "Inter", system-ui, sans-serif;
}
```

**Self-hosted fonts** (no external requests): put the font files in
`templates/puffermedia-bsbasic/fonts/` on the site, then in `user.css`:

```css
@font-face { font-family: "My Font"; src: url("../fonts/my-font.woff2") format("woff2"); font-display: swap; }
:root { --bsbasic-body-font: "My Font", system-ui, sans-serif; }
```

Leave the Body/Title Font params on Default when you do this: their inline
`:root{}` block loads after `user.css`.

**Animating your own elements:** add the class `bsbasic-reveal` to any element
and it joins the entrance animation. No JS or CSS change is needed.

## Joomla gotchas

- **Alternative Layout lists every template's layouts.** A module's Advanced
  → Layout picker shows overrides from every installed template, not only the
  active one. Cassiopeia's menu layouts (for example "Collapsible Dropdown")
  and its "Banner" layout produce markup this template has no CSS for, and
  Joomla's sample data assigns some of them. Pick **Default** or a
  `puffermedia-bsbasic` entry. The template re-provides core's menu layout in
  `html/mod_menu/default.php` so that option is always there.
- **Home page hero:** put a Custom module in `banner`, set a Background Image,
  and choose the `puffermedia-bsbasic` **Banner** layout for a full-bleed hero.
- **Module chrome:** a module's own Advanced settings can override the chrome
  a position asks for. If one module shows no card, check its settings first.
- **Favicon:** set it with the Favicon param. An old `favicon.ico` left in the
  template folder can still add a second icon next to a PNG or SVG pick;
  delete it.

## License

GNU General Public License version 2 or later, as declared in the template and
plugin manifests. Full text: [LICENSE.txt](LICENSE.txt).
