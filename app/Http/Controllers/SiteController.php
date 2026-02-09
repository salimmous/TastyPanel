<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function handle(Request $request)
    {
        $domain = $request->attributes->get('resolved_domain') ?? $this->resolveDomain($request->getHost());

        if (!$domain) {
            return response()->view('platform.site-not-configured', [
                'host' => $request->getHost(),
                'reason' => 'No site is configured for this domain yet.',
            ], 404);
        }

        $tenant = $domain->tenant;
        $environment = $domain->environment ?? 'production';
        $theme = $environment === 'staging'
            ? ($tenant?->stagingTheme ?? $tenant?->theme)
            : $tenant?->theme;

        if (!$tenant || !$theme || !$theme->is_active || !view()->exists($theme->view)) {
            return response()->view('platform.site-not-configured', [
                'host' => $request->getHost(),
                'reason' => 'This site exists, but no active theme is assigned yet.',
            ], 503);
        }

        $settings = $environment === 'staging'
            ? ($tenant->stagingSettings?->data ?? $tenant->settings?->data ?? [])
            : ($tenant->settings?->data ?? []);
        $tenantId = $tenant->id;

        $categories = Category::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->withCount([
                'recipes' => fn ($q) => $q->where('environment', $environment)
                    ->when($environment === 'production', fn ($inner) => $inner->where('status', 'published')),
            ])
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->when($environment === 'production', fn ($q) => $q->where('status', 'published'))
            ->with('category')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        $articles = Article::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->when($environment === 'production', fn ($q) => $q->where('status', 'published'))
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        return view($theme->view, [
            'tenant' => $tenant,
            'domain' => $domain,
            'settings' => $settings,
            'themeKey' => $theme->key,
            'environment' => $environment,
            'categories' => $categories,
            'recipes' => $recipes,
            'articles' => $articles,
        ]);
    }

    public function sitemap(Request $request): Response
    {
        $domain = $request->attributes->get('resolved_domain') ?? $this->resolveDomain($request->getHost());
        if (!$domain) {
            abort(404);
        }

        $cacheKey = 'sitemap:' . $request->getHost();
        $xml = Cache::remember($cacheKey, 600, function () use ($domain, $request) {
            $host = $request->getSchemeAndHttpHost();
            $tenantId = $domain->tenant_id;
            $environment = 'production';

            $urls = [];
            $now = now()->toAtomString();
            $urls[] = ['loc' => $host . '/', 'lastmod' => $now];

            $articles = Article::select('slug', 'updated_at')
                ->where('tenant_id', $tenantId)
                ->where('environment', $environment)
                ->where('status', 'published')
                ->orderByDesc('updated_at')
                ->limit(500)
                ->get();

            foreach ($articles as $article) {
                $urls[] = [
                    'loc' => $host . '/articles/' . $article->slug,
                    'lastmod' => optional($article->updated_at)->toAtomString() ?? $now,
                ];
            }

            $recipes = Recipe::select('slug', 'updated_at')
                ->where('tenant_id', $tenantId)
                ->where('environment', $environment)
                ->where('status', 'published')
                ->orderByDesc('updated_at')
                ->limit(500)
                ->get();

            foreach ($recipes as $recipe) {
                $urls[] = [
                    'loc' => $host . '/recipes/' . $recipe->slug,
                    'lastmod' => optional($recipe->updated_at)->toAtomString() ?? $now,
                ];
            }

            return $this->buildSitemapXml($urls);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(Request $request): Response
    {
        $domain = $request->attributes->get('resolved_domain') ?? $this->resolveDomain($request->getHost());
        if (!$domain) {
            abort(404);
        }

        $content = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admin\n"
            . "Sitemap: " . $request->getSchemeAndHttpHost() . "/sitemap.xml\n";

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    private function resolveDomain(string $host): ?Domain
    {
        $domain = Domain::with(['tenant.theme', 'tenant.stagingTheme', 'tenant.settings', 'tenant.stagingSettings'])
            ->where('hostname', $host)
            ->first();

        if ($domain) {
            return $domain;
        }

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
            return Domain::with(['tenant.theme', 'tenant.stagingTheme', 'tenant.settings', 'tenant.stagingSettings'])
                ->where('hostname', $host)
                ->first();
        }

        return null;
    }

    private function buildSitemapXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1) . "</lastmod>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';
        return $xml;
    }
}
