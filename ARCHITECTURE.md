# Architecture: ps_viewedproduct

## Purpose

A PrestaShop front-office widget module that tracks and displays recently viewed products in a widget on product pages, encouraging customers to revisit items they have already explored.

## Directory Structure

```
ps_viewedproduct.php            - Module class; cookie-based view tracking, widget rendering
views/templates/hook/           - Smarty template for the recently-viewed widget
upgrade/                        - SQL/PHP migration scripts
tests/                          - PHPUnit test stubs and PHPStan bootstrap
translations/                   - Locale string overrides
```

## Key Design Decisions

- **Cookie-based tracking**: Product view history is stored in a browser cookie (no database writes per view) to minimise server load.
- **WidgetInterface**: Implements `WidgetInterface` for theme-editor positioning.
- **Limited history**: The cookie stores only the N most-recent product IDs (configurable), oldest entries are dropped when the limit is exceeded.

## Extension Points

- Override `getWidgetVariables()` to enrich the product data (e.g., add review scores).
- Change the history storage from cookie to session for more robust tracking.

## Dependency Flow

```
ps_viewedproduct (Module + WidgetInterface)
  └─> hookActionProductListModifier() — records the viewed product ID into cookie
  └─> renderWidget()                  — renders the recently-viewed template
        └─> getWidgetVariables()
              └─> reads cookie, loads product data via Product ORM
```
