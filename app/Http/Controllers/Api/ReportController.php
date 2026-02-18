<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use App\Models\Topup;
use App\Models\TransactionFee;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCPDF;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportController extends Controller
{
    use ApiResponse;

    public function transactions(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:pending,paid,issued,cancelled,failed,success',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = Transaction::with(['mitra', 'fee', 'user']);

        if ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->status) {
            if ($request->status === 'success') {
                $query->whereIn('status', ['paid', 'issued']);
            } else {
                $query->where('status', $request->status);
            }
        }

        $transactions = $query->latest('id')->get();

        if ($transactions->isEmpty()) {
            return $this->successResponse([], 'Transaction report retrieved');
        }

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'by_status' => $transactions->groupBy('status')->map->count(),
        ];

        // Format data untuk frontend
        $formattedData = $transactions->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'trx_code' => $transaction->trx_code,
                'tanggal' => $transaction->created_at ?? now(),
                'mitra' => $transaction->mitra->name ?? '-',
                'jenis_transaksi' => 'Pembelian Tiket',
                'jumlah' => $transaction->amount ?? 0,
                'fee' => $transaction->fee->fee_amount ?? 0,
                'status' => $transaction->status ?? '-',
            ];
        });

        return $this->successResponse($formattedData, 'Transaction report retrieved');
    }

    public function topups(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:pending,success,rejected',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = Topup::with(['mitra', 'approver']);

        if ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $topups = $query->latest()->paginate(50);

        $summary = [
            'total_topups' => $query->count(),
            'total_amount' => $query->where('status', 'success')->sum('amount'),
            'by_status' => DB::table('topups')
                ->selectRaw('status, COUNT(*) as count')
                ->when($request->mitra_id, function($q) use ($request) {
                    $q->where('mitra_id', $request->mitra_id);
                })
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        return $this->successResponse('Topup report retrieved', [
            'summary' => $summary,
            'topups' => $topups,
        ]);
    }

    public function fees(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = TransactionFee::with(['mitra', 'transaction']);

        if ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        if ($request->date_from) {
            $query->whereHas('transaction', function($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->date_from);
            });
        }
        if ($request->date_to) {
            $query->whereHas('transaction', function($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->date_to);
            });
        }

        $fees = $query->latest('id')->paginate(50);

        $summary = [
            'total_fee' => $query->sum('fee_amount'),
            'by_mitra' => TransactionFee::select('mitra_id', DB::raw('SUM(fee_amount) as total_fee'))
                ->with('mitra:id,name')
                ->when($request->mitra_id, function($q) use ($request) {
                    $q->where('mitra_id', $request->mitra_id);
                })
                ->groupBy('mitra_id')
                ->get()
                ->map(function($item) {
                    return [
                        'mitra_id' => $item->mitra_id,
                        'mitra_name' => $item->mitra->name,
                        'total_fee' => $item->total_fee,
                    ];
                }),
        ];

        return $this->successResponse('Fee report retrieved', [
            'summary' => $summary,
            'fees' => $fees,
        ]);
    }

    public function balances(Request $request)
    {
        $mitras = Mitra::select('id', 'name', 'balance')
            ->withCount('transactions')
            ->get()
            ->map(function($mitra) {
                return [
                    'mitra_id' => $mitra->id,
                    'mitra_name' => $mitra->name,
                    'balance' => $mitra->balance,
                    'total_transactions' => $mitra->transactions_count,
                ];
            });

        $summary = [
            'total_balance' => Mitra::sum('balance'),
            'total_mitra' => Mitra::count(),
        ];

        return $this->successResponse('Balance report retrieved', [
            'summary' => $summary,
            'balances' => $mitras,
        ]);
    }

    public function exportData(Request $request, $type)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'mitra_id' => 'nullable|exists:mitra,id',
            'format' => 'required|in:pdf,excel',
        ]);

        $format = $request->format ?? 'pdf';

        if ($format === 'excel') {
            return $this->exportExcel($request, $type);
        }

        return $this->exportPdf($request, $type);
    }

    private function exportExcel(Request $request, $type)
    {
        $data = $this->getReportData($request, $type);
        
        $export = new class($data, $type) implements FromCollection, WithHeadings, WithMapping {
            private $data;
            private $type;
            
            public function __construct($data, $type) {
                $this->data = $data;
                $this->type = $type;
            }
            
            public function collection() {
                return $this->data;
            }
            
            public function headings(): array {
                switch($this->type) {
                    case 'transactions':
                        return ['Tanggal', 'Kode', 'Mitra', 'Jumlah', 'Fee', 'Status'];
                    case 'topups':
                        return ['Tanggal', 'Mitra', 'Jumlah', 'Status', 'Disetujui'];
                    case 'fees':
                        return ['Mitra', 'Transaksi', 'Tipe', 'Nilai', 'Jumlah'];
                    case 'balances':
                        return ['Mitra', 'Saldo', 'Total Transaksi'];
                    default:
                        return [];
                }
            }
            
            public function map($row): array {
                switch($this->type) {
                    case 'transactions':
                        return [
                            $row->created_at->format('d/m/Y H:i'),
                            $row->trx_code,
                            $row->mitra->name ?? '-',
                            $row->amount,
                            $row->fee->fee_amount ?? 0,
                            $row->status
                        ];
                    case 'topups':
                        return [
                            $row->created_at->format('d/m/Y H:i'),
                            $row->mitra->name ?? '-',
                            $row->amount,
                            $row->status,
                            $row->approver->name ?? '-'
                        ];
                    case 'fees':
                        return [
                            $row->mitra->name ?? '-',
                            $row->transaction->trx_code ?? '-',
                            $row->fee_type,
                            $row->fee_value . '%',
                            $row->fee_amount
                        ];
                    case 'balances':
                        return [
                            $row->name,
                            $row->balance,
                            $row->transactions_count
                        ];
                    default:
                        return [];
                }
            }
        };
        
        return Excel::download($export, "laporan-{$type}-" . now()->format('YmdHis') . '.xlsx');
    }

    private function getReportData(Request $request, $type)
    {
        switch ($type) {
            case 'transactions':
                $query = Transaction::with(['mitra', 'fee']);
                if ($request->mitra_id) $query->where('mitra_id', $request->mitra_id);
                if ($request->start_date) $query->whereDate('created_at', '>=', $request->start_date);
                if ($request->end_date) $query->whereDate('created_at', '<=', $request->end_date);
                return $query->latest('id')->get();
                
            case 'topups':
                $query = Topup::with(['mitra', 'approver']);
                if ($request->mitra_id) $query->where('mitra_id', $request->mitra_id);
                if ($request->start_date) $query->whereDate('created_at', '>=', $request->start_date);
                if ($request->end_date) $query->whereDate('created_at', '<=', $request->end_date);
                return $query->latest()->get();
                
            case 'fees':
                $query = TransactionFee::with(['mitra', 'transaction']);
                if ($request->mitra_id) $query->where('mitra_id', $request->mitra_id);
                if ($request->start_date) $query->whereHas('transaction', fn($q) => $q->whereDate('created_at', '>=', $request->start_date));
                if ($request->end_date) $query->whereHas('transaction', fn($q) => $q->whereDate('created_at', '<=', $request->end_date));
                return $query->latest('id')->get();
                
            case 'balances':
                return Mitra::select('id', 'name', 'balance')->withCount('transactions')->get();
                
            default:
                return collect([]);
        }
    }

    private function exportPdf(Request $request, $type)
    {
        $data = $this->getReportData($request, $type);
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Bridge System H2H');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        switch ($type) {
            case 'transactions':
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Laporan Transaksi', 0, 1, 'C');
                $pdf->SetFont('helvetica', '', 9);
                
                $html = '<table border="1" cellpadding="4"><thead><tr><th>Tanggal</th><th>Kode</th><th>Mitra</th><th>Jumlah</th><th>Fee</th><th>Status</th></tr></thead><tbody>';
                foreach ($data as $item) {
                    $html .= '<tr><td>' . $item->created_at->format('d/m/Y H:i') . '</td>';
                    $html .= '<td>' . $item->trx_code . '</td>';
                    $html .= '<td>' . ($item->mitra->name ?? '-') . '</td>';
                    $html .= '<td>Rp ' . number_format($item->amount, 0, ',', '.') . '</td>';
                    $html .= '<td>Rp ' . number_format($item->fee->fee_amount ?? 0, 0, ',', '.') . '</td>';
                    $html .= '<td>' . $item->status . '</td></tr>';
                }
                $html .= '</tbody></table>';
                $pdf->writeHTML($html, true, false, true, false, '');
                break;

            case 'topups':
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Laporan Topup', 0, 1, 'C');
                $pdf->SetFont('helvetica', '', 9);
                
                $html = '<table border="1" cellpadding="4"><thead><tr><th>Tanggal</th><th>Mitra</th><th>Jumlah</th><th>Status</th><th>Disetujui</th></tr></thead><tbody>';
                foreach ($data as $item) {
                    $html .= '<tr><td>' . $item->created_at->format('d/m/Y H:i') . '</td>';
                    $html .= '<td>' . ($item->mitra->name ?? '-') . '</td>';
                    $html .= '<td>Rp ' . number_format($item->amount, 0, ',', '.') . '</td>';
                    $html .= '<td>' . $item->status . '</td>';
                    $html .= '<td>' . ($item->approver->name ?? '-') . '</td></tr>';
                }
                $html .= '</tbody></table>';
                $pdf->writeHTML($html, true, false, true, false, '');
                break;

            case 'fees':
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Laporan Fee', 0, 1, 'C');
                $pdf->SetFont('helvetica', '', 9);
                
                $html = '<table border="1" cellpadding="4"><thead><tr><th>Mitra</th><th>Transaksi</th><th>Tipe</th><th>Nilai</th><th>Jumlah</th></tr></thead><tbody>';
                foreach ($data as $item) {
                    $html .= '<tr><td>' . ($item->mitra->name ?? '-') . '</td>';
                    $html .= '<td>' . ($item->transaction->trx_code ?? '-') . '</td>';
                    $html .= '<td>' . $item->fee_type . '</td>';
                    $html .= '<td>' . $item->fee_value . '%</td>';
                    $html .= '<td>Rp ' . number_format($item->fee_amount, 0, ',', '.') . '</td></tr>';
                }
                $html .= '</tbody></table>';
                $pdf->writeHTML($html, true, false, true, false, '');
                break;

            case 'balances':
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Laporan Saldo', 0, 1, 'C');
                $pdf->SetFont('helvetica', '', 9);
                
                $html = '<table border="1" cellpadding="4"><thead><tr><th>Mitra</th><th>Saldo</th><th>Total Transaksi</th></tr></thead><tbody>';
                foreach ($data as $item) {
                    $html .= '<tr><td>' . $item->name . '</td>';
                    $html .= '<td>Rp ' . number_format($item->balance, 0, ',', '.') . '</td>';
                    $html .= '<td>' . $item->transactions_count . '</td></tr>';
                }
                $html .= '</tbody></table>';
                $pdf->writeHTML($html, true, false, true, false, '');
                break;
        }

        return response($pdf->Output("laporan-{$type}.pdf", 'S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-' . $type . '-' . now()->format('YmdHis') . '.pdf"');
    }