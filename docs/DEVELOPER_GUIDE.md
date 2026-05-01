# WhatsApp Connector (Azguards_WhatsAppConnect) — Developer Guide

This document is for **Magento 2 developers / architects** maintaining or extending `Azguards_WhatsAppConnect`.

## Module overview

### Key responsibilities
- Persist and manage WhatsApp templates in Magento.
- Provide Admin UI to select templates, configure variable mapping, and manage campaigns.
- Enqueue message deliveries into a queue table and process via cron (batching).
- Sync customers as “contacts” to the connector API.
- Hook into Magento events (registration/order lifecycle) to enqueue event-driven messages.

### Admin entry points
- Menu: Marketing → WhatsApp
  - Templates: `whatsappconnect/template/index`
  - Campaigns: `whatsappconnect/campaign/index`
- System Config:
  - Tab/Section: `custom_tab` / `whatsApp_conector` (note the spelling: “Conector”)

## Configuration keys (core_config_data)

### General
- `whatsApp_conector/general/enable`
- `whatsApp_conector/general/base_url` (label-only in Admin; default from `etc/config.xml`)
- `whatsApp_conector/general/message_base_url` (label-only in Admin; default from `etc/config.xml`)
- `whatsApp_conector/general/authentication_api_url`
- `whatsApp_conector/general/client_id`
- `whatsApp_conector/general/client_secret_key`
- `whatsApp_conector/general/grant_type`

### Event mappings
Event configuration is centralized in:
- `Azguards\\WhatsAppConnect\\Model\\Config\\EventConfig`

For each event, it defines:
- template path
- variables path (serialized array)
- media_handle path
- request type
- whether contact sync is required

## Data model (DB)
Declarative schema: `src/app/code/Azguards/WhatsAppConnect/etc/db_schema.xml`

### `azguards_whatsapp_templates`
Stores template metadata synced from connector, including:
- `template_id` (connector-side id)
- `template_name`, `template_type`, `template_category`, `language`, `status`
- `header`, `header_format`, `header_handle`, `header_image`
- `body`, `footer`, `buttons`, `carousel_cards`, etc.

### `azguards_whatsapp_campaigns`
Stores campaign configuration:
- template reference (`template_entity_id`)
- target definition (group/customer lists)
- scheduling metadata (`schedule_time`, `is_scheduled`)
- status + counters (`sent_count`, `failed_count`)
- `variable_mapping` (JSON) and optional `media_handle` / `media_url`

### `azguards_whatsapp_campaign_queue`
Delivery queue:
- optional `campaign_id` (campaign vs event-driven send)
- `template_entity_id`, `customer_id`, `recipient_phone`
- per-message `variable_mapping`, `media_handle`, `media_url`
- `status`, `error_message`, `processed_at`

## Customer attributes (EAV)
Data patches in `src/app/code/Azguards/WhatsAppConnect/Setup/Patch/Data/` add:
- `whatsapp_phone_number`
- `whatsapp_country_code`
- `whatsapp_sync_status`
- `whatsapp_last_sync`

Admin grid integration:
- `view/adminhtml/ui_component/customer_listing.xml` adds mass action + bulk sync button.

## Message pipeline (campaigns)

### External API Scheduler (WhatTalk)
Campaigns are now fully offloaded to the external `WhatTalk` scheduling service.
1. `CampaignService::save()`: Forwards the campaign configuration and audience data directly to the `/scheduler-service/api/v1/schedule` API endpoint.
2. The local database simply mirrors the campaign metadata (target group, time) and the external `scheduler_id`.

Primary implementation:
- Service Integration: `src/app/code/Azguards/WhatsAppConnect/Model/Service/CampaignService.php`
- Placeholder resolution: `src/app/code/Azguards/WhatsAppConnect/Model/Service/CampaignPlaceholderResolver.php`

## Message pipeline (event-driven)
Magento event observers are registered in `src/app/code/Azguards/WhatsAppConnect/etc/events.xml`:
- `customer_save_commit_after` → `Observer\\CustomerSaveAfter`
- `checkout_submit_all_after` → `Observer\\CreateOrderAfter`
- `sales_order_invoice_save_commit_after` → `Observer\\OrderFullInvoicePaid`
- `sales_order_shipment_save_commit_after` → `Observer\\OrderShipped`
- `order_cancel_after` → `Observer\\OrderCancel`
- `sales_order_creditmemo_save_commit_after` → `Observer\\OrderRefund`

Each observer should:
- Check module enable flag
- Resolve event configuration (template + variables)
- Enqueue a queue row (event-driven rows have `campaign_id` empty/null)

Sending itself is still performed by the queue worker cron.

Note:
- Event-to-config mapping is defined in `src/app/code/Azguards/WhatsAppConnect/Model/Config/EventConfig.php`.

## Advanced Architecture & Patterns

