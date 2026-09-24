# Platform Content and Policies

**Status:** Implemented  
**Reviewed:** 24 September 2026

## Current behavior

Admin can create/update/delete announcements and platform policies, publish announcements to an audience/schedule, list them, and configure chat welcome content.

## Gaps and acceptance direction

Add publication audit and history/version approval if policies require legally meaningful acknowledgements.

## Source evidence

`app/Http/Controllers/Admin/PlatformSettingsController.php`, `app/Models/Communication/Announcement.php`, `PlatformPolicy.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
