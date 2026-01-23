<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogViewerController extends Controller
{
    /**
     * Display the log viewer page
     */
    public function index(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];
        $allLogs = [];
        
        if (File::exists($logPath)) {
            $content = File::get($logPath);
            
            // Parser les logs Laravel (format: [date] environment.LEVEL: message)
            $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*?)(?=\[\d{4}-\d{2}-\d{2}|\z)/s';
            
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $allLogs[] = [
                    'date' => $match[1],
                    'environment' => $match[2],
                    'level' => strtolower($match[3]),
                    'message' => trim($match[4]),
                ];
            }
            
            // Inverser pour avoir les plus récents en premier
            $allLogs = array_reverse($allLogs);
            
            // Limiter à 500 logs
            $allLogs = array_slice($allLogs, 0, 500);
        }
        
        // Compter par niveau (sur tous les logs avant filtrage)
        $counts = [
            'all' => count($allLogs),
            'emergency' => count(array_filter($allLogs, fn($l) => $l['level'] === 'emergency')),
            'alert' => count(array_filter($allLogs, fn($l) => $l['level'] === 'alert')),
            'critical' => count(array_filter($allLogs, fn($l) => $l['level'] === 'critical')),
            'error' => count(array_filter($allLogs, fn($l) => $l['level'] === 'error')),
            'warning' => count(array_filter($allLogs, fn($l) => $l['level'] === 'warning')),
            'notice' => count(array_filter($allLogs, fn($l) => $l['level'] === 'notice')),
            'info' => count(array_filter($allLogs, fn($l) => $l['level'] === 'info')),
            'debug' => count(array_filter($allLogs, fn($l) => $l['level'] === 'debug')),
        ];
        
        // Filtrer par niveau si demandé
        $filter = $request->get('level', 'all');
        if ($filter !== 'all') {
            $logs = array_values(array_filter($allLogs, fn($log) => $log['level'] === $filter));
        } else {
            $logs = $allLogs;
        }
        
        return view('welcome', compact('logs', 'filter', 'counts'));
    }
    
    /**
     * Clear all logs
     */
    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }
        return redirect('/')->with('success', 'Logs effacés avec succès');
    }
}
