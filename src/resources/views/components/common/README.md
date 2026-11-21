# Laravel Common Blade Components

This directory contains production-ready Laravel Blade components converted from the React Common component library (`apps/web/src/components/common`).

All components integrate with the UI primitives in `resources/views/components/ui/` (`<x-ui.button>`, `<x-ui.badge>`, `<x-ui.dialog>`, `<x-ui.popover>`, `<x-ui.card>`, `<x-ui.icon>`, etc.).

## Component Catalog & Usage Guide

| Component | Blade Tag | Purpose |
|---|---|---|
| **Header** | `<x-common.header>` | Page header with eyebrow, title, description, and right actions slot |
| **HeaderActionButton** | `<x-common.header-action-button>` | Primary header button with standard sizing and gap |
| **IconBtn** | `<x-common.icon-btn>` | Styled icon button with multiple variants, shapes (circle, square), and sizes |
| **InputField** | `<x-common.input-field>` | Form input field wrapped with label, error display, and auto-ID |
| **TextareaField** | `<x-common.textarea-field>` | Form textarea wrapped with label, error display, and auto-ID |
| **SelectField** | `<x-common.select-field>` | Custom dropdown select wrapped with label, options list, and error handling |
| **Loader** | `<x-common.loader>` | Full-screen backdrop spinner loader |
| **Logo** | `<x-common.logo>` | Brand SVG logo with gold sheen/gradient, customizable size, and icon-only mode |
| **NoAccess** | `<x-common.no-access>` | 403 Forbidden / unauthorized state placeholder |
| **SectionHeading** | `<x-common.section-heading>` | Public section title with gold hairline underline |
| **DropdownProfile** | `<x-common.dropdown-profile>` | User avatar dropdown menu with profile link and POST logout form |
| **EmptyState** | `<x-common.empty-state>` | Empty state container with fine grid lines, ambient glow, icon, and actions |
| **ErrorState** | `<x-common.error-state>` | Error state container with destructive styling and retry action |
| **ErrorModal** | `<x-common.error-modal>` | Error alert dialog with icon, message, and retry button |
| **FeatureMarquee** | `<x-common.feature-marquee>` | Animated marquee ticker showcasing hotel amenities |
| **FilterSelect** | `<x-common.filter-select>` | Table/list filter dropdown with "All [Label]" default option |
| **EnumBadge** | `<x-common.enum-badge>` | Semantic color-mapped status badge for bookings, housekeeping, payments, etc. |
| **PageTransition** | `<x-common.page-transition>` | Smooth entrance and fade transitions for page views |
| **Pagination** | `<x-common.pagination>` | Standard table and list pagination with page range and navigation buttons |
| **RoomCard** | `<x-common.room-card>` | Hotel room display card with specs, price badge, amenity tags, and skeleton mode |
| **RoomGallery** | `<x-common.room-gallery>` | Interactive Alpine carousel with photo counter, thumbnails, and keyboard controls |
| **RoomTypeDetailsDialog** | `<x-common.room-type-details-dialog>` | Full room details modal with facts grid, rules, pricing, and booking CTA |
| **SearchableCommand** | `<x-common.searchable-command>` | Autocomplete searchable command palette with instant search filter |
| **Stat** | `<x-common.stat>` | Dashboard KPI metric stat card with icon, value, and trend description |
| **StatCard** | `<x-common.stat-card>` | Deluxe stat card with tone color palettes and background decorative SVG shapes |
| **AvailabilitySearch** | `<x-common.availability-search>` | Hotel reservation search bar with interactive check-in/out pickers and guests popover |
| **DataTable** | `<x-common.data-table>` | Full-featured data table with search, custom column slots, empty state, and pagination |
