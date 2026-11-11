<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /** Human labels for the actions the application already writes to `admin_action_logs`. */
    public const ACTION_LABELS = [
        'registration_approved' => 'Registration approved',
        'registration_rejected' => 'Registration rejected',
        'user_suspended' => 'Account suspended',
        'user_deactivated' => 'Account deactivated',
        'user_activated' => 'Account activated',
        'suspension_lifted' => 'Suspension lifted',
        'deletion_approved' => 'Account deletion approved',
        'deletion_rejected' => 'Account deletion declined',
        'logistics_primary_set' => 'Primary Logistics coverage set',
        'seller_category_request_approved' => 'Seller category approved',
        'seller_category_request_rejected' => 'Seller category rejected',
        'product_approved' => 'Product approved',
        'product_rejected' => 'Product rejected',
        'product_warned' => 'Product warned',
        'delivery_rates_updated' => 'Commission rates changed',
        'policy_created' => 'Policy created',
        'policy_updated' => 'Policy updated',
        'policy_deleted' => 'Policy deleted',
        'announcement_created' => 'Announcement created',
        'announcement_updated' => 'Announcement updated',
        'announcement_deleted' => 'Announcement deleted',
        'chat_welcome_updated' => 'Chat welcome message changed',
        'limit_attempt_granted' => 'Extra delivery attempt granted',
        'return_window_extended' => 'Return window extended',
        'support_ticket_status' => 'Support ticket status changed',
        'logistics_document_approved' => 'Logistics document approved',
        'logistics_document_rejected' => 'Logistics document rejected',
        'admin_account_created' => 'Admin account created',
        'admin_account_suspended' => 'Admin account suspended',
        'admin_account_reactivated' => 'Admin account reactivated',
        'admin_account_archived' => 'Admin account archived',
        'admin_account_restored' => 'Admin account restored',
        'admin_account_deleted' => 'Admin account deleted',
        'complaint_status' => 'Complaint status changed',
        'account_report_approved' => 'Account report approved',
        'account_report_rejected' => 'Account report rejected',
        'review_report_resolved' => 'Review report resolved',
        'review_report_dismissed' => 'Review report dismissed',
    ];

    public function index(Request $request)
    {
        // The log names which Admin suspended, deleted or overrode what; only a super admin may read it.
        abort_unless(auth('admin')->user()?->is_super_admin, 403, 'Only a super admin can view the audit log.');

        $q = trim((string) $request->query('q', ''));
        $action = (string) $request->query('action', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $logs = AdminActionLog::query()
            ->with(['actor:id,name,email', 'target:id,name,email,role'])
            ->when(array_key_exists($action, self::ACTION_LABELS), fn ($query) => $query->where('action', $action))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('reason', 'like', $like)
                        ->orWhereHas('actor', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like))
                        ->orWhereHas('target', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like));
                });
            })
            ->when($from !== '', fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $usedActions = AdminActionLog::query()->distinct()->orderBy('action')->pluck('action')
            ->mapWithKeys(fn ($key) => [$key => self::ACTION_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key))]);

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'usedActions' => $usedActions,
            'q' => $q,
            'action' => $action,
            'from' => $from,
            'to' => $to,
            'label' => fn (string $key) => self::ACTION_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key)),
        ]);
    }
}
