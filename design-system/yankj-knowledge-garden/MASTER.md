# Fieldnote Dusk — Design System

> Project-owned theme for Yankj's Knowledge. This file is the visual source of truth.

## Design idea

Fieldnote Dusk combines the calm editorial restraint of a product studio with the utility of a technical field notebook. The warm paper canvas and oversized, tightly tracked headings nod to dusk; twilight violet and ember are the project's own identifying signals.

Principles:

- Quiet structure, strong hierarchy, almost no decorative chrome.
- Warm, tactile surfaces instead of pure white or pure black.
- Violet communicates action and navigation; ember is used sparingly for emphasis.
- Reading comes first: 68–74 characters per line, 1.75 body line height, clear focus states.
- Motion is subtle, 160–320ms, and disabled when reduced motion is requested.

## Core tokens

| Role              |     Light |      Dark |
| ----------------- | --------: | --------: |
| Canvas            | `#f4f1ea` | `#111217` |
| Surface           | `#fbfaf6` | `#191a20` |
| Raised surface    | `#ffffff` | `#202129` |
| Ink               | `#17181e` | `#f1eee7` |
| Muted ink         | `#5f626c` | `#b2b2b0` |
| Faint ink         | `#85868d` | `#858790` |
| Border            | `#d8d4ca` | `#30323b` |
| Border strong     | `#beb9ae` | `#484b57` |
| Twilight / action | `#5b57d9` | `#9a97ff` |
| Ember / emphasis  | `#bd6546` | `#ef9874` |

## Typography

- Display and headings: `Inter`, system sans fallback.
- Body and Chinese text: `Noto Sans SC`, system sans fallback.
- Code and labels: `IBM Plex Mono`, system monospace fallback.
- Display tracking: `-0.045em`; body tracking: `0`; labels: `0.08em`.

## Scale

- Spacing: `4, 8, 12, 16, 24, 32, 48, 72px`.
- Radius: `4, 8, 14, 20px`, plus pill `999px`.
- Content measure: `72ch`; compact measure: `58ch`.
- Shadows are soft and neutral; borders carry most of the hierarchy.

## Component rules

- Navigation tools are compact pill controls with a 1px border.
- Active navigation uses a tinted violet surface plus an inset indicator.
- Cards and code blocks use the surface token, 14px radius, and a quiet border.
- Inline links retain an underline; navigation links do not.
- Tags are small outlined pills, never filled saturated badges.
- Images use a 14px radius and a one-pixel border.

## Accessibility and delivery

- Normal text contrast is at least 4.5:1 in both modes.
- Every interactive element has a visible `:focus-visible` ring.
- Touch targets are at least 44px on mobile.
- Hover effects never change layout.
- Verify at 375, 768, 1024, and 1440px without horizontal scrolling.
