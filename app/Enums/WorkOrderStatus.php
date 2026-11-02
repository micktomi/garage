<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case AwaitingParts = 'awaiting_parts';
    case ReadyForPickup = 'ready';
    case Completed = 'completed';

    /**
     * Kept for existing cancelled records and the stock-restoration workflow.
     * Cancelled orders never appear in open workshop collections.
     */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Νέα',
            self::InProgress => 'Σε εξέλιξη',
            self::AwaitingParts => 'Αναμονή ανταλλακτικού',
            self::ReadyForPickup => 'Έτοιμη προς παράδοση',
            self::Completed => 'Ολοκληρωμένη',
            self::Cancelled => 'Ακυρωμένη',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'ws-status-badge--new',
            self::InProgress => 'ws-status-badge--in-progress',
            self::AwaitingParts => 'ws-status-badge--awaiting-parts',
            self::ReadyForPickup => 'ws-status-badge--ready',
            self::Completed, self::Cancelled => 'ws-status-badge--completed',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::InProgress, self::AwaitingParts => 'warning',
            self::ReadyForPickup, self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }

    /**
     * The states the counter may move to from this one. Completed and
     * Cancelled are terminal: a filed order is history, and a miscancelled
     * one is replaced by a new order, never resurrected.
     *
     * The single exception — the owner reopening a completed order — is a
     * permission, not a flow, so it lives in WorkOrderPolicy::reopenCompleted
     * rather than here.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::InProgress, self::Cancelled],
            self::InProgress => [self::AwaitingParts, self::ReadyForPickup, self::Cancelled],
            // Parts fitted straight away skip the workshop lane entirely.
            self::AwaitingParts => [self::InProgress, self::ReadyForPickup, self::Cancelled],
            // Ready goes back to the shop when the pickup reveals more work.
            self::ReadyForPickup => [self::Completed, self::InProgress, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        // Re-submitting the current status is a no-op, not a transition.
        if ($target === $this) {
            return true;
        }

        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return array_values(array_map(
            static fn (self $status): string => $status->value,
            array_filter(self::cases(), static fn (self $status): bool => $status->isOpen()),
        ));
    }

    public static function resolve(self|string $status): self
    {
        return $status instanceof self ? $status : self::from($status);
    }
}
