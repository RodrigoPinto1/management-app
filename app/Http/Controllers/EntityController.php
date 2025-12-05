<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Services\ViesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class EntityController extends Controller
{
    public function index(Request $request, string $type = null)
    {
        // type can be 'client' or 'supplier'
        $type = $type ?: $request->query('type', 'client');

        return Inertia::render('entities/Index', [
            'type' => $type,
        ]);
    }

    public function list(Request $request)
    {
        $type = $request->query('type', 'client');

        $query = Entity::query();

        if ($type === 'client') {
            $query->where('is_client', true);
        } elseif ($type === 'supplier') {
            $query->where('is_supplier', true);
        }

        $entities = $query->orderBy('name')->get([
            'id',
            'number',
            'nif',
            'name',
            'phone',
            'mobile',
            'website',
            'email',
            'is_active',
        ]);

        return response()->json(['data' => $entities]);
    }

    public function checkNif(Request $request)
    {
        $nif = $request->query('nif');

        if (!$nif) {
            return response()->json(['exists' => false]);
        }

        $exists = Entity::where('nif', $nif)->exists();

        return response()->json(['exists' => $exists]);
    }

    public function store(Request $request, ViesService $viesService)
    {
        // Allow `name` to be nullable here; we'll try to fill it from VIES when possible
        $data = $request->validate([
            'is_client' => 'boolean',
            'is_supplier' => 'boolean',
            'nif' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country_id' => 'nullable|integer',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'rgpd_consent' => 'boolean',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (!empty($data['nif']) && Entity::where('nif', $data['nif'])->exists()) {
            return response()->json(['message' => 'NIF already exists'], 422);
        }

        // If name is empty but a NIF was provided, try to fetch it from VIES
        if (empty($data['name']) && !empty($data['nif'])) {
            try {
                $vies = $viesService->lookup($data['nif']);
                if (!empty($vies['valid']) && !empty($vies['name'])) {
                    $data['name'] = $vies['name'];
                }
                if (empty($data['address']) && !empty($vies['address'])) {
                    $data['address'] = $vies['address'];
                }
            } catch (\Exception $e) {
                // Do not block creation on VIES failures; keep behaviour minimal.
                // Optionally log the exception for later inspection.
            }
        }

        // assign incremental number if not provided
        if (empty($data['number'])) {
            $max = Entity::max('number');
            $data['number'] = $max ? $max + 1 : 1;
        }

        // Final check: name must exist now (either provided by user or by VIES)
        if (empty($data['name'])) {
            return response()->json(['message' => 'Name is required (provide name or a NIF that VIES can resolve)'], 422);
        }

        $entity = Entity::create($data);

        // Log the activity
        $type = ($data['is_client'] ?? false) ? 'Cliente' : 'Fornecedor';
        activity('gestão')
            ->causedBy(auth()->user())
            ->performedOn($entity)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log("Criou {$type}: {$entity->name}");

        // If the request comes from Inertia (X-Inertia header), return a redirect
        // so Inertia receives a proper Inertia response instead of plain JSON.
        if ($request->header('X-Inertia')) {
            // choose route based on type
            $routeName = ($data['is_client'] ?? false) ? 'clients' : (($data['is_supplier'] ?? false) ? 'suppliers' : 'clients');
            return redirect()->route($routeName);
        }

        return response()->json($entity);
    }

    /**
     * VIES lookup wrapper
     * Accepts query param `nif` (optionally prefixed with country code, e.g. "PT123456789").
     * Returns JSON: { valid: bool, name: string|null, address: string|null }
     */
    public function vies(Request $request, ViesService $viesService)
    {
        $nif = trim((string)$request->query('nif', ''));

        if ($nif === '') {
            return response()->json(['error' => 'missing_nif'], 400);
        }

        try {
            $payload = $viesService->lookup($nif);
            return response()->json($payload);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'vies_error', 'message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'vies_error', 'message' => $e->getMessage()], 500);
        }
    }
}
