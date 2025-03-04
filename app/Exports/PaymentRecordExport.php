<?php

namespace App\Exports;

use App\Models\Pay;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PaymentRecordExport implements
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    FromQuery, // Changed from FromCollection to FromQuery
    WithDrawings,
    WithCustomStartCell
{
    use Exportable;

    private $rowNumber = 0;
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
        $query = Pay::query()->with(['enrollment.stud', 'enrollment.college', 'enrollment.program', 'enrollment.yearlevel', 'enrollment.schoolyear']);

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
        }

        return $query->orderBy('created_at', 'asc');
    }

    public function startCell(): string
    {
        return 'A10'; // Data starts from row 10 to avoid image and header overlap
    }

    public function drawings()
    {
        $drawing1 = new Drawing();
        $drawing1->setPath(public_path('/images/bisu logo2.png'));
        $drawing1->setHeight(96);
        $drawing1->setOffsetX(80);
        $drawing1->setCoordinates('B2');

        $drawing2 = new Drawing();
        $drawing2->setPath(public_path('/images/bagong_pilipinas.png'));
        $drawing2->setHeight(100);
        $drawing2->setCoordinates('F2');

        $drawing3 = new Drawing();
        $drawing3->setPath(public_path('/images/tuv logo.png'));
        $drawing3->setHeight(96);
        $drawing3->setCoordinates('G2');

        return [$drawing1, $drawing2, $drawing3];
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

                // Adjusted text placement closer to the left logo (Bagong Pilipinas)
                $sheet->setCellValue('B2', 'Republic of the Philippines');
                $sheet->setCellValue('B3', 'BOHOL ISLAND STATE UNIVERSITY'); // Less indentation
                $sheet->setCellValue('B4', 'San Isidro, Calape, Bohol');
                $sheet->setCellValue('B5', 'Parents, Teachers, Guardians & Employees Association');
                $sheet->setCellValue('B6', 'Balance | Integrity | Stewardship | Uprightness');

                $sheet->getStyle('B2:B6')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setIndent(20);

                // Merge cells to align text properly
                $sheet->mergeCells('B2:E2');
                $sheet->mergeCells('B3:E3'); // BISU name stays aligned as one line
                $sheet->mergeCells('B4:E4');
                $sheet->mergeCells('B5:E5');
                $sheet->mergeCells('B6:E6');

                // Left-align text for proper formatting
                for ($row = 2; $row <= 6; $row++) {
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                }

                // Bold important headings
                $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(14); // University Name
                $sheet->getStyle('B6')->getFont()->setBold(true)->setSize(12); // Motto

                // Adjust row height for better spacing
                for ($row = 2; $row <= 7; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(14);
                }

                // Title for the report
                $titleRow = 8;
                $sheet->mergeCells("A{$titleRow}:H{$titleRow}");
                $sheet->setCellValue("A{$titleRow}", "Student Payment Records");
                $sheet->getRowDimension($titleRow)->setRowHeight(30);

                // Style the title
                $sheet->getStyle("A{$titleRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Date Range in B9
                $dateRange = 'Date Range: ' . ($this->dateFrom ? date('M d, Y', strtotime($this->dateFrom)) : 'N/A') .
                    ' - ' .
                    ($this->dateTo ? date('M d, Y', strtotime($this->dateTo)) : 'N/A');

                $sheet->setCellValue('B9', $dateRange);
                $sheet->getStyle('B9')->getFont()->setBold(true); // Make it bold
                $sheet->getRowDimension(9)->setRowHeight(30);
                $sheet->getStyle("B9")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Move data down to avoid overlapping
                $startingRow = 10;
                $lastRow = $startingRow + $this->rowNumber;
                $summaryRow = $lastRow + 1;

                // Make headers bold
                $sheet->getStyle("A{$startingRow}:H{$startingRow}")->getFont()->setBold(true);

                // Apply borders to all rows with data
                $cellRange = "A{$startingRow}:H{$lastRow}";
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
                $sheet->setCellValue("F{$summaryRow}", 'Total Amount Paid:');
                $sheet->setCellValue("G{$summaryRow}", number_format($this->totalPayments, 2));

                // Make summary rows bold
                $sheet->getStyle("F{$summaryRow}:G{$summaryRow}")->getFont()->setBold(true);
            },
        ];
    }
}
