<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raio-X de {{ $report->bairro ?: $report->cidade }} - {{ $report->cidade }}/{{ $report->uf }} | {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Explore o legado e a cultura regional de {{ $report->bairro ?: $report->cidade }} em {{ $report->cidade }}. Análise completa com indicadores de segurança, infraestrutura, IDH e valorização imobiliária.">
    <meta name="keywords" content="legado regional {{ $report->bairro }}, cultura de {{ $report->cidade }}, viver em {{ $report->cidade }}, bairro {{ $report->bairro }}, segurança {{ $report->cidade }}, caminhabilidade {{ $report->bairro }}, infraestrutura {{ $report->cidade }}, valorização imobiliária {{ $report->bairro }}">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="Territory Intelligence: {{ $report->bairro ?: $report->cidade }} - [{{ $report->uf }}]">
    <meta property="og:description" content="Descubra o perfil completo de {{ $report->bairro ?: $report->cidade }}. Segurança: {{ $report->safety_level }}, Caminhabilidade: {{ $report->walkability_score }}pts. Análise premium via Territory Engine v3.0.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $report->wiki_json['image'] ?? url('/hero_background_city_1772568797393.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Raio-X: {{ $report->bairro ?: $report->cidade }} - {{ $report->cidade }}/{{ $report->uf }}">
    <meta name="twitter:description" content="Índices de infraestrutura e segurança para o CEP {{ substr($report->cep, 0, 5) }}-{{ substr($report->cep, 5, 3) }}.">
    <meta name="twitter:image" content="{{ $report->wiki_json['image'] ?? url('/hero_background_city_1772568797393.png') }}">

    <!-- Schema.org JSON-LD (Place & AdministrativeArea) -->
    <script type="application/ld+json">
    [
        {
          "@@context": "https://schema.org",
          "@@type": "Place",
          "name": "{{ $report->bairro ?: $report->cidade }}",
          "description": "Análise territorial premium de {{ $report->bairro }} na cidade de {{ $report->cidade }}. Inclui dados de segurança e infraestrutura.",
          "address": {
            "@@type": "PostalAddress",
            "addressLocality": "{{ $report->cidade }}",
            "addressRegion": "{{ $report->uf }}",
            "postalCode": "{{ $report->cep }}",
            "addressCountry": "BR"
          },
          "geo": {
            "@@type": "GeoCoordinates",
            "latitude": "{{ $report->lat }}",
            "longitude": "{{ $report->lng }}"
          },
          "aggregateRating": {
            "@@type": "AggregateRating",
            "ratingValue": "{{ $report->general_score ?? $report->final_score }}",
            "bestRating": "100",
            "reviewCount": "15"
          }
        },
        {
          "@@context": "https://schema.org",
          "@@type": "Dataset",
          "name": "Estatísticas Territoriais de {{ $report->bairro ?: $report->cidade }}",
          "description": "Dados de caminhabilidade, segurança e infraestrutura urbana.",
          "license": "https://creativecommons.org/licenses/by/4.0/"
        }
    ]
    </script>

    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Leaflet Map & Plugins -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
    

    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --glass: rgba(255, 255, 255, 0.75);
            --card-radius: 24px;
            --font-main: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: var(--font-main);
            overflow-x: hidden;
            padding-top: 72px; /* Espaço para a navbar fixa */
        }

        /* NAVBAR & BREADCRUMBS */
        .nav-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            height: 72px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "\f105";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 10px;
            color: var(--secondary);
            opacity: 0.5;
        }

        .breadcrumb-custom .breadcrumb-item a {
            color: var(--secondary);
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: color 0.2s;
        }

        .breadcrumb-custom .breadcrumb-item a:hover {
            color: var(--primary);
        }

        .breadcrumb-custom .breadcrumb-item.active {
            color: var(--dark);
            font-weight: 800;
        }

        /* OMNISEARCH */
        .omnisearch-trigger {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 16px;
            color: #64748b;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 250px;
        }

        .omnisearch-trigger:hover {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        }

        .omnisearch-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            padding: 10vh 1rem;
        }

        .omnisearch-card {
            background: white;
            border-radius: 28px;
            max-width: 700px;
            margin: 0 auto;
            overflow: hidden;
            box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.5);
            transform: translateY(-20px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .omnisearch-active .omnisearch-card {
            transform: translateY(0);
        }

        /* SKELETONS PRO */
        .skeleton {
            background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
            display: inline-block;
            color: transparent !important;
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-card {
            height: 200px;
            width: 100%;
            border-radius: 24px;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: var(--font-heading);
            font-weight: 800;
        }

        /* Hero Imersivo */
        .hero-section {
            position: relative;
            min-height: 480px;
            background-color: var(--dark);
            color: white;
            padding: 80px 0 140px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-bg-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(0deg, rgba(15, 23, 42, 1) 0%, rgba(15, 23, 42, 0.4) 60%, rgba(15, 23, 42, 0.2) 100%);
            z-index: 1;
        }

        .hero-bg-img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.5;
            z-index: 0;
            transition: transform 10s ease;
        }

        .hero-section:hover .hero-bg-img {
            transform: scale(1.1);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .cep-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 99px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px; /* Reduced to avoid breaking flex lines */
        }

        /* Dashboard Bento Grid Style */
        .dashboard-container {
            margin-top: -120px;
            position: relative;
            z-index: 10;
        }

        .card-pro {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: var(--card-radius);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            padding: 32px;
        }

        .card-pro:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 255, 255, 0.8);
        }

        .metric-icon-pro {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1);
        }

        /* Status Pills */
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Mapa e POIs */
        #map-container {
            height: 520px;
            border-radius: var(--card-radius);
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 15px 35px -10px rgba(15, 23, 42, 0.15);
        }

        #map { height: 100%; width: 100%; }

        .poi-drawer {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
            scrollbar-width: thin;
        }

        .poi-item {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #f1f5f9;
            transition: transform 0.2s ease;
        }

        .poi-item:hover {
            transform: scale(1.02);
            border-color: var(--primary);
        }

        /* Typography Decorations */
        .editorial-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #334155;
            font-weight: 400;
            letter-spacing: -0.01em;
        }

        .editorial-text p {
            margin-bottom: 1.5rem;
        }

        .drop-cap::first-letter {
            float: left;
            font-family: var(--font-heading);
            font-size: 5rem;
            line-height: 0.7;
            padding-top: 10px;
            padding-right: 12px;
            padding-left: 2px;
            font-weight: 900;
            color: var(--primary);
            text-transform: uppercase;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reveal { animation: fadeInUp 0.8s ease backwards; }

        /* Categorias do Mapa */
        .map-category-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 99px;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .map-category-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 8px 15px -3px rgba(99, 102, 241, 0.3);
        }

        /* ==================== RESPONSIVE MOBILE ==================== */
        @media (max-width: 767.98px) {
            .hero-section {
                min-height: auto;
                padding: 60px 0 100px;
            }

            .hero-section h1.display-1 {
                font-size: 2.8rem !important;
            }

            .dashboard-container {
                margin-top: -50px;
            }

            .card-pro {
                padding: 16px;
            }

            #map-print-section {
                height: auto !important;
            }

            #map-container {
                height: 320px !important;
            }

            .map-category-btn {
                padding: 7px 12px;
                font-size: 11px;
            }

            .poi-drawer {
                max-height: 280px;
            }

            .editorial-text {
                font-size: 1rem;
            }

            .drop-cap::first-letter {
                font-size: 3.5rem;
            }
        }

        /* ==================== GAMIFICATION & SCORES ==================== */
        .score-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(25px);
            border-radius: var(--card-radius);
            padding: 32px 42px;
            color: #1e293b;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
            margin-top: 15px;
            margin-bottom: 10px;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s;
        }

        .score-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.12);
        }

        .score-circle-container {
            position: relative;
            width: 170px;
            height: 170px;
            margin: 0 auto;
        }

        .score-number {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
        }

        .score-val {
            font-size: 4rem;
            font-weight: 900;
            line-height: 0.8;
            letter-spacing: -2px;
            margin-bottom: 2px;
        }

        .neon-progress {
            height: 10px;
            background: #f1f5f9;
            border-radius: 99px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .neon-bar {
            height: 100%;
            border-radius: 99px;
            transition: width 1.8s cubic-bezier(0.2, 0, 0, 1);
        }

        .badge-medal {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px 20px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-bottom: 10px;
        }

        .badge-medal:hover {
            transform: translateX(8px);
            background: white;
            border-color: var(--primary);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .badge-icon {
            font-size: 18px;
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        /* Float Comparison UI */
        .compare-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: var(--primary);
            color: white;
            width: 65px;
            height: 65px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.5);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid white;
        }

        .compare-fab:hover {
            transform: scale(1.15) rotate(10deg);
            background: var(--primary-dark);
        }

        .compare-panel {
            position: fixed;
            bottom: 110px;
            right: 30px;
            width: 380px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 28px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.15);
            z-index: 999;
            transform: translateY(20px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .compare-panel.active {
            transform: translateY(0);
            opacity: 1;
            pointer-events: all;
        }

        /* PREMIUM LOADER */
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(25px);
            z-index: 9999;
        }

        .ai-pulse {
            animation: ai-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes ai-pulse {
            0%, 100% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            50% { transform: scale(1.05); opacity: 0.9; box-shadow: 0 0 40px 10px rgba(99, 102, 241, 0.4); }
        }

        .orbit {
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* --- PRINT ENGINE (Luxury Specialist Edition) --- */
        /* --- PRINT ENGINE (Word Document Specialist Edition) --- */
        @media print {
            /* Hide all screen-only UI and the main dashboard to prevent overlap and blank pages */
            .no-print, .btn, .compare-fab, .omnisearch-trigger, #explorer-overlay, #loader, 
            .reprocess-btn, .modal, .omnisearch-overlay, .nav-glass, 
            .hero-section, .dashboard-container, footer { 
                display: none !important; 
            }
            
            .d-print-block { display: block !important; }
            
            @page { 
                margin: 2cm; 
                size: A4 portrait; 
            }
            
            html, body { 
                height: auto !important; 
                overflow: visible !important; 
                background: #fff !important; 
                color: #000 !important; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
                padding: 0 !important;
                margin: 0 !important;
                font-family: "Calibri", "Candara", "Segoe UI", "Arial", sans-serif !important;
                font-size: 11pt;
            }

            p, table, h1, h2, h3, h4, h5 {
                break-inside: avoid;
            }

            h1, h2, h3, h4, h5 {
                color: #2F5496 !important;
                font-family: "Calibri Light", "Segoe UI Light", sans-serif !important;
                margin-top: 15pt;
                margin-bottom: 8pt;
                break-after: avoid;
            }

            h1 { font-size: 22pt; border-bottom: 1.5pt solid #2F5496; padding-bottom: 4pt; margin-bottom: 15pt; }
            h2 { font-size: 16pt; margin-top: 20pt; border-bottom: 0.5pt solid #D6DCE4; padding-bottom: 2pt; }

            /* Formal Word Tables */
            .word-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 12pt 0; 
                break-inside: avoid;
                border: 0.5pt solid #8496B0;
            }
            .word-table th, .word-table td { 
                border: 0.5pt solid #8496B0; 
                padding: 6pt 10pt; 
                text-align: left; 
                font-size: 10pt;
            }
            .word-table th { background-color: #D6DCE4 !important; font-weight: bold; }
            .word-table tr:nth-child(even) { background-color: #F9F9F9 !important; }

            .editorial-text { font-size: 11pt !important; line-height: 1.5 !important; text-align: justify !important; }

            /* --- WORD-STYLE COVER (Fixed for A4) --- */
            .pdf-cover {
                min-height: 23cm; /* Using min-height to stay safe but allow content growth */
                max-height: 25cm;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                padding: 1cm;
                box-sizing: border-box;
                page-break-after: always;
                border: none !important;
            }

            .pdf-header-meta {
                display: flex;
                justify-content: space-between;
                border-bottom: 1.5pt solid #2F5496;
                padding-bottom: 8pt;
                margin-bottom: 3cm;
            }

            .pdf-title-block { 
                flex-grow: 1; 
                border-left: 5pt solid #2F5496;
                padding-left: 20pt;
            }
            .pdf-title-block h1 { font-size: 40pt !important; border: none !important; color: #2E75B6 !important; margin: 0; }
            
            .pdf-footer-meta {
                border-top: 0.5pt solid #D6DCE4;
                padding-top: 20pt;
            }

            .pdf-page-header {
                font-size: 8pt;
                color: #999;
                border-bottom: 0.5pt solid #EEE;
                padding-bottom: 4pt;
                margin-bottom: 20pt !important;
            }

            #map-container { height: 400px !important; border: 0.5pt solid #8496B0 !important; break-inside: avoid; }
            .page-break { page-break-before: always; height: 0; overflow: hidden; margin: 0; padding: 0; }
        }

        /* --- MAP EVOLUTION STYLES --- */
        .m-cluster {
            background: white;
            border-radius: 50%;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            color: var(--primary);
            font-size: 14px;
        }

        .poi-pin {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.2s;
        }

        .poi-pin:hover {
            transform: scale(1.2) rotate(5deg);
        }

        .map-style-btn.active, .explorer-style-btn.active {
            background-color: var(--primary) !important;
            color: white !important;
            border-color: var(--primary) !important;
        }

        .radius-tooltip {
            background: var(--dark);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 800;
            font-size: 10px;
            padding: 4px 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .main-center-marker .pulse-container {
            width: 40px;
            height: 40px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-center-marker .dot {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .main-center-marker .pulse {
            position: absolute;
            width: 100%;
            height: 100%;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.4;
            animation: main-pulse 2s infinite;
            z-index: 1;
        }

        @keyframes main-pulse {
            0% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        .poi-popup .fw-black { font-size: 14px; }
        .poi-popup { min-width: 150px; }

        .map-controls-premium {
            position: absolute;
            top: 15px;
            left: 55px; /* Evita o botão de zoom default */
            right: 15px;
            z-index: 1000;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .map-controls-premium {
                left: 10px;
                top: 50px;
                justify-content: center;
            }
        }

        /* --- EXPLORER MODE OVERLAY --- */
        #explorer-overlay {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: #fff;
            z-index: 2147483647; /* Max Possible Z-Index to avoid overlaps */
            display: none;
            flex-direction: column;
            animation: fadeIn 0.3s ease-out;
        }

        /* Esconder Navbar principal quando o explorador estiver aberto */
        body.explorer-active .nav-glass {
            display: none !important;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .explorer-header {
            height: 70px;
            background: white;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            padding: 0 30px;
            gap: 20px;
            flex-shrink: 0;
        }

        @media (max-width: 991px) {
            .explorer-header { height: auto; padding: 15px; flex-wrap: wrap; gap: 10px; }
            .explorer-header .btn-group { display: none; } /* Show in map controls instead or hide for space */
            .explorer-header h5 { font-size: 1rem; }
            .explorer-header p { display: none; }
        }

        .explorer-content {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        #explorer-map {
            flex: 1;
            height: 100%;
        }

        .explorer-list-side {
            width: 420px;
            background: #f8fafc;
            border-left: 1px solid #e2e8f0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .explorer-poi-card {
            background: white;
            border: 1px solid #f1f5f9;
            border-radius: 16px;
            padding: 15px;
            margin: 10px 20px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .explorer-poi-card:hover {
            border-color: var(--primary);
            box-shadow: 0 10px 20px -10px rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }

        .explorer-category-header {
            padding: 20px 20px 10px;
            font-size: 12px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .explorer-category-header i { width: 15px; }

        .explorer-poi-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }

        body.explorer-active {
            overflow: hidden !important;
        }
    </style>
</head>
<body>

    <!-- NAV BAR & BREADCRUMBS -->
    <nav class="nav-glass no-print">
        <div class="container-fluid px-lg-5 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <a href="{{ route('home') }}" class="text-decoration-none">
                    <img src="{{ asset('favicon.png') }}" alt="Logo" style="height: 32px; width: 32px; object-fit: cover; border-radius: 8px;">
                </a>
                
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb breadcrumb-custom mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ $city ? route('city.show', $city->slug) : route('city.show', ['slug' => Str::slug($report->cidade . '-' . $report->uf)]) }}">{{ $report->cidade }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $report->bairro ?: 'Relatório' }}</li>
                    </ol>
                </nav>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="omnisearch-trigger" onclick="openOmnisearch()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span class="d-none d-lg-inline">Buscar outro CEP ou bairro...</span>
                    <span class="ms-auto d-none d-lg-inline text-muted small" style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px;">Ctrl K</span>
                </div>
                <a href="{{ route('ranking.index') }}" class="btn btn-dark btn-sm rounded-pill px-3 font-heading d-none d-md-flex align-items-center gap-2" style="font-size: 11px;">
                    <i class="fa-solid fa-ranking-star"></i> EXPLORAR RANKINGS
                </a>
            </div>
        </div>
    </nav>

    <!-- OMNISEARCH OVERLAY -->
    <div id="omnisearch" class="omnisearch-overlay" onclick="closeOmnisearch(event)">
        <div class="omnisearch-card" onclick="event.stopPropagation()">
            <div class="p-4 border-bottom bg-light d-flex align-items-center gap-3">
                <i class="fa-solid fa-magnifying-glass fs-4 text-primary"></i>
                <input type="text" id="omni-input" placeholder="Digite o CEP ou nome do bairro..." class="form-control border-0 bg-transparent fs-4 fw-bold p-0 shadow-none" autocomplete="off">
                <button class="btn btn-link text-muted p-0" onclick="closeOmnisearch(event)"><i class="fa-solid fa-xmark fs-4"></i></button>
            </div>
            <div id="omni-results" class="p-2 overflow-auto" style="max-height: 400px; min-height: 100px;">
                <div class="p-4 text-center text-muted small uppercase fw-bold tracking-widest">Digite para buscar...</div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center small text-muted">
                <span>Dica: Tente buscar por "Jardim Paulista" ou "01415-000"</span>
                <span>ESC para fechar</span>
            </div>
        </div>
    </div>

    <!-- LOADER / QUEUE OVERLAY (Bloqueia apenas se estiver na fila ou falhar) -->
    @if(in_array($report->status, ['pending', 'failed']) || $report->cidade === 'Localizando...')
    <div id="loader" class="d-flex flex-column align-items-center justify-content-center text-white" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.98); backdrop-filter: blur(25px); z-index: 99999;">
        <div class="relative flex items-center justify-center mb-5" style="width: 200px; height: 200px;">
            <div class="absolute inset-0 orbit opacity-20">
                <div class="absolute top-0 left-1/2 w-4 h-4 bg-indigo-500 rounded-full blur-sm"></div>
                <div class="absolute bottom-0 left-1/2 w-4 h-4 bg-purple-500 rounded-full blur-sm"></div>
            </div>
            <div class="relative w-24 h-24 bg-indigo-600 rounded-full flex items-center justify-center ai-pulse shadow-2xl shadow-indigo-500/50">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.338 2.798H4.136c-1.368 0-2.338-1.798-1.338-2.798L4 15.298" />
                </svg>
            </div>
        </div>
        <div class="text-center space-y-3 px-4" style="max-width: 500px">
            <h3 class="fw-black text-white h2 mb-1">
                @if($report->status === 'pending')
                    Sincronizando Território
                @else
                    Ops! Algo deu errado
                @endif
            </h3>
            
            @if($report->status === 'failed')
                <p class="text-danger small fw-bold text-uppercase mb-4">{{ $report->error_message ?? 'Erro desconhecido' }}</p>
                <a href="{{ route('home') }}" class="btn btn-outline-light rounded-pill px-4">Tentar outro CEP</a>
            @else
                <p id="queue-text" class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.3em;">
                    Aguardando na fila de satélites...
                </p>
                <div class="progress rounded-pill mx-auto mb-4" style="width: 250px; height: 6px; background: rgba(255,255,255,0.1);">
                    <div id="queue-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: 20%"></div>
                </div>
                <div class="p-3 rounded-4 border border-white/10 bg-white/5 backdrop-blur-sm">
                    <p class="small text-white-50 mb-0">
                        <i class="fa-solid fa-clock me-2 text-primary"></i>
                        Isso pode levar até 60 segundos porque estamos gerando os dados em tempo real. Não feche esta aba.
                    </p>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Script de Polling Inteligente --}}
    @if(in_array($report->status, ['pending', 'processing', 'processing_text']))
    <script>
        const pollStatus = async () => {
            try {
                const response = await fetch('/api/report-status/{{ $report->cep }}');
                const data = await response.json();
                
                // Se saiu do processamento inicial ou terminou a narrativa
                if (
                    ('{{ $report->status }}' === 'processing' && data.status !== 'processing') ||
                    ('{{ $report->status }}' === 'processing_text' && data.status === 'completed') ||
                    (data.status === 'failed')
                ) {
                    window.location.reload();
                    return;
                }
                
                setTimeout(pollStatus, 3000);
            } catch (e) {
                console.error("Erro no polling:", e);
                setTimeout(pollStatus, 5000);
            }
        };
        pollStatus();
    </script>
    @endif

    @php
        $wiki = $report->wiki_json ?? [];
        $climate = $report->climate_json['current_weather'] ?? null;
        $aqi = $report->air_quality_index;
        $pois = $report->pois_json ?? [];
        $ibgeRaw = $report->raw_ibge_data ?? [];
        
        $translations = [
            'supermarket' => 'Supermercado', 'restaurant' => 'Restaurante', 'pharmacy' => 'Farmácia', 'cafe' => 'Café/Padaria',
            'bakery' => 'Padaria', 'school' => 'Escola', 'bank' => 'Banco', 'hospital' => 'Hospital',
            'bus_stop' => 'Parada de Ônibus', 'bus_station' => 'Terminal de Ônibus', 'bicycle_parking' => 'Estac. Bicicletas', 
            'convenience' => 'Conveniência', 'clothes' => 'Loja de Roupas', 'mall' => 'Shopping', 'fuel' => 'Posto Combustível', 
            'bar' => 'Bar/Pub', 'fast_food' => 'Fast Food', 'university' => 'Universidade', 'clinic' => 'Clínica', 
            'dentist' => 'Dentista', 'pub' => 'Pub/Bar', 'beauty' => 'Salão de Beleza', 'department_store' => 'Loja de Depto',
            'place_of_worship' => 'Igreja/Templo', 'cinema' => 'Cinema', 'theatre' => 'Teatro',
            'library' => 'Biblioteca', 'post_office' => 'Correios', 'park' => 'Parque/Lazer',
            'gym' => 'Academia', 'sports_centre' => 'Centro Esportivo', 'playground' => 'Playground',
            'ice_cream' => 'Sorveteria', 'food_court' => 'Praça de Alimentação', 'hardware' => 'Material de Construção',
            'electronics' => 'Eletrônicos', 'furniture' => 'Móveis', 'optician' => 'Ótica', 'books' => 'Livraria',
            'car_repair' => 'Oficina Mecânica', 'car_wash' => 'Lava Rápido', 'pet_shop' => 'Pet Shop',
            'veterinary' => 'Veterinária', 'hairdresser' => 'Cabeleireiro', 'laundry' => 'Lavanderia',
            'police' => 'Polícia / Delegacia', 'fire_station' => 'Bombeiros', 'townhall' => 'Prefeitura',
            'public_service' => 'Serviço Público', 'marketplace' => 'Feira Livre / Mercado', 'monument' => 'Monumento',
            'museum' => 'Museu', 'arts_centre' => 'Centro Cultural', 'attraction' => 'Atração Turística', 
            'artwork' => 'Arte / Estátua', 'station' => 'Estação de Trem/Metrô', 'kindergarten' => 'Creche',
            'doctors' => 'Médico/UBS', 'subway_entrance' => 'Entrada do Metrô'
        ];

        // 7 Categorias Oficiais
        $poi_food = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['restaurant', 'cafe', 'fast_food', 'bakery', 'bar', 'pub', 'ice_cream', 'food_court']));
        
        $poi_health = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['pharmacy', 'hospital', 'clinic', 'dentist', 'doctors', 'veterinary']));
        
        $poi_education = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['school', 'university', 'kindergarten', 'childcare', 'library']));
        
        $poi_transport = array_filter($pois, fn($p) => ($p['tags']['highway'] ?? '') === 'bus_stop' || ($p['tags']['amenity'] ?? '') === 'bus_station' || ($p['tags']['railway'] ?? '') === 'station' || ($p['tags']['amenity'] ?? '') === 'subway_entrance');
        
        $poi_shopping = array_filter($pois, fn($p) => isset($p['tags']['shop']) || in_array($p['tags']['amenity'] ?? '', ['marketplace', 'fuel']));
        
        $poi_leisure = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['cinema', 'theatre', 'arts_centre']) || in_array($p['tags']['leisure'] ?? '', ['park', 'gym', 'sports_centre', 'playground', 'stadium', 'garden', 'square']) || isset($p['tags']['tourism']) || isset($p['tags']['historic']));
        
        $poi_services = array_filter($pois, fn($p) => in_array($p['tags']['amenity'] ?? '', ['bank', 'atm', 'police', 'fire_station', 'post_office', 'townhall', 'courthouse', 'community_centre', 'place_of_worship']));

        // Theme colors for score
        $walkColor = match($report->walkability_score) {
            'A' => '#10b981', 'B' => '#6366f1', default => '#64748b'
        };
        $walkLabel = match($report->walkability_score) {
            'A' => 'Excelente Escopo', 'B' => 'Funcionalidade Básica', default => 'Dependente de Veículo'
        };

        // AQI Logic
        $aqiRes = [
            'level' => 'Bom', 'color' => 'success', 'desc' => 'Qualidade ideal para atividades ao ar livre.'
        ];
        if($aqi > 20) $aqiRes = ['level' => 'Moderado', 'color' => 'warning', 'desc' => 'Pessoas sensíveis podem ser afetadas.'];
        if($aqi > 40) $aqiRes = ['level' => 'Ruim', 'color' => 'danger', 'desc' => 'Risco moderado para toda a população.'];

        // Safety Logic
        $safetyRaw = strtoupper($report->safety_level ?? '');
        $sColor = match(true) {
            str_contains($safetyRaw, 'ALT') => 'success',
            str_contains($safetyRaw, 'MODERAD') || str_contains($safetyRaw, 'MEDI') => 'warning',
            str_contains($safetyRaw, 'BAIX') => 'danger',
            default => 'secondary'
        };
    @endphp

    <!-- Botão Flutuante de Comparação -->
    <div class="compare-fab no-print" onclick="toggleCompare()" title="Comparar Bairros">
        <i class="fa-solid fa-right-left"></i>
    </div>

    <!-- --- DOSSIÊ WORD-STYLE (Layout para Documento Oficial) --- -->
    <div class="d-none d-print-block print-only-dossier">
        <!-- CAPA DO DOSSIÊ -->
        <div class="pdf-cover">
            <div class="pdf-header-meta">
                <span class="badge-print">Protocolo #{{ substr($report->uuid, 0, 8) }}</span>
                <span class="badge-print">Emissão: {{ date('d/m/Y') }}</span>
            </div>
            
            <div class="pdf-logo-center">
                <img src="{{ asset('favicon.png') }}" width="60" alt="Logo">
                <div>
                    <h2 class="h4 fw-bold mb-0" style="color: #2F5496 !important;">RAIO-X VIZINHANÇA</h2>
                    <p class="small text-muted mb-0">Inteligência de Dados e Diagnóstico Territorial</p>
                </div>
            </div>

            <div class="pdf-title-block">
                <h6 class="text-muted text-uppercase fw-bold mb-2">Relatório Técnico Individual</h6>
                <h1 class="display-3 fw-bold mb-0">{{ $report->bairro ?: $report->cidade }}</h1>
                <div class="h3 fw-normal text-muted mb-4">{{ $report->cidade }} / {{ $report->uf }}</div>
            </div>

            <div class="pdf-footer-meta">
                <div class="row w-100" style="display: flex !important; flex-direction: row !important;">
                    <div class="col-4" style="width: 33.3%;">
                        <div class="small fw-bold text-muted text-uppercase">Data do Registro</div>
                        <div class="fw-bold">{{ $report->created_at->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-4" style="width: 33.3%;">
                        <div class="small fw-bold text-muted text-uppercase">Status Regional</div>
                        <div class="fw-bold">{{ $report->territorial_classification ?: 'Urbano Consolidado' }}</div>
                    </div>
                    <div class="col-4" style="width: 33.3%;">
                        <div class="small fw-bold text-muted text-uppercase">Identificação</div>
                        <div class="fw-bold">CEP {{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $report->cep) }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-top">
                    <p class="small text-muted">Aviso: Os dados aqui apresentados são resultantes do processamento de múltiplas APIs e modelos de análise espacial. Este documento é para fins informativos e de diagnóstico prévio.</p>
                </div>
            </div>
        </div>
        
        <div class="page-break"></div>
        
        <!-- HEADER PARA PÁGINAS INTERNAS -->
        <div class="pdf-page-header d-flex justify-content-between align-items-center">
             <div class="d-flex align-items-center gap-2">
                <span class="fw-bold">{{ config('app.name') }}</span>
             </div>
             <div class="fw-bold">LOCALIDADE: {{ mb_strtoupper($report->bairro ?: $report->cidade) }}</div>
        </div>

        <h1>1. Resumo da Localidade</h1>
        <p>A região analisada no entorno do CEP {{ $report->cep }} apresenta as seguintes características fundamentais de infraestrutura e qualidade de vida. Este diagnóstico resume os principais indicadores coletados pelo Territory Engine v3.0.</p>
        
        <table class="word-table mb-4">
            <thead>
                <tr>
                    <th colspan="2">Dados Descritivos do Território</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="40%"><strong>Localização:</strong></td>
                    <td>{{ $report->bairro ?: 'Centro / Área Central' }}</td>
                </tr>
                <tr>
                    <td><strong>Cidade e Estado:</strong></td>
                    <td>{{ $report->cidade }} - {{ $report->uf }}</td>
                </tr>
                <tr>
                    <td><strong>Ponto de Referência:</strong></td>
                    <td>{{ $report->logradouro ?: 'Logradouro não informado' }}</td>
                </tr>
                <tr>
                    <td><strong>População Aproximada (Região):</strong></td>
                    <td>{{ number_format($report->populacao ?: 0, 0, ',', '.') }} habitantes</td>
                </tr>
            </tbody>
        </table>

        <h1>2. Indicadores de Performance</h1>
        <p>Abaixo seguem os índices calculados para a vizinhança, permitindo uma comparação técnica entre diferentes áreas urbanas.</p>

        <table class="word-table mb-4">
            <thead>
                <tr>
                    <th width="40%">Índice Analítico</th>
                    <th width="30%">Pontuação</th>
                    <th width="30%">Classificação</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Caminhabilidade:</strong></td>
                    <td>{{ $report->walkability_score }} / 100</td>
                    <td>{{ $walkLabel }}</td>
                </tr>
                <tr>
                    <td><strong>Sensação de Segurança:</strong></td>
                    <td>{{ $report->safety_index ?: ($report->safety_level == 'Baixo' ? 30 : ($report->safety_level == 'Médio' ? 60 : 90)) }} / 100</td>
                    <td>{{ $report->safety_level }}</td>
                </tr>
                <tr>
                    <td><strong>Infraestrutura Urbana:</strong></td>
                    <td>{{ $report->infra_score ?: 75 }} / 100</td>
                    <td>{{ ($report->infra_score ?: 75) >= 80 ? 'Excelente' : (($report->infra_score ?: 75) >= 50 ? 'Adequada' : 'Em desenvolvimento') }}</td>
                </tr>
                <tr>
                    <td><strong>Mobilidade e Acesso:</strong></td>
                    <td>{{ $report->mobility_score ?: 70 }} / 100</td>
                    <td>{{ ($report->mobility_score ?: 70) >= 70 ? 'Fluida' : 'Saturada' }}</td>
                </tr>
            </tbody>
        </table>

        <h1>3. Mercado Imobiliário e Valorização</h1>
        <p>Abaixo seguem as projeções e estimativas para o mercado imobiliário nesta região específica, baseadas em tendências de mercado e disponibilidade local.</p>

        <table class="word-table mb-4">
            <thead>
                <tr>
                    <th width="40%">Atributo Imobiliário</th>
                    <th width="60%">Análise e Preditiva</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Preço Estimado do m²:</strong></td>
                    <td>{{ $report->real_estate_json['preco_m2'] ?? 'Sob consulta regional' }}</td>
                </tr>
                <tr>
                    <td><strong>Tendência de Curto Prazo:</strong></td>
                    <td>{{ $report->real_estate_json['tendencia_valorizacao'] ?? 'Estabilidade projetada' }}</td>
                </tr>
                <tr>
                    <td><strong>Perfil Predominante:</strong></td>
                    <td>{{ $report->real_estate_json['perfil_imoveis'] ?? 'Imóveis residenciais padrão' }}</td>
                </tr>
            </tbody>
        </table>

        <h1>4. Localização Estratégica</h1>
        <p>Abaixo, a representação cartográfica da região do CEP {{ $report->cep }}. O marcador central indica o ponto de referência principal utilizado para o cálculo dos indicadores de vizinhança.</p>
        
        <div id="map-print-container" style="margin: 20pt 0; break-inside: avoid;">
            <!-- O mapa original será movido ou clonado aqui via JS ou estilo -->
            <div id="map-container" class="border border-secondary shadow-none"></div>
        </div>

        <h1>5. Dados Ambientais e Clima</h1>
        <p>Condições atmosféricas observadas e diagnósticos de qualidade do ar e infraestrutura ambiental.</p>

        <table class="word-table mb-5">
            <thead>
                <tr>
                    <th>Indicador Ambiental</th>
                    <th>Valor / Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Índice de Qualidade do Ar (AQI):</strong></td>
                    <td>{{ $report->air_quality_index ?: 'Bom' }}</td>
                </tr>
                <tr>
                    <td><strong>Temperatura Média (Sazonal):</strong></td>
                    <td>{{ $report->climate_json['temperature'] ?? '--' }}°C</td>
                </tr>
                <tr>
                    <td><strong>Sensação Térmica:</strong></td>
                    <td>{{ $report->climate_json['feels_like'] ?? '--' }}°C</td>
                </tr>
            </tbody>
        </table>
        
        <div class="page-break"></div>
        
        <h1>5. Diagnóstico Narrativo e Histórico</h1>
        <div class="editorial-text" style="orphans: 4; widows: 4;">
            {!! nl2br(e($report->history_extract)) !!}
        </div>

        <h1>6. Equipamentos e Serviços de Vizinhança</h1>
        <p>Levantamento dos principais pontos de interesse identificados em um raio de 1.5km a partir do centro do CEP.</p>

        <table class="word-table mb-4">
            <thead>
                <tr>
                    <th width="40%">Categoria</th>
                    <th width="60%">Locais Identificados</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $pois = collect($report->pois_json ?? []);
                    $groups = $pois->groupBy(fn($p) => $p['tags']['amenity'] ?? $p['tags']['shop'] ?? 'Outros')->take(8);
                @endphp
                @foreach($groups as $category => $items)
                    <tr>
                        <td><strong>{{ ucfirst($category) }}:</strong></td>
                        <td>
                            @foreach($items->take(3) as $item)
                                {{ $item['tags']['name'] ?? 'Local sem nome' }}{{ !$loop->last ? ', ' : '' }}
                            @endforeach
                            {{ $items->count() > 3 ? ' (e outros ' . ($items->count() - 3) . ')' : '' }}
                        </td>
                    </tr>
                @endforeach
                @if($groups->isEmpty())
                    <tr>
                        <td colspan="2" class="text-center italic">Nenhum equipamento de relevância mapeado nesta escala.</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="mt-5 p-4" style="border: 0.5pt solid #D6DCE4; background: #F8F9FA;">
            <p class="small mb-0"><strong>Nota Final:</strong> Este dossiê foi gerado automaticamente pelo sistema {{ config('app.name') }}. As informações são consolidadas a partir de dados geográficos, históricos e censitários. Para decisões de investimento robustas, recomenda-se visita técnica in loco.</p>
        </div>
    </div>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <!-- Floating Logo Overlay -->
        <div class="position-absolute top-0 start-0 p-4 no-print" style="z-index: 100;">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-4 border border-white border-opacity-20 shadow-lg overflow-hidden" style="width: 44px; height: 44px;">
                    <img src="{{ asset('favicon.png') }}" class="w-100 h-100 object-fit-cover" alt="Logo">
                </div>
                <div class="text-white">
                    <div class="fw-black h6 mb-0 text-uppercase italic tracking-tighter" style="font-size: 14px;">{{ config('app.name') }}</div>
                    <div class="small opacity-50 text-uppercase fw-bold" style="font-size: 7px; letter-spacing: 2px;">Terrestrial Intelligence</div>
                </div>
            </div>
        </div>
        @if($wiki['image'] ?? null)
            <img src="{{ $wiki['image'] }}" class="hero-bg-img" alt="{{ $report->cidade }}">
        @endif
        <div class="hero-bg-overlay"></div>
        
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-8 reveal">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <div class="cep-badge no-print mb-0">
                            <i class="fa-solid fa-location-crosshairs text-primary"></i>
                            @php $fCep = preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $report->cep); @endphp
                            <span>REGIÃO DO CEP {{ $fCep }}</span>
                        </div>
                        
                        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm no-print d-flex align-items-center gap-2" style="background: var(--primary); border: 2px solid rgba(255,255,255,0.2); height: 42px;">
                             <i class="fa-solid fa-file-pdf"></i> BAIXAR DOSSIÊ PDF
                        </button>

                        @if($report->territorial_classification)
                        <div class="cep-badge no-print mb-0" style="background: rgba(99, 102, 241, 0.15); border-color: rgba(99, 102, 241, 0.4);">
                            <i class="fa-solid fa-robot text-primary"></i>
                            <span class="text-white">TURMA AACT: <span class="text-primary fw-black">{{ mb_strtoupper($report->territorial_classification) }}</span></span>
                        </div>
                        @endif
                    </div> <!-- d-flex closure -->
                    
                    <h1 class="display-1 text-white mb-2" style="font-size: clamp(2.5rem, 5vw, 4rem);">
                        @if($report->bairro) {{ $report->bairro }} <br> @endif
                        <span class="{{ $report->bairro ? 'h3 text-white-50 fw-light' : '' }}">
                            @if($city)
                                <a href="{{ route('city.show', $city->slug) }}" class="text-white-50 text-decoration-none hover-white">
                                    {{ $report->cidade }} <span style="color: var(--primary)">{{ $report->uf }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square ms-1 small" style="font-size: 0.5em;"></i>
                                </a>
                            @else
                                {{ $report->cidade }} <span style="color: var(--primary)">{{ $report->uf }}</span>
                            @endif
                        </span>
                    </h1>
                    @php
                        $formattedCep = preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $report->cep);
                    @endphp
                    <p class="h5 fw-light text-white-50 opacity-75">
                        {{ $report->logradouro ?: $report->bairro }} • <span class="fw-medium text-white">CEP {{ $formattedCep }}</span>
                    </p>
                    <div class="mt-4 d-flex flex-wrap gap-3 no-print">
                        <a href="{{ route('home') }}" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold">
                            <i class="fa-solid fa-arrow-left me-2"></i>Nova Busca
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container dashboard-container">
        <!-- TOP METRICS GRID -->
        <div class="row g-4 mb-5">

            <!-- Caminhabilidade -->
            <div class="col-lg-4 col-md-12 reveal" style="animation-delay: 0.1s">
                <div class="card-pro d-flex flex-column justify-content-between overflow-hidden position-relative h-100">
                    <div class="mb-3">
                        <div class="metric-icon-pro bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-person-walking"></i>
                        </div>
                        <h4 class="mb-1">Caminhabilidade</h4>
                        <p class="text-muted small mb-0">Mobilidade ativa e acesso peatonal.</p>
                    </div>
                    <div class="text-center py-2 flex-grow-1 d-flex flex-column justify-content-center">
                        <div class="{{ $report->status === 'processing' ? 'skeleton' : '' }}" style="font-size: 4rem; line-height: 1; font-weight: 900; color: {{ $walkColor }}">
                            {{ $report->status === 'processing' ? 'A' : $report->walkability_score }}
                        </div>
                        <div class="mt-2">
                            <span class="status-pill {{ $report->status === 'processing' ? 'skeleton' : '' }}" style="background: {{ $walkColor }}15; color: {{ $walkColor }}">
                                {{ $report->status === 'processing' ? 'Carregando Indicadores...' : $walkLabel }}
                            </span>
                        </div>
                    </div>
                    <div style="position: absolute; bottom: -15px; right: -15px; opacity: 0.05; font-size: 6rem;">
                        <i class="fa-solid fa-shoe-prints"></i>
                    </div>
                </div>
            </div>
            <!-- Qualidade do Ar -->
            <div class="col-lg-4 col-md-6 reveal" style="animation-delay: 0.2s">
                <div class="card-pro h-100">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="metric-icon-pro bg-amber-100 text-amber-600">
                            <i class="fa-solid fa-wind"></i>
                        </div>
                        @if($climate)
                            <div class="text-end">
                                <div class="h2 mb-0 fw-black text-dark">{{ round($climate['temperature']) }}°C</div>
                                <div class="badge bg-light text-muted fw-bold">Tempo Real</div>
                            </div>
                        @endif
                    </div>
                    <h5 class="fw-bold">Qualidade do Ar</h5>
                    <div class="mt-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="h4 mb-0 fw-bold {{ $report->status === 'processing' ? 'skeleton' : '' }}">{{ $report->status === 'processing' ? '00' : $aqi }}</span>
                            <span class="status-pill bg-{{ $aqiRes['color'] }} text-white {{ $report->status === 'processing' ? 'skeleton' : '' }}">{{ $report->status === 'processing' ? 'ANALISANDO' : $aqiRes['level'] }}</span>
                        </div>
                        <p class="small text-muted mb-0 leading-tight {{ $report->status === 'processing' ? 'skeleton' : '' }}">
                            {{ $report->status === 'processing' ? 'Aguardando dados da estação climática local...' : $aqiRes['desc'] }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Saneamento & Infra -->
            <div class="col-lg-4 col-md-6 reveal" style="animation-delay: 0.3s">
                <div class="card-pro bg-dark text-white border-0 shadow-xl h-100" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="metric-icon-pro bg-white bg-opacity-10 text-primary">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="text-end">
                            <div class="h2 mb-0 fw-black text-primary">R${{ number_format($report->average_income, 0, ',', '.') }}</div>
                            <div class="small fw-bold opacity-50 uppercase">Renda Média</div>
                        </div>
                    </div>
                    <h5 class="fw-bold">Saneamento & Infra</h5>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small opacity-75">Cobertura de Esgoto</span>
                            <span class="small fw-bold {{ $report->status === 'processing' ? 'skeleton' : '' }}">{{ $report->status === 'processing' ? '--%' : $report->sanitation_rate . '%' }}</span>
                        </div>
                        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                            <div class="progress-bar bg-primary" style="width:{{ $report->status === 'processing' ? '20' : $report->sanitation_rate }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- SCORE SECTION (GLASS DESIGN) -->
        <div class="row mb-3 reveal">
            <div class="col-12">
                @php
                    // Lógica de Gamificação do Score (0-100)
                    $poisCount = count($pois);
                    $safetyVal = match(true) {
                        str_contains($safetyRaw, 'ALT') => 92,
                        str_contains($safetyRaw, 'MODERAD') || str_contains($safetyRaw, 'MEDI') => 70,
                        str_contains($safetyRaw, 'BAIX') => 45,
                        default => 55
                    };
                    $commerceScore = min(100, (int)($poisCount * 1.5)); 
                    $infraScore = $report->sanitation_rate ?: 50;
                    $cultureScore = $report->history_extract ? min(100, (int)(strlen($report->history_extract) / 20)) : 35;

                    $finalScore = round(($safetyVal * 0.4) + ($commerceScore * 0.3) + ($infraScore * 0.2) + ($cultureScore * 0.1));
                    
                    $tierLabel = match(true) {
                        $finalScore >= 90 => 'Distrito de Elite',
                        $finalScore >= 75 => 'Alto Padrão',
                        $finalScore >= 60 => 'Conforto Urbano',
                        default => 'Região em Ascensão'
                    };
                    $tierColor = match(true) {
                        $finalScore >= 90 => '#10b981',
                        $finalScore >= 75 => '#6366f1',
                        $finalScore >= 60 => '#f59e0b',
                        default => '#64748b'
                    };
                @endphp
                <div class="score-card">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-center mb-5 mb-lg-0 border-end border-light border-opacity-50">
                            <div class="score-circle-container mb-3">
                                <svg width="170" height="170" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="45" fill="none" stroke="#f1f5f9" stroke-width="7"></circle>
                                    <circle cx="50" cy="50" r="45" fill="none" stroke="{{ $tierColor }}" stroke-width="7" stroke-dasharray="{{ ($finalScore/100) * 283 }} 283" stroke-linecap="round" transform="rotate(-90 50 50)" style="filter: drop-shadow(0 0 10px {{ $tierColor }}40)"></circle>
                                </svg>
                                <div class="score-number">
                                    <div class="score-val {{ $report->status === 'processing' ? 'skeleton' : '' }}" style="color: {{ $tierColor }}">
                                        {{ $report->status === 'processing' ? '00' : $finalScore }}
                                    </div>
                                    <div class="text-muted small fw-black text-uppercase tracking-widest">Score Real</div>
                                </div>
                            </div>
                            <span class="status-pill px-4 py-2" style="background: {{ $tierColor }}10; color: {{ $tierColor }}; font-size: 11px; border: 1px solid {{ $tierColor }}20;">
                                <i class="fa-solid fa-medal me-2"></i>{{ $tierLabel }}
                            </span>
                        </div>
                        
                        <div class="col-lg-8 ps-lg-5 text-start">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <h6 class="text-secondary small fw-black mb-4 text-uppercase tracking-widest" style="opacity: 0.8;">Análise de Performance</h6>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-2 small fw-bold">
                                            <span class="text-dark">Segurança & Proteção</span>
                                            <span style="color: #059669">{{ $safetyVal }}%</span>
                                        </div>
                                        <div class="neon-progress">
                                            <div class="neon-bar" style="width: {{ $safetyVal }}%; background: #10b981;"></div>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <div class="d-flex justify-content-between mb-2 small fw-bold">
                                            <span class="text-dark">Capacidade Comercial</span>
                                            <span style="color: #4f46e5">{{ $commerceScore }}%</span>
                                        </div>
                                        <div class="neon-progress">
                                            <div class="neon-bar" style="width: {{ $commerceScore }}%; background: #6366f1;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 class="text-secondary small fw-black mb-4 text-uppercase tracking-widest" style="opacity: 0.8;">Posicionamento</h6>
                                    <div class="d-flex flex-column gap-2">
                                        @php
                                            // Pega a posição desse bairro no ranking da cidade dele
                                            $results = \App\Models\LocationReport::select('bairro', DB::raw('COUNT(*) as searches'))
                                                ->where('cidade', $report->cidade)
                                                ->groupBy('bairro')
                                                ->orderBy('searches', 'desc')
                                                ->get();
                                            
                                            $position = $results->filter(fn($r) => $r->bairro == $report->bairro)->keys()->first() + 1;
                                            $totalBairros = $results->count();
                                        @endphp

                                        <div class="badge-medal {{ $report->status === 'processing' ? 'skeleton' : '' }}" style="background: var(--primary)10; border-color: var(--primary)30;">
                                            <div class="badge-icon" style="color: var(--primary);"><i class="fa-solid fa-ranking-star"></i></div>
                                            <div>
                                                <div class="fw-black small text-primary">#{{ $report->status === 'processing' ? '0' : $position }}º Mais Popular</div>
                                                <div class="text-muted" style="font-size: 10px;">{{ $report->cidade }}</div>
                                            </div>
                                        </div>

                                        <a href="{{ route('ranking.index') }}" class="text-primary fw-black uppercase tracking-widest mt-2 text-decoration-none" style="font-size: 10px;">
                                            Ver Ranking Completo <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <h6 class="text-secondary small fw-black mb-4 text-uppercase tracking-widest" style="opacity: 0.8;">Destaques</h6>
                                    <div class="d-flex flex-column gap-2">
                                        @if($safetyVal >= 80)
                                            <div class="badge-medal">
                                                <div class="badge-icon" style="color: #10b981;"><i class="fa-solid fa-shield-halved"></i></div>
                                                <div class="fw-black small text-dark" style="font-size: 10px;">Seguro</div>
                                            </div>
                                        @endif
                                        @if($commerceScore >= 70)
                                            <div class="badge-medal">
                                                <div class="badge-icon" style="color: #6366f1;"><i class="fa-solid fa-store"></i></div>
                                                <div class="fw-black small text-dark" style="font-size: 10px;">Comercial</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>




        <!-- MIDDLE SECTION: MAP & INFRASTRUCTURE -->
        <div class="row g-4 mb-0">
            <div class="col-xl-8 col-lg-7 reveal">
                <div id="map-print-section" class="card-pro p-0 overflow-hidden d-flex flex-column bg-white border-0 shadow-lg h-100">
                    <div class="p-4 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 bg-white border-bottom no-print">
                        <div>
                            <h4 class="mb-0">Mapeamento Territorial</h4>
                            <p class="text-muted small mb-0">Visualização interativa de pontos próximos ao CEP.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="map-category-btn active" data-filter="all"><i class="fa-solid fa-layer-group"></i>Tudo</span>
                            <span class="map-category-btn" data-filter="food"><i class="fa-solid fa-utensils text-danger"></i>Alimentação</span>
                            <span class="map-category-btn" data-filter="health"><i class="fa-solid fa-house-medical text-success"></i>Saúde</span>
                            <span class="map-category-btn" data-filter="education"><i class="fa-solid fa-graduation-cap text-primary"></i>Educação</span>
                            <span class="map-category-btn" data-filter="transport"><i class="fa-solid fa-bus text-info"></i>Transporte</span>
                            <span class="map-category-btn" data-filter="shopping"><i class="fa-solid fa-cart-shopping text-warning"></i>Compras</span>
                            <span class="map-category-btn" data-filter="leisure"><i class="fa-solid fa-tree text-success"></i>Lazer</span>
                            <span class="map-category-btn" data-filter="services"><i class="fa-solid fa-building-columns text-dark"></i>Serviços</span>
                        </div>
                    </div>
                    <div id="map-container" style="flex-grow: 1; height: auto; min-height: 950px; position: relative;">
                        <!-- Custom Map Style Controls -->
                        <div class="map-controls-premium no-print">
                            <button class="btn btn-light btn-sm fw-bold shadow-sm border" onclick="toggleHeatmap(this)"><i class="fa-solid fa-fire me-1"></i>Calor</button>
                            <button class="btn btn-primary btn-sm fw-bold shadow-sm border px-3" onclick="toggleExplorer()">
                                <i class="fa-solid fa-maximize me-1"></i> MODO TELA CHEIA (EXPLORER)
                            </button>
                            
                            <div class="btn-group shadow-sm border rounded-pill overflow-hidden bg-white">
                                <button class="btn btn-light btn-sm fw-bold map-style-btn" data-style="suave">Suave</button>
                                <button class="btn btn-light btn-sm fw-bold map-style-btn" data-style="padrao">Padrão</button>
                                <button class="btn btn-light btn-sm fw-bold map-style-btn" data-style="clara">Clara</button>
                                <button class="btn btn-light btn-sm fw-bold map-style-btn" data-style="escura">Escura</button>
                                <button class="btn btn-light btn-sm fw-bold map-style-btn" data-style="satelite">Satélite</button>
                            </div>
                        </div>
                        
                        <div id="map" style="height: 100%;"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5 reveal" style="animation-delay: 0.1s">
                <div class="card-pro h-100 d-flex flex-column">
                    <div class="mb-4 d-flex align-items-center justify-content-between">
                         <h5 class="mb-0 fw-black">Análise de Bairro</h5>
                         <div class="text-primary fw-bold small">RAIO {{ ($report->search_radius >= 1000) ? ($report->search_radius / 1000) . 'KM' : $report->search_radius . 'M' }}</div>
                     </div>
                    
                    <!-- Quick Stats Grid -->
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="card p-2 border-0 bg-danger bg-opacity-10 text-center rounded-4">
                                <div class="h6 fw-black text-danger mb-0">{{ count($poi_food) }}</div>
                                <div class="text-danger opacity-75" style="font-size: 8px; font-weight: 800; text-transform: uppercase;">Alimentação</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card p-2 border-0 bg-success bg-opacity-10 text-center rounded-4">
                                <div class="h6 fw-black text-success mb-0">{{ count($poi_health) }}</div>
                                <div class="text-success opacity-75" style="font-size: 8px; font-weight: 800; text-transform: uppercase;">Saúde</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card p-2 border-0 bg-primary bg-opacity-10 text-center rounded-4">
                                <div class="h6 fw-black text-primary mb-0">{{ count($poi_education) }}</div>
                                <div class="text-primary opacity-75" style="font-size: 8px; font-weight: 800; text-transform: uppercase;">Educação</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card p-2 border-0 bg-info bg-opacity-10 text-center rounded-4">
                                <div class="h6 fw-black text-info mb-0">{{ count($poi_transport) }}</div>
                                <div class="text-info opacity-75" style="font-size: 8px; font-weight: 800; text-transform: uppercase;">Transporte</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card p-2 border-0 bg-warning bg-opacity-10 text-center rounded-4">
                                <div class="h6 fw-black text-warning mb-0">{{ count($poi_shopping) }}</div>
                                <div class="text-warning opacity-75" style="font-size: 8px; font-weight: 800; text-transform: uppercase;">Compras</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card p-2 border-0 bg-dark bg-opacity-10 text-center rounded-4">
                                <div class="h6 fw-black text-dark mb-0">{{ count($poi_services) }}</div>
                                <div class="text-dark opacity-75" style="font-size: 8px; font-weight: 800; text-transform: uppercase;">Serviços</div>
                            </div>
                        </div>
                    </div>

                    <div class="poi-drawer flex-grow-1" style="max-height: 720px; overflow-y: auto;">
                        @php
                            $cat_list = [
                                ['label' => 'Alimentação', 'data' => $poi_food, 'icon' => 'utensils', 'color' => 'danger'],
                                ['label' => 'Saúde', 'data' => $poi_health, 'icon' => 'house-medical', 'color' => 'success'],
                                ['label' => 'Educação', 'data' => $poi_education, 'icon' => 'graduation-cap', 'color' => 'primary'],
                                ['label' => 'Transporte', 'data' => $poi_transport, 'icon' => 'bus', 'color' => 'info'],
                                ['label' => 'Compras', 'data' => $poi_shopping, 'icon' => 'cart-shopping', 'color' => 'warning'],
                                ['label' => 'Lazer', 'data' => $poi_leisure, 'icon' => 'palette', 'color' => 'success'],
                                ['label' => 'Serviços', 'data' => $poi_services, 'icon' => 'building-columns', 'color' => 'dark'],
                            ];
                        @endphp

                        @foreach($cat_list as $cat)
                            @if(count($cat['data']) > 0)
                                <div class="mb-4">
                                    <h6 class="text-muted fw-black mb-2 small text-uppercase tracking-widest" style="font-size: 10px;">{{ $cat['label'] }} ({{ count($cat['data']) }})</h6>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach(array_slice($cat['data'], 0, 10) as $p)
                                            <div class="poi-item d-flex align-items-center px-3 py-2 border-0 shadow-sm" style="background: white; cursor: pointer; border-radius: 12px;" onclick="focusPoi('{{ $p['id'] }}')">
                                                <div class="bg-{{ $cat['color'] }} bg-opacity-10 text-{{ $cat['color'] }} d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; border-radius: 8px;">
                                                    @php
                                                        $tag = $p['tags']['amenity'] ?? $p['tags']['shop'] ?? $p['tags']['leisure'] ?? $p['tags']['highway'] ?? '';
                                                        $p_icon = $cat['icon'];
                                                        if($tag === 'pharmacy') $p_icon = 'pills';
                                                        if($tag === 'restaurant') $p_icon = 'utensils';
                                                    @endphp
                                                    <i class="fa-solid fa-{{ $p_icon }} small"></i>
                                                </div>
                                                <div class="text-truncate small fw-bold text-dark">{{ $p['tags']['name'] ?? ($translations[$tag] ?? 'Local') }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Gráfico de Radar de Dimensões -->
                    <div class="mt-4 p-4 rounded-4 bg-white shadow-pro">
                        <h6 class="text-dark fw-black mb-3 small text-uppercase tracking-widest text-center" style="font-size: 10px;">Perfil Multidimensional</h6>
                        <div style="height: 250px;">
                            <canvas id="territoryRadarChart"></canvas>
                        </div>
                    </div>

                    <!-- Insights Automáticos -->
                    <div class="mt-4 p-4 rounded-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; border-radius: 10px;">
                                <i class="fa-solid fa-lightbulb"></i>
                            </div>
                            <h6 class="mb-0 fw-black text-dark">Insights do Bairro</h6>
                        </div>
                        <div class="row g-2">
                            @php
                                $insights = [];
                                if(count($poi_food) > 15) $insights[] = ['icon' => 'utensils', 'text' => 'Polo Gastronômico: Alta densidade de restaurantes e bares.', 'color' => 'danger'];
                                if(count($poi_transport) > 5) $insights[] = ['icon' => 'bus', 'text' => 'Mobilidade Fluida: Excelente acesso ao transporte público.', 'color' => 'info'];
                                if(count($poi_leisure) < 3) $insights[] = ['icon' => 'tree', 'text' => 'Déficit de Lazer: Poucas áreas verdes ou parques próximos.', 'color' => 'success'];
                                if(count($poi_health) > 8) $insights[] = ['icon' => 'house-medical', 'text' => 'Hub de Saúde: Grande oferta de clínicas e farmácias.', 'color' => 'success'];
                                if(count($poi_education) > 5) $insights[] = ['icon' => 'graduation-cap', 'text' => 'Região Educacional: Boa presença de escolas e apoio.', 'color' => 'primary'];
                                if(count($poi_shopping) > 20) $insights[] = ['icon' => 'cart-shopping', 'text' => 'Comércio Vibrante: Grande variedade de lojas locais.', 'color' => 'warning'];
                            @endphp

                            @forelse(array_slice($insights, 0, 3) as $ins)
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 p-2 bg-white rounded-3 small fw-bold text-dark border border-light shadow-sm">
                                        <i class="fa-solid fa-{{ $ins['icon'] }} text-{{ $ins['color'] }}" style="width: 15px;"></i>
                                        {{ $ins['text'] }}
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-muted small fw-bold">Analise os pontos no mapa para tirar suas conclusões.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Duelo Territorial CTA -->
                    <div class="mt-4 p-4 rounded-4 bg-dark text-white shadow-pro text-center position-relative overflow-hidden">
                        <div class="position-absolute" style="top: -20px; right: -20px; font-size: 100px; opacity: 0.05;">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <h6 class="text-uppercase fw-black tracking-widest text-warning mb-2" style="font-size: 11px;">
                            <i class="fa-solid fa-bolt me-1"></i> Duelo Territorial
                        </h6>
                        <h5 class="fw-bold mb-3">Qual bairro vence?</h5>
                        <p class="small text-white-50 mb-3" style="line-height: 1.4;">Compare a infraestrutura e qualidade de vida deste CEP com outro território.</p>
                        
                        <div class="input-group input-group-sm mb-2 shadow-sm rounded-pill overflow-hidden border border-white border-opacity-25">
                            <span class="input-group-text bg-transparent border-0 text-white-50 ms-2"><i class="fa-solid fa-location-dot"></i></span>
                            <input type="text" id="duelCepInput" class="form-control bg-transparent border-0 text-white shadow-none ps-1" placeholder="Digite o CEP adversário..." maxlength="9" oninput="this.value = this.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2')">
                            <button class="btn btn-warning fw-black px-3" onclick="startDuel()" type="button">DUELAR</button>
                        </div>
                        <script>
                            function startDuel() {
                                let cepA = "{{ $report->cep }}";
                                let cepB = document.getElementById('duelCepInput').value.replace(/\D/g, '');
                                if(cepB.length === 8) {
                                    window.location.href = `/compare/${cepA}/${cepB}`;
                                } else {
                                    alert("Por favor, digite um CEP válido com 8 dígitos para o duelo.");
                                }
                            }
                        </script>
                    </div>

                </div>
            </div>
        </div>

        <!-- NEW BOTTOM SECTION: SECURITY & REAL ESTATE -->
        <div class="row g-4 mb-5">
            <!-- Segurança -->
            <div class="col-lg-6 col-md-12 reveal" style="animation-delay: 0.4s">
                <div class="card-pro overflow-hidden border-0 shadow-sm position-relative h-100" style="background: white;">
                    <!-- Decoração de fundo sutil -->
                    <div style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; background: var(--bs-{{ $sColor }}); opacity: 0.08; border-radius: 50%; filter: blur(40px);"></div>
                    <div style="position: absolute; bottom: -20px; left: -20px; width: 80px; height: 80px; background: var(--bs-{{ $sColor }}); opacity: 0.03; border-radius: 50%; filter: blur(30px);"></div>
                    
                    <div class="d-flex align-items-start gap-3 position-relative" style="z-index: 2;">
                        <div class="metric-icon-pro bg-{{ $sColor }} bg-opacity-10 text-{{ $sColor }} flex-shrink-0 shadow-none border border-{{ $sColor }} border-opacity-10" style="width: 56px; height: 56px; font-size: 26px; border-radius: 18px;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                <div>
                                    <h5 class="mb-0 fw-black text-dark" style="letter-spacing: -0.02em;">Índice de Segurança</h5>
                                    <div class="small text-muted opacity-75">Análise regional de proteção</div>
                                </div>
                                <span class="badge bg-{{ $sColor }} rounded-pill px-3 py-2 fw-black shadow-sm text-wrap" style="font-size: 0.85rem; letter-spacing: 0.03em; max-width: 200px;">
                                    <i class="fa-solid fa-check-circle me-1 small"></i>{{ $report->safety_level ?? 'N/A' }}
                                </span>
                            </div>

                            <div class="editorial-text small text-muted mb-0" style="line-height: 1.6; text-align: justify; font-size: 0.95rem;">
                                {{ $report->safety_description ?: 'Baseado em dados estatísticos regionais e infraestrutura de vigilância local.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($report->real_estate_json))
            <!-- Mercado Imobiliário -->
            <div class="col-lg-6 col-md-12 reveal" style="animation-delay: 0.5s">
                <div class="card-pro overflow-hidden border-0 shadow-sm h-100 d-flex flex-column" style="background: white;">
                    @php
                        $fullTend = $report->real_estate_json['tendencia_valorizacao'] ?? 'ESTÁVEL';
                        $isAlta = str_contains(strtoupper($fullTend), 'ALTA') || str_contains(strtoupper($fullTend), 'POSITIVA') || str_contains(strtoupper($fullTend), 'VALORIZAÇÃO') || str_contains(strtoupper($fullTend), 'VALORIZ');
                        $isBaixa = str_contains(strtoupper($fullTend), 'BAIXA') || str_contains(strtoupper($fullTend), 'NEGATIVA') || str_contains(strtoupper($fullTend), 'DESVALORIZAÇÃO');
                        
                        $badgeText = $isAlta ? 'TENDÊNCIA POSITIVA' : ($isBaixa ? 'TENDÊNCIA BAIXA' : 'ESTÁVEL');
                        $tColor = $isAlta ? 'success' : ($isBaixa ? 'danger' : 'warning');
                        $tIcon = $isAlta ? 'arrow-trend-up' : ($isBaixa ? 'arrow-trend-down' : 'minus');
                    @endphp

                    <!-- Cabeçalho com Tendência -->
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="metric-icon-pro bg-primary bg-opacity-10 text-primary mb-0 me-3 shadow-none" style="width:48px;height:48px; border-radius: 14px;">
                                <i class="fa-solid fa-house-circle-check" style="font-size: 20px;"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-black text-dark" style="letter-spacing: -0.02em;">Mercado Imobiliário (IA)</h5>
                                <div class="small text-muted opacity-75">Análise preditiva de valores</div>
                            </div>
                        </div>
                        <span class="badge bg-{{ $tColor }} bg-opacity-10 text-{{ $tColor }} border border-{{ $tColor }} border-opacity-20 px-3 py-2 rounded-pill whitespace-nowrap">
                            <i class="fa-solid fa-{{ $tIcon }} me-1 small"></i> 
                            <span class="fw-black" style="font-size: 0.65rem; letter-spacing: 0.05em;">{{ $badgeText }}</span>
                        </span>
                    </div>

                    <!-- AI Narrative Insight -->
                    <div class="p-3 rounded-4 mb-4" style="background: rgba(var(--bs-{{ $tColor }}-rgb), 0.03); border: 1px dashed rgba(var(--bs-{{ $tColor }}-rgb), 0.2);">
                        <div class="d-flex gap-2">
                             <i class="fa-solid fa-quote-left text-{{ $tColor }} opacity-25" style="font-size: 14px;"></i>
                             <p class="small text-secondary mb-0 fw-medium italic" style="line-height: 1.4; text-align: justify; font-size: 0.85rem;">
                                 {{ $fullTend }}
                             </p>
                        </div>
                    </div>
                    
                    <!-- Preço em Destaque -->
                    <div class="mb-4">
                        <div class="small fw-bold text-muted mb-2 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.08em;">Estimativa de Valor M²</div>
                        <div class="fw-black text-dark" style="color: #1e293b; line-height: 1.1; font-size: 1.25rem;">
                            {{ $report->real_estate_json['preco_m2'] ?? 'Sob Consulta' }}
                        </div>
                    </div>

                    <!-- Perfil da Região (Seção Inferior) -->
                    <div class="mt-auto pt-3 border-top border-light">
                        <div class="small fw-bold text-muted mb-2 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.08em;">Perfil Comentado da Região</div>
                        <div class="small text-secondary fw-medium" style="line-height: 1.5; text-align: justify; font-size: 0.9rem;">
                            {{ $report->real_estate_json['perfil_imoveis'] ?? 'Misto' }}
                        </div>
                    </div>

                </div>
            </div>
            @endif

        <!-- HISTORY SECTION -->
        <div class="page-break"></div>
        @if($report->history_extract)
            <div class="row mb-4 reveal">
                <div class="col-12">
                    <div class="card-pro border-0 shadow-lg overflow-hidden position-relative">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-primary text-white d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 14px;">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <h2 class="mb-0 fw-black text-dark" style="letter-spacing: -0.02em;">Legado e Cultura Regional</h2>
                        </div>
                        
                        <div class="position-relative">
                            <!-- Imagem History (Newspaper Style Float) -->
                            @if($wiki['image'] ?? null)
                                <div class="float-md-start me-md-4 mb-4 text-center text-md-start" style="max-width: 450px;">
                                    <div class="position-relative">
                                        <img src="{{ $wiki['image'] }}" class="img-fluid rounded-4 shadow-lg mb-2" style="max-height: 400px; object-fit: cover;" alt="História Local">
                                        <div class="position-absolute bottom-0 start-0 w-100 p-2 bg-dark bg-opacity-50 text-white rounded-bottom-4 d-md-none" style="backdrop-filter: blur(5px); font-size: 10px;">
                                            {{ $report->cidade }}
                                        </div>
                                    </div>
                                    <div class="small text-muted italic opacity-75 d-none d-md-block" style="font-size: 11px;">
                                        <i class="fa-solid fa-camera me-1"></i> Registro histórico: {{ $report->cidade }}
                                    </div>
                                </div>
                            @endif
                            
                            <div class="editorial-text drop-cap" style="text-align: justify;">{!! nl2br(e(trim($report->history_extract))) !!}
                                
                                <div class="mt-4 no-print">
                                    @if($wiki['desktop_url'] ?? null)
                                        <a href="{{ $wiki['desktop_url'] }}" target="_blank" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.1em;">
                                            <i class="fa-brands fa-wikipedia-w me-2"></i>Consultar Fonte Wikipedia
                                        </a>
                                    @endif
                                </div>

                                    @if(str_contains($report->history_extract, 'temporariamente indisponível'))
                                        <div class="mt-4 p-4 rounded-4 bg-primary bg-opacity-5 border border-primary border-opacity-10 d-flex align-items-center justify-content-between flex-wrap gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-primary text-white p-2 rounded-3 shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                                                <div>
                                                    <h6 class="mb-0 fw-black text-dark">Tentar Novamente?</h6>
                                                    <p class="small text-muted mb-0">Podemos tentar gerar esta narrativa agora que a demanda pode ter baixado.</p>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary rounded-pill px-4 fw-black shadow-sm" onclick="reprocessNarrative(this)">
                                                REPROCESSAR COM IA
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($report->status === 'processing_text')
            <div class="row mb-5 reveal" id="narrative-loading-section">
                <div class="col-12 text-center py-5">
                    <div class="card-pro border-0 shadow-sm p-5" style="background: rgba(255,255,255,0.5); backdrop-filter: blur(10px);">
                        <div class="ai-pulse bg-primary bg-opacity-10 text-primary mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 20px;">
                            <i class="fa-solid fa-wand-magic-sparkles fa-2x"></i>
                        </div>
                        <h4 class="fw-black text-dark mb-2">Construindo Narrativa Territorial</h4>
                        <p class="text-muted small mb-0">Nossa IA está cruzando dados da Wikipedia e registros históricos para gerar o contexto cultural do bairro. <br>A página atualizará automaticamente em alguns segundos.</p>
                    </div>
                </div>
            </div>
@endif
    </div>


    <!-- EXPLORER MODE OVERLAY -->
    <div id="explorer-overlay">
        <div class="explorer-header">
            <!-- BOTÃO SAIR (Top-Left Visibility) -->
            <button class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm me-4" onclick="toggleExplorer()">
                <i class="fa-solid fa-arrow-left-long me-2"></i> VOLTAR (ESC)
            </button>

            <div class="d-flex align-items-center gap-3 border-start ps-4 d-none d-md-flex">
                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-black text-dark">Modo Explorador</h5>
                    <p class="small text-muted mb-0">{{ $report->bairro ?: $report->cidade }} • Raio de {{ ($report->search_radius / 1000) }}km</p>
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold" onclick="toggleExplorerHeatmap(this)">
                    <i class="fa-solid fa-fire me-1"></i>Calor
                </button>
                <div class="btn-group border rounded-pill overflow-hidden shadow-sm d-none d-lg-flex">
                    <button class="btn btn-light btn-sm fw-bold explorer-style-btn" data-style="suave">Suave</button>
                    <button class="btn btn-light btn-sm fw-bold explorer-style-btn" data-style="padrao">Padrão</button>
                    <button class="btn btn-light btn-sm fw-bold explorer-style-btn" data-style="clara">Clara</button>
                    <button class="btn btn-light btn-sm fw-bold explorer-style-btn" data-style="escura">Escura</button>
                    <button class="btn btn-light btn-sm fw-bold explorer-style-btn" data-style="satelite">Satélite</button>
                </div>
            </div>
        </div>
        
        <div class="explorer-content">
            <div id="explorer-map"></div>
            <div class="explorer-list-side">
                @php
                    $categories = [
                        'food' => ['label' => 'Alimentação', 'icon' => 'utensils', 'color' => '#ef4444'],
                        'health' => ['label' => 'Saúde', 'icon' => 'hospital', 'color' => '#10b981'],
                        'education' => ['label' => 'Educação', 'icon' => 'graduation-cap', 'color' => '#6366f1'],
                        'transport' => ['label' => 'Transporte', 'icon' => 'bus', 'color' => '#0ea5e9'],
                        'shopping' => ['label' => 'Compras', 'icon' => 'cart-shopping', 'color' => '#f59e0b'],
                        'leisure' => ['label' => 'Lazer', 'icon' => 'palette', 'color' => '#15803d'],
                        'services' => ['label' => 'Serviços', 'icon' => 'location-dot', 'color' => '#64748b'],
                    ];
                @endphp

                <div class="p-4 bg-white border-bottom">
                    <h6 class="fw-bold mb-1">Guia de Proximidade</h6>
                    <p class="small text-muted mb-0">Explore os {{ count($report->pois_json ?? []) }} locais identificados nesta região.</p>
                </div>

                @foreach($categories as $key => $cat)
                    @php
                        $filtered = array_filter($report->pois_json ?? [], function($p) use ($key) {
                            $tags = $p['tags'] ?? [];
                            $type = $tags['amenity'] ?? $tags['shop'] ?? $tags['leisure'] ?? $tags['tourism'] ?? '';
                            
                            if ($key === 'food' && preg_match('/restaurant|cafe|fast_food|bakery|bar|pub/i', $type)) return true;
                            if ($key === 'health' && preg_match('/pharmacy|hospital|clinic|dentist/i', $type)) return true;
                            if ($key === 'education' && preg_match('/school|university|kindergarten|library/i', $type)) return true;
                            if ($key === 'transport' && preg_match('/bus_stop|bus_station|subway/i', $type)) return true;
                            if ($key === 'shopping' && ($tags['shop'] ?? false)) return true;
                            if ($key === 'leisure' && preg_match('/park|gym|sports|playground|cinema/i', $type)) return true;
                            if ($key === 'services' && !empty($type)) return true;
                            return false;
                        });
                        usort($filtered, function($a, $b) use ($report) {
                            return 0; // Or proximity if calculated
                        });
                    @endphp

                    @if(count($filtered) > 0)
                        <div class="explorer-category-header">
                            <i class="fa-solid fa-{{ $cat['icon'] }}" style="color: {{ $cat['color'] }}"></i>
                            {{ $cat['label'] }} ({{ count($filtered) }})
                        </div>
                        @foreach(array_slice($filtered, 0, 30) as $poi)
                            <div class="explorer-poi-card d-flex align-items-center gap-3" onclick="focusExplorerPoi('{{ $poi['id'] }}')">
                                <div class="explorer-poi-icon" style="background: {{ $cat['color'] }}">
                                    <i class="fa-solid fa-{{ $cat['icon'] }}"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <h6 class="mb-0 fw-bold text-truncate" style="font-size: 13px;">{{ $poi['tags']['name'] ?? 'Local sem nome' }}</h6>
                                    <p class="small text-muted mb-0 text-truncate" style="font-size: 11px;">
                                        {{ $translations[$poi['tags']['amenity'] ?? $poi['tags']['shop'] ?? ''] ?? 'Ponto de Interesse' }}
                                    </p>
                                </div>
                                <div class="ms-auto text-end">
                                    <i class="fa-solid fa-chevron-right text-light" style="font-size: 10px;"></i>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endforeach
                <div class="py-5 text-center opacity-30">
                    <i class="fa-solid fa-map-marked-alt fa-3x mb-2"></i>
                    <p class="small fw-bold">Fim da lista</p>
                </div>
            </div>
        </div>
    </div>

    <div id="compare-panel" class="compare-panel no-print">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-black text-dark">⚔️ Iniciar Comparativo</h5>
            <button class="btn btn-link text-muted p-0" onclick="toggleCompare()"><i class="fa-solid fa-times"></i></button>
        </div>
        <p class="small text-muted mb-4 text-justify">Coloque dois bairros lado a lado. Digite o novo CEP para gerar o confronto de scores.</p>
        
        <div class="position-relative mb-4">
            <input type="text" id="v-compare-input" class="form-control rounded-4 border-light bg-light py-3 ps-4 shadow-none" placeholder="Digite o novo CEP..." maxlength="9">
            <button class="btn btn-primary position-absolute end-0 top-0 mt-1 me-1 h-75 rounded-4 px-4 fw-bold" onclick="startComparison()">
                CONFRONTAR
            </button>
        </div>
        
        <div class="d-flex align-items-center gap-2 p-3 bg-indigo-50 rounded-4 border border-indigo-100">
            <div class="bg-white p-2 rounded-circle shadow-sm">💡</div>
            <div class="small text-indigo-900 leading-tight">Compare Segurança, Comércio e IDH de forma instantânea.</div>
        </div>
    </div>


    <!-- Scripts -->
    <script>
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const centerLat = {{ $report->lat }};
            const centerLng = {{ $report->lng }};
            const pois = @json($report->pois_json ?? []);
            const translations = @json($translations);
            const wiki = @json($wiki);

            // 1. Configuração do Mapa
            const layerConfigs = {
                "suave": ['https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 19 }],
                "padrao": ['https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }],
                "clara": ['https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 19 }],
                "escura": ['https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 19 }],
                "satelite": ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 }]
            };

            const mainLayers = {};
            Object.keys(layerConfigs).forEach(k => {
                mainLayers[k] = L.tileLayer(layerConfigs[k][0], layerConfigs[k][1]);
            });

            const map = L.map('map', { 
                scrollWheelZoom: false,
                attributionControl: false,
                layers: [] 
            }).setView([centerLat, centerLng], 15);

            let currentMapStyle = 'padrao'; 
            mainLayers["padrao"].addTo(map);

            // 2. Estilos e Controles de Mapa
            document.querySelectorAll('.map-style-btn').forEach(btn => {
                if (btn.getAttribute('data-style') === currentMapStyle) btn.classList.add('active');

                btn.addEventListener('click', function() {
                    const style = this.getAttribute('data-style');
                    if (style === currentMapStyle || !mainLayers[style]) return;
                    
                    document.querySelectorAll('.map-style-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    map.removeLayer(mainLayers[currentMapStyle]);
                    mainLayers[style].addTo(map);
                    currentMapStyle = style;
                });
            });

            // 3. Raios de Acessibilidade (Zonas Urbanas)
            const searchRadius = {{ $report->search_radius ?? 1000 }};
            const radii = [
                { r: 300, color: '#10b981', label: '300m • 4 min' },
                { r: 800, color: '#6366f1', label: '800m • 10 min' }
            ];

            if (searchRadius <= 1500) {
                radii.push({ r: 1500, color: '#f59e0b', label: '1.5km • Bairro' });
            } else {
                radii.push({ r: Math.round(searchRadius / 2), color: '#f59e0b', label: (searchRadius/2000).toFixed(1) + 'km' });
                radii.push({ r: searchRadius, color: '#ef4444', label: (searchRadius/1000) + 'km • Busca' });
            }

            radii.forEach(conf => {
                L.circle([centerLat, centerLng], {
                    radius: conf.r,
                    color: conf.color,
                    fillOpacity: 0.02,
                    weight: 1,
                    dashArray: '5, 10'
                }).addTo(map).bindTooltip(conf.label, { sticky: true, className: 'radius-tooltip' });
            });

            // Marcador Central
            const mainIcon = L.divIcon({
                className: 'main-center-marker',
                html: `<div class="pulse-container"><div class="pulse"></div><div class="dot"><i class="fa-solid fa-house"></i></div></div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });
            L.marker([centerLat, centerLng], { icon: mainIcon }).addTo(map);

            // 4. Engine de Marcadores e Clusters
            const markerClusters = L.markerClusterGroup({
                showCoverageOnHover: false,
                maxClusterRadius: 40,
                spiderfyOnMaxZoom: true,
                iconCreateFunction: function(cluster) {
                    const childCount = cluster.getChildCount();
                    return L.divIcon({
                        html: `<div><span>${childCount}</span></div>`,
                        className: 'm-cluster',
                        iconSize: [35, 35]
                    });
                }
            });

            // Camada de Calor (Heatmap)
            const heatData = [];
            const poiRefs = [];

            // Mapeador Profissional de Estilos
            function getPoiConfig(poi) {
                const tag = poi.tags;
                const type = tag.amenity || tag.shop || tag.leisure || tag.tourism || tag.highway || '';
                
                let icon = 'location-dot', color = '#64748b', category = 'services';

                if (/restaurant|cafe|fast_food|bakery|bar|pub|ice_cream|food_court/i.test(type)) {
                    icon = 'utensils'; color = '#ef4444'; category = 'food';
                } else if (/pharmacy|hospital|clinic|dentist|doctors|veterinary/i.test(type)) {
                    icon = 'hospital'; color = '#10b981'; category = 'health';
                } else if (/school|university|kindergarten|childcare|library/i.test(type)) {
                    icon = 'graduation-cap'; color = '#6366f1'; category = 'education';
                } else if (/bus_stop|bus_station/i.test(type) || poi.tags.railway === 'station' || type === 'subway_entrance') {
                    icon = 'bus'; color = '#0ea5e9'; category = 'transport';
                } else if (tag.shop || /marketplace|fuel/i.test(type)) {
                    icon = 'cart-shopping'; color = '#f59e0b'; category = 'shopping';
                } else if (/park|gym|sports_centre|playground|stadium|garden|square|cinema|museum|attraction/i.test(type)) {
                    icon = 'palette'; color = '#15803d'; category = 'leisure';
                }

                return { icon, color, category };
            }

            // Cálculo de Distância Haversine no Frontend
            function getDistance(lat1, lon1, lat2, lon2) {
                const R = 6371e3; // metros
                const φ1 = lat1 * Math.PI/180;
                const φ2 = lat2 * Math.PI/180;
                const Δφ = (lat2-lat1) * Math.PI/180;
                const Δλ = (lon2-lon1) * Math.PI/180;
                const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ/2) * Math.sin(Δλ/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return Math.round(R * c);
            }

            // Gerar Marcadores
            pois.forEach(poi => {
                if (!poi.lat || !poi.lon) return;
                
                const conf = getPoiConfig(poi);
                const dist = getDistance(centerLat, centerLng, poi.lat, poi.lon);
                const walkTime = Math.ceil(dist / 80); // 80m por minuto

                // Heatmap Data
                heatData.push([poi.lat, poi.lon, 0.5]);

                const customIcon = L.divIcon({
                    className: 'custom-poi-marker',
                    html: `<div style="background:${conf.color};" class="poi-pin"><i class="fa-solid fa-${conf.icon}"></i></div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                const marker = L.marker([poi.lat, poi.lon], { icon: customIcon }).bindPopup(`
                    <div class="poi-popup">
                        <div class="fw-black text-dark mb-1">${poi.tags.name || 'Ponto de Interesse'}</div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-light text-dark border">${translations[poi.tags.amenity || poi.tags.shop || ''] || 'Local'}</span>
                        </div>
                        <div class="d-flex gap-3 small text-muted bg-light p-2 rounded-3">
                            <span><i class="fa-solid fa-person-walking me-1"></i>${dist}m</span>
                            <span><i class="fa-regular fa-clock me-1"></i>${walkTime} min</span>
                        </div>
                    </div>
                `);

                markerClusters.addLayer(marker);
                poiRefs.push({ marker, category: conf.category, id: poi.id });
            });

            map.addLayer(markerClusters);

            // 5. Heatmap (Opcional - Ativado via Console ou Botão se existisse)
            const heatLayer = L.heatLayer(heatData, { radius: 25, blur: 15, maxZoom: 17, opacity: 0.4 });
            
            // Toggle Heatmap (Expansão Futura)
            window.toggleHeatmap = function(btn) {
                if (map.hasLayer(heatLayer)) {
                    map.removeLayer(heatLayer);
                    if(btn) {
                        btn.classList.remove('active', 'btn-warning');
                        btn.classList.add('btn-light');
                    }
                } else {
                    heatLayer.addTo(map);
                    if(btn) {
                        btn.classList.add('active', 'btn-warning');
                        btn.classList.remove('btn-light');
                    }
                }
            };

            // 6. Filtros e Controle
            document.querySelectorAll('.map-category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    document.querySelectorAll('.map-category-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    markerClusters.clearLayers();
                    poiRefs.forEach(ref => {
                        if (filter === 'all' || ref.category === filter) markerClusters.addLayer(ref.marker);
                    });
                });
            });

            window.focusPoi = function(id) {
                const ref = poiRefs.find(r => r.id == id);
                if (ref) {
                    markerClusters.zoomToShowLayer(ref.marker, function() {
                        ref.marker.openPopup();
                    });
                }
            };

            // ================== MODO EXPLORADOR (FULLSCREEN) ==================
            let explorerMap = null;
            let explorerClusters = null;
            let explorerPois = [];
            let explorerHeatLayer = null;
            let currentExplorerStyle = null;

            window.toggleExplorer = function() {
                const overlay = document.getElementById('explorer-overlay');
                const isActive = overlay.style.display === 'flex';
                
                if (isActive) {
                    overlay.style.display = 'none';
                    document.body.classList.remove('explorer-active');
                    // IMPORTANTE: Recalcular o mapa original ao fechar o explorador
                    setTimeout(() => map.invalidateSize(), 300);
                } else {
                    overlay.style.display = 'flex';
                    document.body.classList.add('explorer-active');
                    if (!explorerMap) {
                        initExplorerMap();
                    } else {
                        // Sincronizar estilo com o mapa principal
                        if (currentMapStyle !== currentExplorerStyle) {
                            switchExplorerStyle(currentMapStyle);
                        }
                        // Recalcular o mapa do explorador ao abrir
                        setTimeout(() => explorerMap.invalidateSize(), 300);
                    }
                }
            };

            const explorerLayers = {};
            Object.keys(layerConfigs).forEach(k => {
                explorerLayers[k] = L.tileLayer(layerConfigs[k][0], layerConfigs[k][1]);
            });

            function switchExplorerStyle(style) {
                if (!explorerMap || !explorerLayers[style]) return;
                if (currentExplorerStyle) {
                    explorerMap.removeLayer(explorerLayers[currentExplorerStyle]);
                }
                explorerLayers[style].addTo(explorerMap);
                currentExplorerStyle = style;
                
                // Update UI active state
                document.querySelectorAll('.explorer-style-btn').forEach(b => {
                    b.classList.toggle('active', b.getAttribute('data-style') === style);
                    b.classList.toggle('btn-primary', b.getAttribute('data-style') === style);
                    b.classList.toggle('btn-light', b.getAttribute('data-style') !== style);
                });
            }

            function initExplorerMap() {
                explorerMap = L.map('explorer-map', {
                    scrollWheelZoom: true,
                    attributionControl: false
                }).setView([centerLat, centerLng], 15);

                // Forçar renderização inicial
                setTimeout(() => explorerMap.invalidateSize(), 300);

                // Inicializar com o mesmo estilo do mapa principal
                switchExplorerStyle(currentMapStyle);

                // Listeners para troca de estilo no explorador
                document.querySelectorAll('.explorer-style-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const style = this.getAttribute('data-style');
                        switchExplorerStyle(style);
                        // Opcional: sincronizar de volta para o mapa principal
                        if (currentMapStyle !== style) {
                            map.removeLayer(mainLayers[currentMapStyle]);
                            mainLayers[style].addTo(map);
                            currentMapStyle = style;
                            // Atualizar botões do mapa principal
                            document.querySelectorAll('.map-style-btn').forEach(b => {
                                b.classList.toggle('active', b.getAttribute('data-style') === style);
                            });
                        }
                    });
                });

                // Reutilizamos a lógica de clusters
                explorerClusters = L.markerClusterGroup({
                    showCoverageOnHover: false,
                    maxClusterRadius: 40,
                    spiderfyOnMaxZoom: true,
                    iconCreateFunction: function(cluster) {
                        return L.divIcon({
                            html: `<div><span>${cluster.getChildCount()}</span></div>`,
                            className: 'm-cluster',
                            iconSize: [35, 35]
                        });
                    }
                });

                // Heatmap no explorador
                explorerHeatLayer = L.heatLayer(heatData, { radius: 25, blur: 15, maxZoom: 17, opacity: 0.4 });
                window.toggleExplorerHeatmap = function(btn) {
                    if (explorerMap.hasLayer(explorerHeatLayer)) {
                        explorerMap.removeLayer(explorerHeatLayer);
                        if(btn) {
                            btn.classList.remove('active', 'btn-warning');
                            btn.classList.add('btn-light');
                        }
                    } else {
                        explorerHeatLayer.addTo(explorerMap);
                        if(btn) {
                            btn.classList.add('active', 'btn-warning');
                            btn.classList.remove('btn-light');
                        }
                    }
                };

                // Raios no explorador
                radii.forEach(conf => {
                    L.circle([centerLat, centerLng], {
                        radius: conf.r,
                        color: conf.color,
                        fillOpacity: 0.01,
                        weight: 1,
                        dashArray: '5, 8'
                    }).addTo(explorerMap);
                });

                // Central
                L.marker([centerLat, centerLng], { icon: mainIcon }).addTo(explorerMap);

                // POIs
                pois.forEach(poi => {
                    if (!poi.lat || !poi.lon) return;
                    const conf = getPoiConfig(poi);
                    const dist = getDistance(centerLat, centerLng, poi.lat, poi.lon);
                    
                    const customIcon = L.divIcon({
                        className: 'custom-poi-marker',
                        html: `<div style="background:${conf.color};" class="poi-pin"><i class="fa-solid fa-${conf.icon}"></i></div>`,
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });

                    const marker = L.marker([poi.lat, poi.lon], { icon: customIcon }).bindPopup(`
                        <div class="poi-popup">
                            <div class="fw-black text-dark mb-1">${escapeHtml(poi.tags.name || 'Ponto de Interesse')}</div>
                            <div class="badge bg-light text-dark border mb-2">${escapeHtml(translations[poi.tags.amenity || poi.tags.shop || ''] || 'Local')}</div>
                            <div class="small text-muted"><i class="fa-solid fa-person-walking me-1"></i>${dist}m</div>
                        </div>
                    `);

                    explorerClusters.addLayer(marker);
                    explorerPois.push({ marker, id: poi.id });
                });

                explorerMap.addLayer(explorerClusters);
            }

            window.focusExplorerPoi = function(id) {
                const item = explorerPois.find(p => p.id == id);
                if (item && explorerMap) {
                    explorerClusters.zoomToShowLayer(item.marker, function() {
                        item.marker.openPopup();
                    });
                }
            };

            // ================== REPROCESSAR NARRATIVA ==================
            window.reprocessNarrative = async function(btn) {
                if(!confirm('Deseja solicitar uma nova geração desta narrativa via IA?')) return;
                
                const originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.textContent = 'Solicitando...';
                const spinner = document.createElement('i');
                spinner.className = 'fa-solid fa-spinner fa-spin me-2';
                btn.prepend(spinner);

                try {
                    const response = await fetch('{{ route('report.reprocess', $report->cep) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        // Recarregar a página para ativar o estado 'processing_text' e o polling
                        window.location.reload();
                    } else {
                        alert('Erro ao solicitar reprocessamento. Tente novamente mais tarde.');
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                } catch (e) {
                    console.error(e);
                    alert('Erro na conexão. Verifique sua internet.');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            };

            // Polling para narrativa se estiver processando
            @if($report->status === 'processing_text')
                const narrativeInterval = setInterval(async () => {
                    try {
                        const response = await fetch('/api/report-status/{{ $report->cep }}');
                        const data = await response.json();
                        if (data.status === 'completed') {
                            clearInterval(narrativeInterval);
                            window.location.reload();
                        }
                    } catch (e) { console.error("Erro no polling da narrativa:", e); }
                }, 4000);
            @endif

            // ================== NOVO MODO COMPARATIVO ==================
            window.toggleCompare = function() {
                const panel = document.getElementById('compare-panel');
                panel.classList.toggle('active');
                if (panel.classList.contains('active')) {
                    document.getElementById('v-compare-input').focus();
                }
            };

            window.startComparison = function() {
                const newCepRaw = document.getElementById('v-compare-input').value;
                const newCep = newCepRaw.replace(/\D/g, '');
                
                if (newCep.length === 8) {
                    const currentCep = '{{ $report->cep }}';
                    const loader = document.getElementById('loader');
                    
                    // Show loader
                    loader.setAttribute('style', 'display: flex !important');
                    
                    // Loader Animation Logic
                    const loaderSteps = [
                        { text: "Acessando satélites...", progress: 20 },
                        { text: "Analisando dados estruturais...", progress: 45 },
                        { text: "Calculando Scores Territoriais...", progress: 70 },
                        { text: "Sincronizando Gemini AI...", progress: 90 },
                        { text: "Quase pronto...", progress: 98 }
                    ];
                    
                    let step = 0;
                    const textEl = document.getElementById('loader-text');
                    const barEl = document.getElementById('progress-bar');
                    
                    setInterval(() => {
                        if (step < loaderSteps.length) {
                            textEl.innerText = loaderSteps[step].text;
                            barEl.style.width = loaderSteps[step].progress + '%';
                            step++;
                        }
                    }, 2500);

                    window.location.href = `/compare/${currentCep}/${newCep}`;
                } else {
                    alert("Por favor, digite um CEP válido com 8 dígitos.");
                }
            };
            
            document.getElementById('v-compare-input').addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '');
                if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5, 8);
                e.target.value = v;
            });
            
            // Fechar componentes com a tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    // Fechar Painel Comparativo
                    const panel = document.getElementById('compare-panel');
                    if (panel && panel.classList.contains('active')) toggleCompare();
                    
                    // Fechar Modo Explorador
                    const explorer = document.getElementById('explorer-overlay');
                    if (explorer && explorer.style.display === 'flex') toggleExplorer();
                }
            });
        });
    </script>
    
    <style>
        @keyframes pulse {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }

        /* PRINT / PDF FULL FIDELITY */
        @media print {
            .no-print, .btn-pro { display: none !important; }
            .card-pro { 
                break-inside: avoid !important; 
                page-break-inside: avoid !important;
                border: 1px solid rgba(0,0,0,0.05) !important;
                box-shadow: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body { 
                background: #f8fafc !important; 
                color: #1e293b !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .metric-icon-pro { 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .badge-pro { 
                border: 1px solid rgba(0,0,0,0.1) !important;
                background: white !important;
                color: black !important;
            }
            #map { height: 400px !important; }
        }

    </style>

    <!-- OUTRAS REGIÕES NA CIDADE (CROSS-LINKING) -->
    <section class="container-fluid px-lg-5 mb-10 no-print" style="margin-top: 5rem;">
        <div class="d-flex align-items-center gap-3 mb-5">
            <div class="h-px bg-slate-200 flex-grow-1"></div>
            <h5 class="font-heading text-secondary text-uppercase tracking-widest mb-0" style="font-size: 13px;">Explorar mais em {{ $report->cidade }}</h5>
            <div class="h-px bg-slate-200 flex-grow-1"></div>
        </div>

        <div class="row g-4">
            @php
                $others = \App\Models\LocationReport::where('cidade', $report->cidade)
                    ->where('cep', '!=', $report->cep)
                    ->whereNotNull('bairro')
                    ->orderBy('created_at', 'desc')
                    ->limit(4)
                    ->get();
            @endphp

            @foreach($others as $other)
            <div class="col-md-3">
                <a href="{{ route('report.show', $other->cep) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="transition: all 0.3s ease;">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">{{ $other->final_score ?? 0 }} pts</span>
                                <i class="fa-solid fa-chevron-right text-muted small"></i>
                            </div>
                            <h6 class="text-dark fw-bold mb-1">{{ $other->bairro }}</h6>
                            <p class="text-muted small mb-0">{{ $other->cidade }} / {{ $other->uf }}</p>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach

            @if($others->count() > 0)
            <div class="col-md-12 text-center mt-5">
                <a href="{{ $city ? route('city.show', $city->slug) : route('city.show', ['slug' => Str::slug($report->cidade . '-' . $report->uf)]) }}" class="btn btn-outline-primary rounded-pill px-5 fw-bold text-uppercase tracking-widest" style="font-size: 11px;">
                    Ver todos os bairros de {{ $report->cidade }} <i class="fa-solid fa-arrow-right ms-2"></i>
                </a>
            </div>
            @endif
        </div>
    </section>

    <!-- PREMIUM FOOTER: STATE OF THE ART -->
    <footer class="bg-dark py-14 mt-10 no-print border-top border-white/5 overflow-hidden position-relative">
        <!-- Subtle Background Glow -->
        <div style="position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%); width: 300px; height: 100px; background: rgba(99, 102, 241, 0.05); filter: blur(80px); border-radius: 50%; pointer-events: none;"></div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row align-items-center">
                
                <!-- Brand Identity -->
                <div class="col-lg-4 text-center text-lg-start mb-5 mb-lg-0">
                    <div class="d-inline-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-5 p-2 rounded-4 border border-white border-opacity-10 backdrop-blur-md shadow-sm" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('favicon.png') }}" class="w-75 h-75 opacity-90" alt="Logo">
                        </div>
                        <div>
                            <h5 class="text-white fw-black text-uppercase tracking-tighter mb-0" style="font-size: 1.2rem; line-height: 1;">{{ config('app.name') }}</h5>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge bg-indigo-500 bg-opacity-10 text-indigo-400 border border-indigo-500 border-opacity-20 px-2 py-0.5" style="font-size: 8px; font-weight: 900; letter-spacing: 0.1em;">V1.4 TERRITORY ENGINE™</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Status -->
                <div class="col-lg-4 text-center mb-5 mb-lg-0">
                    <div class="d-inline-flex flex-column align-items-center">
                        <div class="h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent w-full mb-3" style="width: 120px;"></div>
                        <p class="text-slate-500 text-[9px] font-bold uppercase tracking-[0.4em] mb-0" style="opacity: 0.6;">
                            Territory Data Intelligence Protocol
                        </p>
                    </div>
                </div>

                <!-- Personal Signature -->
                <div class="col-lg-4 text-center text-lg-end">
                    <div class="d-inline-flex flex-column align-items-lg-end align-items-center">
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-2" style="font-size: 10px;">
                            Powered by <span class="text-white border-bottom border-indigo-500 border-opacity-50 pb-0.5">Werneck</span>
                        </p>
                        <div class="d-flex align-items-center gap-2 text-slate-600 fw-bold" style="font-size: 9px; letter-spacing: 0.05em;">
                            <span>© {{ date('Y') }}</span>
                            <span class="opacity-25 text-indigo-400">•</span>
                            <span class="text-indigo-400/50">Brasil</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // OMNISEARCH LOGIC
        function openOmnisearch() {
            const el = document.getElementById('omnisearch');
            el.style.display = 'block';
            setTimeout(() => {
                document.body.classList.add('omnisearch-active');
                document.getElementById('omni-input').focus();
            }, 10);
        }

        function closeOmnisearch(e) {
            if (e && e.key === 'Escape') {
                document.body.classList.remove('omnisearch-active');
                setTimeout(() => document.getElementById('omnisearch').style.display = 'none', 300);
            } else if (e) {
                document.body.classList.remove('omnisearch-active');
                setTimeout(() => document.getElementById('omnisearch').style.display = 'none', 300);
            }
        }

        // Shortcut Ctrl + K
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openOmnisearch();
            }
        });

        // Search Input Logic
        const omniInput = document.getElementById('omni-input');
        const omniResults = document.getElementById('omni-results');

        if (omniInput) {
            let debounceTimer;
            omniInput.addEventListener('input', (e) => {
                const q = e.target.value.trim();
                
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(async () => {
                    if (q.length < 2) {
                        omniResults.innerHTML = '<div class="p-4 text-center text-muted small uppercase fw-bold tracking-widest">Digite para buscar...</div>';
                        return;
                    }

                    try {
                        const response = await fetch(`/suggestions?q=${encodeURIComponent(q)}`);
                        const data = await response.json();
                        
                        if (data.length === 0) {
                            omniResults.innerHTML = '<div class="p-4 text-center text-muted">Nenhum resultado encontrado.</div>';
                            return;
                        }

                        omniResults.innerHTML = data.map(item => `
                            <a href="/cep/${item.cep.replace(/\D/g, '')}" class="d-flex align-items-center gap-3 p-3 text-decoration-none hover:bg-light rounded-3 transition-all border-bottom">
                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3" style="width: 40px; text-align: center;">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div>
                                    <div class="text-dark fw-bold">${item.details.road || item.details.neighborhood || 'Localização'}</div>
                                    <div class="text-muted small">${item.details.city} - ${item.details.state} • ${item.cep}</div>
                                </div>
                            </a>
                        `).join('');
                    } catch (err) {
                        console.error(err);
                    }
                }, 300);
            });
        }

        // Territory Radar Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('territoryRadarChart').getContext('2d');
            
            @php
                $scores = [
                    'Sicherheit' => ($report->score_safety ?? 70),
                    'Educação' => min(100, count($poi_education) * 10 + 40),
                    'Saúde' => min(100, count($poi_health) * 15 + 30),
                    'Caminhabilidade' => ($report->walkability_score == 'A' ? 100 : ($report->walkability_score == 'B' ? 70 : 40)),
                    'Ambiente' => (100 - ($report->air_quality_index ?? 20))
                ];
            @endphp

            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Segurança', 'Educação', 'Saúde', 'Mobilidade', 'Ambiente'],
                    datasets: [{
                        label: 'Desempenho Territorial',
                        data: [
                            {{ $scores['Sicherheit'] }},
                            {{ $scores['Educação'] }},
                            {{ $scores['Saúde'] }},
                            {{ $scores['Caminhabilidade'] }},
                            {{ $scores['Ambiente'] }}
                        ],
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: '#6366f1',
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#6366f1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            angleLines: { color: 'rgba(0,0,0,0.05)' },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            pointLabels: { font: { size: 10, weight: 'bold' }, color: '#64748b' },
                            ticks: { display: false, stepSize: 20 },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
</body>
</html>
