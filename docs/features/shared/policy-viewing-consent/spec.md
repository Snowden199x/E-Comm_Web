# Policy Viewing and Consent

**Status:** Partial  
**Reviewed:** 26 September 2026

## Current behavior

Admin can store and display platform policies. Admin previews and Buyer/Seller policy notification dialogs share a formatted policy component. Ordered lists, bullet lists, headings, emphasis and paragraph spacing are preserved from the saved editor content. Rendering allows only supported formatting tags and attributes; scripts and arbitrary HTML attributes are discarded. Existing stored policies do not need to be re-entered.

## Account access

Buyer and Seller Account Management include a Policies section with the role policy and the Prohibited Item Policy when they have content. The Logistics portal links to a new authenticated `/logistics/account` page with the center's registered details and the same policy section. The new page requires an approved, active logistics-center account.

Policy rows show the saved version and update date. View policy opens a scrollable dialog in the current page; Escape, the close button and backdrop dismiss it. Numbering and formatting use the shared policy renderer. Content comes from Admin's saved policies on each account-page load, independent of notification read/deletion state. Opening a policy does not record consent or mark a notification read.

## Gaps and acceptance direction

Versioned user consent records and acceptance history are not evidenced.

## Source evidence

`app/Models/Communication/PlatformPolicy.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
