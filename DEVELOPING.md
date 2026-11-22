# Developing puffermedia-bsbasic

For people working on the template's code. How to install and configure it,
and what every param does for site owners: [README.md](README.md). What changed
and why: [CHANGELOG.md](CHANGELOG.md).

## Repository layout

```
base-template/              the template (edit this)
  templateDetails.xml       manifest: positions, params
  joomla.asset.json         named assets
  index.php                 main page layout
  component.php             component-only layout (?tmpl=component)
  error.php                 403 / 404 / 500 page
  scss/                     source: template.scss @use's one partial per area
  css/template.css          compiled output, committed (never hand-edit)
  js/                       template.js (back-to-top), sticky.js,
                            animate.js, sidebar-menu.js
  html/                     overrides: card chrome, mod_custom Banner,
                            mod_feed List and Tiles, mod_menu Default
  layouts/bsbasic/          admin form field layout (Animation Speed slider)
  language/en-GB/           .ini and .sys.ini
plugins/plg_system_bsbasicog/  companion plugin
package/                    package manifest (template + plugin)
clients/                    bespoke copies (see below)
scripts/                    build, package and scaffold scripts
tools/                      Dart Sass binary (gitignored)
dist/                       built zips (gitignored)
README.md                   using and configuring the template
LICENSE.txt                 GPL v2
DEVELOPING.md               this file
CHANGELOG.md                version history
```

SCSS partials: `_variables` (design tokens), `_base` (custom properties,
typography, buttons, forms), `_layout`, `_header`, `_branding`, `_topbar`,
`_search`, `_sections`, `_cards`, `_breadcrumbs`, `_content`, `_footer`,
`_back-to-top`, `_hero`, `_feed`, `_animations`, `_sidebar-menu`, `_dev`.

## Development

Run all scripts from the repo root. First, fetch Dart Sass (the build script
also does this the first time if you skip it):

```sh
scripts/install-dart-sass.sh
```

Work against the repo directly instead of installing a zip after every change:
symlink `base-template/` into your local Joomla site's `templates/` folder
(use `sudo` if the web server user owns that folder):

```sh
ln -s "$PWD/base-template" /path/to/joomla/templates/puffermedia-bsbasic
```

If an installed copy of the template is already in `templates/`, move it
outside `templates/` first. Do not install the zip on a site where the
template is symlinked: Joomla would write through the link into this repo.
Register the template under **System → Install → Discover** only if Joomla
does not already know it.

The web server user must be able to read the repo: execute permission on every
folder in the path and world-readable files in `base-template/`
(`chmod -R go+rX base-template`).

In the site's Global Configuration, turn **System Debug** on and **System
Cache** / **Conservative Cache** off. Then:

```sh
scripts/build-scss.sh --watch        # rebuild CSS on every save
scripts/build-scss.sh                # one-off build
```

- Styles: edit `base-template/scss/`. `css/template.css` is generated and
  overwritten on every build; it is committed so the template installs from
  the repo without a build.
- Markup: edit `index.php`, `component.php`, `error.php` and `html/`, then
  refresh the page.

## Building packages

| Script | What it does |
|---|---|
| `scripts/install-dart-sass.sh` | Downloads the standalone Dart Sass binary into `tools/` |
| `scripts/build-scss.sh [dir] [--watch]` | Compiles `<dir>/scss/template.scss` to `<dir>/css/template.css`. `dir` defaults to `base-template` |
| `scripts/package-template.sh [dir]` | Builds the CSS and writes `dist/<name>-<version>.zip`, taking name and version from `templateDetails.xml`. Leaves out `scss/`, `*.css.map` and `css/user.css`. Pass e.g. `clients/acme` for a client copy |
| `scripts/package-bundle.sh` | Runs `package-template.sh` and writes `dist/pkg_puffermedia_bsbasic-<version>.zip` (template + plugin) |
| `scripts/new-client.sh <slug>` | Scaffolds a bespoke copy (below) |
| `scripts/export-public.sh` | Maintainer only: publishes a tagged release from the private working repo to this public repo |

