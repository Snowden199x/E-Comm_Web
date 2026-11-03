# Admin Messaging

**Status:** Implemented basic conversation operations  
**Reviewed:** 7 October 2026

## Current behavior

Admin has one **Messages** inbox (31 October 2026 redesign). Each row is either a **complaint case** (`CMP-{year}-{id}`, one row per case with the buyer and the seller, the complaint type and the order) or a **direct** support conversation (`ADM-{year}-{id}`, the latest message as a preview). Tabs: All, Complaints, Direct Messages, each with a count. Filters: search (name, email, message ID, complaint type, order), date of the last message, and status (All status, Unread, Open, Closed; a resolved case counts as closed). The list shows the latest 100 matches. Selecting a row opens the conversation in the right pane; on phones the pane replaces the list and has a back button. A case pane has one tab per person (buyer, seller) and sends through the complaint route; a resolved case is read-only. A direct pane can attach one file (up to 5 MB, staged with a remove button before sending), and attachments open in the shared document popup. Deep links `?conversation={id}` and `?case={id}` open that pane.

Admin can delete a support conversation and its stored attachments from the support thread. Buyer and seller support deletion routes are absent. Threads and conversation lists show available profile photos with initials as a fallback. The notification bell offers **Mark all as read** next to **View all**.

## Gaps and acceptance direction

Ordinary support threads remain separate from case threads. Admin case messages are sent from the complaint page through `CaseMessageController`; Buyer/Seller recipients receive a private case link and can reply there. Each case conversation is scoped by complaint and participant. The ordinary support thread list excludes case conversations, and the ordinary support deletion endpoint cannot delete a case conversation. Courier mobile case messaging, case attachments, and moderation remain future work.

## Source evidence

`app/Http/Controllers/Admin/MessageController.php`, `app/Models/Communication/`

## Related documentation

See [domain status](../../../domain-feature-status.md), the relevant domain page, and [feature implementation guide](../../../feature-implementation-guide.md).
## 25 September 2026 update

The open thread refreshes about every 2.5 seconds and the list every 5 seconds while the tab is visible (background refreshes never dim the list or show errors). Opening a thread marks the other side's messages as read. Incoming support messages also create an admin notification. This uses short requests on the current Laravel server; broadcast infrastructure is not configured.
