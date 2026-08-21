# Fieldnote Dusk — Design System

> Project-owned theme for Yankj's Knowledge. This file is the visual source of truth.

## Design idea

Fieldnote Dusk combines the calm editorial restraint of a product studio with the utility of a technical field notebook. The warm paper canvas and oversized, tightly tracked headings nod to dusk; twilight violet and ember are the project's own identifying signals.

Principles:

- Quiet structure, strong hierarchy, almost no decorative chrome.
- Warm, tactile surfaces instead of pure white or pure black.
- Violet communicates action and navigation; ember is used sparingly for emphasis.
- Reading comes first: 65–72 characters per line, 1.72 body line height, clear focus states.
- The layout is intentionally asymmetric: a narrow library rail, a generous reading column, and a quiet context rail.
- One 2px-stroke outline icon language is used for every utility control.
- Motion is subtle, 160–320ms, and disabled when reduced motion is requested.

## Core tokens

| Role              |     Light |      Dark |
| ----------------- | --------: | --------: |
| Canvas            | `#f6f4ef` | `#111217` |
| Surface           | `#fcfbf8` | `#18191f` |
| Raised surface    | `#ffffff` | `#202127` |
| Ink               | `#1b1c22` | `#f3f0e9` |
| Muted ink         | `#585b64` | `#b8b6b1` |
| Faint ink         | `#6b6e77` | `#92939a` |
| Border            | `#dfdcd4` | `#2d2f37` |
| Border strong     | `#c5c1b8` | `#444751` |
| Twilight / action | `#5652c7` | `#aaa7ff` |
| Ember / emphasis  | `#b95f42` | `#f09a77` |

## Typography

- Display and headings: `Inter`, system sans fallback.
- Body and Chinese text: `Noto Sans SC`, system sans fallback.
- Code and labels: `IBM Plex Mono`, system monospace fallback.
- Display tracking: `-0.04em`; body tracking: `-0.006em`; labels: `0.08em`.
- Article titles cap at `54.4px`; the home statement alone may reach `83.2px`.

## Scale

- Spacing: `4, 8, 12, 16, 24, 32, 48, 72px`.
- Radius: `4, 8, 14, 20px`, plus pill `999px`.
- Content measure: `70ch`; compact measure: `56ch`.
- Desktop columns: `256 / 736 / 224px` maximum, with responsive gaps.
- Shadows are soft and neutral; borders carry most of the hierarchy.

## Component rules

- Navigation tools use a shared 40px control height, restrained pills, and consistent outline icons.
- Active navigation uses a tinted violet surface plus an inset indicator.
- Cards and code blocks use the surface token, 14px radius, and a quiet border.
- Inline links retain an underline; navigation links do not.
- Tags are small outlined pills, never filled saturated badges.
- Images use a 14px radius and a one-pixel border.
- The home page suppresses duplicate body H1, properties, and reading metadata so the opening statement owns the first viewport.
- Article breadcrumbs show hierarchy only; the current-page segment is omitted because the title immediately follows it.
- Frontmatter remains indexed but its default property table is hidden; dates and tags carry visible metadata.
- Search uses a single-column result list with two-line summaries; the empty default preview pane is disabled.

## Accessibility and delivery

- Normal text contrast is at least 4.5:1 in both modes.
- Every interactive element has a visible `:focus-visible` ring.
- Touch targets are at least 44px on mobile.
- Hover effects never change layout.
- Verify at 375, 768, 1024, and 1440px without horizontal scrolling.
