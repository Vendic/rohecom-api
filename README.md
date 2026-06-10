# Vendic_RoheconApi

Rohecon (UniShop) RHC-API integration for Magento 2.

It reproduces the entire original module — all REST endpoints, the `/rhc-api/version` frontend
endpoint, and the non-API behaviour (price observer, swatch console command, and the two cron
jobs) — using proper dependency injection and parameter-bound queries.

The HTTP contract is **identical** to the original so **UniShop needs no changes**. The only
functional differences are bearer-token validation instead of anonymous access, and all known
security issues fixed.

## REST endpoints (`/V1/rhc-api/*`)

| Method | URL                                                    | Service                                          |
|--------|--------------------------------------------------------|--------------------------------------------------|
| GET    | `/welcome`                                             | `WelcomeInterface::welcome`                      |
| GET    | `/product/:pid`                                        | `ProductInterface::getProductSkuById`            |
| POST   | `/product/websites`                                    | `ProductInterface::getWebsiteIdsBySku`           |
| POST   | `/productimage/remove`                                 | `ProductInterface::removeProductImage`           |
| GET    | `/category/:cid/info`                                  | `CategoryInterface::categoryInfoImageUrl`        |
| GET    | `/category/roots`                                      | `CategoryInterface::getRootCategoryIds`          |
| POST   | `/category/update-image`                               | `CategoryInterface::categoryUpdateImage`         |
| GET    | `/category/remove-image/:cid`                          | `CategoryInterface::categoryRemoveImage`         |
| GET    | `/partstreaminfo`                                      | `PartstreamInterface::partstreamInfo`            |
| GET    | `/attribute/:aid/options/list`                         | `AttributeInterface::attributeOptionsList`       |
| POST   | `/attribute/option/update`                             | `AttributeInterface::updateAttributeLabel`       |
| GET    | `/attribute/frontend-labels/:attributeId`              | `AttributeInterface::getAttributeFrontendLabels` |
| POST   | `/file/add`                                            | `FileInterface::fileAdd`                         |
| GET    | `/file/exists/:filename`                               | `FileInterface::fileExists`                      |
| GET    | `/file/list/:productId`                                | `FileInterface::fileList`                        |
| GET    | `/file/remove/:productId/:filename`                    | `FileInterface::unlinkFileFromProduct`           |
| GET    | `/file/update_position/:productId/:filename/:position` | `FileInterface::updatePosition`                  |

All REST routes require the ACL resource **`Vendic_RoheconApi::api`** (see *Authentication*).

## Frontend endpoint

- `GET /rhc-api/version/` → `Controller\Version\Index` — returns the module + Magento version and
  the demo-store flag (public, no token). The module version is resolved from Composer's installed
  metadata via `Service\ModuleVersion`.

## Non-API behaviour

| Type     | Class                                      | Trigger                                                                               |
|----------|--------------------------------------------|---------------------------------------------------------------------------------------|
| Observer | `Observer\ConfigurableProductPriceCleanup` | `catalog_product_save_before` — strips price/special_price from configurable products |
| Console  | `Console\UpdateSizeSwatchTextCommand`      | `bin/magento rohecom:catalog:update-swatch-text --attribute=<code>`                   |
| Cron     | `Cron\UpdateSizeTextSwatch`                | every 15 min — backfills `auto_maat` text swatches                                    |
| Cron     | `Cron\GalleryCleanupCron`                  | daily 05:17 — removes orphaned product gallery rows (disabled by default)             |

The swatch backfill is shared by the command and cron via `Service\SwatchTextUpdater`.

## Authentication

Grant the ACL resource `Vendic_RoheconApi::api` to the integration whose bearer token UniShop
already sends:

1. **System → Extensions → Integrations** → open/create the UniShop integration.
2. **API → Resource Access** → enable **Rohecon API (UniShop)** (`Vendic_RoheconApi::api`).
3. Save & (re)activate. UniShop keeps using the same `Bearer` token.

## Security fixes vs. the original

- **SQL injection** in `updateAttributeLabel` and the swatch SQL → parameter-bound `update()` /
  validated + quoted attribute codes.
- **Anonymous access** on every route → custom ACL resource + bearer validation.
- **Unvalidated uploads** (`categoryUpdateImage`, `fileAdd`) → MIME whitelist, strict base64
  decode, size limits, `basename()`/integer-derived paths (no traversal), `Filesystem` writer.
- **`ObjectManager` everywhere** → constructor dependency injection throughout.
- **`removeProductImage`** (was a no-op "Not implemented yet") now actually removes gallery
  entries; an empty request body still returns a safe 203 no-op.

## Config

Cron toggle: **Stores → Configuration → Vendic → Rohecon API → Cron → Gallery cleanup enabled**
(`vendic_rohecon_api/cron/gallery_cleanup_cron_enabled`, default off).

```bash
composer remove rohecom/unishop
bin/magento setup:upgrade
bin/magento setup:di:compile
```
