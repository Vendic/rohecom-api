# Vendic_RoheconApi

Rohecon (UniShop) RHC-API integration for Magento 2. Mirrors the original RHC-API URLs and JSON
responses, but every endpoint requires a valid Magento bearer token (no anonymous access).

## Endpoints

REST — `/V1/rhc-api/*`, bearer token required:

| Method | URL                                                    |
|--------|--------------------------------------------------------|
| GET    | `/welcome`                                             |
| GET    | `/product/:pid`                                        |
| POST   | `/product/websites`                                    |
| POST   | `/productimage/remove`                                 |
| GET    | `/category/:cid/info`                                  |
| GET    | `/category/roots`                                      |
| POST   | `/category/update-image`                               |
| GET    | `/category/remove-image/:cid`                          |
| GET    | `/partstreaminfo`                                      |
| GET    | `/attribute/:aid/options/list`                         |
| POST   | `/attribute/option/update`                             |
| GET    | `/attribute/frontend-labels/:attributeId`              |
| POST   | `/file/add`                                            |
| GET    | `/file/exists/:filename`                               |
| GET    | `/file/list/:productId`                                |
| GET    | `/file/remove/:productId/:filename`                    |
| GET    | `/file/update_position/:productId/:filename/:position` |

Frontend (public, no token) — `GET /rhc-api/version/`.

## Authentication

Grant the integration UniShop uses access to the **Vendic Rohecon API** ACL resource:
**System → Extensions → Integrations** → the UniShop integration → **API → Resource Access** →
enable *Vendic Rohecon API* (or just its *Products* / *Categories* / *Attributes* sub-resources).
UniShop keeps sending the same `Bearer` token.

## Install

The legacy `rohecom/unishop` package defines the same routes and `rhc-api` frontName, so remove it
first:

```bash
composer remove rohecom/unishop
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Notes
- Gallery-cleanup cron is off by default: **Stores → Configuration → Vendic → Rohecon API → Cron**.