Versioning is semantic: patch for fixes and CSS tweaks, minor for new
backward-compatible features such as new params, major for anything that can
break an existing site (removing or renaming a param or module position).
Releases are tagged `v<version>`.

## Cloning a bespoke template

Most client differences (logo, colours, fonts, layout, sticky header, custom
code) are params, so **no fork is needed**. Scaffold a client copy only when a
site needs something params cannot express (a new layout, extra module
chrome, deeper SCSS changes):

```sh
scripts/new-client.sh acme
```

This copies `base-template/` to `clients/acme/`, renames every
`puffermedia-bsbasic` / `bsbasic` / `BSBASIC` occurrence (manifest name, CSS
classes, JS ids, language keys and filenames) to `acme` / `ACME`, resets the
version to 1.0.0 and builds the CSS. The copy installs alongside the base
template without colliding.

The copy is an **independent snapshot** with no merge path back: picking up
later base-template improvements is a manual diff-and-reapply. Slugs must be
lowercase letters and numbers, starting with a letter.

## For Bootstrap developers

The page loads Joomla's precompiled `bootstrap.min.css` (Bootstrap 5.3 on
Joomla 6.2), then `template.css`. There is no Bootstrap Sass in the build, so
Sass variables and maps (`$grid-breakpoints`, `$theme-colors`, …) can't be
changed. Every option works through `--bs-*` variables, classes and
`data-bs-*` attributes.

- The template sets `--bs-body-color`, `--bs-body-bg`,
  `--bs-body-font-family`, `--bs-border-color` and the full primary set in
  `scss/_base.scss`. When colour params are set, `index.php` emits one inline
  `:root{}` block overriding the primary set and each Bootstrap Theme Color.
- So `.text-*`, `.bg-*`, `.text-bg-*`, `.border-*`, `.link-*`, `.alert-*`,
  `.badge`, `.btn-*`, `.btn-outline-*` and `.list-group-item-*` follow the
  site palette. `.table-*` variants follow only with **Tables follow Theme
  Colors** on.
- Colour modes (`data-bs-theme`) are only used on the header `<nav>` for the
  dark header schemes.
- The header menu loads Bootstrap's Collapse or Offcanvas itself. Other
  components load through the **Bootstrap JS Components** param.

## Param implementation reference

What each Template Style param emits and where: classes, CSS variables and
helper functions. Labels, options and defaults are in
`base-template/templateDetails.xml`; the user-facing description of each param
is in README. Params not listed (logo, alt text, custom code, the theme colour
picker) do what their label says.

Most params follow one pattern: `index.php` reads the param and, when it isn't
the default, writes a CSS variable into one inline `:root{}` block
(`$wa->addInlineStyle()`) or adds a modifier class; `template.css` consumes the
variables. At default params no inline `<style>` block is emitted.

When a param is added, renamed or changes its default, update this table and
README in the same change.

### Details tab (Branding)

| Param | Implementation |
|---|---|
| Tagline (`tagline`) | Shown from md up, uppercase behind a thin divider; hidden on phones |
| Copyright Text (`copyrightText`) | `index.php` replaces `{year}` with `date('Y')` and `{sitename}` with the site name at render time |

### Appearance tab

