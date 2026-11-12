<?php

namespace App\Console\Commands;

use App\Models\DialysisSession;
use App\Services\InitialHdPostWeightService;
use Illuminate\Console\Command;

class BackfillInitialHdPostWeights extends Command
{
    protected $signature = 'app:backfill-initial-hd-post-weights {--apply : Simpan perubahan ke database}';

    protected $description = 'Koreksi BB Post pada sesi HD pertama berdasarkan data awal.';

    public function handle(InitialHdPostWeightService $postWeights): int
    {
        $shouldApply = (bool) $this->option('apply');

        $updates = DialysisSession::query()
            ->with('patient:id,name')
            ->orderBy('patient_id')
            ->orderBy('session_date')
            ->orderBy('id')
            ->get()
            ->groupBy('patient_id')
            ->map(fn ($sessions): ?array => $this->candidateForFirstSession($sessions->first(), $postWeights))
            ->filter()
            ->values();

        if ($updates->isEmpty()) {
            $this->info('Tidak ada BB Post sesi HD pertama yang perlu dikoreksi.');

            return self::SUCCESS;
        }

        $this->info(($shouldApply ? 'Mengoreksi' : 'Dry-run menemukan').' '.$updates->count().' BB Post sesi HD pertama.');
        $this->table(
            ['ID', 'Tanggal', 'Pasien', 'BB Post Lama', 'BB Post Benar'],
            $updates->map(fn (array $candidate): array => $this->row($candidate))->all()
        );

        if (! $shouldApply) {
            $this->comment('Jalankan lagi dengan --apply untuk menyimpan perubahan.');

            return self::SUCCESS;
        }

        $updates->each(function (array $candidate): void {
            $candidate['session']
                ->forceFill(['current_post_hd_weight' => $candidate['expected']])
                ->save();
        });

        $this->info('Berhasil mengoreksi '.$updates->count().' BB Post sesi HD pertama.');

        return self::SUCCESS;
    }

    private function candidateForFirstSession(?DialysisSession $session, InitialHdPostWeightService $postWeights): ?array
    {
        if (! $session) {
            return null;
        }

        $expected = $postWeights->firstWeightForPatient($session->patient);

        if ($expected === null) {
            return null;
        }

        $current = $session->current_post_hd_weight !== null
            ? round((float) $session->current_post_hd_weight, 2)
            : null;

        if ($current !== null && abs($current - $expected) < 0.01) {
            return null;
        }

        return [
            'session' => $session,
            'current' => $current,
            'expected' => $expected,
        ];
    }

    private function row(array $candidate): array
    {
        /** @var DialysisSession $session */
        $session = $candidate['session'];

        return [
            $session->id,
            $session->session_date?->toDateString() ?? '-',
            $session->patient?->name ?? '-',
            $candidate['current'] !== null ? number_format($candidate['current'], 2, '.', '') : '-',
            number_format((float) $candidate['expected'], 2, '.', ''),
        ];
    }
}
