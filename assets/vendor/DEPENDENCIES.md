# Browser dependency register

These files are vendored so production pages do not execute mutable `latest` CDN resources. Update them deliberately, verify their SHA-256 digest, run the repository quality checks, and test the public, club, scoresheet and administrator pages before deployment.

| Local file | Upstream version/source | SHA-256 |
|---|---|---|
| `tailwindcss-3.4.17.js` | `https://cdn.tailwindcss.com/3.4.17` | `176e894661aa9cdc9a5cba6c720044cbbf7b8bd80d1c9a142a7c24b1b6c50d15` |
| `lucide-1.31.0.min.js` | `https://unpkg.com/lucide@1.31.0/dist/umd/lucide.min.js` | `f96167bbf0e73ae1031328116cc36ba633c71953d0ccce2e4b5cfc17c420f869` |
| `xlsx-0.20.3.full.min.js` | `https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js` | `cc015130aa8521e7f088f88898eba949ccdcbfb38df0bd129b44b7273c3a6f41` |
| `alpine-3.16.0.min.js` | `https://unpkg.com/alpinejs@3.16.0/dist/cdn.min.js` | `b737f25315f9519fe7e614749b7c5bf6864fdc2ee6a41c4bf0de90266770166c` |
| `leaflet-1.9.4.css` | `https://unpkg.com/leaflet@1.9.4/dist/leaflet.css` | `a7837102824184820dfa198d1ebcd109ff6d0ff9a2672a074b9a1b4d147d04c6` |
| `leaflet-1.9.4.js` | `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js` | `db49d009c841f5ca34a888c96511ae936fd9f5533e90d8b2c4d57596f4e5641a` |
| `html2pdf-0.10.1.bundle.min.js` | `https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js` | `85e6ee9ce246e3ae4424313f7e46a5ed860a28d757811de8dc9c43f306049d65` |

Leaflet's five image assets are the unmodified files distributed with Leaflet 1.9.4 and are stored in `images/`, matching the relative paths in its stylesheet.
