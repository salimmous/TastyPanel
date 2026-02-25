<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\SiteSetting;
use App\Services\AutomationRunnerService;
use App\Services\AutomationSettingsService;
use App\Services\CanvaService;
use App\Services\DiscordService;
use App\Services\OpenAiService;
use App\Services\PinterestService;
use App\Support\AdminEnvironmentResolver;
use App\Support\AdminPermissions;
use App\Support\AdminTenantResolver;
use App\Support\ContentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AutomationController extends Controller
{
    public function __construct(
        private AutomationSettingsService $settingsService,
        private OpenAiService $openAi,
        private CanvaService $canva,
        private DiscordService $discord,
        private PinterestService $pinterest,
    ) {}

    public function index(Request $request, $tenantId)
    {
        $tenant = \App\Models\Tenant::findOrFail($tenantId);
        if (! AdminPermissions::canManageTenantInfrastructure($request->user()) && $request->user()->tenant_id !== $tenant->id) {
            abort(403);
        }

        $environment = AdminEnvironmentResolver::resolve($request);
        $settings = $this->loadSettings($tenantId, $environment);
        $automation = $settings?->data['automation'] ?? [];
        $data = $this->settingsService->mergeWithDefaults($automation);
        $data = $this->settingsService->maskSecrets($data);

        $runs = \App\Models\AutomationRun::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('platform.automation.index', compact('tenant', 'data', 'runs', 'environment'));
    }

    public function show(Request $request)
    {
        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }

        $environment = AdminEnvironmentResolver::resolve($request);
        $settings = $this->loadSettings($tenantId, $environment);
        $automation = $settings?->data['automation'] ?? [];
        $payload = $this->settingsService->mergeWithDefaults($automation);

        return response()->json([
            'data' => $this->settingsService->maskSecrets($payload),
            'status' => $this->settingsService->statusSummary($payload),
        ]);
    }

    public function update(Request $request)
    {
        if (! AdminPermissions::canManageTenantInfrastructure($request->user())) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }
        $environment = AdminEnvironmentResolver::resolve($request);

        $data = $request->validate([
            'openai' => ['nullable', 'array'],
            'openai.enabled' => ['nullable', 'boolean'],
            'openai.api_key' => ['nullable', 'string', 'max:200'],
            'openai.model' => ['nullable', 'string', 'max:120'],
            'openai.temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'openai.max_tokens' => ['nullable', 'integer', 'min:64', 'max:8000'],
            'openai.system_prompt' => ['nullable', 'string', 'max:2000'],

            'midjourney' => ['nullable', 'array'],
            'midjourney.enabled' => ['nullable', 'boolean'],
            'midjourney.bot_token' => ['nullable', 'string', 'max:200'],
            'midjourney.guild_id' => ['nullable', 'string', 'max:64'],
            'midjourney.channel_id' => ['nullable', 'string', 'max:64'],
            'midjourney.webhook_url' => ['nullable', 'string', 'max:255'],
            'midjourney.default_style' => ['nullable', 'string', 'max:120'],

            'canva' => ['nullable', 'array'],
            'canva.enabled' => ['nullable', 'boolean'],
            'canva.template_id' => ['nullable', 'string', 'max:160'],
            'canva.brand_kit_id' => ['nullable', 'string', 'max:160'],
            'canva.auto_brand' => ['nullable', 'boolean'],

            'pinterest' => ['nullable', 'array'],
            'pinterest.enabled' => ['nullable', 'boolean'],
            'pinterest.access_token' => ['nullable', 'string', 'max:255'],
            'pinterest.board_id' => ['nullable', 'string', 'max:120'],
            'pinterest.default_title' => ['nullable', 'string', 'max:160'],
            'pinterest.default_description' => ['nullable', 'string', 'max:500'],
            'pinterest.link_format' => ['nullable', 'string', 'max:40'],

            'pipeline' => ['nullable', 'array'],
            'pipeline.generate_title' => ['nullable', 'boolean'],
            'pipeline.generate_excerpt' => ['nullable', 'boolean'],
            'pipeline.generate_image' => ['nullable', 'boolean'],
            'pipeline.auto_tag' => ['nullable', 'boolean'],
            'pipeline.auto_category' => ['nullable', 'boolean'],
            'pipeline.auto_publish' => ['nullable', 'boolean'],

            'content' => ['nullable', 'array'],
            'content.topics' => ['nullable', 'array'],
            'content.language' => ['nullable', 'string', 'max:20'],
            'content.voice' => ['nullable', 'string', 'max:200'],
            'content.min_words' => ['nullable', 'integer', 'min:100', 'max:4000'],
            'content.max_words' => ['nullable', 'integer', 'min:100', 'max:6000'],

            'schedule' => ['nullable', 'array'],
            'schedule.enabled' => ['nullable', 'boolean'],
            'schedule.posts_per_day' => ['nullable', 'integer', 'min:0', 'max:50'],
            'schedule.publish_status' => ['nullable', 'string', Rule::in(ContentWorkflow::statuses())],
            'schedule.timezone' => ['nullable', 'string', 'max:80'],
            'schedule.window_start' => ['nullable', 'string', 'max:10'],
            'schedule.window_end' => ['nullable', 'string', 'max:10'],
            'schedule.environment' => ['nullable', 'string', Rule::in(['production', 'staging', 'preview'])],
        ]);

        $settings = $this->loadSettings($tenantId, $environment) ?? new SiteSetting;
        $current = $settings->data ?? [];
        $currentAutomation = $current['automation'] ?? [];

        $merged = array_replace_recursive($currentAutomation, $data);
        $merged = $this->settingsService->preserveSecrets($currentAutomation, $merged);
        $merged = $this->settingsService->sealSecrets($merged);

        $current['automation'] = $merged;
        $settings->tenant_id = $tenantId;
        $settings->environment = $environment;
        $settings->data = $current;
        $settings->save();

        $payload = $this->settingsService->mergeWithDefaults($merged);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $this->settingsService->maskSecrets($payload),
                'status' => $this->settingsService->statusSummary($payload),
            ]);
        }

        return back()->with('success', 'Automation settings updated successfully.');
    }

    public function test(Request $request)
    {
        if (! AdminPermissions::canManageTenantInfrastructure($request->user())) {
            abort(403);
        }

        $data = $request->validate([
            'provider' => ['required', 'string', Rule::in(['openai', 'midjourney', 'canva', 'pinterest'])],
        ]);

        $tenantId = AdminTenantResolver::resolveId($request);
        $environment = AdminEnvironmentResolver::resolve($request);
        $settings = $this->loadSettings($tenantId, $environment);
        $payload = $this->settingsService->mergeWithDefaults($settings?->data['automation'] ?? []);
        $payload = $this->settingsService->revealSecrets($payload);

        if ($data['provider'] === 'canva') {
            $payload = $this->refreshCanvaTokenIfNeeded($settings, $payload);
        }

        $check = $this->checkProvider($data['provider'], $payload);
        if (! $check['ok']) {
            return response()->json([
                'success' => false,
                'message' => $check['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $check['message'],
        ]);
    }

    public function createDraft(Request $request)
    {
        if (! AdminPermissions::canManageContent($request->user())) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }
        $environment = AdminEnvironmentResolver::resolve($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(ContentWorkflow::statuses())],
        ]);

        $settings = $this->loadSettings($tenantId, $environment);
        $automation = $this->settingsService->mergeWithDefaults($settings?->data['automation'] ?? []);
        $automation = $this->settingsService->revealSecrets($automation);

        $slug = $this->uniqueSlug($tenantId, $environment, $data['title']);
        $status = $data['status']
            ? ContentWorkflow::normalizeStatus($data['status'], $request->user())
            : ContentWorkflow::STATUS_DRAFT;

        $summary = $this->generateSummary($data['title'], $data['summary'] ?? '', $automation);

        $article = new Article;
        $article->tenant_id = $tenantId;
        $article->environment = $environment;
        $article->title = $data['title'];
        $article->slug = $slug;
        $article->description = $summary;
        $article->image = null;
        $article->status = $status;
        ContentWorkflow::applyStatusTimestamps($article, $status);
        $article->save();

        return response()->json([
            'data' => $article,
        ], 201);
    }

    public function canvaConnect(Request $request)
    {
        if (! AdminPermissions::canManageTenantInfrastructure($request->user())) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }

        $environment = AdminEnvironmentResolver::resolve($request);

        try {
            $url = $this->canva->buildAuthorizationUrl($tenantId, $environment, $request->user()->id);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    public function runs(Request $request)
    {
        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }
        $environment = AdminEnvironmentResolver::resolve($request);
        $limit = (int) $request->get('limit', 10);
        $limit = max(1, min(50, $limit));

        $runs = \App\Models\AutomationRun::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $runs,
        ]);
    }

    public function run(Request $request, AutomationRunnerService $runner)
    {
        if (! AdminPermissions::canManageContent($request->user())) {
            abort(403);
        }

        $tenantId = AdminTenantResolver::resolveId($request);
        if (! $tenantId) {
            abort(422, 'Tenant required.');
        }
        $environment = AdminEnvironmentResolver::resolve($request);
        $tenant = \App\Models\Tenant::findOrFail($tenantId);

        $data = $request->validate([
            'topic' => ['nullable', 'string', 'max:200'],
        ]);

        $run = $runner->runForTenant($tenant, $environment, 'manual', $request->user()?->id, $data['topic'] ?? null);

        return response()->json([
            'data' => $run,
        ]);
    }

    public function canvaCallback(Request $request)
    {
        if (! AdminPermissions::canManageTenantInfrastructure($request->user())) {
            abort(403);
        }

        $error = $request->query('error');
        if ($error) {
            return redirect('/admin/auto-post-settings?canva=error');
        }

        $code = $request->query('code');
        $state = $request->query('state');
        if (! $code || ! $state) {
            return redirect('/admin/auto-post-settings?canva=missing');
        }

        $stateData = $this->canva->consumeState($state);
        if (! $stateData) {
            return redirect('/admin/auto-post-settings?canva=expired');
        }

        if ((int) ($stateData['user_id'] ?? 0) !== (int) $request->user()->id) {
            abort(403);
        }

        try {
            $token = $this->canva->exchangeCode($code, $stateData);
        } catch (\Throwable $e) {
            return redirect('/admin/auto-post-settings?canva=failed');
        }

        $settings = $this->loadSettings($stateData['tenant_id'], $stateData['environment']) ?? new SiteSetting;
        $automation = $this->settingsService->mergeWithDefaults($settings?->data['automation'] ?? []);
        $automation = $this->settingsService->revealSecrets($automation);

        $automation['canva']['enabled'] = true;
        $automation['canva']['oauth'] = $this->normalizeCanvaToken($token, $automation['canva']['oauth'] ?? []);

        try {
            $profile = $this->canva->getUserProfile($automation['canva']['oauth']['access_token']);
            $displayName = data_get($profile, 'display_name')
                ?: data_get($profile, 'user.display_name')
                ?: data_get($profile, 'user.name');
            if ($displayName) {
                $automation['canva']['profile']['display_name'] = $displayName;
            }
        } catch (\Throwable $e) {
        }

        $automation = $this->settingsService->sealSecrets($automation);
        $current = $settings->data ?? [];
        $current['automation'] = $automation;
        $settings->tenant_id = $stateData['tenant_id'];
        $settings->environment = $stateData['environment'];
        $settings->data = $current;
        $settings->save();

        return redirect('/admin/auto-post-settings?canva=connected');
    }

    private function loadSettings(?int $tenantId, ?string $environment): ?SiteSetting
    {
        if (! $tenantId) {
            return null;
        }

        return SiteSetting::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->first();
    }

    private function checkProvider(string $provider, array $payload): array
    {
        return match ($provider) {
            'openai' => $this->checkOpenAi($payload),
            'midjourney' => $this->checkMidjourney($payload),
            'canva' => $this->checkCanva($payload),
            'pinterest' => $this->checkPinterest($payload),
            default => ['ok' => false, 'message' => 'Unknown provider.'],
        };
    }

    private function checkOpenAi(array $payload): array
    {
        if (empty($payload['openai']['enabled'])) {
            return ['ok' => false, 'message' => 'OpenAI is disabled.'];
        }
        $apiKey = $payload['openai']['api_key'] ?? '';
        if (! $apiKey) {
            return ['ok' => false, 'message' => 'OpenAI key is missing.'];
        }

        return $this->openAi->testConnection($apiKey, $payload);
    }

    private function checkMidjourney(array $payload): array
    {
        if (empty($payload['midjourney']['enabled'])) {
            return ['ok' => false, 'message' => 'Midjourney is disabled.'];
        }
        $botToken = $payload['midjourney']['bot_token'] ?? '';
        if (! $botToken) {
            return ['ok' => false, 'message' => 'Discord bot token is missing.'];
        }
        if (empty($payload['midjourney']['channel_id'])) {
            return ['ok' => false, 'message' => 'Discord channel ID is missing.'];
        }

        return $this->discord->testBotToken($botToken);
    }

    private function checkCanva(array $payload): array
    {
        if (empty($payload['canva']['enabled'])) {
            return ['ok' => false, 'message' => 'Canva is disabled.'];
        }
        $oauth = $payload['canva']['oauth'] ?? [];
        if (empty($oauth['access_token'])) {
            return ['ok' => false, 'message' => 'Canva is not connected.'];
        }

        try {
            $this->canva->getUserProfile($oauth['access_token']);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => true, 'message' => 'Canva connected successfully.'];
    }

    private function checkPinterest(array $payload): array
    {
        if (empty($payload['pinterest']['enabled'])) {
            return ['ok' => false, 'message' => 'Pinterest is disabled.'];
        }
        $token = $payload['pinterest']['access_token'] ?? '';
        if (! $token) {
            return ['ok' => false, 'message' => 'Pinterest token is missing.'];
        }

        return $this->pinterest->testAccessToken($token);
    }

    private function refreshCanvaTokenIfNeeded(?SiteSetting $settings, array $payload): array
    {
        $oauth = $payload['canva']['oauth'] ?? [];
        $expiresAt = $oauth['expires_at'] ?? null;

        if (! $expiresAt || empty($oauth['refresh_token'])) {
            return $payload;
        }

        try {
            $expiry = Carbon::parse($expiresAt);
        } catch (\Throwable $e) {
            return $payload;
        }

        if ($expiry->isFuture()) {
            return $payload;
        }

        try {
            $token = $this->canva->refreshToken($oauth['refresh_token']);
        } catch (\Throwable $e) {
            return $payload;
        }

        $payload['canva']['oauth'] = $this->normalizeCanvaToken($token, $oauth);

        if ($settings) {
            $automation = $this->settingsService->sealSecrets($payload);
            $current = $settings->data ?? [];
            $current['automation'] = $automation;
            $settings->data = $current;
            $settings->save();
        }

        return $payload;
    }

    private function normalizeCanvaToken(array $token, array $existing): array
    {
        $expiresIn = (int) ($token['expires_in'] ?? 0);
        $expiresAt = $expiresIn > 0
            ? now()->addSeconds(max(0, $expiresIn - 60))->toIso8601String()
            : ($existing['expires_at'] ?? null);

        return [
            'access_token' => $token['access_token'] ?? ($existing['access_token'] ?? ''),
            'refresh_token' => $token['refresh_token'] ?? ($existing['refresh_token'] ?? ''),
            'expires_at' => $expiresAt,
            'token_type' => $token['token_type'] ?? ($existing['token_type'] ?? ''),
            'scopes' => $this->normalizeScopes($token['scope'] ?? ($token['scopes'] ?? ($existing['scopes'] ?? []))),
        ];
    }

    private function normalizeScopes(mixed $scopes): array
    {
        if (is_array($scopes)) {
            return $scopes;
        }
        if (is_string($scopes)) {
            return array_values(array_filter(preg_split('/\s+/', $scopes)));
        }

        return [];
    }

    private function uniqueSlug(int $tenantId, string $environment, string $title): string
    {
        $base = Str::slug($title) ?: 'ai-draft';
        $slug = $base;
        $counter = 1;

        while (Article::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->where('slug', $slug)
            ->exists()) {
            $counter += 1;
            $slug = $base.'-'.$counter;
        }

        return $slug;
    }

    private function generateSummary(string $title, string $summary, array $automation): string
    {
        if ($summary) {
            return $summary;
        }

        $enabled = ! empty($automation['openai']['enabled']);
        $apiKey = $automation['openai']['api_key'] ?? '';
        if ($enabled && $apiKey) {
            $draft = $this->openAi->generateDraft($apiKey, $automation, $title, $summary);
            if ($draft) {
                return $draft;
            }
        }

        return $this->fallbackDraft($title, $automation);
    }

    private function fallbackDraft(string $title, array $automation): string
    {
        $tone = $automation['openai']['system_prompt'] ?? '';
        $toneLine = $tone ? "Tone guidance: {$tone}\n\n" : '';

        return $toneLine
            ."{$title}\n\n"
            ."This is an automated draft placeholder. Add your key points, data, and sources here.\n\n"
            ."Outline:\n"
            ."1. Introduction\n"
            ."2. Key takeaways\n"
            ."3. Practical tips\n"
            ."4. Conclusion\n";
    }
}
