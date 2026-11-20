# Laravel Blade Component Library (Converted from React UI)

This directory contains production-ready Laravel Blade components converted from the React/Tailwind UI library (`apps/web/src/components/ui`).

## 1. Directory Structure & Component Inventory

| Component | Files / Recommended Path | Invocation Example |
|---|---|---|
| **Button** | `button.blade.php` | `<x-button variant="default" size="default">Submit</x-button>` |
| **Badge** | `badge.blade.php` | `<x-badge variant="secondary">Active</x-badge>` |
| **Input** | `input.blade.php` | `<x-input name="email" type="email" placeholder="you@domain.com" />` |
| **Textarea** | `textarea.blade.php` | `<x-textarea name="notes" placeholder="Enter notes..." />` |
| **Label** | `label.blade.php` | `<x-label for="email">Email Address</x-label>` |
| **Checkbox** | `checkbox.blade.php` | `<x-checkbox name="remember" id="remember">Remember me</x-checkbox>` |
| **Separator** | `separator.blade.php` | `<x-separator orientation="horizontal" />` |
| **Skeleton** | `skeleton.blade.php` | `<x-skeleton class="h-4 w-32" />` |
| **Spinner** | `spinner.blade.php` | `<x-spinner class="size-5" />` |
| **Icon** | `icon.blade.php` | `<x-icon name="chevron-down" class="size-4" />` |
| **Card** | `card/index.blade.php`, `header`, `title`, `description`, `action`, `content`, `footer` | `<x-card><x-card.header><x-card.title>Title</x-card.title></x-card.header><x-card.content>...</x-card.content></x-card>` |
| **Avatar** | `avatar/index.blade.php`, `image`, `fallback`, `badge`, `group`, `group-count` | `<x-avatar><x-avatar.image src="/avatar.jpg" /><x-avatar.fallback>JD</x-avatar.fallback></x-avatar>` |
| **Breadcrumb** | `breadcrumb/index.blade.php`, `list`, `item`, `link`, `page`, `separator`, `ellipsis` | `<x-breadcrumb><x-breadcrumb.list><x-breadcrumb.item><x-breadcrumb.link href="/">Home</x-breadcrumb.link></x-breadcrumb.item></x-breadcrumb.list></x-breadcrumb>` |
| **Table** | `table/index.blade.php`, `header`, `body`, `footer`, `row`, `head`, `cell`, `caption` | `<x-table><x-table.header><x-table.row><x-table.head>Col</x-table.head></x-table.row></x-table.header></x-table>` |
| **Tabs** | `tabs/index.blade.php`, `list`, `trigger`, `content` | `<x-tabs default="tab1"><x-tabs.list><x-tabs.trigger value="tab1">Tab 1</x-tabs.trigger></x-tabs.list><x-tabs.content value="tab1">Content</x-tabs.content></x-tabs>` |
| **Input Group** | `input-group/index.blade.php`, `addon`, `button`, `input`, `text`, `textarea` | `<x-input-group><x-input-group.addon align="inline-start">@</x-input-group.addon><x-input-group.input name="user" /></x-input-group>` |
| **Scroll Area** | `scroll-area/index.blade.php`, `bar` | `<x-scroll-area class="h-48">Long text...</x-scroll-area>` |
| **Accordion** | `accordion/index.blade.php`, `item`, `trigger`, `content` | `<x-accordion type="single"><x-accordion.item value="1"><x-accordion.trigger value="1">Question</x-accordion.trigger><x-accordion.content value="1">Answer</x-accordion.content></x-accordion.item></x-accordion>` |
| **Collapsible** | `collapsible/index.blade.php`, `trigger`, `content` | `<x-collapsible><x-collapsible.trigger>Toggle</x-collapsible.trigger><x-collapsible.content>Details</x-collapsible.content></x-collapsible>` |
| **Dialog** | `dialog/index.blade.php`, `trigger`, `content`, `header`, `title`, `description`, `footer`, `close` | `<x-dialog><x-dialog.trigger><x-button>Open</x-button></x-dialog.trigger><x-dialog.content><x-dialog.title>Title</x-dialog.title></x-dialog.content></x-dialog>` |
| **Sheet** | `sheet/index.blade.php`, `trigger`, `content`, `header`, `title`, `description`, `footer`, `close` | `<x-sheet><x-sheet.trigger><x-button>Sidebar</x-button></x-sheet.trigger><x-sheet.content side="right">...</x-sheet.content></x-sheet>` |
| **Popover** | `popover/index.blade.php`, `trigger`, `content`, `header`, `title`, `description` | `<x-popover><x-popover.trigger><x-button>Menu</x-button></x-popover.trigger><x-popover.content>...</x-popover.content></x-popover>` |
| **Tooltip** | `tooltip/index.blade.php`, `trigger`, `content` | `<x-tooltip><x-tooltip.trigger>Hover</x-tooltip.trigger><x-tooltip.content>Tooltip text</x-tooltip.content></x-tooltip>` |
| **Dropdown Menu** | `dropdown-menu/index.blade.php`, `trigger`, `content`, `item`, `group`, `label`, `separator`, `shortcut`, `sub`, etc. | `<x-dropdown-menu><x-dropdown-menu.trigger><x-button>Options</x-button></x-dropdown-menu.trigger><x-dropdown-menu.content><x-dropdown-menu.item>Profile</x-dropdown-menu.item></x-dropdown-menu.content></x-dropdown-menu>` |
| **Select** | `select/index.blade.php`, `trigger`, `value`, `content`, `item`, `group`, `label`, `separator` | `<x-select name="status" placeholder="Select status"><x-select.trigger /><x-select.content><x-select.item value="active">Active</x-select.item></x-select.content></x-select>` |
| **Combobox** | `combobox/index.blade.php`, `input`, `content`, `list`, `item`, `chips`, `chip`, etc. | `<x-combobox name="country"><x-combobox.input /><x-combobox.content><x-combobox.item value="us">United States</x-combobox.item></x-combobox.content></x-combobox>` |
| **Command** | `command/index.blade.php`, `input`, `list`, `empty`, `group`, `item` | `<x-command><x-command.input /><x-command.list><x-command.item>New File</x-command.item></x-command.list></x-command>` |
| **Sonner (Toaster)** | `sonner/index.blade.php` | `<x-sonner />` (listens to session flash & window `toast` events) |
| **Calendar** | `calendar/index.blade.php` | `<x-calendar name="booking_date" value="2026-11-20" />` |
| **Chart** | `chart/index.blade.php`, `tooltip`, `legend` | `<x-chart :config="['desktop' => ['color' => '#2563eb']]">...</x-chart>` |
| **Sidebar** | `sidebar/provider.blade.php`, `index`, `trigger`, `rail`, `inset`, `header`, `content`, `footer`, `menu`, `menu-item`, `menu-button`, etc. | Full responsive desktop collapsible sidebar + mobile drawer |