### 1. Media Resolution Engine
Located in `Azguards\WhatsAppConnect\Model\Service\MediaResolver`. 
This service provides a centralized heuristic for resolving diverse media identifiers into API-compliant `media_id` or `link` values. It distinguishes between raw handles, Cloud API Document IDs, and public URLs, ensuring correct mapping even when input data is heterogeneous.

### 2. High-Performance Payload Building
`Azguards\WhatsAppConnect\Model\Service\MetaTemplatePayloadBuilder` manages the complexity of the Meta Cloud API JSON structure.
- **Dynamic Variable Mapping**: Automatically maps numeric placeholders (e.g., `{{1}}`) to descriptive attribute names (e.g., `order_id`) to ensure the ERP/Middleware receives human-readable keys.
- **Component Synchronization**: Synchronizes headers, footers, and buttons, ensuring the exact schema expected by the messaging API.

### 3. Senior Level Error Handling
Standardized in `Azguards\WhatsAppConnect\Helper\ApiHelper`.
- **`extractErrorMessage`**: A robust scanner that extracts meaningful error messages from deeply nested JSON responses.
- **Diagnostic Logging**: When a 400+ HTTP status is encountered, `callApi` forces a detailed log dump including:
  - Full Request Headers and Payload.
  - Full Raw Response Body.
- Logs are written to `var/log/whatsapp_connector.log`.

### 4. Campaign Integrity Guardians
- **Grid-Level Locking**: `CampaignActions` UI component hides the "Edit" button for campaigns that are `Completed`, `Processing`, or have a `Sent Count > 0`.
- **Validation Guard**: `CampaignService` enforces these restrictions at the service level to prevent programmatic modifications.

## Code Standards Compliance (Senior Architecture)
- The entire module strictly adheres to `Magento 2` PHP CodeSniffer standards with exactly 0 errors and 0 warnings.
- UI Grid Collections utilize native Magento Dependency Injection (`di.xml`) definitions via `<type>` and `<virtualType>` rather than hard-coded constructor overrides to satisfy code boundaries perfectly.
- No `phpcs:ignore` overrides are present in the core module UI/Service integrations!

## Extension Points

### Adding Custom Variables
To add new dynamic variables, extend `Azguards\WhatsAppConnect\Model\Service\TemplateVariableResolver` or the relevant `CustomerDataBuilder`.

---
*Senior Architecture & Documentation by Azguards Technolabs.*

## Connector API integration

### API helper
Core integration is in:
- `Azguards\\WhatsAppConnect\\Helper\\ApiHelper`

It handles:
- token retrieval/refresh and request headers
- template fetch
- contact sync
- message send
- caching (templates cached via Magento cache for 24 hours)

### Token validation (Admin “Generate Token”)
- UI button renderer: `Block/Adminhtml/System/Config/ValidateButton.php`
- AJAX controller: `Controller/Adminhtml/Validate/Credentials.php`
- Validates by calling `ApiHelper::getConnectorAuthentication(...)`

## Cron jobs
`src/app/code/Azguards/WhatsAppConnect/etc/crontab.xml` registers:
- `whatsapp_connect_sync_contacts` (hourly) → `Cron\\SyncUnsyncedContacts`
- `whatsapp_connect_process_campaigns` (every minute) → `Cron\\ProcessCampaigns`

## Logging
Monolog handler writes to:
- `var/log/whatsapp_connector.log`

Handler:
- `Azguards\\WhatsAppConnect\\Logger\\Handler\\WhatsApp`

## Extensibility points (recommended patterns)

### Add a new event-driven WhatsApp notification
1. Add a new observer entry in `etc/events.xml` (or `etc/frontend/events.xml` if needed).
2. Implement observer class under `Observer/`:
   - Validate module enabled
   - Resolve template + variables config path (follow `EventConfig` pattern)
   - Enqueue into `azguards_whatsapp_campaign_queue`
3. Add system config group/fields in `etc/adminhtml/system.xml`:
   - searchable template selector
   - variable mapping serialized field
   - optional media handle uploader
4. Add variable mapping UI block if needed under:
   - `Block/Adminhtml/Config/Form/Field/`

### Add a new API endpoint or payload strategy
- Prefer adding a dedicated service under `Model/Service/` and keep `ApiHelper` focused on HTTP mechanics.
- Keep payload building isolated (e.g., `MetaTemplatePayloadBuilder`, `CampaignPlaceholderResolver`).

## Local verification checklist (dev)
- `php bin/magento module:enable Azguards_WhatsAppConnect`
- `php bin/magento setup:upgrade --keep-generated`
- `php bin/magento cache:flush`
- `php bin/magento cron:run`
- Validate token in Admin config
- Sync templates (Admin)
- Create a small campaign for 1–2 customers and watch:
  - queue table rows
  - `var/log/whatsapp_connector.log`
