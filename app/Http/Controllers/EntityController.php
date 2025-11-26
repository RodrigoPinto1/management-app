<?php

namespace App\Http\Controllers;

use App\Models\Entity;
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'is_client' => 'boolean',
            'is_supplier' => 'boolean',
            'nif' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
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

        // assign incremental number if not provided
        if (empty($data['number'])) {
            $max = Entity::max('number');
            $data['number'] = $max ? $max + 1 : 1;
        }

        $entity = Entity::create($data);

        return response()->json($entity);
    }
}
