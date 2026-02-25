<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentEvent;

class IncidentService
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ACKED = 'acked';

    public const STATUS_SNOOZED = 'snoozed';

    public const STATUS_RESOLVED = 'resolved';

    public function recordIssue(
        int $tenantId,
        string $category,
        string $fingerprint,
        string $title,
        ?string $summary = null,
        string $severity = 'medium',
        array $meta = [],
        ?string $resourceType = null,
        ?int $resourceId = null
    ): Incident {
        $now = now();

        $incident = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('fingerprint', $fingerprint)
            ->first();

        if (! $incident) {
            $incident = Incident::create([
                'tenant_id' => $tenantId,
                'fingerprint' => $fingerprint,
                'category' => $category,
                'status' => self::STATUS_OPEN,
                'severity' => $severity,
                'title' => $title,
                'summary' => $summary,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'meta' => $meta ?: null,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            $this->event($incident->id, 'created', 'Incident created.', null, [
                'category' => $category,
                'severity' => $severity,
            ]);

            return $incident;
        }

        $statusBefore = (string) $incident->status;
        $changed = false;

        // Reopen if it was resolved.
        if ($incident->status === self::STATUS_RESOLVED) {
            $incident->status = self::STATUS_OPEN;
            $incident->resolved_at = null;
            $incident->acked_by = null;
            $incident->acked_at = null;
            $incident->snoozed_until = null;
            $changed = true;
        }

        // Expired snooze automatically reverts to open when issue is seen again.
        if ($incident->status === self::STATUS_SNOOZED && $incident->snoozed_until && $incident->snoozed_until->lte($now)) {
            $incident->status = self::STATUS_OPEN;
            $incident->snoozed_until = null;
            $changed = true;
            $this->event($incident->id, 'unsnoozed', 'Snooze expired (issue still present).', null);
        }

        // Keep ack/snooze status if still active.

        if ($incident->category !== $category) {
            $incident->category = $category;
            $changed = true;
        }
        if ($incident->severity !== $severity) {
            $incident->severity = $severity;
            $changed = true;
        }
        if ($incident->title !== $title) {
            $incident->title = $title;
            $changed = true;
        }
        if (($incident->summary ?? null) !== $summary) {
            $incident->summary = $summary;
            $changed = true;
        }
        if (($incident->resource_type ?? null) !== $resourceType) {
            $incident->resource_type = $resourceType;
            $changed = true;
        }
        if ((int) ($incident->resource_id ?? 0) !== (int) ($resourceId ?? 0)) {
            $incident->resource_id = $resourceId;
            $changed = true;
        }

        $incident->meta = $meta ?: null;
        $incident->last_seen_at = $now;
        if (! $incident->first_seen_at) {
            $incident->first_seen_at = $now;
            $changed = true;
        }

        $incident->save();

        if ($statusBefore === self::STATUS_RESOLVED && $incident->status === self::STATUS_OPEN) {
            $this->event($incident->id, 'reopened', 'Incident reopened (issue detected again).', null);
        }

        return $incident;
    }

    public function resolveIssue(int $tenantId, string $category, string $fingerprint, string $message = 'Recovered.', ?int $actorId = null): bool
    {
        $incident = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('category', $category)
            ->where('fingerprint', $fingerprint)
            ->whereIn('status', [self::STATUS_OPEN, self::STATUS_ACKED, self::STATUS_SNOOZED])
            ->first();

        if (! $incident) {
            return false;
        }

        $incident->status = self::STATUS_RESOLVED;
        $incident->resolved_at = now();
        $incident->snoozed_until = null;
        $incident->save();

        $this->event($incident->id, 'resolved', $message, $actorId);

        return true;
    }

    public function autoResolveMissing(int $tenantId, string $category, array $seenFingerprints, string $message = 'Recovered.'): int
    {
        $seenFingerprints = array_values(array_unique(array_map('strval', $seenFingerprints)));

        $query = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('category', $category)
            ->whereIn('status', [self::STATUS_OPEN, self::STATUS_ACKED, self::STATUS_SNOOZED]);

        if ($seenFingerprints !== []) {
            $query->whereNotIn('fingerprint', $seenFingerprints);
        }

        $incidents = $query->get();
        if ($incidents->isEmpty()) {
            return 0;
        }

        $count = 0;
        foreach ($incidents as $incident) {
            $incident->status = self::STATUS_RESOLVED;
            $incident->resolved_at = now();
            $incident->snoozed_until = null;
            $incident->save();

            $this->event($incident->id, 'auto_resolved', $message, null);
            $count++;
        }

        return $count;
    }

    public function ack(Incident $incident, int $actorId): void
    {
        if ($incident->status === self::STATUS_RESOLVED) {
            return;
        }

        $incident->status = self::STATUS_ACKED;
        $incident->acked_by = $actorId;
        $incident->acked_at = now();
        $incident->save();

        $this->event($incident->id, 'acked', 'Acknowledged.', $actorId);
    }

    public function unack(Incident $incident, int $actorId): void
    {
        if ($incident->status === self::STATUS_RESOLVED) {
            return;
        }

        $incident->status = self::STATUS_OPEN;
        $incident->acked_by = null;
        $incident->acked_at = null;
        $incident->save();

        $this->event($incident->id, 'unacked', 'Un-acknowledged.', $actorId);
    }

    public function snooze(Incident $incident, int $actorId, \Carbon\CarbonInterface $until): void
    {
        if ($incident->status === self::STATUS_RESOLVED) {
            return;
        }

        $incident->status = self::STATUS_SNOOZED;
        $incident->snoozed_until = $until;
        $incident->save();

        $this->event($incident->id, 'snoozed', 'Snoozed until '.$until->toDateTimeString().'.', $actorId, [
            'until' => $until->toIso8601String(),
        ]);
    }

    public function unsnooze(Incident $incident, int $actorId): void
    {
        if ($incident->status === self::STATUS_RESOLVED) {
            return;
        }

        $incident->status = self::STATUS_OPEN;
        $incident->snoozed_until = null;
        $incident->save();

        $this->event($incident->id, 'unsnoozed', 'Unsnoozed.', $actorId);
    }

    public function resolve(Incident $incident, int $actorId, string $message = 'Resolved (manual).'): void
    {
        if ($incident->status === self::STATUS_RESOLVED) {
            return;
        }

        $incident->status = self::STATUS_RESOLVED;
        $incident->resolved_at = now();
        $incident->snoozed_until = null;
        $incident->save();

        $this->event($incident->id, 'resolved', $message, $actorId);
    }

    public function reopen(Incident $incident, int $actorId): void
    {
        if ($incident->status !== self::STATUS_RESOLVED) {
            return;
        }

        $incident->status = self::STATUS_OPEN;
        $incident->resolved_at = null;
        $incident->acked_by = null;
        $incident->acked_at = null;
        $incident->snoozed_until = null;
        $incident->save();

        $this->event($incident->id, 'reopened', 'Reopened (manual).', $actorId);
    }

    private function event(int $incidentId, string $kind, ?string $message = null, ?int $actorId = null, array $meta = []): void
    {
        try {
            IncidentEvent::create([
                'incident_id' => $incidentId,
                'kind' => $kind,
                'message' => $message,
                'meta' => $meta ?: null,
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never break monitoring/ops flows due to incident logging.
        }
    }
}
