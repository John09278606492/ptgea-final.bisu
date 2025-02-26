<?php

namespace App\Exports;

use App\Models\Enrollment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;

class StudentpaymentExport implements WithMapping, WithHeadings, ShouldAutoSize, WithEvents, FromQuery
{
    use Exportable;

    private $rowNumber = 0;
    private $totalBalance = 0;
    private $totalPayments = 0;
    private $year;
    private $collegeId;
    private $programId;
    private $yearlevelId;
    private $status;

    public function __construct(?int $year = null, ?int $collegeId = null, ?int $programId = null, ?int $yearlevelId = null, ?string $status = null)
    {
        $this->year = $year;
        $this->collegeId = $collegeId;
        $this->programId = $programId;
        $this->yearlevelId = $yearlevelId;
        $this->status = $status;
    }

    public function query()
    {
        $query = Enrollment::query();

        if ($this->year) {
            $query->where('schoolyear_id', $this->year);
        }
        if ($this->collegeId) {
            $query->where('college_id', $this->collegeId);
        }
        if ($this->programId) {
            $query->where('program_id', $this->programId);
        }
        if ($this->yearlevelId) {
            $query->where('yearlevel_id', $this->yearlevelId);
        }
        if ($this->status === 'paid') {
            $query->where('status', 'paid');
        } elseif ($this->status === 'not_paid') {
            $query->whereNull('status');
        } elseif ($this->status === '' || $this->status === null) {
            $query->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'paid');
            });
        }

        return $query;
    }

    public function map($students): array
    {
        $balance = is_numeric($students->balance) ? (float)$students->balance : 0;
        $payments = is_numeric($students->payments) ? (float)$students->payments : 0;

        $this->totalBalance += $balance;
        $this->totalPayments += $payments;

        return [
            ++$this->rowNumber,
            $students->stud->studentidn,
            $students->stud->lastname . ', ' . $students->stud->firstname . ', ' . $students->stud->middlename,
            $students->college->college,
            $students->program->program,
            $students->yearlevel->yearlevel,
            $students->schoolyear->schoolyear,
            number_format($payments, 2),
            number_format($balance, 2)
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Student IDN',
            'Complete Name',
            'College',
            'Program',
            'Year Level',
            'School Year',
            'Total Amount Paid',
            'Total Remaining Balance',
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
                $sheet->getStyle('A1:I1')->getFont()->setBold(true);

                // Apply borders to all rows with data
                $cellRange = 'A1:I' . ($this->rowNumber + 1);
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
                $sheet->setCellValue('G' . $lastRow, 'Grand Total Amount Paid:');
                $sheet->setCellValue('H' . $lastRow, number_format($this->totalPayments, 2));
                $sheet->setCellValue('G' . $summaryRow, 'Grand Total Remaining Balance:');
                $sheet->setCellValue('I' . $summaryRow, number_format($this->totalBalance, 2));
                $sheet->setCellValue('G' . $totalRow, 'Overall Total Expected Amount:');
                $sheet->setCellValue('H' . $totalRow, number_format($this->totalPayments + $this->totalBalance, 2));

                // Make summary rows bold
                $sheet->getStyle('G' . $lastRow . ':I' . $lastRow)->getFont()->setBold(true);
                $sheet->getStyle('G' . $summaryRow . ':I' . $summaryRow)->getFont()->setBold(true);
                $sheet->getStyle('G' . $totalRow . ':I' . $totalRow)->getFont()->setBold(true);

                // Center align the summary values
                $sheet->getStyle('H' . $lastRow . ':I' . $summaryRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('H' . $totalRow . ':I' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Merge Overall Total Expected Amount row
                $sheet->mergeCells('H' . $totalRow . ':I' . $totalRow);
            },
        ];
    }
}
