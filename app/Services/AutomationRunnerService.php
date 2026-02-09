<?php

namespace App\Services;

use App\Models\AutomationRun;
use App\Models\Article;
use App\Models\SiteSetting;
use App\Models\Tenant;
use App\Support\ContentWorkflow;
use Illuminate\Support\Str;

class AutomationRunnerService
{
    public function __construct(
        private AutomationSettingsService $settingsService,
        private OpenAiService $openAi,
        private ContentScoringService $scoring,
        private WebhookService $webhooks
    ) {
    }

    public function runForTenant(
        Tenant $tenant,
        string $environment,
        string $trigger = 'manual',
        ?int $userId = null,
        ?string $topicOverride = null
    ): AutomationRun {
        $run = AutomationRun::create([
            'tenant_id' => $tenant->id,
            'environment' => $environment,
            'trigger' => $trigger,
            'status' => 'running',
            'topic' => $topicOverride,
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        try {
            $settings = $this->loadAutomationSettings($tenant->id, $environment);
            $automation = $this->settingsService->mergeWithDefaults($settings?->data['automation'] ?? []);
            $automation = $this->settingsService->revealSecrets($automation);

            $apiKey = $automation['openai']['api_key'] ?? '';
            if (empty($automation['openai']['enabled']) || !$apiKey) {
                throw new \RuntimeException('OpenAI is not configured for this tenant.');
            }

            $topic = $topicOverride ?: $this->pickTopic($automation);
            if (!$topic) {
                throw new \RuntimeException('No topics configured for automation.');
            }

            $contentSettings = $automation['content'] ?? [];
            $language = $contentSettings['language'] ?? 'en';
            $voice = $contentSettings['voice'] ?? ($automation['openai']['system_prompt'] ?? '');
            $minWords = (int) ($contentSettings['min_words'] ?? 400);
            $maxWords = (int) ($contentSettings['max_words'] ?? 900);

            $title = $topic;
            if (!empty($automation['pipeline']['generate_title'])) {
                $generatedTitle = $this->openAi->generateTitle($apiKey, $automation, $topic, $language, $voice);
                if ($generatedTitle) {
                    $title = $generatedTitle;
                }
            }

            $body = $this->openAi->generateArticle($apiKey, $automation, $title, $topic, $language, $voice, $minWords, $maxWords);
            if (!$body) {
                throw new \RuntimeException('OpenAI returned an empty response.');
            }

            $status = $automation['schedule']['publish_status'] ?? ContentWorkflow::STATUS_DRAFT;
            if (!in_array($status, ContentWorkflow::statuses(), true)) {
                $status = ContentWorkflow::STATUS_DRAFT;
            }

            $slug = $this->uniqueSlug($tenant->id, $environment, $title);

            $article = new Article();
            $article->tenant_id = $tenant->id;
            $article->environment = $environment;
            $article->title = $title;
            $article->slug = $slug;
            $article->description = $body;
            $article->image = null;
            $article->status = $status;
            ContentWorkflow::applyStatusTimestamps($article, $status);

            $score = $this->scoring->score($title, $body);
            $article->fill($score);
            $article->save();

            $this->webhooks->dispatchEvent($tenant, 'article.created', $article->toArray());

            $run->status = 'completed';
            $run->topic = $topic;
            $run->title = $title;
            $run->article_id = $article->id;
            $run->output = 'Article created.';
            $run->finished_at = now();
            $run->save();
        } catch (\Throwable $e) {
            $run->status = 'failed';
            $run->error = $e->getMessage();
            $run->finished_at = now();
            $run->save();
        }

        return $run;
    }

    public function runsToday(Tenant $tenant, string $environment): int
    {
        return AutomationRun::where('tenant_id', $tenant->id)
            ->where('environment', $environment)
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    private function loadAutomationSettings(int $tenantId, string $environment): ?SiteSetting
    {
        return SiteSetting::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->first();
    }

    private function pickTopic(array $automation): ?string
    {
        $topics = $automation['content']['topics'] ?? [];
        if (is_string($topics)) {
            $topics = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $topics)));
        }
        if (!is_array($topics) || empty($topics)) {
            return null;
        }
        return $topics[array_rand($topics)];
    }

    private function uniqueSlug(int $tenantId, string $environment, string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'post';
        }
        $slug = $base;
        $i = 1;
        while (Article::where('tenant_id', $tenantId)
            ->where('environment', $environment)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
