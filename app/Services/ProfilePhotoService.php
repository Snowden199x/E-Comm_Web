<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoService
{
    public function replace(User $user, UploadedFile $file, string $directory): void
    {
        abort_unless(in_array($user->role, ['buyer', 'seller', 'logistics_center'], true), 403);

        $old = $user->profile_picture;
        $path = $file->store($directory, 'public');
        abort_unless($path, 500, 'The profile photo could not be stored.');

        try {
            DB::transaction(function () use ($user, $path) {
                $user->update(['profile_picture' => $path]);
                $this->notifyAdmin($user, 'updated');
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        $this->deleteOldPhoto($old);
    }

    public function remove(User $user): bool
    {
        abort_unless(in_array($user->role, ['buyer', 'seller', 'logistics_center'], true), 403);

        $old = $user->profile_picture;
        if (! $old) {
            return false;
        }

        DB::transaction(function () use ($user) {
            $user->update(['profile_picture' => null]);
            $this->notifyAdmin($user, 'removed');
        });

        $this->deleteOldPhoto($old);

        return true;
    }

    private function notifyAdmin(User $user, string $action): void
    {
        $role = $user->role === 'logistics_center' ? 'Logistics Center' : ucfirst($user->role);

        Notification::create([
            'user_id' => null,
            'type' => 'profile_photo_changed',
            'title' => $role.' profile photo '.$action,
            'message' => $user->name.' '.$action.' their profile photo.',
            'link' => route('admin.user-management.index', [
                'user_type' => $user->role,
                'search' => $user->email,
            ]),
        ]);
    }

    private function deleteOldPhoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'profile-pictures/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
