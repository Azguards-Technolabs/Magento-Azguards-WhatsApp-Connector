# Azguards_WhatsAppConnect (Magento 2)

`azguards/module-whatsappconnect`

Magento 2 module for syncing customers to WhatsApp, syncing WhatsApp templates, and sending WhatsApp template messages via:
- Marketing Campaigns (scheduled + queued delivery)
- Event-driven notifications (registration, order lifecycle)

## Docs
- User Guide: `src/app/code/Azguards/WhatsAppConnect/docs/USER_GUIDE.md`
- Developer Guide: `src/app/code/Azguards/WhatsAppConnect/docs/DEVELOPER_GUIDE.md`

## Quick Install
### From `app/code`
- Copy module to `app/code/Azguards/WhatsAppConnect`
- `php bin/magento module:enable Azguards_WhatsAppConnect`
- `php bin/magento setup:upgrade --keep-generated`
- `php bin/magento cache:flush`

### By Composer
- `composer require azguards/module-whatsappconnect`
- `php bin/magento module:enable Azguards_WhatsAppConnect`
- `php bin/magento setup:upgrade --keep-generated`
- `php bin/magento cache:flush`

## Quick Start (Admin)
- Stores → Configuration → WhatsApp Conector → WhatsApp Conector:
  - Enable module
  - Fill `Authentication Api URL`, `Client Id`, `Client secret`, `Grant Type`
  - Click “Generate Token” to validate credentials
- Marketing → WhatsApp → Templates: Sync templates, review status, preview
- Marketing → WhatsApp → Campaigns: Create campaign, select template + target, schedule and monitor sending


