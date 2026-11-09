DESIGN SYSTEM — Beauty Center (white + rose + gold). Follow exactly, invent nothing.

COLORS (CSS vars)
--rose-500 #B4506B (primary) | --rose-700 #8E3A55 (hover) | --rose-300 #E39FB2
--rose-100 #F7DEE5 | --rose-050 #FDF5F7 | --gold-500 #C9A24D (accent only)
--gold-300 #E3C88A | --gold-100 #F6EBD2 | --ink-900 #2E2126 | --ink-600 #6B565D
--line #EFE3E7 | --surface #FFFFFF | --canvas #FDF9FA
status: success #3E8E6E, warning #C98A2E, danger #C1453F

TYPE
Display/headings: "Reem Kufi" 600. Body/UI: "Tajawal" 300/400/500.
Sizes: display clamp(30-44), h1 32, h2 24, h3 19, body 16, small 14, caption 12.
line-height 1.25 headings / 1.7 body. Max line length 75ch. No ALL-CAPS labels.

SHAPE + SPACE
radius: sm 8 / md 14 (web cards) / lg 22 (mobile cards) / pill 999 (buttons, badges)
spacing scale: 4 8 12 16 24 32 48 64
shadow-sm 0 1px 2px rgba(94,36,54,.06) | shadow-md 0 8px 24px rgba(94,36,54,.09)

BUTTONS
pill shape, min-height 44px, font 14/500.
primary rose-500 -> hover rose-700, white text.
secondary transparent + 1px rose-300 border, rose-500 text.
gold gradient(gold-500, gold-300) with #3B2B08 text — max one per screen.
focus-visible: 2px solid gold-500, offset 2px. One primary button per screen.

LAYOUT
Web: fixed 200-240px sidebar on rose-050, white topbar, 24px gaps, 12-col content.
KPI cards white + 1px border, number in Reem Kufi rose-700; only one card gets a
3px gold top border. Tables: horizontal dividers only, row hover rose-050,
status always as a colored pill badge.
Mobile: bottom tab bar (max 5), 16px side padding, full-width primary button.

BILINGUAL
Support ar (RTL) + en (LTR) from one stylesheet: use logical properties
(margin-inline, padding-inline, border-inline-start, text-align:start).
Switch only dir + lang on <html>. Latin digits in both languages.

DO NOT: pure black, gold as a large background, more than 2 typefaces,
decorative gradients, heavy shadows, emoji as icons in the dashboard.