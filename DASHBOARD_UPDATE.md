# Dashboard IDWG - Update Desain Profesional 🎨

## 📋 Ringkasan Perubahan

Dashboard IDWG telah diperbarui dengan desain yang lebih modern, profesional, dan mudah dipahami. Perubahan mencakup:

### ✨ Fitur Baru

1. **System Health Widget** - Monitoring kesehatan sistem secara real-time
2. **Quick Insights Widget** - 6 metrik penting dalam satu tampilan
3. **Enhanced Charts** - Grafik dengan styling yang lebih baik dan interaktif
4. **Improved Stats Cards** - Kartu statistik dengan gradient dan animasi hover
5. **Enhanced Alert Table** - Tabel dengan icon dan badge yang lebih informatif

---

## 🎯 Widget yang Diperbarui

### 1. **System Health Widget** (Baru)
**Lokasi:** `app/Filament/Widgets/SystemHealthWidget.php`

**Fitur:**
- Health Score dengan circular progress bar
- Status sistem (Excellent/Good/Fair/Poor)
- 4 metrik utama:
  - Total Pasien & Pasien Aktif
  - Aktivitas 24 Jam
  - Total Alert & Alert Belum Selesai
  - Kepatuhan Monitoring Hari Ini
- Auto-refresh setiap 60 detik

**Warna Status:**
- 🟢 Excellent (80-100): Hijau
- 🔵 Good (60-79): Biru
- 🟡 Fair (40-59): Kuning
- 🔴 Poor (0-39): Merah

---

### 2. **Admin Stats Overview** (Enhanced)
**Lokasi:** `app/Filament/Widgets/AdminStatsOverview.php`

**Peningkatan:**
- Hero card dengan gradient background untuk pasien risiko tinggi
- Kartu statistik dengan gradient dan shadow
- Icon yang lebih besar dengan efek hover
- Badge "LIVE" untuk real-time data
- Animasi smooth hover dengan scale transform
- Section "Ringkasan Tindakan" dengan 3 metric follow-up
- Dekoratif background elements

**Layout:**
- Hero Card: 1 kolom penuh dengan informasi critical
- Stats Grid: 6 kartu dalam grid responsive
- Action Bar: 3 metrik penting di bawah

---

### 3. **Quick Insights Widget** (Baru)
**Lokasi:** `app/Filament/Widgets/QuickInsightsWidget.php`

**6 Metrik:**
1. **Kepatuhan Monitoring** (%)
   - Persentase pasien yang melakukan monitoring hari ini
   - Warna: Success/Warning/Danger berdasarkan threshold

2. **Durasi Rata-rata HD** (jam)
   - Rata-rata durasi hemodialisis minggu ini
   
3. **Alert Kritis Hari Ini**
   - Jumlah alert Tinggi/Darurat yang belum ditindaklanjuti
   
4. **Pasien Perlu Perhatian**
   - Pasien dengan alert risiko tinggi atau darurat
   
5. **IDWG > 5%**
   - Jumlah pasien dengan IDWG melebihi 5% minggu ini
   
6. **Tren Alert Mingguan** (%)
   - Perbandingan alert minggu ini vs minggu lalu
   - Icon: ↑ (naik), ↓ (turun), - (stabil)

---

### 4. **Chart Widgets** (Enhanced)

#### a. **IDWG Trend Chart**
**Peningkatan:**
- Line chart dengan area fill
- Warna: Blue gradient
- Border width 3px dengan smooth curve
- Point hover effects
- Y-axis dengan suffix "%"
- X-axis dengan format tanggal "d M"

#### b. **Daily Weight Trend Chart**
**Peningkatan:**
- Line chart dengan area fill
- Warna: Green gradient
- Y-axis dengan suffix "kg"
- Smooth tension curve
- Auto-refresh 30s

#### c. **Risk Alerts Per Day Chart**
**Peningkatan:**
- Bar chart dengan rounded corners
- Warna: Red gradient
- Border radius 6px
- Y-axis dengan step size 1
- Auto-refresh 30s

#### d. **Risk Alerts By Level Chart**
**Peningkatan:**
- Doughnut chart dengan cutout 60%
- 4 warna berbeda untuk setiap level:
  - Normal: Green
  - Waspada: Amber
  - Tinggi: Orange
  - Darurat: Red
- Legend di bottom
- Auto-refresh 30s

---

### 5. **Latest Risk Alerts Table** (Enhanced)
**Lokasi:** `app/Filament/Widgets/LatestRiskAlertsTable.php`

**Peningkatan:**
- Limit 10 record terbaru
- Icon untuk setiap kolom
- Badge dengan warna semantik
- Icon dinamis berdasarkan level alert:
  - 🔥 Darurat
  - ⚠️ Tinggi
  - ⚪ Waspada
  - ℹ️ Normal
