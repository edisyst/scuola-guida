<?php

namespace App\Exports;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private AuditLogService $service;

    public function __construct(private Builder $builder)
    {
        $this->service = app(AuditLogService::class);
    }

    public function query(): Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return ['Data', 'Utente', 'Azione', 'Tipo oggetto', 'ID oggetto', 'Riepilogo modifiche'];
    }

    /**
     * @param AuditLog $log
     */
    public function map($log): array
    {
        return [
            $log->created_at?->format('d/m/Y H:i'),
            $this->service->formatUser($log),
            ucfirst($log->event),
            $this->service->typeLabel($log->model_type),
            $log->model_id,
            $this->service->diffSummary($log),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D3D3D3'],
                ],
            ],
        ];
    }
}
