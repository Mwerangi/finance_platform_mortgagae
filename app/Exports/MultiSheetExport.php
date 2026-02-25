<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiSheetExport implements WithMultipleSheets
{
    protected array $sheets;

    public function __construct(array $sheets)
    {
        $this->sheets = $sheets;
    }

    public function sheets(): array
    {
        $exportSheets = [];

        foreach ($this->sheets as $title => $data) {
            $exportSheets[] = new class($data, $title) implements 
                \Maatwebsite\Excel\Concerns\FromCollection,
                \Maatwebsite\Excel\Concerns\WithTitle,
                \Maatwebsite\Excel\Concerns\WithStyles
            {
                protected Collection $data;
                protected string $title;

                public function __construct(Collection $data, string $title)
                {
                    $this->data = $data;
                    $this->title = $title;
                }

                public function collection()
                {
                    return $this->data;
                }

                public function title(): string
                {
                    return $this->title;
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                {
                    return [
                        1 => ['font' => ['bold' => true]],
                    ];
                }
            };
        }

        return $exportSheets;
    }
}
