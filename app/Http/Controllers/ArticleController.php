<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function index()
    {
        return Inertia::render('settings/articles/Index');
    }

    public function list(Request $request)
    {
        $query = Article::query()->orderBy('name');

        $articles = $query->get(['id', 'reference', 'photo', 'name', 'description', 'price']);

        return response()->json(['data' => $articles]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reference' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'vat_rate' => 'nullable|numeric',
            'photo' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $article = Article::create($data);

        // Log the activity
        activity('configurações')
            ->causedBy(auth()->user())
            ->performedOn($article)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Criou artigo: ' . $article->name);

        return response()->json($article);
    }
}
