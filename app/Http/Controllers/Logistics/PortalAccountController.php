<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsDocumentRequest;

class PortalAccountController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string) $request->route('portal_role');
        $user = $request->user();
        $facility = $request->attributes->get('logistics_facility');
        $center = $role === 'company' ? $user->logisticsCenterDetail : null;

        return view('logistics.shared.live-account', [
            'role' => $role, 'user' => $user, 'facility' => $facility,
            'company' => $role === 'company' ? $center->company : $facility->company,
            'center' => $center,
            'documentRequests' => $center ? LogisticsDocumentRequest::query()
                ->where('logistics_center_id', $center->id)->latest()->limit(10)->get() : collect(),
            'pendingEmail' => DB::table('logistics_email_changes')
                ->where('user_id', $user->id)->where('expires_at', '>', now())->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $role = (string) $request->route('portal_role');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:24'],
            'business_name' => [$role === 'company' ? 'required' : 'prohibited', 'string', 'max:160'],
            'street' => [$role === 'company' ? 'nullable' : 'prohibited', 'string', 'max:160'],
            'house_no' => [$role === 'company' ? 'nullable' : 'prohibited', 'string', 'max:80'],
        ]);
        DB::transaction(function () use ($request, $role, $data) {
            $request->user()->update(['name' => $data['name'], 'phone_number' => $data['phone_number']]);
            if ($role === 'company') {
                $center = $request->user()->logisticsCenterDetail;
                $center->update(['business_name' => $data['business_name'], 'street' => $data['street'] ?? null,
                    'house_no' => $data['house_no'] ?? null]);
                $center->company->update(['business_name' => $data['business_name']]);
            }
        });

        return back()->with('success', 'Account details saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password updated.');
    }

    public function requestEmailChange(Request $request): RedirectResponse
    {
        $this->authorizeLogistics($request);
        $data = $request->validate([
            'new_email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users', 'email')],
            'current_password' => ['required', 'current_password'],
        ]);
        DB::table('logistics_email_changes')->where('expires_at', '<', now())->delete();
        if (DB::table('logistics_email_changes')->where('new_email', $data['new_email'])
            ->where('user_id', '!=', $request->user()->id)->exists()) {
            throw ValidationException::withMessages(['new_email' => 'This email has a pending change request.']);
        }
        $token = bin2hex(random_bytes(32));
        DB::table('logistics_email_changes')->updateOrInsert(['user_id' => $request->user()->id], [
            'new_email' => $data['new_email'], 'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $relativeUrl = URL::temporarySignedRoute('lgp.account.email.confirm', now()->addMinutes(30),
            ['token' => $token], absolute: false);
        $link = rtrim(config('app.url'), '/').$relativeUrl;
        try {
            Mail::raw('Confirm your Vendo Logistics email change within 30 minutes: '.$link
                .' Sign in with your current email before opening this link.',
                fn ($message) => $message->to($data['new_email'])->subject('Confirm your Vendo Logistics email'));
        } catch (\Throwable $e) {
            DB::table('logistics_email_changes')->where('user_id', $request->user()->id)->delete();
            throw ValidationException::withMessages(['new_email' => 'We could not send the verification email. Try again later.']);
        }

        return back()->with('success', 'Verification link sent to the new email. Your current email remains active until you confirm it.');
    }

    public function confirmEmailChange(Request $request): RedirectResponse
    {
        $this->authorizeLogistics($request);
        $token = (string) $request->query('token');
        abort_unless(strlen($token) === 64 && ctype_xdigit($token), 403);
        DB::transaction(function () use ($request, $token) {
            $pending = DB::table('logistics_email_changes')->where('user_id', $request->user()->id)
                ->lockForUpdate()->first();
            abort_unless($pending && \Illuminate\Support\Carbon::parse($pending->expires_at)->isFuture()
                && hash_equals($pending->token_hash, hash('sha256', $token)), 403,
                'This email change link has expired or was already used.');
            abort_if(User::query()->where('email', $pending->new_email)
                ->where('id', '!=', $request->user()->id)->exists(), 409,
                'This email is already used by another account.');
            $request->user()->forceFill(['email' => $pending->new_email, 'email_verified_at' => now()])->save();
            DB::table('logistics_email_changes')->where('user_id', $request->user()->id)->delete();
        }, 3);

        return redirect()->route(match ($request->user()->role) {
            'logistics_center' => 'lgp.company.account',
            'province_hub' => 'lgp.province.account',
            default => 'lgp.station.account',
        })->with('success', 'Your verified email address was updated.');
    }

    public function requestDocumentChange(Request $request): RedirectResponse
    {
        $this->authorizeLogistics($request);
        abort_unless($request->user()->role === 'logistics_center', 403);
        $data = $request->validate([
            'document_type' => ['required', 'in:valid_id,business_permit'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $center = $request->user()->logisticsCenterDetail;
        $path = $request->file('document')->store('private/logistics-document-updates/'.$center->id, 'local');
        try {
            DB::transaction(function () use ($request, $center, $data, $path) {
                \App\Models\Profiles\LogisticsCenter::query()->whereKey($center->id)->lockForUpdate()->firstOrFail();
                abort_if(LogisticsDocumentRequest::query()->where('logistics_center_id', $center->id)
                    ->where('document_type', $data['document_type'])->where('status', 'pending')->exists(), 409,
                    'A replacement for this document is already awaiting review.');
                LogisticsDocumentRequest::create([
                    'logistics_center_id' => $center->id, 'submitted_by' => $request->user()->id,
                    'document_type' => $data['document_type'], 'path' => $path, 'status' => 'pending',
                ]);
                Notification::create(['user_id' => null, 'type' => 'logistics_document_review',
                    'title' => 'Logistics document replacement',
                    'message' => $center->business_name.' submitted a '.str_replace('_', ' ', $data['document_type']).' for review.',
                    'link' => route('admin.logistics-documents.index')]);
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Document submitted for Admin review. The current approved document remains in use.');
    }

    private function authorizeLogistics(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['logistics_center', 'province_hub', 'station_hub'], true)
            && $user->status === 'approved' && ! $user->archived_at
            && in_array($user->account_status, [null, 'active'], true), 403);
        if ($user->role === 'logistics_center') {
            abort_unless($user->logisticsCenterDetail?->company?->status === 'approved', 403);
        } else {
            abort_unless($user->facilityStaff?->facility?->is_active
                && $user->facilityStaff->facility->company?->status === 'approved', 403);
        }
    }
}
