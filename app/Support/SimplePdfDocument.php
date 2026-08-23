<?php

namespace App\Support;

class SimplePdfDocument
{
    private float $width;

    private float $height;

    /** @var array<int, string> */
    private array $pages = [];

    private int $currentPage = -1;

    public function __construct()
    {
        $this->width = 841.89;
        $this->height = 595.28;

        $this->addPage();
    }

    public function width(): float
    {
        return $this->width;
    }

    public function height(): float
    {
        return $this->height;
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->currentPage = array_key_last($this->pages);
    }

    public function text(
        float $x,
        float $y,
        string $text,
        float $size = 10,
        bool $bold = false,
        array $color = [0.08, 0.1, 0.14],
    ): void {
        $font = $bold ? 'F2' : 'F1';

        $this->write(sprintf(
            'q %.3F %.3F %.3F rg BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
            $color[0],
            $color[1],
            $color[2],
            $font,
            $size,
            $x,
            $y,
            $this->escapeText($text),
        ));
    }

    public function line(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        array $color = [0.78, 0.82, 0.86],
        float $width = 0.5,
    ): void {
        $this->write(sprintf(
            'q %.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S Q',
            $color[0],
            $color[1],
            $color[2],
            $width,
            $x1,
            $y1,
            $x2,
            $y2,
        ));
    }

    public function fillRect(float $x, float $y, float $width, float $height, array $color): void
    {
        $this->write(sprintf(
            'q %.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f Q',
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $width,
            $height,
        ));
    }

    public function strokeRect(
        float $x,
        float $y,
        float $width,
        float $height,
        array $color = [0.78, 0.82, 0.86],
        float $lineWidth = 0.5,
    ): void {
        $this->write(sprintf(
            'q %.3F %.3F %.3F RG %.2F w %.2F %.2F %.2F %.2F re S Q',
            $color[0],
            $color[1],
            $color[2],
            $lineWidth,
            $x,
            $y,
            $width,
            $height,
        ));
    }

    /**
     * @return array<int, string>
     */
    public function wrapText(string $text, float $width, float $fontSize): array
    {
        $text = $this->normalizeText($text);

        if ($text === '') {
            return ['-'];
        }

        $maxChars = max(4, (int) floor($width / ($fontSize * 0.52)));
        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            foreach ($this->splitLongWord($word, $maxChars) as $part) {
                $candidate = $line === '' ? $part : $line.' '.$part;

                if (strlen($candidate) <= $maxChars) {
                    $line = $candidate;

                    continue;
                }

                if ($line !== '') {
                    $lines[] = $line;
                }

                $line = $part;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: ['-'];
    }

    public function render(): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];

        $kids = [];

        foreach ($this->pages as $index => $stream) {
            $pageObject = 5 + ($index * 2);
            $contentObject = $pageObject + 1;
            $kids[] = "{$pageObject} 0 R";

            $objects[$pageObject] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                $this->width,
                $this->height,
                $contentObject,
            );

            $content = $stream."\n";
            $objects[$contentObject] = '<< /Length '.strlen($content)." >>\nstream\n{$content}endstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';

        ksort($objects);

        $output = "%PDF-1.4\n%".chr(226).chr(227).chr(207).chr(211)."\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($output);
            $output .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($output);
        $size = max(array_keys($objects)) + 1;

        $output .= "xref\n0 {$size}\n";
        $output .= "0000000000 65535 f \n";

        for ($number = 1; $number < $size; $number++) {
            $output .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        $output .= "trailer\n<< /Size {$size} /Root 1 0 R >>\n";
        $output .= "startxref\n{$xrefOffset}\n%%EOF";

        return $output;
    }

    private function write(string $operation): void
    {
        if ($this->currentPage < 0) {
            $this->addPage();
        }

        $this->pages[$this->currentPage] .= $operation."\n";
    }

    private function escapeText(string $text): string
    {
        $text = $this->normalizeText($text);
        $encoded = function_exists('iconv') ? @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) : false;

        if ($encoded === false) {
            $encoded = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
        }

        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', ' ', ' '],
            $encoded,
        );
    }

    private function normalizeText(string $text): string
    {
        $text = trim($text);
        $normalized = @preg_replace('/\s+/u', ' ', $text);

        return $normalized ?? (preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * @return array<int, string>
     */
    private function splitLongWord(string $word, int $maxChars): array
    {
        if (strlen($word) <= $maxChars) {
            return [$word];
        }

        return str_split($word, $maxChars);
    }
}
