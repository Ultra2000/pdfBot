<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\TaskJob;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index()
    {
        // Statistiques dynamiques pour la landing page
        $stats = [
            'documents_processed' => Document::count(),
            'total_jobs' => TaskJob::count(),
            'success_rate' => $this->getSuccessRate(),
            'avg_processing_time' => $this->getAverageProcessingTime(),
            'popular_operations' => $this->getPopularOperations()
        ];

        return view('landing', compact('stats'));
    }

    private function getSuccessRate()
    {
        $totalJobs = TaskJob::count();
        if ($totalJobs === 0) return 99.9;

        $successfulJobs = TaskJob::where('status', 'completed')->count();
        return round(($successfulJobs / $totalJobs) * 100, 1);
    }

    private function getAverageProcessingTime()
    {
        $avgTime = TaskJob::whereNotNull('processing_time_seconds')
            ->where('processing_time_seconds', '>', 0)
            ->avg('processing_time_seconds');

        return $avgTime ? round($avgTime) : 25;
    }

    private function getPopularOperations()
    {
        return TaskJob::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->orderBy('total', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $this->getOperationName($item->type),
                    'count' => $item->total
                ];
            });
    }

    private function getOperationName($type)
    {
        $operations = [
            'compress' => 'Compression',
            'convert' => 'Conversion',
            'ocr' => 'Extraction OCR',
            'summarize' => 'Résumé',
            'translate' => 'Traduction',
            'secure' => 'Sécurisation'
        ];

        return $operations[$type] ?? ucfirst($type);
    }

    public function getStats()
    {
        // API endpoint pour obtenir les stats en temps réel
        return response()->json([
            'documents_processed' => number_format(Document::count()),
            'uptime' => '99.9%',
            'avg_processing_time' => $this->getAverageProcessingTime() . 's',
            'success_rate' => $this->getSuccessRate() . '%'
        ]);
    }
}
