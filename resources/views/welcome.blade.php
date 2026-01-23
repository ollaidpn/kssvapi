<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KSSV API - System Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f97316',
                    }
                }
            }
        }
    </script>
    <style>
        .accordion-content { 
            max-height: 0; 
            overflow: hidden; 
            transition: max-height 0.3s ease-out; 
        }
        .accordion-content.open { 
            max-height: 800px; 
        }
        .arrow-icon {
            transition: transform 0.2s ease;
        }
        .arrow-icon.rotated {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Header -->
    <header class="bg-gray-800 border-b border-gray-700 px-6 py-4 sticky top-0 z-10">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">KSSV API</h1>
                    <p class="text-gray-400 text-sm">System Logs Viewer</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-gray-400 text-sm">
                    Laravel v{{ App::VERSION() }} (PHP v{{ PHP_VERSION }})
                </span>
                <form action="{{ route('logs.clear') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir vider tous les logs ?')">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Vider les logs
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">
        @if(session('success'))
            <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- Tabs de filtrage -->
        <div class="flex flex-wrap gap-2 mb-6">
            @php
                $tabs = [
                    'all' => ['label' => 'Tous', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    'error' => ['label' => 'Error', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'critical' => ['label' => 'Critical', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                    'warning' => ['label' => 'Warning', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                    'info' => ['label' => 'Info', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'debug' => ['label' => 'Debug', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                ];
            @endphp
            
            @foreach($tabs as $key => $tab)
                <a href="?level={{ $key }}" 
                   class="px-4 py-2.5 rounded-lg font-medium text-sm transition-all flex items-center gap-2
                          {{ $filter === $key 
                              ? 'bg-primary text-white shadow-lg shadow-primary/25' 
                              : 'bg-gray-800 text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"></path>
                    </svg>
                    {{ $tab['label'] }}
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $filter === $key ? 'bg-white/20' : 'bg-gray-700' }}">
                        {{ $counts[$key] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Stats rapides -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-500">{{ ($counts['error'] ?? 0) + ($counts['critical'] ?? 0) }}</p>
                        <p class="text-gray-400 text-sm">Erreurs</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-yellow-500">{{ $counts['warning'] ?? 0 }}</p>
                        <p class="text-gray-400 text-sm">Warnings</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-blue-500">{{ $counts['info'] ?? 0 }}</p>
                        <p class="text-gray-400 text-sm">Info</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-400">{{ $counts['debug'] ?? 0 }}</p>
                        <p class="text-gray-400 text-sm">Debug</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des logs -->
        <div class="space-y-2">
            @forelse($logs as $index => $log)
                @php
                    $levelColors = [
                        'emergency' => 'bg-purple-600 text-white',
                        'alert' => 'bg-pink-600 text-white',
                        'critical' => 'bg-red-700 text-white',
                        'error' => 'bg-red-500 text-white',
                        'warning' => 'bg-yellow-500 text-black',
                        'notice' => 'bg-cyan-500 text-white',
                        'info' => 'bg-blue-500 text-white',
                        'debug' => 'bg-gray-500 text-white',
                    ];
                    $borderColors = [
                        'emergency' => 'border-purple-600/50',
                        'alert' => 'border-pink-600/50',
                        'critical' => 'border-red-700/50',
                        'error' => 'border-red-500/50',
                        'warning' => 'border-yellow-500/50',
                        'notice' => 'border-cyan-500/50',
                        'info' => 'border-blue-500/50',
                        'debug' => 'border-gray-500/50',
                    ];
                @endphp
                
                <div class="bg-gray-800 rounded-lg border {{ $borderColors[$log['level']] ?? 'border-gray-700' }} overflow-hidden">
                    <button onclick="toggleAccordion({{ $index }})" 
                            class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-750 transition-colors">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <!-- Badge niveau -->
                            <span class="px-2.5 py-1 text-xs font-bold rounded uppercase shrink-0 {{ $levelColors[$log['level']] ?? 'bg-gray-600 text-white' }}">
                                {{ $log['level'] }}
                            </span>
                            
                            <!-- Date -->
                            <span class="text-gray-500 text-sm shrink-0 font-mono">
                                {{ $log['date'] }}
                            </span>
                            
                            <!-- Environment badge -->
                            <span class="px-2 py-0.5 text-xs rounded bg-gray-700 text-gray-400 shrink-0">
                                {{ $log['environment'] }}
                            </span>
                            
                            <!-- Message preview -->
                            <span class="text-gray-300 truncate">
                                {{ Str::limit(preg_replace('/\s+/', ' ', $log['message']), 100) }}
                            </span>
                        </div>
                        
                        <!-- Arrow icon -->
                        <svg class="w-5 h-5 text-gray-500 shrink-0 ml-2 arrow-icon" id="arrow-{{ $index }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <!-- Accordion content -->
                    <div id="content-{{ $index }}" class="accordion-content">
                        <div class="px-4 py-4 bg-gray-900 border-t border-gray-700">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-gray-500 text-xs uppercase tracking-wider">Détails du log</span>
                                <button onclick="copyToClipboard({{ $index }})" 
                                        class="text-gray-500 hover:text-white text-xs flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Copier
                                </button>
                            </div>
                            <pre id="log-message-{{ $index }}" class="text-sm text-gray-300 whitespace-pre-wrap overflow-x-auto font-mono bg-gray-950 p-4 rounded-lg max-h-96 overflow-y-auto">{{ $log['message'] }}</pre>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-gray-400 text-lg font-medium mb-2">Aucun log trouvé</h3>
                    <p class="text-gray-600 text-sm">
                        @if($filter !== 'all')
                            Aucun log de type "{{ $filter }}" n'a été trouvé.
                            <a href="/" class="text-primary hover:underline">Voir tous les logs</a>
                        @else
                            Le fichier de logs est vide.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
        
        @if(count($logs) > 0)
            <div class="text-center py-6 text-gray-500 text-sm">
                Affichage de {{ count($logs) }} logs (max 500)
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-800 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-6 text-center text-gray-500 text-sm">
            KSSV API Log Viewer &middot; {{ date('Y') }}
        </div>
    </footer>

    <script>
        function toggleAccordion(index) {
            const content = document.getElementById('content-' + index);
            const arrow = document.getElementById('arrow-' + index);
            
            content.classList.toggle('open');
            arrow.classList.toggle('rotated');
        }
        
        function copyToClipboard(index) {
            const messageElement = document.getElementById('log-message-' + index);
            const text = messageElement.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                // Show brief notification
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                notification.textContent = 'Copié !';
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 2000);
            });
        }
    </script>
</body>
</html>
