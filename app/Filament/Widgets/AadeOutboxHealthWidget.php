<?php

namespace App\Filament\Widgets;

use App\Support\Aade\OutboxHealth;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The dashboard half of the same finding as aade:outbox-alerts: a scheduled
 * command only helps whoever reads scheduler output, and nobody in a garage
 * does. Both read the same OutboxHealth so they can never disagree.
 */
class AadeOutboxHealthWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $counts = app(OutboxHealth::class)->counts();

        return [
            Stat::make('ΑΑΔΕ: αμφίβολες εγγραφές', $counts['ambiguous'])
                ->description($counts['ambiguous'] > 0
                    ? 'Χρειάζονται έλεγχο στην ΑΑΔΕ πριν σταλούν ξανά'
                    : 'Καμία εκκρεμότητα')
                ->color($counts['ambiguous'] > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-question-mark-circle'),

            Stat::make('ΑΑΔΕ: αποτυχημένες αποστολές', $counts['failed'])
                ->description($counts['failed'] > 0
                    ? 'Δεν θα ξαναδοκιμαστούν μόνες τους'
                    : 'Καμία αποτυχία')
                ->color($counts['failed'] > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
