<?php

namespace App\Http\Controllers;

use App\Models\CepScanLog;
use App\Models\CepScanSession;
use App\Models\LocationReport;
use Illuminate\Http\Request;

class CepScanController extends Controller
{
    public function index(Request $request)
    {
        $state = $request->get('state');

        $sessions = CepScanSession::orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $stats = [
            'total_scanned' => CepScanSession::sum('processed'),
            'total_success' => CepScanSession::sum('success'),
            'total_failed' => CepScanSession::sum('failed'),
            'total_ceps' => LocationReport::count(),
            'pending_ceps' => LocationReport::where('status', 'pending')->count(),
            'completed_ceps' => LocationReport::where('status', 'completed')->count(),
        ];

        $recentLogs = CepScanLog::with('session')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $stateStats = CepScanSession::selectRaw('state, COUNT(*) as sessions, SUM(processed) as processed, SUM(success) as success')
            ->whereNotNull('state')
            ->groupBy('state')
            ->orderBy('success', 'desc')
            ->get();

        return view('admin.cep-scan', compact(
            'sessions',
            'stats',
            'recentLogs',
            'stateStats'
        ));
    }

    public function start(Request $request)
    {
        $limit = $request->get('limit', 100);
        $delay = $request->get('delay', 2000);
        $state = $request->get('state');

        $command = "cep:scan --limit={$limit} --delay={$delay}";
        if ($state) {
            $command .= " --state={$state}";
        }

        return response()->json([
            'success' => true,
            'message' => 'Scan iniciado! Use o comando: php artisan '.$command,
            'command' => $command,
        ]);
    }

    public function stats()
    {
        $byState = CepScanSession::selectRaw('state, SUM(success) as total')
            ->whereNotNull('state')
            ->groupBy('state')
            ->orderBy('total', 'desc')
            ->get();

        $dailyStats = CepScanLog::selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'by_state' => $byState,
            'daily' => $dailyStats,
        ]);
    }
}