---

## 2. Setup in Laravel

### Option A: Standard Copy to `resources/views/components/`
Copy the contents of `apps/blade` directly into your Laravel project:
```bash
cp -r apps/blade/* resources/views/components/
```
In Laravel, `resources/views/components/button.blade.php` is invoked as `<x-button>`, and `resources/views/components/card/index.blade.php` is invoked as `<x-card>`, with `resources/views/components/card/header.blade.php` as `<x-card.header>`.

### Option B: Custom Component Namespace
In your `AppServiceProvider.php`:
```php
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Blade::anonymousComponentPath(base_path('apps/blade'), 'ui');
}
```
Then invoke via:
```html
<x-ui::button variant="default">Click me</x-ui::button>
<x-ui::card>
    <x-ui::card.header>
        <x-ui::card.title>Card Title</x-ui::card.title>
    </x-ui::card.header>
</x-ui::card>
```

---

## 3. Dependencies

1. **Alpine.js v3+**:
   Used for client-side state in interactive components (Dropdown, Modal, Sheet, Popover, Accordion, Tabs, Combobox, Sidebar, Calendar, Toaster).
   Add to `resources/js/app.js`:
   ```javascript
   import Alpine from 'alpinejs';
   import collapse from '@alpinejs/collapse';

   Alpine.plugin(collapse);
   window.Alpine = Alpine;
   Alpine.start();
   ```

2. **Tailwind CSS v4 (or Tailwind CSS v3 with CSS variables)**:
   Ensure your theme variables (`--background`, `--foreground`, `--primary`, `--secondary`, `--muted`, `--accent`, `--destructive`, `--border`, `--input`, `--ring`, `--radius`) are loaded in your global CSS.
