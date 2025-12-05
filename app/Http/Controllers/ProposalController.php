<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalLine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    public function index()
    {
        return Inertia::render('proposals/Index');
    }

    public function list(Request $request)
    {
        $query = Proposal::with('entity')->orderBy('date', 'desc');
        $proposals = $query->get(['id', 'number', 'date', 'valid_until', 'entity_id', 'status', 'total']);
        return response()->json(['data' => $proposals]);
    }

    public function show(Proposal $proposal)
    {
        $proposal->load('lines.article', 'entity');
        return response()->json($proposal);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'valid_until' => 'nullable|date',
            'entity_id' => 'required|integer|exists:entities,id',
            'status' => 'nullable|in:draft,closed',
            'lines' => 'required|array|min:1',
            'lines.*.reference' => 'nullable|string',
            'lines.*.name' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.cost_price' => 'nullable|numeric',
            'lines.*.supplier_id' => 'nullable|integer|exists:entities,id',
        ]);

        return DB::transaction(function () use ($data, $request) {
            // generate number
            $max = Proposal::max('number');
            $data['number'] = $max ? $max + 1 : 1;
            $data['total'] = 0;

            $proposal = Proposal::create([
                'number' => $data['number'],
                'date' => $data['date'],
                'valid_until' => $data[ ' valid_until'] ?? date('Y-m-d', strtotime($data['date'].' +30 days')),
                'entity_id' => $data['entity_id'],
                'status' => $data['status'] ?? 'draft',
            ]);

            $total = 0;
            foreach ($data['lines'] as $line) {
                $lineTotal = ($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0);
                $pl = ProposalLine::create([
                    'proposal_id' => $proposal->id,
                    'article_id' => $line['article_id'] ?? null,
                    'reference' => $line['reference'] ?? null,
                    'name' => $line['name'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'] ?? 1,
                    'unit_price' => $line['unit_price'] ?? 0,
                    'cost_price' => $line['cost_price'] ?? null,
                    'supplier_id' => $line['supplier_id'] ?? null,
                    'line_total' => $lineTotal,
                ]);
                $total += $lineTotal;
            }

            $proposal->total = $total;
            if (($data['status'] ?? 'draft') === 'closed') {
                $proposal->closed_at = now();
            }
            $proposal->save();

            // Log the activity
            activity('vendas')
                ->causedBy(auth()->user())
                ->performedOn($proposal)
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Criou proposta Nº ' . $proposal->number);

            return response()->json($proposal->load('lines'));
        });
    }

    public function convert(Proposal $proposal)
    {
        // Minimal stub: mark proposal as closed and return success.
        $proposal->status = 'closed';
        $proposal->closed_at = now();
        $proposal->save();

        // Log the activity
        activity('vendas')
            ->causedBy(auth()->user())
            ->performedOn($proposal)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Converteu proposta Nº ' . $proposal->number . ' em encomenda');

        return response()->json(['message' => 'Converted to order (stub).', 'proposal' => $proposal]);
    }
}