- Status icon:
  - ✅ Ditindaklanjuti
  - 👁️ Dibaca
  - 🔔 Baru
- Tooltip untuk deskripsi panjang
- Empty state yang informatif
- Auto-refresh 30s

---

## 🎨 Desain System

### Color Palette
```
Primary: Teal
Success: Emerald
Info: Blue
Warning: Amber
Danger: Rose
Neutral: Slate
```

### Gradient Styles
- **Card Backgrounds:** `from-{color}-50 to-{color}-100/60`
- **Icons:** `from-{color}-500 to-{color}-600`
- **Shadows:** `shadow-lg shadow-{color}-500/30`

### Animation & Effects
- **Hover Scale:** `hover:scale-[1.02]` atau `hover:scale-105`
- **Hover Rotate:** `hover:rotate-3` atau `hover:rotate-6`
- **Transition:** `duration-300` untuk smooth animation
- **Blur Effects:** Background blur untuk depth

---

## 📱 Responsive Design

### Breakpoints
- **Mobile (sm):** 1 kolom
- **Tablet (md):** 2 kolom
- **Desktop (lg):** 3 kolom
- **Large (xl):** 4-6 kolom tergantung widget

### Dashboard Columns
```php
'sm' => 1,
'md' => 2,
'lg' => 2,
'xl' => 4,
```

---

## 🔄 Auto-Refresh

Semua widget mendukung polling untuk data real-time:

| Widget | Polling Interval |
|--------|-----------------|
| System Health | 60s |
| Charts | 30s |
| Alerts Table | 30s |

---

## 🚀 Cara Menggunakan

### Melihat Dashboard
1. Login ke admin panel
2. Klik menu "Dashboard" (default landing page)
3. Dashboard akan otomatis refresh sesuai polling interval

### Header Actions
Di bagian atas dashboard ada 3 quick action buttons:
- **Data Pasien** - Akses langsung ke daftar pasien
- **Monitoring** - Akses ke daily monitoring
- **Alert** - Akses ke risk alerts (dengan badge counter)

---

## 📊 Widget Order (Sort)

```
0. System Health Widget
-2. Admin Stats Overview
6. Quick Insights Widget
1. IDWG Trend Chart
2. Daily Weight Trend Chart
3. Risk Alerts Per Day Chart
4. Risk Alerts By Level Chart
5. Latest Risk Alerts Table
```

---

## 🛠️ File yang Dimodifikasi

### PHP Files
1. `app/Filament/Pages/Dashboard.php` - Layout dan actions
2. `app/Filament/Widgets/AdminStatsOverview.php` - Enhanced stats
3. `app/Filament/Widgets/IdwgTrendChart.php` - Chart styling
4. `app/Filament/Widgets/DailyWeightTrendChart.php` - Chart styling
5. `app/Filament/Widgets/RiskAlertsPerDayChart.php` - Chart styling
6. `app/Filament/Widgets/RiskAlertsByLevelChart.php` - Chart styling
7. `app/Filament/Widgets/LatestRiskAlertsTable.php` - Enhanced table
8. `app/Providers/Filament/AdminPanelProvider.php` - Widget registration

### Blade Views
1. `resources/views/filament/widgets/admin-stats-overview.blade.php` - Enhanced UI
2. `resources/views/filament/widgets/quick-insights.blade.php` - New widget
3. `resources/views/filament/widgets/system-health.blade.php` - New widget

---

## 💡 Tips Customization

### Mengubah Warna Theme
Edit `AdminPanelProvider.php`:
```php
->colors([
    'primary' => Color::Teal,  // Ubah sesuai kebutuhan
    'danger' => Color::Rose,
    // ...
])
```

### Mengubah Polling Interval
Edit widget PHP file:
```php
protected static ?string $pollingInterval = '30s'; // Ubah sesuai kebutuhan
```

### Menambah/Mengurangi Metrics
Edit `QuickInsightsWidget.php` method `getViewData()`:
```php
'insights' => [
    [
        'label' => 'Metric Name',
        'value' => 100,
        'unit' => 'unit',
        'description' => 'Description',
        'icon' => 'heroicon-o-icon',
        'color' => 'success',
    ],
    // Tambah metric lain...
],
```

---

## 📝 Notes

- Semua widget menggunakan TailwindCSS untuk styling
- Chart.js digunakan untuk visualisasi data
- Icons menggunakan Heroicons
- Support dark mode out of the box
- Fully responsive untuk semua device sizes

---

## 🎯 Hasil Akhir

Dashboard yang baru memberikan:
✅ Visualisasi data yang lebih jelas
✅ Informasi critical yang mudah terlihat
✅ Desain modern dan profesional
✅ User experience yang lebih baik
✅ Real-time monitoring dengan auto-refresh
✅ Responsive di semua device

---

**Created by:** Kiro AI Assistant
**Date:** 2026-07-03
**Version:** 2.0
