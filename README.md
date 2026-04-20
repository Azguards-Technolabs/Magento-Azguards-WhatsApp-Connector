# Azguards WhatsApp Connector for Magento 2

An enterprise-grade WhatsApp Cloud API integration for Magento 2. This module enables seamless communication with your customers via WhatsApp, supporting high-volume marketing campaigns and automated transaction notifications.

## Key Features

-   **Multi-Channel Messaging**: Support for Marketing Campaigns and Event-Driven notifications (Order, Invoice, Shipping, etc.).
-   **Advanced Template System**: Sync and manage WhatsApp templates with support for Image, Video, and Document headers.
-   **Intelligent Placeholder Mapping**: Automatically resolve dynamic Magento data (e.g., `{{order_id}}`, `{{customer_name}}`) to WhatsApp template placeholders.
-   **Resilient Media Resolution**: Automatic handling of media handles, URLs, and Document IDs.
-   **Enterprise Logging**: High-detail diagnostic logging capturing full request/response context for API troubleshooting.
-   **Queue-Based Dispatch**: High-performance background processing using Magento Cron for batch delivery.
-   **UI Guardians**: Integrity checks to prevent editing of executed campaigns.

## Documentation

-   [User Guide](docs/USER_GUIDE.md): Admin configuration and operational workflows.
-   [Developer Guide](docs/DEVELOPER_GUIDE.md): Architectural deep dives and extension patterns.
-   [Architecture Review](docs/ARCHITECTURE_REVIEW.md): Senior-level technical assessment.

## Installation

```bash
composer require azguards/module-whatsappconnect
php bin/magento module:enable Azguards_WhatsAppConnect
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Quick Start

1.  **Configure Credentials**: Navigate to `Stores > Configuration > WhatsApp Connector` and enter your API credentials.
2.  **Sync Templates**: Go to `Marketing > WhatsApp > Templates` and click **Sync Templates**.
3.  **Launch Campaign**: Create a new campaign, select an approved template, define your target audience, and schedule the delivery.

---
Developed with ❤️ by Azguards Technolabs.


