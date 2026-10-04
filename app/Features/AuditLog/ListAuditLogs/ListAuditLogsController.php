<?php

namespace App\Features\AuditLog\ListAuditLogs;

use App\Models\User;
use App\Shared\Audit\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListAuditLogsController
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'type' => $request->string('type')->toString(),
            'id' => $request->integer('id') ?: null,
            'user' => $request->integer('user') ?: null,
            'event' => $request->string('event')->toString(),
            'from' => $request->date('from'),
            'to' => $request->date('to'),
        ];

        $logs = AuditLog::query()
            ->with('user')
            ->when($filters['type'], fn ($q, $type) => $q->where('auditable_type', $type))
            ->when($filters['id'], fn ($q, $id) => $q->where('auditable_id', $id))
            ->when($filters['user'], fn ($q, $id) => $q->where('user_id', $id))
            ->when(in_array($filters['event'], ['created', 'updated', 'deleted'], true), fn ($q) => $q->where('event', $filters['event']))
            ->when($filters['from'], fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($filters['to'], fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('audit-log::index', [
            'logs' => $logs,
            'filters' => $filters,
            'types' => AuditLog::query()->distinct()->orderBy('auditable_type')->pluck('auditable_type')
                ->mapWithKeys(fn (string $type) => [$type => class_basename($type)]),
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
