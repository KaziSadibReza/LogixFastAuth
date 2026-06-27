# WordPress.org plugin assets

These files are **not** shipped in the plugin ZIP. They go in the **SVN `assets/` directory**
of your wordpress.org plugin repo (`https://plugins.svn.wordpress.org/logixfast-auth/assets/`),
which controls how the plugin looks on the directory page.

## Files

| Source (SVG) | Purpose | What to commit to SVN `assets/` |
|---|---|---|
| `icon.svg` | Plugin icon (lists + header) | `icon.svg` *(SVG is accepted directly)* and/or `icon-256x256.png`, `icon-128x128.png` |
| `banner-1544x500.svg` | Retina page banner | `banner-1544x500.png` |
| `banner-772x250.svg` | Standard page banner | `banner-772x250.png` |
| `logo.svg` | Master brandmark (your own use) | — |

> wordpress.org accepts **`icon.svg`** directly, so for the icon you can just commit `icon.svg`.
> **Banners must be PNG or JPG** — convert the banner SVGs below.

## Convert SVG → PNG

Use whichever tool you have installed.

**rsvg-convert** (librsvg):
```bash
rsvg-convert -w 256 -h 256 icon.svg            -o icon-256x256.png
rsvg-convert -w 128 -h 128 icon.svg            -o icon-128x128.png
rsvg-convert -w 1544 -h 500 banner-1544x500.svg -o banner-1544x500.png
rsvg-convert -w 772  -h 250 banner-772x250.svg  -o banner-772x250.png
```

**ImageMagick**:
```bash
magick -background none icon.svg -resize 256x256 icon-256x256.png
magick -background none icon.svg -resize 128x128 icon-128x128.png
magick banner-1544x500.svg banner-1544x500.png
magick banner-772x250.svg  banner-772x250.png
```

**Inkscape**:
```bash
inkscape icon.svg -w 256 -h 256 -o icon-256x256.png
inkscape banner-1544x500.svg -o banner-1544x500.png
```

> Note: banners contain text. Convert on a machine that has a "Segoe UI" / system
> sans-serif font (any modern Windows/macOS/Linux desktop) so the type renders correctly.

## Brand

- Primary: `#d6336c`  ·  Accent: `#ae3ec9`  ·  gradient used throughout.
- Mark: rounded security shield + "S" monogram.

The same shield+S mark is wired into the WordPress admin menu (see
`includes/Admin/class-logixfast-auth-admin-menu.php` → `menu_icon()`), rendered in the
sidebar grey `#a7aaad` so it matches WordPress's native icons.
