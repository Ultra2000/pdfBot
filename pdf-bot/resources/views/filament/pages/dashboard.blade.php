<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Statistiques --}}
        <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
            @php $stats = $this->getStats() @endphp
            
            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-8 h-8 bg-blue-500 rounded-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Documents Total</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['documents_total'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-8 h-8 bg-green-500 rounded-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Jobs Terminés</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['jobs_completed'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-8 h-8 bg-yellow-500 rounded-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Jobs En attente</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['jobs_pending'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-8 h-8 bg-red-500 rounded-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Jobs Échoués</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['jobs_failed'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documents récents --}}
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Documents récents</h3>
            </div>
            <div class="p-6">
                @php $recentDocuments = $this->getRecentDocuments() @endphp
                @if($recentDocuments->count() > 0)
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taille</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($recentDocuments as $document)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ Str::limit($document->original_name, 40) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($document->status === 'completed') bg-green-100 text-green-800
                                                @elseif($document->status === 'processing') bg-yellow-100 text-yellow-800
                                                @elseif($document->status === 'failed') bg-red-100 text-red-800
                                                @else bg-blue-100 text-blue-800 @endif">
                                                {{ $document->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $document->file_size ? number_format($document->file_size / 1024, 2) . ' KB' : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $document->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">Aucun document trouvé</p>
                @endif
            </div>
        </div>

        {{-- Jobs récents --}}
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Jobs récents</h3>
            </div>
            <div class="p-6">
                @php $recentJobs = $this->getRecentJobs() @endphp
                @if($recentJobs->count() > 0)
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durée</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($recentJobs as $job)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ $job->type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $job->document ? Str::limit($job->document->original_name, 30) : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($job->status === 'completed') bg-green-100 text-green-800
                                                @elseif($job->status === 'running') bg-yellow-100 text-yellow-800
                                                @elseif($job->status === 'failed') bg-red-100 text-red-800
                                                @else bg-blue-100 text-blue-800 @endif">
                                                {{ $job->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($job->processing_time_seconds)
                                                {{ $job->processing_time_seconds }}s
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $job->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">Aucun job trouvé</p>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
