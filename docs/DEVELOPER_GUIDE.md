# WhatsApp Connector Developer Guide

This guide is for developers maintaining or extending `Azguards_WhatsAppConnect`.

## Module purpose

The module integrates Magento with an external WhatsApp/WhatTalk service for:

- syncing templates into Magento
- synchronizing customer contact data
- scheduling outbound campaigns
- sending transactional and abandoned-cart notifications

## Module entry points

Admin menu:

- `Marketing > WhatsApp > Templates`
- `Marketing > WhatsApp > Campaigns`

System configuration:

- section `whatsApp_conector`
- section `whatsapp_abandoned_cart`

Important:
The module uses the legacy spelling `whatsApp_conector` in config paths. Keep that spelling when reading or writing configuration values.

## Configuration paths

### General

- `whatsApp_conector/general/enable`
- `whatsApp_conector/general/base_url`
- `whatsApp_conector/general/authentication_api_url`
- `whatsApp_conector/general/client_id`
- `whatsApp_conector/general/client_secret_key`
- `whatsApp_conector/general/grant_type`
- `whatsApp_conector/general/project_name`
- `whatsApp_conector/general/enable_order`
- `whatsApp_conector/general/enable_invoice`
- `whatsApp_conector/general/enable_shipment`
- `whatsApp_conector/general/enable_cancellation`
- `whatsApp_conector/general/enable_credit_memo`
- `whatsApp_conector/general/enable_abandoned_cart`

### Cron

- `whatsApp_conector/cron/campaign_sync_schedule`
- `whatsApp_conector/cron/contact_sync_schedule`
- `whatsApp_conector/cron/template_sync_schedule`

### Template groups

Order flow templates live under:

- `whatsApp_conector/order_template/*`
- `whatsApp_conector/order_invoice_template/*`
- `whatsApp_conector/order_shipment_template/*`
- `whatsApp_conector/order_cancellation_template/*`
- `whatsApp_conector/order_credit_memo_template/*`

Abandoned cart lives under a separate section:

- `whatsapp_abandoned_cart/abandoned_cart_template/*`

## Runtime architecture

### 1. External API integration

Core HTTP/authentication behavior is centralized in:

- [ApiHelper.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Helper/ApiHelper.php)

Related service-layer classes include:

- [MetaWhatsAppApiClient.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/MetaWhatsAppApiClient.php)
- [TemplateService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/TemplateService.php)
- [MetaLibraryTemplateService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/MetaLibraryTemplateService.php)
- [MediaUploadService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/MediaUploadService.php)

### 2. Campaign flow

Campaign persistence and scheduler synchronization are centered in:

- [CampaignService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/CampaignService.php)

Current behavior:

- campaigns are saved in Magento
- the selected audience is resolved at save time
- the campaign is synchronized to the external scheduler
- the external job ID is stored in `scheduler_id`

Supporting classes:

- [CampaignPlaceholderResolver.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/CampaignPlaceholderResolver.php)
- [ExternalSchedulerService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/ExternalSchedulerService.php)

There is also older queue-oriented campaign code in the module, including `CampaignSchedulerService`, `CampaignWorkerService`, and the `azguards_whatsapp_campaign_queue` table. The active cron wiring in `etc/crontab.xml` currently schedules external sync, template sync, contact sync, and abandoned-cart processing, not the legacy `ProcessCampaigns` job.

### 3. Event-driven notifications

Observers are registered in [events.xml](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/etc/events.xml):

- `checkout_submit_all_after` -> `Observer\CreateOrderAfter`
- `sales_order_invoice_save_commit_after` -> `Observer\OrderFullInvoicePaid`
- `sales_order_shipment_save_commit_after` -> `Observer\OrderShipped`
- `order_cancel_after` -> `Observer\OrderCancel`
- `sales_order_creditmemo_save_commit_after` -> `Observer\OrderRefund`
- `cataloginventory_stock_item_save_after` -> `Observer\SendOutOfStockNotification`

The transaction-notification send pipeline is implemented through:

- [WhatsAppNotificationService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/WhatsAppNotificationService.php)
- [RecipientResolver.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/RecipientResolver.php)
- [CustomerDataBuilder.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/CustomerDataBuilder.php)
- [TemplateVariableResolver.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/TemplateVariableResolver.php)

### 4. Event configuration map