| Param | Implementation |
|---|---|
| Primary Color (`primaryColor`) | Blank = follows Accent (`--bsbasic-primary: var(--bsbasic-accent)`). When set, emits the full `--bs-primary-*` set (`-rgb`, `-text-emphasis`, `-bg-subtle`, `-border-subtle`) in the inline `:root{}`. Drives resting links, `.btn-primary`, `.btn-outline-primary`, active pagination and the Primary header/footer scheme |
| Accent Color (`accentColor`) | Blank = `#ff0000` (`$accent` in `scss/_variables.scss`). Emitted as `--bsbasic-accent`; `bsbasicShade()` in `index.php` derives the 15% darker `--bsbasic-accent-dark` for hover and pressed states |
| Link Color (`linkColor`) | `--bsbasic-link`. Blank = Primary; hover stays `--bsbasic-accent-dark` |
| Body Background Color (`bodyBgColor`) | `--bsbasic-paper` and `--bs-body-bg`. Cards, header and footer keep their own surfaces |
| Container Background Color (`containerBgColor`) | Emits `--bsbasic-container-bg` and wraps `top-a`, `top-b`, main, `bottom-a` and `bottom-b` in one `div.bsbasic-main-inner` carrying the container class. Inside it the bands drop their own container class (`$inner`) so gutters don't double; the wrapper is a growing flex column (`scss/_layout.scss`) so the sticky footer still works; `.bsbasic-bottom` turns transparent inside it (`scss/_sections.scss`). Banner, top bar, header and footer stay outside. Blank = markup unchanged |
| Bootstrap Theme Colors (`bsSecondary` … `bsDark`) | Blank = Bootstrap's default. Each colour set emits `--bs-{c}`, `-rgb`, `-text-emphasis`, `-bg-subtle` and `-border-subtle` (the last three not for Light/Dark) in the inline `:root{}`, plus `.btn-{c}`, `.btn-outline-{c}`, `.text-bg-{c}` text colour and `.link-{c}:hover` rules, because `bootstrap.min.css` hard-codes those per colour. Shades and button text follow Bootstrap's own rules (`bsbasicThemeColor()`; output matches Bootstrap's for its own colours). Secondary overrides the template's "`.btn-secondary` follows Primary" rule |
| Body Font (`bodyFont`) | `--bsbasic-body-font`. System font stacks only; Default = `system-ui` sans stack |
| Title Font (`headingFont`) | `--bsbasic-heading-font`, h1–h6. Default = Georgia serif stack |
| Title Font Color (`headingColor`) | `--bsbasic-heading-color`: h1–h6, article h2–h4 and blog item titles. Blank = ink |
| Border Radius (`borderRadius`) | `--bsbasic-radius`: Square `0` / Soft `.5rem` (default, emits nothing) / Round `1rem`. `--bsbasic-radius-sm` is 80% of it (buttons, inputs, toggler, feed tiles). Tag pills and the search field stay pill-shaped |
| Tile Hover Effect (`animHover`) | Body class `bsbasic-hover-<value>`; rules in `scss/_animations.scss`. Targets `.mod-articles-item` and `.bsbasic-feed__tile` |
| Entrance Animation (`animEntrance`) | Loads `js/animate.js` only when set; settings travel as `data-bsbasic-anim*` attributes on `<html>`. Honours `prefers-reduced-motion`. See [Animations](#animations) |
| Animate (`animTargets`) | Sections = banner, `top-a`, `top-b`, bottom (the footer is excluded on purpose). With cards on, a section holding cards leaves the animating to them; bare ones (the banner) animate as a block. Sections only animates every band as a block |
| Blog Items (`animBlog`) | `.blog-items > .blog-item` always join the entrance animation when Animate includes cards. Entrance and hover adds the `bsbasic-blog-hover` body class: Lift on Boxed blog layouts only, Zoom on the intro image of any item |
| Animation Speed (`animSpeed`) | Sets `--bsbasic-anim-dur` when not 600. Joomla's core range layout has no value readout, so the field uses `layouts/bsbasic/range-ms.php` (`layout="bsbasic.range-ms"` plus `layoutIncludePath`; `layouts` is listed in the manifest's `<files>`) |
| Stagger (`animStagger`) | Cards revealed in the same scroll step get a delay of 2/15 of Animation Speed each (80 ms at 600), passed as `data-bsbasic-anim-step` on `<html>`; max 8 steps |

### Header & Footer tab

| Param | Implementation |
|---|---|
| Header Color (`headerScheme`) | Adds `bsbasic-header--dark` / `--primary` / `--accent` to `<header>` and `data-bs-theme="dark"` to the `<nav>` (light toggler icon); the SCSS swaps the `--bsbasic-nav-*` tokens. Drop-down panels stay paper. Formerly `navbarScheme`: a style saved under the old name falls back to Light |
| Header Font Color (`headerLinkColor`) | Overrides `--bsbasic-nav-fg` on `header.bsbasic-header` (one element deeper than the scheme modifiers). Hover/current colours and drop-down panels keep their own |
| Header Spacing (`headerSpacing`) | `--bsbasic-header-pad`, the navbar `padding-block`: Compact `.5rem` / Default `1rem` (emits nothing) / Roomy `1.5rem` |
| Header Shadow (`headerShadow`) | None adds `bsbasic-header--flat`, which removes the shadow including the sticky one |
| Menu Collapse Breakpoint (`collapseBreakpoint`) | Sets `navbar-expand-*`, `flex-*-row`, `align-items-*-center`, `gap-*-3` and a `bsbasic-header--bp-md/lg/xl` modifier; `_header.scss` emits the collapsed/desktop menu rules once per breakpoint under that modifier. The 992px content-indent queries are separate |
| Menu Alignment (`menuAlign`) | `ms-auto` / `mx-auto` / `me-auto` on the menu + search wrapper inside the navbar |
| Menu Style (`menuStyle`) | Pills / Underline add `bsbasic-header--nav-pills` / `--nav-underline`; rules in `_header.scss`, inside the per-breakpoint desktop query. Core `mod_menu` links have no `.nav-link`, so Bootstrap's `.nav-pills` / `.nav-underline` can't be used directly; the rules read `--bs-nav-pills-border-radius` and `--bs-nav-underline-border-width`. Pills target the whole top-level `li` (link and caret); the active fill is `--bsbasic-nav-pill-bg` |
| Mobile Menu (`mobileMenu`) | Offcanvas swaps `.collapse.navbar-collapse#bsbasicNav` for `.offcanvas.offcanvas-end/-start#bsbasicNav` (header with site name and close button) and loads `bootstrap.offcanvas` instead of `bootstrap.collapse`. The panel takes the header scheme (`--bs-offcanvas-bg: var(--bsbasic-nav-bg)`); a sticky header is lifted to `z-index: 1046` while it is open, and `sticky.js` keeps it shown |
| Sticky Header (`stickyHeader`) | Bootstrap `sticky-top` on `<header>` plus a soft shadow. Loads `js/sticky.js`, which measures the header into `--bsbasic-sticky-offset`; `html.bsbasic-has-sticky { scroll-padding-top }` uses it so anchor links stop below the header |
| Sticky Header Behavior (`stickyBehavior`) | Shrink: `is-scrolled` (past 60px, back under 10px) lowers `--bsbasic-header-pad` and the brand size. Hide: `is-hidden` slides the header away; scrolling up, opening the mobile menu or focus inside brings it back. Transitions off under `prefers-reduced-motion` |
| Footer Color (`footerColor`) | Adds `bsbasic-footer--light` / `--primary` / `--accent`; the SCSS swaps the `--bsbasic-footer-*` tokens |
| Footer Font Color (`footerLinkColor`) | `--bsbasic-footer-link` (falls back to `--bsbasic-footer-strong`). Footer headings and the hover underline are unchanged |

### Layout tab

| Param | Implementation |
|---|---|
| Layout Width (`layoutWidth`) | Fluid = `container-fluid`; Boxed = `container`, or the Boxed From class |
| Boxed Max Width (`boxedMaxWidth`) | 1320 is Bootstrap's own cap and emits nothing; other values emit one inline `.<container class>{max-width:Npx}` |
| Boxed From (`boxedFrom`) | `container-md` / `-lg` / `-xl` / `-xxl` replace `container` everywhere the template uses it (`$container`, so also `$bandContainer` and the Container Background wrapper) |
| Top Bar, Header and Footer Width (`bandWidth`) | Full width swaps the top bar, header nav and footer containers to `container-fluid` (`$bandContainer`); body sections stay boxed |
| Reading Width (`readingWidth`) | `--bsbasic-reading-width`, the measure of a solo article (no sidebar: `div.bsbasic-content--solo`). Default `46rem` emits nothing |
| Sidebar Width (`sidebarWidth`) | N in the sidebar `col-{bp}-N`; `index.php` subtracts N from the main column per populated sidebar |
| Sidebar Breakpoint (`sidebarStack`) | The `{bp}` in the sidebar and main `col-{bp}-N` classes; below it the columns stack |
| Content First on Small Screens (`contentFirst`) | Main column gets `order-1 order-{bp}-2`, left sidebar `order-2 order-{bp}-1` |
| Grid Gutter (`gridGutter`) | Default adds nothing (Bootstrap's 1.5rem on the main row, `g-4` on the bottom row). 0–5 adds `gx-N` to the main row and replaces `g-4` with `g-N` on the `bottom-a`/`bottom-b` row |
| Sidebar Menu (`sidebarMenu`) | Each sidebar is wrapped in `div.bsbasic-sidebar` (`--collapsible` when on). `js/sidebar-menu.js` (asset `tpl.bsbasic.sidebarmenu`, loaded only then) adds `.is-ready`, opens the branch holding the current item and toggles `is-open` / `aria-expanded` on `.mod-menu__toggle-sub`. Without JS the menu stays expanded. Needs the Default `mod_menu` layout |
| Sidebar Menu Dividers (`sidebarDividers`) | Adds `bsbasic-sidebar--divided`: a `--bsbasic-rule` hairline between top-level items of any sidebar menu |
| Content Indent (`contentIndent`) | `--bsbasic-indent`: left padding of the header, breadcrumbs, page headers, blog items and the bottom and footer bands, at 992px and wider only (boxed blog cards exempt). Default `5rem` emits nothing |
| Container Padding (`containerPadding`) | `--bsbasic-container-pad` on `div.bsbasic-main-inner`, replacing the container's `.75rem` gutter. Emitted only when Container Background Color is set and the choice isn't Default (`_layout.scss` falls back to `1.5rem`) |

### Bootstrap tab

| Param | Implementation |
|---|---|
| Extra Classes (`clsBody` … `clsFooter`) | Appended to `<body>`, `<header>`, `<nav class="navbar">`, `div.bsbasic-main`, `<main>`, both `<aside>`s, the bottom `.row` and `<footer>`. The `$extraCls()` closure keeps only letters, digits, `_`, `-`, `:` and spaces |
| Tables follow Theme Colors (`tableVariants`) | Emits `.table-{c}` for Primary (Primary, else Accent, else `#ff0000`) and every Theme Color that is set, via `bsbasicTableVariant()`: Bootstrap's `table-variant()` math (80% tint background, Light/Dark use the colour itself, text by contrast, striped 5% / hover 7.5% / active 10% / border 20% toward the text colour). `bootstrap.min.css` hard-codes these, so they never follow otherwise |
| List Groups follow Theme Colors (`listGroupVariants`) | `.list-group-item-{c}` reads `--bs-{c}-text-emphasis` / `-bg-subtle` / `-border-subtle`, so Primary–Danger follow the palette with no extra CSS. Yes also emits scoped `.list-group-item-light` / `-dark` rules (`bsbasicListGroupVariant()`) when Light/Dark are set. No emits scoped rules from Bootstrap's own colours for Primary and each set colour Secondary–Danger; the global `--bs-*` set is untouched either way |
| Bootstrap JS Components (`bsComponents`) | Alert, Carousel, Dropdown, Modal, Offcanvas, Scrollspy, Tab: `$wa->useScript('bootstrap.<name>')`. Tooltip, Popover, Toast: `HTMLHelper::_('bootstrap.<name>', $selector)` on `[data-bs-toggle="tooltip"]`, `[data-bs-toggle="popover"]` and `.toast`, which also initializes them (the tooltip ships in `bootstrap.popover`). Per page: the companion plugin adds menu item param `bsbasic_bs_components` (`forms/menuitem.xml`); `index.php` merges it from the active menu item only while the plugin is enabled (`PluginHelper::isEnabled()`) |
| Load Font Awesome (`fontAwesome`) | `$wa->useStyle('fontawesome')`: Joomla's bundled Font Awesome Free. Bootstrap Icons is not bundled, so it isn't offered |
| Breakpoint Indicator (`bpIndicator`) | Fixed badge at bottom centre, xs–xxl via `d-*` spans, no JS (`scss/_dev.scss`). Rendered only while System Debug is on |

### Advanced tab

| Param | Implementation |
|---|---|
| Back-to-Top Button (`backToTop`) | `js/template.js` is registered and loaded only when on (passive scroll listener, smooth `scrollTo`) |
| Back-to-Top Position (`backToTopPosition`) | Left adds `bsbasic-back-to-top--left` |
| Back-to-Top Appears After (`backToTopThreshold`) | Written to `data-threshold` on the button, read by `js/template.js` |
| Favicon (`favicon`) | `$this->addFavicon()` with a MIME type from the file extension. Core only auto-adds a template-folder `favicon.ico` when no ICO-type link exists, so a leftover ICO can add a second icon |
| Browser Theme Color (`themeColor`) | `<meta name="theme-color">`. Primary falls back to Accent, then `#ff0000` |
| Social Sharing Tags (`socialTags`) | Open Graph via `$this->setMetaData(..., 'property')`; a tag already set by another extension is kept. Per-article title, description, image and article dates come from the companion plugin |
| Twitter / X Card (`twitterCard`) | `summary_large_image` with a share image, otherwise `summary` |
| Custom CSS (`customCss`) | Added as the last inline `<style>`, so it wins over the template and params. Opening and closing `<style>` tags are stripped |

## Animations

Hover effects are pure CSS (`scss/_animations.scss`, selected by the
`bsbasic-hover-<value>` body class). Entrance animations are JS-driven:
`js/animate.js` marks targets with `bsbasic-anim-item` plus an effect class, an
`IntersectionObserver` adds `is-visible`, and the script strips the classes
after the transition so hover works again.

With an entrance effect set, `<html>` carries `bsbasic-anim-pending` from the
server, which hides the targets from the first paint. Its selector list in
`scss/_animations.scss` mirrors `CARDS` / `SECTIONS` in `js/animate.js`; keep
the two in step when adding a target kind. `animate.js` swaps it for its own
hidden classes, forces a reflow and observes two frames later, so targets
already in view animate on load. A CSS keyframe (`bsbasic-anim-failsafe`)
reveals everything after 2.5s if the script never runs.

Targets carry one shared class: `bsbasic-reveal` on cards and tiles
(`html/layouts/chromes/card.php`, `html/mod_feed/tiles.php`), and
`bsbasic-reveal bsbasic-reveal--section` on the section bands in `index.php`.
The remaining selectors are `.mod-articles-item`, `.blog-items > .blog-item`
and `.com-content-article__body .card`, because that is core or
editor-authored markup the template does not override. To animate a new
element, add `bsbasic-reveal` in its markup.

## Assets

`joomla.asset.json` registers the template's styles and scripts;
`index.php`, `component.php` and `error.php` load them with `useStyle()` /
`useScript()`. Bootstrap is registered in PHP from `media/vendor/bootstrap/`.
Asset `uri` values are bare filenames: Joomla adds the `css/` or `js/` folder
itself, so `css/template.css` would be silently dropped. The `?xxxxxx`
cache-buster is Joomla's site-wide media version (each asset defaults to
`version: auto`); the JSON's top-level `"version"` is metadata only, but keep
it in step with the manifest `<version>`.
