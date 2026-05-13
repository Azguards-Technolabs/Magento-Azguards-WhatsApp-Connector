# WhatsApp Connector User Guide

This guide is for Magento administrators and operations users working with `Azguards_WhatsAppConnect`.

## What the module does

The module adds WhatsApp messaging features to Magento in three main areas:

1. Template management
   Sync and manage WhatsApp templates inside Magento Admin.
2. Campaign management
   Create campaigns for customer groups or selected customers and sync them to the external WhatTalk scheduler.
3. Automated notifications
   Send WhatsApp notifications for supported commerce events and abandoned carts.

## Supported message flows

The module currently supports these automatic notifications:

- Order Created
- Order Invoice
- Order Shipment
- Order Cancellation
- Order Credit Memo
- Abandoned Cart

The current codebase does not register a live customer-registration observer, so customer-registration messaging should not be treated as active unless a developer adds that flow.

## Requirements

- Magento cron must be running.
- Valid WhatTalk credentials are required.
- Customers must have a usable WhatsApp phone number.
- WhatsApp templates should exist and be approved on the provider side before production use.

## Admin locations

Configuration:

- `Stores > Configuration > WhatsApp Conector > WhatsApp Conector`
- `Stores > Configuration > WhatsApp Abandoned Cart > WhatsApp Abandoned Cart`

Operational screens:

- `Marketing > WhatsApp > Templates`
- `Marketing > WhatsApp > Campaigns`

Note:
The configuration section uses the existing key and label spelling `whatsApp_conector` / `WhatsApp Conector` in code and admin.

## Initial setup

1. Enable the module in `Stores > Configuration > WhatsApp Conector > General Configuration`.
2. Fill in:
   - `Authentication Api URL`
   - `Client Id`
   - `Client secret`
   - `Grant Type`
   - `Project Name Suffix` if your template naming convention requires it
3. Click `Generate Authentication Credentials` to verify the credentials.
4. Save configuration.

## General configuration fields

### General Configuration

- `WhatsAppConector Enable`
  Master enable for the module.
- `API Base URL`
  Read-only base API endpoint.
- `Authentication Api URL`
  OAuth/OpenID token endpoint used for authentication.
- `Client Id`
  Provider-issued client ID.
- `Client secret`
  Provider-issued client secret.
- `Grant Type`
  Usually `client_credentials`.
- `Project Name Suffix`
  Appended to template names for uniqueness when needed.

### Event toggles

These switches control whether automatic sends are allowed for each event:

- `Enable Order Created WhatsApp Send Message`
- `Enable Order Invoice WhatsApp Send Message`
- `Enable Order Shipment WhatsApp Send Message`
- `Enable Order Cancellation WhatsApp Send Message`
- `Enable Order Credit Memo WhatsApp Send Message`
- `Enable Abandoned Cart WhatsApp Send Message`

## Template configuration in system config

Each supported event has its own template configuration group. These groups let you maintain template content directly in Magento and save it back through the module UI.

Common fields include:

- `Language`
- `Header Type`
- `Header Text`
- `Message Body`
- `Footer`
- `Live Preview`
- `Save Template`

Hidden fields such as `event_code`, `header_handle`, `header_image`, and `buttons_json` are maintained by the module.

Best practice:

- Finalize and approve template content outside production first.
- Use `Live Preview` before saving.
- Test one event flow end to end before enabling all event types.

## Template sync

Use `Marketing > WhatsApp > Templates` to review templates stored in Magento.

The module also provides a template sync cron job. Template sync pulls templates from the external service into Magento so they can be used in campaigns and previews.

Recommended process:

1. Confirm credentials.
2. Run cron or wait for the next template sync interval.
3. Verify templates appear in the Templates grid.
4. Confirm important templates are approved before using them in campaigns.

## Campaigns

Go to `Marketing > WhatsApp > Campaigns` to create or manage campaigns.

### Creating a campaign

1. Enter a campaign name.
2. Select a template.
3. Choose a target type:
   - Customer groups
   - Specific contacts
4. Select the audience.
5. Set schedule time and timezone.
6. Save the campaign.

### How campaigns work

Campaigns are synced to an external WhatTalk scheduler. Magento stores the campaign record and the returned `scheduler_id`.

After a campaign has been synchronized or processed, local edits may be restricted to avoid divergence from the external scheduler.

If you need a modified version of a completed or already-synced campaign, create a new campaign instead of reusing the old one.

## Customer WhatsApp fields

The module adds WhatsApp-related customer attributes such as:

- `whatsapp_phone_number`
- `whatsapp_country_code`
- `whatsapp_sync_status`
- `whatsapp_last_sync`

These fields are used for contact sync and message delivery.

## Abandoned cart

Abandoned cart settings are configured in a separate configuration section:

- `Stores > Configuration > WhatsApp Abandoned Cart > WhatsApp Abandoned Cart`

Important fields:

- `Consider Abandoned After (minutes)`
- `Max Quotes Per Cron Run`
- Template content fields such as `Header Text`, `Message Body`, and `Footer`

The abandoned cart cron runs every minute and only sends messages when:

- abandoned-cart messaging is enabled
- the quote is older than the configured threshold
- the quote has not already been recorded as notified
- a valid recipient phone number can be resolved

## Cron jobs

The module depends on Magento cron for background processing.

Configured jobs:

- Template sync
- Campaign sync from external scheduler
- Contact sync
- Abandoned cart processing

If campaigns, templates, or contacts are not updating, verify that Magento cron is healthy first.

## Logs and troubleshooting

Main logs:

- `var/log/whatsapp_connector.log`
- `var/log/sync_process.log`

Check these logs when:

- credential validation fails
- templates do not sync
- campaigns do not update
- abandoned cart sends are skipped or fail

## Common issues

### Credential validation fails

Recheck:

- `Authentication Api URL`
- `Client Id`
- `Client secret`
- `Grant Type`

Then review `var/log/whatsapp_connector.log`.

### Templates are missing

- Confirm cron is running.
- Confirm the external provider has templates available for the authenticated project.
- Review both log files for sync errors.

### Campaign saves but does not progress

- Verify Magento cron is running.
- Confirm the external scheduler accepted the campaign.
- Check whether the campaign record has a `scheduler_id`.

### Abandoned cart messages are not sent

- Confirm `Enable Abandoned Cart WhatsApp Send Message` is enabled.
- Check the abandoned-cart threshold and `Max Quotes Per Cron Run`.
- Confirm the quote/customer has a real WhatsApp phone number.

