@php
    $appName = config('app.name', 'IDWG Monitoring');
    $displayName = $appName === 'Laravel' ? 'IDWG Monitoring' : $appName;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="IDWG Monitoring membantu tim hemodialisis memantau berat harian, jadwal HD, gejala risiko, edukasi pasien, dan alert klinis dalam satu sistem.">

        <title>{{ $displayName }} | Monitoring Hemodialisis</title>

        <style>
            @font-face {
                font-family: "Inter Variable";
                font-style: normal;
                font-display: swap;
                font-weight: 100 900;
                src: url("{{ asset('fonts/filament/filament/inter/inter-latin-wght-normal-NRMW37G5.woff2') }}") format("woff2-variations");
                unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
            }

            @font-face {
                font-family: "Inter Variable";
                font-style: normal;
                font-display: swap;
                font-weight: 100 900;
                src: url("{{ asset('fonts/filament/filament/inter/inter-latin-ext-wght-normal-HA22NDSG.woff2') }}") format("woff2-variations");
                unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
            }

            :root {
                color-scheme: light;
                --ink: #10202a;
                --muted: #52616e;
                --line: #e0ece9;
                --soft: #f7fbfa;
                --surface: #ffffff;
                --teal: #0f766e;
                --teal-dark: #134e4a;
                --emerald: #047857;
                --sky: #0369a1;
                --amber: #b45309;
                --rose: #be123c;
                --shadow: 0 18px 48px rgba(15, 118, 110, .11);
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                font-family: "Inter Variable", "Segoe UI", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
                font-feature-settings: "cv02", "cv03", "cv04", "cv11";
                font-size: 16px;
                color: var(--ink);
                background: var(--soft);
                line-height: 1.65;
                text-rendering: optimizeLegibility;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .site-header {
                position: sticky;
                top: 0;
                z-index: 20;
                background: rgba(255, 255, 255, .9);
                border-bottom: 1px solid rgba(220, 232, 230, .9);
                backdrop-filter: blur(16px);
            }

            .nav {
                width: min(1160px, calc(100% - 32px));
                min-height: 70px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                min-width: 0;
                font-weight: 760;
                letter-spacing: 0;
            }

            .brand-mark {
                width: 38px;
                height: 38px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                color: #ffffff;
                background: linear-gradient(135deg, var(--teal), var(--emerald));
                box-shadow: 0 10px 24px rgba(15, 118, 110, .24);
                font-size: 15px;
                line-height: 1;
                flex: 0 0 auto;
            }

            .brand span:last-child {
                max-width: min(48vw, 430px);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .nav-links {
                display: flex;
                align-items: center;
                gap: 24px;
                color: #405463;
                font-size: 14px;
                font-weight: 650;
            }

            .nav-links a {
                white-space: nowrap;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                padding: 0 18px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 720;
                transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
            }

            .button:hover {
                transform: translateY(-1px);
            }

            .button-primary {
                color: #ffffff;
                background: var(--teal);
                box-shadow: 0 12px 26px rgba(15, 118, 110, .2);
            }

            .button-primary:hover {
                background: var(--teal-dark);
            }

            .button-secondary {
                color: #e8fffb;
                border: 1px solid rgba(232, 255, 251, .38);
                background: rgba(255, 255, 255, .12);
            }

            .button-outline {
                color: var(--teal-dark);
                border: 1px solid var(--line);
                background: var(--surface);
            }

            .nav-cta-mobile {
                display: none;
                min-height: 40px;
                padding-inline: 14px;
            }

            .hero {
                position: relative;
                min-height: 76svh;
                display: flex;
                align-items: center;
                overflow: hidden;
                background-image:
                    linear-gradient(90deg, rgba(7, 38, 42, .88) 0%, rgba(7, 38, 42, .7) 38%, rgba(7, 38, 42, .2) 70%, rgba(7, 38, 42, .05) 100%),
                    url('{{ asset('images/landing-hero.png') }}');
                background-position: center;
                background-size: cover;
            }

            .hero-inner {
                width: min(1160px, calc(100% - 32px));
                margin: 0 auto;
                padding: 86px 0 62px;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin: 0 0 18px;
                color: #b7fff4;
                font-size: 12px;
                font-weight: 760;
                text-transform: uppercase;
                letter-spacing: .08em;
            }

            .eyebrow::before {
                content: "";
                width: 28px;
                height: 2px;
                background: #6ee7b7;
            }

            .hero-copy {
                max-width: 630px;
                color: #ffffff;
            }

            .hero h1 {
                margin: 0;
                font-size: clamp(40px, 6vw, 74px);
                line-height: 1.02;
                letter-spacing: 0;
                font-weight: 820;
            }

            .hero p {
                max-width: 570px;
                margin: 22px 0 0;
                color: #dff8f4;
                font-size: clamp(16px, 1.5vw, 20px);
                line-height: 1.72;
            }

            .hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 32px;
            }

            .hero-metrics {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
                max-width: 630px;
                margin-top: 40px;
            }

            .metric {
                min-height: 106px;
                padding: 17px;
                border: 1px solid rgba(255, 255, 255, .22);
                border-radius: 8px;
                background: rgba(6, 43, 47, .48);
                backdrop-filter: blur(10px);
            }

            .metric strong {
                display: block;
                color: #ffffff;
                font-size: 22px;
                line-height: 1.15;
                font-weight: 780;
            }

            .metric span {
                display: block;
                margin-top: 8px;
                color: #cdf7ef;
                font-size: 13px;
                font-weight: 600;
            }

            .section {
                padding: 76px 0;
            }

            .section.alt {
                background: #ffffff;
            }

            .container {
                width: min(1160px, calc(100% - 32px));
                margin: 0 auto;
            }

            .section-heading {
                max-width: 760px;
                margin-bottom: 34px;
            }

            .section-kicker {
                margin: 0 0 10px;
                color: var(--teal);
                font-size: 12px;
                font-weight: 780;
                text-transform: uppercase;
                letter-spacing: .08em;
            }

            .section h2 {
                margin: 0;
                font-size: clamp(28px, 3.2vw, 44px);
                line-height: 1.12;
                letter-spacing: 0;
                font-weight: 780;
            }

            .section-heading p {
                margin: 18px 0 0;
                color: var(--muted);
                font-size: 16px;
                line-height: 1.75;
            }

            .feature-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .feature-card {
                min-height: 238px;
                padding: 22px;
                border: 1px solid var(--line);
                border-radius: 8px;
                background: var(--surface);
                box-shadow: 0 10px 28px rgba(16, 32, 42, .045);
            }

            .feature-icon {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                color: #ffffff;
                font-size: 18px;
                font-weight: 780;
            }

            .feature-icon.teal {
                background: var(--teal);
            }

            .feature-icon.sky {
                background: var(--sky);
            }

            .feature-icon.amber {
                background: var(--amber);
            }

            .feature-icon.rose {
                background: var(--rose);
            }

            .feature-icon.emerald {
                background: var(--emerald);
            }

            .feature-icon.ink {
                background: #334155;
            }

            .feature-card h3 {
                margin: 18px 0 10px;
                font-size: 19px;
                line-height: 1.3;
                font-weight: 760;
            }

            .feature-card p {
                margin: 0;
                color: var(--muted);
                font-size: 15px;
                line-height: 1.7;
            }

            .workflow {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 1px;
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 8px;
                background: var(--line);
                box-shadow: var(--shadow);
            }

            .step {
                min-height: 226px;
                padding: 24px;
                background: #ffffff;
            }

            .step-number {
                width: 36px;
                height: 36px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                color: #ffffff;
                background: var(--teal);
                font-size: 14px;
                font-weight: 760;
            }

            .step h3 {
                margin: 20px 0 10px;
                font-size: 18px;
                line-height: 1.32;
                font-weight: 760;
            }

            .step p {
                margin: 0;
                color: var(--muted);
                font-size: 15px;
                line-height: 1.7;
            }

            .module-band {
                display: grid;
                grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
                gap: 42px;
                align-items: start;
            }

            .module-panel {
                border: 1px solid var(--line);
                border-radius: 8px;
                background: #ffffff;
                box-shadow: var(--shadow);
                overflow: hidden;
            }

            .module-panel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 20px 22px;
                border-bottom: 1px solid var(--line);
            }

            .module-panel-header strong {
                font-size: 15px;
                font-weight: 760;
            }

            .status-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: var(--emerald);
                box-shadow: 0 0 0 6px rgba(4, 120, 87, .1);
            }

            .module-list {
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .module-list li {
                display: grid;
                grid-template-columns: 120px 1fr auto;
                gap: 16px;
                align-items: center;
                padding: 18px 22px;
                border-bottom: 1px solid #eef4f2;
            }

            .module-list li:last-child {
                border-bottom: 0;
            }

            .module-list span {
                color: var(--muted);
                font-size: 14px;
                font-weight: 640;
            }

            .module-list strong {
                font-size: 15px;
                font-weight: 720;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 28px;
                padding: 0 10px;
                border-radius: 999px;
                font-size: 12px;
                font-style: normal;
                font-weight: 760;
                white-space: nowrap;
            }

            .badge.safe {
                color: #065f46;
                background: #d1fae5;
            }

            .badge.watch {
                color: #92400e;
                background: #fef3c7;
            }

            .badge.risk {
                color: #9f1239;
                background: #ffe4e6;
            }

            .audience-grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 14px;
            }

            .audience {
                min-height: 150px;
                padding: 18px;
                border: 1px solid var(--line);
                border-radius: 8px;
                background: #ffffff;
            }

            .audience h3 {
                margin: 0 0 8px;
                font-size: 16px;
                line-height: 1.3;
                font-weight: 760;
            }

            .audience p {
                margin: 0;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.65;
            }

            .integration {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(320px, .85fr);
                gap: 34px;
                align-items: center;
                padding: 32px;
                border-radius: 8px;
                color: #ffffff;
                background: #0b3b3f;
            }

            .integration h2 {
                color: #ffffff;
            }

            .integration p {
                color: #d7f6f0;
            }

            .endpoint-box {
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, .16);
                border-radius: 8px;
                background: rgba(2, 20, 23, .46);
            }

            .endpoint-row {
                display: grid;
                grid-template-columns: 72px 1fr;
                gap: 12px;
                padding: 14px 16px;
                border-bottom: 1px solid rgba(255, 255, 255, .12);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
                font-size: 13px;
            }

            .endpoint-row:last-child {
                border-bottom: 0;
            }

            .method {
                color: #99f6e4;
                font-weight: 760;
            }

            .path {
                color: #ffffff;
                overflow-wrap: anywhere;
            }

            .footer {
                padding: 28px 0;
                color: #526474;
                background: #ffffff;
                border-top: 1px solid var(--line);
            }

            .footer-inner {
                width: min(1180px, calc(100% - 32px));
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                font-size: 14px;
            }

            @media (max-width: 1120px) {
                .feature-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .workflow {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .audience-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (max-width: 980px) {
                .nav-links {
                    display: none;
                }

                .nav-cta-mobile {
                    display: inline-flex;
                    width: auto;
                }

                .hero {
                    min-height: auto;
                    background-position: 62% center;
                }

                .hero-inner {
                    padding: 72px 0 48px;
                }

                .module-band,
                .integration {
                    grid-template-columns: 1fr;
                }

                .hero-metrics {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                .section {
                    padding: 66px 0;
                }

                .module-band {
                    gap: 28px;
                }
            }

            @media (max-width: 760px) {
                .feature-grid,
                .workflow,
                .audience-grid {
                    grid-template-columns: 1fr;
                }

                .feature-card,
                .step,
                .audience {
                    min-height: auto;
                }
            }

            @media (max-width: 640px) {
                .nav {
                    width: min(1180px, calc(100% - 24px));
                    min-height: 64px;
                    gap: 12px;
                }

                .brand-mark {
                    width: 36px;
                    height: 36px;
                    font-size: 13px;
                }

                .brand span:last-child {
                    max-width: calc(100vw - 168px);
                    font-size: 14px;
                }

                .hero .button,
                .module-band .button {
                    width: 100%;
                }

                .hero {
                    background-image:
                        linear-gradient(180deg, rgba(7, 38, 42, .92) 0%, rgba(7, 38, 42, .84) 52%, rgba(7, 38, 42, .68) 100%),
                        url('{{ asset('images/landing-hero.png') }}');
                    background-position: 66% center;
                }

                .hero-inner,
                .container {
                    width: min(1180px, calc(100% - 24px));
                }

                .hero-inner {
                    padding: 54px 0 36px;
                }

                .eyebrow,
                .section-kicker {
                    font-size: 11px;
                    line-height: 1.35;
                }

                .hero h1 {
                    font-size: clamp(36px, 12vw, 48px);
                    line-height: 1.06;
                }

                .hero p {
                    margin-top: 18px;
                    font-size: 16px;
                    line-height: 1.68;
                }

                .hero-actions {
                    flex-direction: column;
                    gap: 10px;
                    margin-top: 26px;
                }

                .hero-metrics {
                    grid-template-columns: 1fr;
                    gap: 10px;
                    margin-top: 28px;
                }

                .metric {
                    min-height: auto;
                    padding: 16px;
                }

                .section h2 {
                    font-size: clamp(26px, 8vw, 34px);
                    line-height: 1.18;
                }

                .section-heading {
                    margin-bottom: 26px;
                }

                .section-heading p {
                    margin-top: 14px;
                    font-size: 15px;
                    line-height: 1.72;
                }

                .section {
                    padding: 54px 0;
                }

                .feature-card,
                .step,
                .integration,
                .module-panel-header,
                .module-list li {
                    padding: 20px;
                }

                .module-list li {
                    grid-template-columns: 1fr;
                    gap: 6px;
                    align-items: start;
                }

                .integration {
                    gap: 24px;
                }

                .endpoint-row {
                    grid-template-columns: 58px 1fr;
                    gap: 10px;
                    padding: 13px 14px;
                    font-size: 12px;
                }

                .badge {
                    width: max-content;
                }

                .footer-inner {
                    width: min(1180px, calc(100% - 24px));
                    align-items: flex-start;
                    flex-direction: column;
                }
            }
        </style>
    </head>
    <body>
        <header class="site-header" aria-label="Navigasi utama">
            <nav class="nav">
                <a class="brand" href="{{ url('/') }}" aria-label="{{ $displayName }}">
                    <span class="brand-mark">ID</span>
                    <span>{{ $displayName }}</span>
                </a>

                <div class="nav-links" aria-label="Navigasi halaman">
                    <a href="#fitur">Fitur</a>
                    <a href="#alur">Alur</a>
                    <a href="#modul">Modul</a>
                    <a href="#integrasi">API</a>
                    <a class="button button-primary" href="{{ url('/admin') }}">Masuk Admin</a>
                </div>

                <a class="button button-primary nav-cta-mobile" href="{{ url('/admin') }}">Admin</a>
            </nav>
        </header>

        <main>
            <section class="hero">
                <div class="hero-inner">
                    <div class="hero-copy">
                        <p class="eyebrow">Aplikasi monitoring hemodialisis</p>
                        <h1>IDWG Monitoring</h1>
                        <p>
                            Sistem terpadu untuk memantau berat harian, jadwal hemodialisis, gejala risiko, edukasi pasien, dan tindak lanjut alert klinis secara lebih cepat.
                        </p>

                        <div class="hero-actions">
                            <a class="button button-primary" href="{{ url('/admin') }}">Buka Dashboard Admin</a>
                            <a class="button button-secondary" href="#fitur">Lihat Fitur Utama</a>
                        </div>

                        <div class="hero-metrics" aria-label="Ringkasan kemampuan aplikasi">
                            <div class="metric">
                                <strong>IDWG</strong>
                                <span>Pantau kenaikan berat antar sesi HD.</span>
                            </div>
                            <div class="metric">
                                <strong>Alert</strong>
                                <span>Deteksi pasien yang perlu diprioritaskan.</span>
                            </div>
                            <div class="metric">
                                <strong>API</strong>
                                <span>Siap terhubung dengan aplikasi mobile pasien.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="fitur" class="section alt">
                <div class="container">
                    <div class="section-heading">
                        <p class="section-kicker">Fitur inti</p>
                        <h2>Satu ruang kerja untuk memantau pasien HD dari data harian sampai tindak lanjut klinis.</h2>
                        <p>Landing page ini menggambarkan modul yang sudah tersedia di backend: pasien, profil medis, monitoring harian, jadwal dan sesi HD, vital sign, gejala risiko, edukasi, notifikasi, dan laporan.</p>
                    </div>

                    <div class="feature-grid">
                        <article class="feature-card">
                            <div class="feature-icon teal">01</div>
                            <h3>Monitoring Harian</h3>
                            <p>Catat berat badan, kondisi harian, dan status risiko agar perubahan pasien dapat dilihat sebelum sesi berikutnya.</p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon sky">02</div>
                            <h3>Jadwal Hemodialisis</h3>
                            <p>Kelola jadwal HD, presensi, dan pengingat sehingga perawat memiliki gambaran operasional harian yang jelas.</p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon amber">03</div>
                            <h3>Perhitungan IDWG</h3>
                            <p>Bantu tim melihat tren kenaikan berat antar sesi dan status pasien yang perlu edukasi atau evaluasi.</p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon rose">04</div>
                            <h3>Risk Alert</h3>
                            <p>Alert otomatis dari gejala, monitoring harian, dan sesi HD membantu dokter serta perawat menentukan prioritas.</p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon emerald">05</div>
                            <h3>Edukasi Pasien</h3>
                            <p>Materi edukasi dapat dikelola dan dibagikan agar pasien memahami pemantauan cairan, gejala, dan kepatuhan.</p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon ink">06</div>
                            <h3>Laporan Manajemen</h3>
                            <p>Data monitoring, alert, pasien berisiko, dan sesi HD tersusun untuk kebutuhan evaluasi layanan.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="alur" class="section">
                <div class="container">
                    <div class="section-heading">
                        <p class="section-kicker">Alur kerja</p>
                        <h2>Dibuat untuk kolaborasi pasien, perawat, dokter, admin, dan manajemen.</h2>
                    </div>

                    <div class="workflow">
                        <article class="step">
                            <div class="step-number">1</div>
                            <h3>Pasien melengkapi profil</h3>
                            <p>Data pasien dan profil medis menjadi dasar personalisasi monitoring serta akses dashboard.</p>
                        </article>

                        <article class="step">
                            <div class="step-number">2</div>
                            <h3>Data harian masuk</h3>
                            <p>Berat, vital sign, gejala, dan aktivitas monitoring tersimpan melalui API atau dashboard petugas.</p>
                        </article>

                        <article class="step">
                            <div class="step-number">3</div>
                            <h3>Sistem menilai risiko</h3>
                            <p>Perhitungan IDWG dan aturan risiko membantu menandai kondisi waspada, tinggi, atau darurat.</p>
                        </article>

                        <article class="step">
                            <div class="step-number">4</div>
                            <h3>Tim menindaklanjuti</h3>
                            <p>Dokter dan perawat dapat membaca, mencatat tindak lanjut, menyelesaikan alert, dan meninjau laporan.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="modul" class="section alt">
                <div class="container module-band">
                    <div class="section-heading">
                        <p class="section-kicker">Modul dashboard</p>
                        <h2>Status klinis lebih mudah dipindai.</h2>
                        <p>Dashboard admin Filament menyediakan tampilan operasional untuk data pasien, monitoring, chart, quick insight, dan daftar alert terbaru. Peran pengguna dibatasi sesuai kebutuhan akses.</p>
                        <div class="hero-actions">
                            <a class="button button-primary" href="{{ url('/admin') }}">Masuk ke Admin Panel</a>
                            <a class="button button-outline" href="#integrasi">Cek Endpoint API</a>
                        </div>
                    </div>

                    <div class="module-panel" aria-label="Contoh daftar modul monitoring">
                        <div class="module-panel-header">
                            <strong>Ringkasan pemantauan</strong>
                            <span class="status-dot" aria-hidden="true"></span>
                        </div>
                        <ul class="module-list">
                            <li>
                                <span>Monitoring</span>
                                <strong>Berat harian dan gejala risiko</strong>
                                <em class="badge watch">Waspada</em>
                            </li>
                            <li>
                                <span>Jadwal HD</span>
                                <strong>Presensi dan pengingat sesi</strong>
                                <em class="badge safe">Aktif</em>
                            </li>
                            <li>
                                <span>Risk Alert</span>
                                <strong>Tindak lanjut dokter dan perawat</strong>
                                <em class="badge risk">Prioritas</em>
                            </li>
                            <li>
                                <span>Laporan</span>
                                <strong>Rekap pasien berisiko dan sesi HD</strong>
                                <em class="badge safe">Siap</em>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="section">
                <div class="container">
                    <div class="section-heading">
                        <p class="section-kicker">Akses peran</p>
                        <h2>Setiap pengguna melihat data yang relevan dengan tugasnya.</h2>
                    </div>

                    <div class="audience-grid">
                        <article class="audience">
                            <h3>Admin</h3>
                            <p>Mengelola master data, pengguna, pasien, laporan, dan konfigurasi aplikasi.</p>
                        </article>
                        <article class="audience">
                            <h3>Perawat</h3>
                            <p>Mencatat jadwal, sesi HD, monitoring, vital sign, dan tindak lanjut awal.</p>
                        </article>
                        <article class="audience">
                            <h3>Dokter</h3>
                            <p>Meninjau alert prioritas, catatan dokter, laporan, dan kondisi pasien berisiko.</p>
                        </article>
                        <article class="audience">
                            <h3>Pasien</h3>
                            <p>Mengakses jadwal, edukasi, monitoring terakhir, dan informasi status risiko.</p>
                        </article>
                        <article class="audience">
                            <h3>Manajemen</h3>
                            <p>Melihat laporan agregat untuk evaluasi operasional dan kualitas layanan.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="integrasi" class="section alt">
                <div class="container">
                    <div class="integration">
                        <div class="section-heading">
                            <p class="section-kicker">Integrasi aplikasi</p>
                            <h2>Backend API siap dipakai aplikasi mobile.</h2>
                            <p>Endpoint versi pertama mendukung autentikasi Sanctum, login Google, device token, dashboard per role, data pasien, jadwal HD, monitoring harian, vital sign, risk alert, edukasi, notifikasi, dan laporan.</p>
                        </div>

                        <div class="endpoint-box" aria-label="Contoh endpoint API">
                            <div class="endpoint-row">
                                <span class="method">POST</span>
                                <span class="path">/api/v1/login</span>
                            </div>
                            <div class="endpoint-row">
                                <span class="method">GET</span>
                                <span class="path">/api/v1/dashboard</span>
                            </div>
                            <div class="endpoint-row">
                                <span class="method">POST</span>
                                <span class="path">/api/v1/daily-monitorings</span>
                            </div>
                            <div class="endpoint-row">
                                <span class="method">PATCH</span>
                                <span class="path">/api/v1/risk-alerts/{riskAlert}/follow-up</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-inner">
                <strong>{{ $displayName }}</strong>
                <span>Monitoring hemodialisis, IDWG, jadwal HD, dan risk alert.</span>
            </div>
        </footer>
    </body>
</html>
