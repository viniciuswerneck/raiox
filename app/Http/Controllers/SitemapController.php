<?php

namespace App\Http\Controllers;

use App\Models\LocationReport;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $reports = LocationReport::select('cep', 'updated_at', 'cidade', 'bairro')
            ->orderBy('updated_at', 'desc')
            ->limit(1000)
            ->get();
            
        $duels = \App\Models\RegionComparison::select('cep_a', 'cep_b', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(500)
            ->get();

        $cities = \App\Models\City::select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->view('sitemap', [
            'reports' => $reports,
            'duels' => $duels,
            'cities' => $cities,
        ])->header('Content-Type', 'text/xml');
    }

    public function robots(): Response
    {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Sitemap: " . url('/sitemap.xml') . "\n";
        
        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