[EventConfig.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Config/EventConfig.php) maps event codes to:

- request type
- builder group
- enable flag
- whether contact sync is required

Supported event constants:

- `ORDER_CREATION`
- `ORDER_INVOICE`
- `ORDER_SHIPMENT`
- `ORDER_CANCELLATION`
- `ORDER_CREDIT_MEMO`
- `ABANDON_CART`

### 5. Abandoned cart flow

Abandoned cart behavior is configured through:

- [WhatsAppTemplateConfig.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Config/WhatsAppTemplateConfig.php)
- [ProcessAbandonedCarts.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Cron/ProcessAbandonedCarts.php)

Key behavior:

- cron runs every minute
- lock manager prevents overlap
- quotes are filtered by age and notification history
- sends are tracked in `azguards_whatsapp_abandoned_cart_notify`

## Database model

Declarative schema:

- [db_schema.xml](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/etc/db_schema.xml)

Main tables:

- `azguards_whatsapp_templates`
  Stores synchronized and locally managed template metadata.
- `azguards_whatsapp_campaigns`
  Stores campaign definitions, schedule data, status, counts, and external `scheduler_id`.
- `azguards_whatsapp_campaign_queue`
  Legacy/per-message queue storage still present in the schema.
- `azguards_whatsapp_abandoned_cart_notify`
  Tracks quotes already processed for abandoned-cart notifications.

## Customer attributes

Data patches add WhatsApp-related customer attributes:

- `whatsapp_phone_number`
- `whatsapp_country_code`
- `whatsapp_sync_status`
- `whatsapp_last_sync`
- `whatsapp_contact_id`

Relevant setup patches are under:

- [Setup/Patch/Data](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Setup/Patch/Data)

## Cron jobs

Scheduled jobs are defined in [crontab.xml](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/etc/crontab.xml):

- `whatsapp_connect_sync_templates`
- `whatsapp_connect_sync_campaigns`
- `whatsapp_connect_sync_contacts`
- `whatsapp_connect_process_abandoned_carts`

Notes:

- the first three use `config_path` values backed by the cron configuration fields
- abandoned cart processing is hard-coded to `* * * * *`

## Logging

Log handlers:

- [WhatsApp.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Logger/Handler/WhatsApp.php) -> `var/log/whatsapp_connector.log`
- [SyncProcess.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Logger/Handler/SyncProcess.php) -> `var/log/sync_process.log`

Use these logs first when debugging:

- authentication failures
- template synchronization problems
- campaign scheduler sync issues
- abandoned-cart delivery problems

## Important review findings

While reviewing the module, these documentation mismatches stood out:

- the previous docs claimed customer-registration messaging is active, but no corresponding observer is registered in `etc/events.xml`
- the previous docs listed a `message_base_url` config path, but that field is not present in the current `system.xml`
- the previous docs described cron behavior that did not match the current `crontab.xml`
- the README referenced `docs/ARCHITECTURE_REVIEW.md`, but that file is not present

## Safe extension points

### Add a new automatic notification

1. Add a new constant and mapping in [EventConfig.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Config/EventConfig.php).
2. Add the corresponding system configuration group in `etc/adminhtml/system.xml`.
3. Add or reuse a template config accessor in [WhatsAppTemplateConfig.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Config/WhatsAppTemplateConfig.php).
4. Register the observer in `etc/events.xml`.
5. Add the notification entry point in [WhatsAppNotificationService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/WhatsAppNotificationService.php).

### Add new template variables

Update the data-building and resolution pipeline here:

- [CustomerDataBuilder.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/CustomerDataBuilder.php)
- [TemplateVariableResolver.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/TemplateVariableResolver.php)
- [TemplateVariableExtractor.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/TemplateVariableExtractor.php)

### Add new media handling behavior

Relevant services:

- [MediaResolver.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/MediaResolver.php)
- [MediaPersistenceService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/MediaPersistenceService.php)
- [MediaDocumentService.php](/home/zubair/carl-fisher-skeleton/src/app/code/Azguards/WhatsAppConnect/Model/Service/MediaDocumentService.php)

## Verification checklist

- run `bin/magento setup:upgrade` after setup/data/schema changes
- flush cache after config or UI changes
- verify cron is running
- validate credentials in admin
- confirm templates sync into the Templates grid
- create a small campaign and confirm `scheduler_id` is saved
- test one transactional event and review both log files
