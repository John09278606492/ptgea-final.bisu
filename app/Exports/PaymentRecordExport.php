<?php

namespace App\Exports;

use App\Models\Enrollment;
use App\Models\Pay;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;

class PaymentRecordExport implements WithMapping, WithHeadings, ShouldAutoSize, WithEvents, FromQuery
{
    use Exportable;

    private $rowNumber = 0;
    private $totalBalance = 0;
    private $totalPayments = 0;
    private $dateFrom;
    private $dateTo;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function query()
    {
        $query = Pay::query();

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
        }

        // Order by created_at in ascending order
        $query->orderBy('created_at', 'asc');

        return $query;
    }


    public function map($students): array
    {
        $payments = is_numeric($students->amount) ? (float)$students->amount : 0;

        $this->totalPayments += $payments;

        return [
            ++$this->rowNumber,
            $students->enrollment->stud->lastname . ', ' . $students->enrollment->stud->firstname . ', ' . $students->enrollment->stud->middlename,
            $students->enrollment->college->college,
            $students->enrollment->program->program,
            $students->enrollment->yearlevel->yearlevel,
            $students->enrollment->schoolyear->schoolyear,
            number_format($payments, 2),
            $students->enrollment->created_at->format('M d, Y - h:i a'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Complete Name',
            'College',
            'Program',
            'Year Level',
            'School Year',
            'Amount Paid',
            'Date Paid',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $this->rowNumber + 2;
                $summaryRow = $lastRow + 1;
                $totalRow = $summaryRow + 1;

                // Make headers bold
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);

                // Apply borders to all rows with data
                $cellRange = 'A1:H' . ($this->rowNumber + 1);
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'shrinkToFit' => true,
                        'wrapText' => true,
                    ],
                ]);

                // Add summary values
                $sheet->setCellValue('F' . $lastRow, 'Total Amount Paid:');
                $sheet->setCellValue('G' . $lastRow, number_format($this->totalPayments, 2));

                // Make summary rows bold
                $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F' . $lastRow . ':G' . $lastRow)->getFont()->setBold(true);
            },
        ];
    }

}
