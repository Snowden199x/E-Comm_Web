# Buyer Messaging

**Status:** Implemented in code; manual verification pending.

Buyers can open a seller's shop from a product or order and choose **Chat with Seller**. The latest order with that seller, when present, appears in the thread. **Send order** posts a linked order card so the seller can open the correct order details. The buyer may also ask about one item from Order Details. A shop inquiry can start before the buyer places an order.

Seller chats support text, one private JPEG/PNG/WebP image up to 5 MB, unread counts, asynchronous sending, and incremental refresh. Only the buyer and seller in that conversation can read its messages and attachments. The buyer's separate Vendo Support conversation remains available in Messages.

The buyer notification bell and inbox show order updates, seller chat replies, review outcomes, and admin support replies. Opening a notification marks it read. The bell sounds for new notifications after the browser has accepted a user interaction.

**Related:** [Seller Messaging](../../seller/chat-messaging/spec.md) · [Buyer domain](../../../domains/Buyer.md)
## 25 September 2026 update

Order references in buyer–seller chat display the first available product photo in both existing and newly received messages. Open chats refresh about once per second while visible, and the seller chat list updates without page reload. Buyer support replies use the same visible-tab refresh cadence.

## 26 September 2026 update

The Vendo Support thread now renders its Alpine behavior inside the page instead of exposing script text. A support message can be sent asynchronously with text or an attachment; invalid empty sends return a validation response. Existing profile photos appear beside messages and in the seller conversation list, with initials when no photo exists. Buyers can end a support conversation but cannot delete support or buyer–seller conversation history. The notification bell offers **Mark all as read** next to **View all**.
