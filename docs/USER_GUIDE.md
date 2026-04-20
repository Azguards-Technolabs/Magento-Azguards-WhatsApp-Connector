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

### Campaign lifecycle (high-level)
- **Pending**: created but not yet scheduled/queued
- **Processing**: queued items are being sent
- **Completed**: all queued items processed (sent/failed)

### Success/Failures
Campaign shows:
- Sent count
- Failed count
- Error message (if applicable)

If a campaign has failures:
- Check log file `var/log/whatsapp_connector.log`
- Verify phone numbers are present
- Verify template is approved and matches expected variables
- Verify connector API/token is working

## Customer Sync from Customer Grid
Admin → Customers → All Customers

This module adds:
- **Bulk Sync WhatsApp** button (sync all customers)
- Mass Action: **Sync WhatsApp Contacts** (sync selected customers)

Use cases:
- Initial migration (bulk sync)
- Targeted re-sync for corrected phone numbers (mass sync)

## Cron jobs (important)
This module relies on cron for background work:
- Contact sync (scheduled hourly): `whatsapp_connect_sync_contacts`
- Campaign processing (runs every minute): `whatsapp_connect_process_campaigns`

To validate cron is running:
- Ensure Magento cron is installed (`bin/magento cron:install`) and system cron is active.
- Run manually for testing:
  - `php bin/magento cron:run`

## Logs and monitoring

### Log file
- `var/log/whatsapp_connector.log`

### What to look for
- Auth/token errors (invalid client/secret, wrong URL)
- 401 responses (expired token; module auto-refreshes in many cases)
- Template send failures (missing variables, invalid phone, template mismatch)

## Common issues / FAQ

### Cron is not running (most common)
Symptoms:
- Campaign stuck in pending/processing
- No new queue items are processed
- Customer sync doesn’t happen

Checks:
- Magento cron installed: `php bin/magento cron:install`
- System cron is active for the web user
- Run manually: `php bin/magento cron:run`
- Check `var/log/system.log` and `var/log/exception.log`

### “Generate Token” says error
Checklist:
- Client Id/Secret/Grant Type filled correctly
- Authentication Api URL is correct and accessible from the Magento server
- Server outbound HTTPS is allowed (firewall)

### Messages not sending even though campaign exists
Checklist:
- Cron running
- Campaign status transitions to processing
- Queue items exist (campaign scheduler populates queue)
- Customer has WhatsApp phone number

Optional deeper checks (developer help may be needed):
- Confirm queue rows exist in `azguards_whatsapp_campaign_queue` with `status=pending`
- Confirm templates exist in `azguards_whatsapp_templates` and match the selected template in the campaign/event config

### Some customers fail with “Missing mobile number”
This means the recipient phone is empty.
- Fix customer attribute `whatsapp_phone_number`
- Re-sync contacts if required
- Retry the campaign / re-run for those customers

Phone formatting tips:
- Prefer E.164 format, including country code (example: `+919999999999`)
- Avoid spaces and leading zeros; keep digits only (except the leading `+`)

### Template shows but preview/sending fails
Possible reasons:
- Template is not approved on WhatsApp/connector side
- Variable placeholders configured do not match template variables
- Template requires media header but media handle is missing

### Media header templates fail
If a template’s header format is `IMAGE` / `VIDEO` / `DOCUMENT`:
- Upload media (Admin config “Header Media”) to generate a **Media Handle**
- Ensure the correct handle is saved under the relevant event section (or campaign)

## Operational checklist (recommended)
- Credentials validated in Admin
- Templates synced and approved
- One event configured and tested end-to-end
- Cron confirmed working
- Log file monitored during initial rollout
