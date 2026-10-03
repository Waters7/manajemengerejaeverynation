<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SYSTEM → Audit Logs: who did what, to which record, and when.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.audit-logs', [
            'logs' => AuditLog::with('user')
                ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
                ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->integer('user')))
                ->when($request->filled('model'), fn ($q) => $q->where('auditable_type', $request->string('model')))
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
                ->latest('created_at')->latest('id')
                ->paginate(50)
                ->withQueryString(),
            'actions' => AuditAction::options(),
            'users' => User::whereIn('id', AuditLog::whereNotNull('user_id')->select('user_id'))->orderBy('name')->pluck('name', 'id'),
            'models' => AuditLog::whereNotNull('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type')->mapWithKeys(fn ($t) => [$t => class_basename($t)]),
        ]);
    }
}
