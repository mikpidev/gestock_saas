<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $documentationUrl = rtrim((string) config('services.documentation.url'), '/');
        $documentationHost = parse_url($documentationUrl, PHP_URL_HOST);
        $onDocumentationHost = is_string($documentationHost)
            && strcasecmp($request->getHost(), $documentationHost) === 0;

        $homeUrl = $onDocumentationHost
            ? rtrim((string) config('app.url'), '/')
            : route('landing');

        return view('kb.index', [
            'categories' => $this->categories(),
            'homeUrl' => $homeUrl,
            'query' => $this->searchQuery($request),
        ]);
    }

    private function searchQuery(Request $request): string
    {
        $raw = $request->query('q', '');

        if (! is_string($raw)) {
            return '';
        }

        $query = trim($raw);

        if (mb_strlen($query) > 120) {
            $query = mb_substr($query, 0, 120);
        }

        return $query;
    }

    /**
     * Placeholder topics only. Real articles are out of scope.
     *
     * @return list<array{title: string, summary: string, icon: string}>
     */
    private function categories(): array
    {
        $summary = 'Tema de ejemplo. Todavía no hay artículos publicados.';

        return [
            ['title' => 'Primeros pasos', 'summary' => $summary, 'icon' => 'steps'],
            ['title' => 'Ventas', 'summary' => $summary, 'icon' => 'sales'],
            ['title' => 'Productos', 'summary' => $summary, 'icon' => 'products'],
            ['title' => 'Clientes', 'summary' => $summary, 'icon' => 'customers'],
            ['title' => 'Reportes', 'summary' => $summary, 'icon' => 'reports'],
            ['title' => 'Usuarios', 'summary' => $summary, 'icon' => 'users'],
            ['title' => 'Plataforma', 'summary' => $summary, 'icon' => 'platform'],
            ['title' => 'Preguntas frecuentes', 'summary' => $summary, 'icon' => 'faq'],
        ];
    }
}
