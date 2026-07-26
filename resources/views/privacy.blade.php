<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi | JUPE BB</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: #f9f9f9;
        }
        .container {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #0f766e; border-bottom: 2px solid #0f766e; padding-bottom: 10px; }
        h2 { color: #134e4a; margin-top: 30px; }
        p { margin-bottom: 15px; }
        ul { margin-bottom: 15px; }
        li { margin-bottom: 8px; }
        .contact-info {
            background: #eef4f2;
            padding: 20px;
            border-left: 4px solid #0f766e;
            margin-top: 30px;
        }
        footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Kebijakan Privasi JUPE BB</h1>
        <p>Terakhir diperbarui: {{ date('d F Y') }}</p>

        <p>Selamat datang di <strong>JUPE BB (Jadwal Untuk Pengontrolan Berat Badan)</strong>. Kami sangat menghargai privasi Anda dan berkomitmen untuk melindungi data pribadi Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi Anda saat Anda menggunakan aplikasi mobile dan layanan kami.</p>

        <h2>1. Informasi yang Kami Kumpulkan</h2>
        <p>Kami mengumpulkan informasi untuk memberikan layanan yang lebih baik kepada semua pengguna kami, yang meliputi:</p>
        <ul>
            <li><strong>Informasi Akun:</strong> Nama, alamat email (termasuk melalui Login Google), dan nomor telepon.</li>
            <li><strong>Data Kesehatan:</strong> Berat badan harian, tanda-tanda vital (tekanan darah, suhu, dll), gejala yang dirasakan, dan jadwal hemodialisis.</li>
            <li><strong>Data Profil Medis:</strong> Nomor rekam medis, NIK, tanggal lahir, jenis kelamin, dan riwayat kesehatan.</li>
            <li><strong>Informasi Perangkat:</strong> Token perangkat (untuk notifikasi FCM).</li>
        </ul>

        <h2>2. Penggunaan Informasi</h2>
        <p>Kami menggunakan data yang dikumpulkan untuk:</p>
        <ul>
            <li>Memantau kondisi kesehatan dan berat badan Anda (perhitungan IDWG).</li>
            <li>Memberikan notifikasi pengingat jadwal hemodialisis.</li>
            <li>Memungkinkan dokter dan perawat untuk menindaklanjuti kondisi berisiko (Risk Alerts).</li>
            <li>Keperluan administrasi dan sinkronisasi data antar perangkat.</li>
        </ul>

        <h2>3. Perlindungan Data</h2>
        <p>Kami menggunakan enkripsi protokol HTTPS untuk semua transmisi data. Data Anda disimpan di server yang aman dan hanya dapat diakses oleh tenaga medis yang berwenang (Dokter/Perawat) yang menangani Anda serta Admin sistem.</p>

        <h2>4. Berbagi Informasi</h2>
        <p>Kami tidak membagikan, menjual, atau menyewakan informasi pribadi Anda kepada pihak ketiga untuk tujuan pemasaran. Data medis Anda hanya dibagikan kepada tenaga medis terdaftar di dalam aplikasi ini untuk kepentingan perawatan Anda.</p>

        <h2>5. Izin Perangkat</h2>
        <p>Aplikasi kami meminta izin tertentu seperti:</p>
        <ul>
            <li><strong>Internet:</strong> Untuk sinkronisasi data dengan server.</li>
            <li><strong>Notifikasi:</strong> Untuk mengirimkan pengingat jadwal dan peringatan risiko.</li>
        </ul>

        <h2>6. Hak Anda</h2>
        <p>Anda berhak untuk mengakses, memperbarui, atau meminta penghapusan data pribadi Anda dengan menghubungi kami melalui kontak di bawah ini.</p>

        <div class="contact-info">
            <h2>Hubungi Kami</h2>
            <p>Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi kami:</p>
            <ul>
                <li><strong>Email:</strong> jupebb@mentarimentalhealth.com</li>
                <li><strong>Telepon/WA:</strong> 081276599838</li>
                <li><strong>Alamat:</strong> Mentari Mental Health, Indonesia</li>
            </ul>
        </div>
    </div>

    <footer>
        &copy; {{ date('Y') }} JUPE BB - Mentari Mental Health. All rights reserved.
    </footer>
</body>
</html>
