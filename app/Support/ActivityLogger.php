<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(string $activity, string $description, ?User $user = null): void
    {
        $user ??= auth()->user();

        try {
            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Sistem',
                'role' => $user?->role ?? 'sistem',
                'activity' => $activity,
                'description' => $description,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable) {
            // Logging must never break the main transaction.
        }
    }
}
