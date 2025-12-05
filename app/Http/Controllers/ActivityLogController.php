<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->latest();

        // Filter by user if provided
        if ($request->filled('user')) {
            $query->where('causer_id', $request->user);
        }

        // Filter by date range if provided
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                    ->orWhere('log_name', 'like', '%' . $request->search . '%');
            });
        }

        $logs = $query->paginate(20);

        return Inertia::render('Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['user', 'from', 'to', 'search']),
        ]);
    }

    public function list(Request $request)
    {
        $query = Activity::with('causer')
            ->latest();

        // Filter by user if provided
        if ($request->filled('user')) {
            $query->where('causer_id', $request->user);
        }

        // Filter by date range if provided
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                    ->orWhere('log_name', 'like', '%' . $request->search . '%');
            });
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function generateTest(Request $request)
    {
        $actions = [
            'Criou uma proposta',
            'Atualizou cliente',
            'Eliminou fornecedor',
            'Exportou relatório',
            'Criou evento no calendário',
            'Atualizou dados da empresa',
            'Adicionou artigo',
            'Gerou fatura',
            'Enviou email',
            'Alterou permissões',
        ];

        $menus = [
            'vendas',
            'gestão',
            'gestão',
            'financeiro',
            'calendário',
            'configurações',
            'configurações',
            'financeiro',
            'operacional',
            'acessos',
        ];

        $ips = [
            '192.168.1.100',
            '192.168.1.101',
            '10.0.0.50',
            '172.16.0.20',
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) Firefox/121.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
        ];

        for ($i = 0; $i < 10; $i++) {
            $userAgent = $userAgents[array_rand($userAgents)];
            $actionIndex = array_rand($actions);
            
            activity($menus[$actionIndex])
                ->causedBy(auth()->user())
                ->withProperties([
                    'ip' => $ips[array_rand($ips)],
                    'user_agent' => $userAgent,
                    'device' => $this->getDeviceName($userAgent),
                ])
                ->log($actions[$actionIndex]);
        }

        return back()->with('success', '10 logs de teste criados!');
    }

    private function getDeviceName(string $userAgent): string
    {
        if (str_contains($userAgent, 'iPhone')) {
            return 'iPhone';
        } elseif (str_contains($userAgent, 'Macintosh')) {
            return 'macOS';
        } elseif (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        } elseif (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }
        
        return 'Unknown';
    }
}
