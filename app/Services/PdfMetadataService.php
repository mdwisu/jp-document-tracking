<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfMetadataService
{
    public function extract(string $filePath): array
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $details = $pdf->getDetails();

            return [
                'title'     => $this->clean($details['Title'] ?? null),
                'author'    => $this->clean($details['Author'] ?? null),
                'creator'   => $this->clean($details['Creator'] ?? null),
                'producer'  => $this->clean($details['Producer'] ?? null),
                'created_at'  => $this->parseDate($details['CreationDate'] ?? null),
                'modified_at' => $this->parseDate($details['ModDate'] ?? null),
            ];
        } catch (\Exception $e) {
            return [
                'title'      => null,
                'author'     => null,
                'creator'    => null,
                'producer'   => null,
                'created_at'  => null,
                'modified_at' => null,
            ];
        }
    }

    private function clean(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        return trim($value);
    }

    private function parseDate(?string $value): ?\DateTime
    {
        if (empty($value)) {
            return null;
        }

        // PDF date format: D:YYYYMMDDHHmmSSOHH'mm'
        if (preg_match("/D:(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $value, $m)) {
            try {
                return new \DateTime("{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}");
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try parsing as generic date string
        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
