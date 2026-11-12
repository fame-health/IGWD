<?php

namespace App\Console\Commands;

use App\Models\DialysisSession;
use App\Services\PreviousHdWeightService;
use Illuminate\Console\Command;

class BackfillInitialHdPreviousWeights extends Command
{
    protected $signature = 'app:backfill-initial-hd-previous-weights {--apply : Simpan perubahan ke database}';

    protected $description = 'Isi BB HD terakhir yang kosong dari sesi sebelumnya atau data awal sesi HD pertama.';

    public function handle(PreviousHdWeightService $previousWeights): int
    {
        $shouldApply = (bool) $this->option('apply');

        $updates = DialysisSession::query()
            ->with('patient:id,name')
            ->whereNull('previous_post_hd_weight')
            ->orderBy('session_date')
            ->orderBy('id')
            ->get()
            ->map(fn (DialysisSession $session): array => [
                'session' => $session,
                'weight' => $previousWeights->resolveForSession($session),
            ])
            ->filter(fn (array $candidate): bool => $candidate['weight'] !== null)
            ->values();

        if ($updates->isEmpty()) {
            $this->info('Tidak ada sesi HD kosong yang cocok untuk diisi.');

            return self::SUCCESS;
        }

        $this->info(($shouldApply ? 'Mengisi' : 'Dry-run menemukan').' '.$updates->count().' sesi HD.');
        $this->table(
            ['ID', 'Tanggal', 'Pasien', 'BB HD Terakhir'],
            $updates->map(fn (array $candidate): array => $this->row($candidate['session'], (float) $candidate['weight']))->all()
        );

        if (! $shouldApply) {
            $this->comment('Jalankan lagi dengan --apply untuk menyimpan perubahan.');

            return self::SUCCESS;
        }

        $updates->each(function (array $candidate): void {
            $candidate['session']
                ->forceFill(['previous_post_hd_weight' => $candidate['weight']])
                ->save();
        });

        $this->info('Berhasil mengisi '.$updates->count().' sesi HD.');

        return self::SUCCESS;
    }

    private function row(DialysisSession $session, float $weight): array
    {
        return [
            $session->id,
            $session->session_date?->toDateString() ?? '-',
            $session->patient?->name ?? '-',
            number_format($weight, 2, '.', ''),
        ];
    }
}
