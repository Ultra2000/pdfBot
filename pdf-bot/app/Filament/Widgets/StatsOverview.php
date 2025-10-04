<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\TaskJob;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        
        // Jobs d'aujourd'hui
        $jobsToday = TaskJob::whereDate('created_at', $today)->count();
        
        // Temps moyen de traitement (en secondes)
        $avgProcessingTime = TaskJob::whereNotNull('processing_time_seconds')
            ->avg('processing_time_seconds');
        
        // Taux d'échec (%)
        $totalJobs = TaskJob::count();
        $failedJobs = TaskJob::where('status', 'failed')->count();
        $failureRate = $totalJobs > 0 ? round(($failedJobs / $totalJobs) * 100, 1) : 0;
        
        // Taille totale traitée aujourd'hui (en MB)
        $totalSizeToday = Document::whereDate('created_at', $today)
            ->whereNotNull('file_size')
            ->sum('file_size');
        $totalSizeMB = round($totalSizeToday / (1024 * 1024), 2);
        
        return [
            Stat::make('Jobs aujourd\'hui', $jobsToday)
                ->description('Tâches créées')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('Temps moyen', $this->formatTime($avgProcessingTime))
                ->description('Durée de traitement')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Taux d\'échec', $failureRate . '%')
                ->description('Jobs échoués')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failureRate > 10 ? 'danger' : 'success'),
                
            Stat::make('Volume traité', $totalSizeMB . ' MB')
                ->description('Aujourd\'hui')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),
        ];
    }
    
    private function formatTime(?float $seconds): string
    {
        if (!$seconds) {
            return '-';
        }
        
        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . 'min';
        } else {
            return round($seconds / 3600, 1) . 'h';
        }
    }
}
