{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Home -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Explorar / Ranking -->
    <url>
        <loc>{{ route('ranking.index') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Relatórios Dinâmicos -->
    @foreach($reports as $report)
    <url>
        <loc>{{ route('report.show', $report->cep) }}</loc>
        <lastmod>{{ $report->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
    <!-- Duelos -->
    <url>
        <loc>{{ route('duels.index') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    @foreach($duels as $duel)
    <url>
        <loc>{{ route('report.compare', ['cepA' => $duel->cep_a, 'cepB' => $duel->cep_b]) }}</loc>
        <lastmod>{{ $duel->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    @endforeach
</urlset>
