# Seller Messages

**Status:** Implemented in code; manual verification pending.

Buyers can open a seller's public shop from a product or order and start a chat. If they already have an order with that seller, the latest order appears as a pinned card in the conversation. They can send that order as a separate message card. An item-level **Ask about item** link keeps the item context. Buyers without an order can still start a shop inquiry. Existing order conversations remain available.

Buyer and seller messages use the same asynchronous thread: text, one private JPEG/PNG/WebP image up to 5 MB, incremental refresh while the page is visible, unread counts, and recipient notifications. The sender can share only an order belonging to that buyer and seller. Every thread read, send, and attachment request checks the signed-in participant. Images are served through an authorized route.

Seller Messages also keeps its separate Vendo Support conversation with admin. It supports text, JPEG/PNG/WebP/PDF attachments up to 5 MB, and close/reopen actions. Marketplace chats do not enter the admin support inbox.

**Related:** [Admin Messaging](../../admin/chat-messaging/spec.md) · [Buyer Messaging](../../buyer/chat-messaging/spec.md) · [Seller Notifications](../notification/spec.md)
## 25 September 2026 update

Shared order cards show a product photo when the order has one. The buyer chat list and open Vendo Support thread refresh without page reload. Incoming seller support messages create an admin notification. These updates use short requests; a WebSocket service is not installed.

## 26 September 2026 update

The support and buyer–seller message views show existing profile photos when available, falling back to initials. Sellers can end/reopen support conversations but cannot delete support or buyer–seller conversation history. The notification bell offers **Mark all as read** next to **View all**.
