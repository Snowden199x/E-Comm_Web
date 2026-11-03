# Platform Content and Policies

**Status:** Implemented  
**Reviewed:** 3 November 2026

## Current behavior

Admin can create/update/delete announcements and platform policies, publish announcements to an audience/schedule, list them, and configure chat welcome content.

Platform settings page (Admin UI/UX pass, 3 November 2026):

- **Announcements.** Stat cards (All, Published, Scheduled, Drafts) filter the list; search and a single-date filter combine with them. The list refreshes in place with a loading bar, an error banner with "Try again", and separate empty states for "no announcements" and "no matches". The date filter matches the date shown in the table (go-live date for scheduled items, created date otherwise). One dialog creates, edits and deletes. Audience values are unchanged because the Buyer dashboard and Buyer API match on them.
- **Validation.** Title is required (255 characters), message is required (2,000 characters), audience and status must be one of the listed values. A scheduled announcement needs a future date and time. The date is stored only while the status is Scheduled; Draft and Published clear it.
- **Policies.** The four types are always listed. Adding a type creates an empty policy with version 1.0 and opens its editor. Editing saves the version and content and notifies the matching users; the editor says who before saving, and Save stays off until something changes. A policy with no content is hidden from users and marked "Empty" in the list. Delete is inside the editor and asks once more.
- **Chat greeting.** Required, up to 1,000 characters. Save stays off until the text changes.
- **Feedback.** Results use the shared banner (`session('success')`). Validation errors show under their field and the dialog reopens with the typed values.

## Gaps and acceptance direction

Add publication audit and history/version approval if policies require legally meaningful acknowledgements.

## Source evidence

`app/Http/Controllers/Admin/PlatformSettingsController.php`, `app/Models/Communication/Announcement.php`, `PlatformPolicy.php`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
