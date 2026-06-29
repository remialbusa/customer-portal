<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\ServiceStatus;

/**
 * Adds a `statusLabel()` helper to any model that has a
 * `service_status` string column. The label is pulled from the
 * ServiceStatus enum, with a graceful fallback for legacy rows.
 */
trait HasServiceStatusLabel
{
    public function statusLabel(): string
    {
        $raw = $this->getRawStatus();
        $status = ServiceStatus::tryFrom($raw);
        return $status?->label() ?? ucfirst($raw);
    }

    public function statusEnum(): ?ServiceStatus
    {
        return ServiceStatus::tryFrom($this->getRawStatus());
    }

    /**
     * Read the column as a plain string regardless of whether the
     * model has cast it to the ServiceStatus enum. Avoids PHP 8.5's
     * "(string) $enum" deprecation / Error.
     */
    protected function getRawStatus(): string
    {
        $val = $this->service_status;
        if ($val instanceof ServiceStatus) {
            return $val->value;
        }
        return (string) ($val ?? '');
    }

    /**
     * Same as getRawStatus() but returns the ServiceStatus enum
     * instance (or null if unknown). Use this anywhere that needs
     * the typed enum instead of the raw string.
     */
    public function serviceStatusEnum(): ?ServiceStatus
    {
        return ServiceStatus::tryFrom($this->getRawStatus());
    }

    public function statusColor(): string
    {
        return $this->statusEnum()?->color() ?? 'gray';
    }
}
