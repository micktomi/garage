<?php

namespace App\Enums;

/**
 * Two roles, deliberately. A garage has an owner and the people who work the
 * counter; anything finer would be a permission matrix nobody maintains.
 *
 * The split is not seniority, it is blast radius: Staff can run the shop all
 * day, but cannot destroy a record, cannot rewrite what a part cost, and
 * cannot re-issue the closure document of an order that has already been
 * completed. Those are the three things that are hard or impossible to undo.
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Ιδιοκτήτης',
            self::Staff => 'Προσωπικό',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $role): array => $carry + [$role->value => $role->label()],
            [],
        );
    }
}
