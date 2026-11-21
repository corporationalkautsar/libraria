<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\DateFilter;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $activity = (string) $request->get('activity', '');
        $userId = (string) $request->get('user_id', '');

        $query = ActivityLog::query()->with('user');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('activity', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($activity !== '') {
            $query->where('activity', $activity);
        }

        if ($userId === 'sistem') {
            $query->whereNull('user_id');
        } elseif ($userId !== '') {
            $query->where('user_id', $userId);
        }

        DateFilter::apply($query, 'created_at', $request);

        $sortable = [
            'created_at' => 'created_at',
            'activity' => 'activity',
            'user_name' => 'user_name',
            'ip_address' => 'ip_address',
        ];
        $requestedSort = (string) $request->get('sort', '');
        $sort = array_key_exists($requestedSort, $sortable) ? $requestedSort : 'created_at';
        $direction = $requestedSort !== ''
            ? ($request->get('direction') === 'asc' ? 'asc' : 'desc')
            : 'desc';
        $query->orderBy($sortable[$sort], $direction)->orderBy('id', 'desc');

        $logs = $query->paginate(20)->withQueryString();

        $activities = ActivityLog::query()->distinct()->orderBy('activity')->pluck('activity', 'activity');
        $users = User::query()->orderBy('name')->pluck('name', 'id');

        return view('logs.index', compact(
            'logs', 'search', 'activity', 'userId', 'activities', 'users'
        ));
    }
}
