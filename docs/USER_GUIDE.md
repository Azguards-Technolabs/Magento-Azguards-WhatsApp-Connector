# WhatsApp Connector (Azguards_WhatsAppConnect) — User Guide

This document is written for **store admins / operations teams** who will configure and use the WhatsApp Connector inside Magento Admin.

## What this module does

### 1) Customer → WhatsApp contact sync
- Adds WhatsApp fields on customer profile (phone + country code).
- Allows syncing customers to the connector contact store (bulk + selected customers).

### 2) WhatsApp Templates management
- Syncs WhatsApp templates from the configured connector API into Magento.
- Lets you preview templates and use them in campaigns and event-driven messages.

### 3) Campaigns (Marketing → WhatsApp → Campaigns)
- Send a template to a selected audience:
  - By **Customer Group(s)**, or
  - By manually selected **Customers**
- Supports scheduling, queue processing, success/failure counts, and retrying.

### 4) Event-driven notifications (automatic)
When enabled/configured, the module can enqueue and send WhatsApp template messages for store events:
- Customer Registration
- Order Creation
- Order Invoice
- Order Shipment
- Order Cancellation
- Order Credit Memo (Refund)

These messages are processed via cron and follow the selected template + variable mapping configured in Admin.

## Requirements / Preconditions
- Magento 2 with cron running.
- Valid connector credentials (Client ID/Secret, Grant Type, Auth URL).
- WhatsApp templates available on the connector side (approved templates are recommended).
- For each recipient, a valid WhatsApp phone number (E.164 recommended, e.g. `+919999999999`).

## Installation (for admins/ops validation)
Installation is usually done by a developer, but after installation verify:
- Module enabled: `php bin/magento module:status Azguards_WhatsAppConnect`
- Setup upgraded: `php bin/magento setup:upgrade --keep-generated`
- Cache flushed: `php bin/magento cache:flush`
- Cron enabled (see “Cron jobs” below)

## Configuration (Admin)

### Admin path
Stores → Configuration → **WhatsApp Conector** → **WhatsApp Conector**

### General Configuration
**Enable**
- Turns the module on/off for store event handling and related operations.

**API Base URL (read-only)**
- Base URL for template/contact APIs.
- Default comes from module config and is shown read-only.

**Message API Base URL (read-only)**
- Base URL for message send API.
- Default comes from module config and is shown read-only.

**Authentication Api URL**
- Token endpoint (OpenID/OAuth token endpoint).

**Client Id / Client secret / Grant Type**
- Provided by the connector provider (usually `client_credentials` grant type).

**Generate Authentication Credentials (Generate Token)**
- Validates your credentials by calling the configured auth endpoint.
- Success indicates Magento can obtain a token.

### Event configurations (templates + variables + media)
Admin also contains separate sections (per event) to select a template and map variables:
- User Registration
- Order Creation
- Order Invoice
- Order Shipment
- Order Cancellation
- Order Credit Memo

Each event section typically includes:
- **Select with Search**: searchable template selector
- **Header Media (Image/Video/Doc)**: only needed when the selected template has a media header
- **Searchable content**: variable mapping UI (serialized field)

Best practice:
- Start with **one event** (e.g., Order Creation), configure it end-to-end, test, then enable others.

## Customer data setup (WhatsApp fields)
This module adds customer attributes:
- `whatsapp_phone_number` (visible in admin + customer account create/edit)
- `whatsapp_country_code` (visible in admin + customer account create/edit)
- `whatsapp_sync_status` (admin)
- `whatsapp_last_sync` (admin; typically hidden from forms)

### Where admins will see it
- Admin → Customers → All Customers:
  - WhatsApp fields can appear as grid columns depending on your grid configuration/indexing.
- Customer account forms:
  - WhatsApp phone and country code fields are available on create/edit (if the theme renders them).

## Templates (Marketing → WhatsApp → Templates)

### What “Sync Templates” does
Sync pulls templates from the connector and stores them in Magento for:
- Preview
- Use in Campaigns
- Use in event-driven messages

### Recommended flow
1. Configure credentials and validate token.
2. Sync templates.
3. Confirm template status is `APPROVED` (recommended).
4. Use approved templates in events/campaigns.

## Campaigns (Marketing → WhatsApp → Campaigns)

### Create a campaign
1. Choose a **Template**
2. Choose **Target Type**
   - Customer groups
   - Selected customers
3. Configure variable mapping (static values if needed)
4. Schedule time
5. Save

### Campaign Lifecycle & Integrity
- **Pending**: Created but not yet scheduled. Fully editable.
- **Processing**: Messages are currently being dispatched. **Editing is locked.**
- **Completed**: Execution finished. **Editing is locked** to preserve historical accuracy.

> [!IMPORTANT]
> To prevent data divergence, campaigns with `Sent Count > 0` or a status of `Completed`/`Processing` cannot be edited. If you need to send a similar campaign, please create a new one.

### Success/Failures & Retries
If a campaign has failures:
-   **Sent/Failed Counters**: The grid displays real-time counts of successful vs failed deliveries.
-   **Retry Action**: Click "Retry Failed" in the grid actions to re-enqueue only the items that failed (e.g., due to temporary API issues).

---

## 8. Logs and Advanced Monitoring

### Diagnostic Log File
-   Path: `var/log/whatsapp_connector.log`

### Senior Level Diagnostics
The module now includes high-detail monitoring. If an API call fails (HTTP 400+), the log will automatically capture:
-   **Request Context**: The exact URL, headers, and payload sent.
-   **Response Context**: The full raw response from the API.
-   This allows developers to troubleshoot 500 errors or structural issues without needing additional debugging tools.

---

## 9. Common issues / FAQ

### "Generate Token" Error
Check your credentials. The log will contain the specific reason returned by the authentication server.

### Messages show "Sent: 0" but API says success
This usually means a status mapping issue. Ensure your Magento cron is running and check the `whatsapp_connector.log` for any "Success Detection" warnings.

---
*Operational Guide by Azguards Technolabs.*
