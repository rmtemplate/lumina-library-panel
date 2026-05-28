---
name: Lumina Digital Library
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#44474d'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#75777e'
  outline-variant: '#c5c6ce'
  surface-tint: '#4e5f7e'
  primary: '#031632'
  on-primary: '#ffffff'
  primary-container: '#1a2b48'
  on-primary-container: '#8293b5'
  inverse-primary: '#b6c7eb'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#6cf8bb'
  on-secondary-container: '#00714d'
  tertiary: '#001828'
  on-tertiary: '#ffffff'
  tertiary-container: '#002e46'
  on-tertiary-container: '#2a9ad9'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d7e2ff'
  primary-fixed-dim: '#b6c7eb'
  on-primary-fixed: '#081b38'
  on-primary-fixed-variant: '#374765'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#cae6ff'
  tertiary-fixed-dim: '#8ccdff'
  on-tertiary-fixed: '#001e2f'
  on-tertiary-fixed-variant: '#004b6f'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.05em
  display-lg-mobile:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  margin-mobile: 20px
  gutter-mobile: 16px
---

## Brand & Style
The design system for this premium digital library is rooted in **Modern Academic** aesthetics. It balances the prestige of traditional academia with the frictionless efficiency of high-end SaaS. The target audience—university students, researchers, and faculty—requires a UI that facilitates deep focus and intellectual exploration.

The style is **Minimalist / Modern Corporate**, characterized by expansive white space, a restricted and authoritative color palette, and high-quality typography. The interface should feel "quiet" yet "powerful," evoking a sense of calm, organized intelligence. Subtle luxury is conveyed through generous padding and soft, layered depth rather than decorative ornamentation.

## Colors
The palette is led by **Oxford Navy**, providing an immediate sense of institutional trust and authority. This is balanced by a **Pure White** background to maintain a "paper-like" clarity for reading. 

**Emerald Green** serves as the primary action color, used for high-intent interactions like "Borrow" or "Download," while **Sky Blue** is reserved for secondary informational highlights or category tags. **Soft Gray** is used exclusively for background layering and subtle structural borders to prevent visual fatigue.

## Typography
**Inter** is utilized across all levels to ensure a systematic, utilitarian, yet modern feel. The hierarchy relies on substantial weight contrasts—using Bold for headings and Regular for body text—to guide the eye through dense academic catalogs.

Line heights are intentionally generous (1.5x - 1.6x for body text) to optimize for long-form reading on mobile devices. Letter spacing is slightly tightened for large displays to maintain a premium, "locked-in" look, while labels utilize increased tracking and uppercase styling for clear categorization.

## Layout & Spacing
The layout follows a **fluid grid** model tailored for mobile-first consumption. A standard 4-column grid is used for mobile views with 20px side margins to create a "breathing" frame around the content.

Spacing follows a strict 4pt / 8pt rhythmic scale. Components like book cards and list items should favor `lg` (24px) vertical spacing to emphasize individual importance and prevent a cluttered "discount bookstore" appearance. Content density should remain low to medium.

## Elevation & Depth
Depth is achieved through **Ambient Shadows** and **Tonal Layering**. The design system eschews heavy borders in favor of very soft, diffused shadows (Blur: 20px, Opacity: 4%, Y: 8px) that make cards appear to float slightly above the `Soft Gray` background.

- **Level 0 (Base):** Soft Gray (#F8F9FA) for the main app canvas.
- **Level 1 (Cards):** Pure White (#FFFFFF) with a soft ambient shadow.
- **Level 2 (Overlays):** Modals and Floating Action Buttons with a more pronounced shadow to indicate stack priority.
- **Level 3 (Navigation):** The bottom navigation bar uses a subtle top-border (1px, #E9ECEF) or a background blur effect to stay anchored.

## Shapes
The shape language is defined by large, inviting radii. A standard radius of **16px** (rounded-lg) is applied to all primary containers and cards, creating a friendly, modern silhouette. Interactive elements like buttons and input fields utilize a **12px** radius to maintain a cohesive look. 

Search bars and Floating Action Buttons may use **fully rounded (pill)** shapes to distinguish them as high-priority utility elements.

## Components
- **Premium Cards:** Feature a subtle 1px light gray stroke combined with an ambient shadow. Book covers should have a 4px internal radius.
- **Floating Action Buttons (FAB):** Circular or Pill-shaped, using the Emerald Accent (#10B981) with a white icon to represent primary actions like "Start Reading."
- **Form Inputs:** Utilize floating labels that transition from `body-md` to `label-md` upon focus. Use a 1.5px stroke for the active state in Oxford Navy.
- **Bottom Navigation:** A clean, minimalist bar with 24px outline icons. The active state is indicated by the Navy primary color and a small 4px dot below the icon.
- **Chips:** Used for genres and tags; they should be semi-transparent versions of the accent colors (e.g., Emerald at 10% opacity) with dark text for high legibility.
- **Progress Indicators:** Linear bars for reading progress should use a dual-tone Teal/Gray approach with rounded end-caps.