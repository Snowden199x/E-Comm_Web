# Buyer Support Tickets

**Status:** Implemented in code (Buyer pages, Admin inbox, notifications, private attachments); owner migration and verification pending
**Reviewed:** 11 November 2026

Buyer asks Vendo for help; Admin answers and closes the request. Separate from **complaints**, which are reports about a Seller or an order problem and stay in Complaints & Disputes.

## Buyer

- **`GET /buyer/support`** (`buyer.support.index`): the Buyer's own tickets only, newest activity first, 10 per page, with Open / In review / Resolved counts. Linked from the buyer account menu as **Support**. Empty state points to the new-request page.
- **`GET /buyer/support/create`**: topic (Order or delivery, Payment or refund, Account and login, Product or shop question, Something else), optional link to one of the Buyer's own orders (last 40), subject (3–140) and the first message (10–5000). A foreign order id is rejected server-side.
- **`GET /buyer/support/{ticket}`**: the thread, staff replies labelled **Vendo Support**, plus a reply box. A reply on a resolved ticket is refused. While unresolved the Buyer can **mark it resolved**; the resolved ticket then shows a closed note instead of the reply box.
- **`POST /buyer/support/{ticket}/messages`** (throttle 20/min) and **`POST /buyer/support/{ticket}/close`**.
- **Attachments:** the new-request and reply forms accept up to two files (JPG/PNG/WebP/PDF, 5 MB each). Files go to the private disk (`support/{ticket}`) and are never public URLs; the Buyer opens only files on their own ticket through `buyer.support.attachments.show` (403 otherwise). The files show under the message with name and size.

## Admin

- **`GET /admin/support-tickets`** (`admin.support-tickets.index`), sidebar **Support Tickets** under Users & Safety with a badge for open + in-review. Counts cards filter by status; search covers ticket id, subject, Buyer name and email; topic select; page number in the URL.
- **`GET /admin/support-tickets/{ticket}`**: Buyer (name, email, phone), linked order, the full thread and a reply box. Replying to an `open` ticket moves it to `in_review`.
- **`PATCH /admin/support-tickets/{ticket}/status`** sets Open / In review / Resolved; resolving notifies the Buyer. A resolved ticket cannot be replied to until its status changes.
- New ticket or Buyer reply creates an Admin operational notification (`user_id = null`) linking to the thread; an Admin reply or a resolution notifies the Buyer.
- The Admin reply form accepts the same two attachments and lists them in the thread; `admin.support-tickets.attachments.show` streams any ticket file for the authenticated Admin. Ticket list counts are unchanged.

## Rules

- One ticket belongs to one Buyer; Admin sees all. Nothing here changes an order, a stock level or money.
- Messages store `is_staff`, so staff replies keep the **Vendo Support** label even if the Admin account is renamed later.
- No email copy, no SLA timer and no canned replies yet. Admin resolution is a status change only. Attachments are stored on the private disk and are not scanned or virus-checked; they are validated by extension/MIME and size only.
- Attachment files are not deleted when a ticket or message is removed from the database (no delete UI yet).

## Source evidence

`app/Http/Controllers/Buyer/SupportTicketController.php`, `app/Http/Controllers/Admin/SupportTicketController.php`, `app/Services/SupportTicketService.php`, `app/Models/Support/`, `app/Http/Controllers/SupportTicketAttachmentController.php`, `resources/views/buyer/support/`, `resources/views/admin/support-tickets/`, `database/migrations/2026_11_11_171000_create_support_tickets_tables.php` and `2026_11_11_182000_create_support_ticket_attachments_table.php`.

## Related documentation

See [Admin complaints and disputes](../../admin/manage-complaints-and-disputes/spec.md), [Buyer notifications](../notifications/spec.md), and [domain status](../../../domain-feature-status.md).
