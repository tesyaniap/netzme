# 📄 REPORT & PDF EXPORT - DOKUMENTASI LENGKAP

## 📊 OVERVIEW

Sistem ini menyediakan 4 jenis laporan **KHUSUS ADMIN** yang bisa dilihat di web dan di-export ke PDF:
1. **Transaction Report** - Laporan transaksi tiket semua mitra
2. **Topup Report** - Laporan deposit/topup semua mitra
3. **Fee Report** - Laporan komisi semua mitra
4. **Balance Report** - Laporan saldo semua mitra

**⚠️ PENTING:** Report hanya bisa diakses oleh **ADMIN**. Mitra tidak bisa akses endpoint ini.

---

## 🎯 ENDPOINTS REPORT

### 1. GET `/api/v1/reports/transactions`
**Akses:** Admin Only ⚠️

**Query Parameters:**
```
date_from    : 2024-01-01 (optional)
date_to      : 2024-01-31 (optional)
status       : pending|paid|issued|cancelled|failed (optional)
mitra_id     : 1 (optional, admin only)
export       : pdf|excel (optional)
```

**Response (JSON):**
```json
{
  "status": true,
  "message": "Transaction report retrieved",
  "data": {
    "summary": {
      "total_transactions": 150,
      "total_amount": 22500000,
      "by_status": {
        "pending": 10,
        "paid": 50,
        "issued": 80,
        "cancelled": 10
      }
    },
    "transactions": [
      {
        "id": 1,
        "trx_code": "TRX20240101120000",
        "mitra": {
          "id": 1,
          "name": "PT. Travel Sejahtera"
        },
        "route": "Jakarta - Bandung",
        "travel_date": "2024-01-15",
        "amount": 150000,
        "status": "issued",
        "created_at": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

**Response (PDF):**
- Download file: `transaction_report_2024-01-01_to_2024-01-31.pdf`

---

### 2. GET `/api/v1/reports/topups`
**Akses:** Admin Only ⚠️

**Query Parameters:**
```
date_from    : 2024-01-01 (optional)
date_to      : 2024-01-31 (optional)
status       : pending|success|rejected (optional)
mitra_id     : 1 (optional, admin only)
export       : pdf|excel (optional)
```

**Response (JSON):**
```json
{
  "status": true,
  "message": "Topup report retrieved",
  "data": {
    "summary": {
      "total_topups": 50,
      "total_amount": 50000000,
      "by_status": {
        "pending": 5,
        "success": 40,
        "rejected": 5
      }
    },
    "topups": [
      {
        "id": 1,
        "mitra": {
          "id": 1,
          "name": "PT. Travel Sejahtera"
        },
        "amount": 1000000,
        "payment_method": "transfer",
        "status": "success",
        "approved_by": "Admin",
        "approved_at": "2024-01-01 14:00:00",
        "created_at": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

---

### 3. GET `/api/v1/reports/fees`
**Akses:** Admin Only ⚠️

**Query Parameters:**
```
date_from    : 2024-01-01 (optional)
date_to      : 2024-01-31 (optional)
mitra_id     : 1 (optional, admin only)
export       : pdf|excel (optional)
```

**Response (JSON):**
```json
{
  "status": true,
  "message": "Fee report retrieved",
  "data": {
    "summary": {
      "total_fee": 1125000,
      "by_mitra": [
        {
          "mitra_id": 1,
          "mitra_name": "PT. Travel Sejahtera",
          "total_fee": 750000
        },
        {
          "mitra_id": 2,
          "mitra_name": "CV. Wisata Indah",
          "total_fee": 375000
        }
      ]
    },
    "fees": [
      {
        "id": 1,
        "mitra": {
          "id": 1,
          "name": "PT. Travel Sejahtera"
        },
        "transaction": {
          "trx_code": "TRX20240101120000",
          "amount": 150000
        },
        "fee_type": "percent",
        "fee_value": 5,
        "fee_amount": 7500,
        "created_at": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

---

### 4. GET `/api/v1/reports/balances`
**Akses:** Admin Only ⚠️

**Query Parameters:**
```
export : pdf|excel (optional)
```

**Response (JSON - Admin):**
```json
{
  "status": true,
  "message": "Balance report retrieved",
  "data": {
    "summary": {
      "total_balance": 100000000,
      "total_mitra": 10
    },
    "balances": [
      {
        "mitra_id": 1,
        "mitra_name": "PT. Travel Sejahtera",
        "balance": 50000000,
        "total_transactions": 150
      }
    ]
  }
}
```

---

## 🔧 IMPLEMENTASI PDF EXPORT

### Step 1: Install Package
```bash
composer require barryvdh/laravel-dompdf
```

### Step 2: Publish Config
```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### Step 3: Update ReportController

```php
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
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    use ApiResponse;

    // GET /api/v1/reports/transactions
    public function transactions(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:pending,paid,issued,cancelled,failed',
            'mitra_id' => 'nullable|exists:mitra,id',
            'export' => 'nullable|in:pdf,excel',
        ]);

        $query = Transaction::with(['mitra', 'user', 'passengers']);

        // Admin only - no role filtering needed
        // Admin can filter by mitra_id
        if ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        // Filter by date
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Summary
        $summary = [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'by_status' => $query->clone()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        $transactions = $query->latest()->get();

        // Export PDF
        if ($request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.transactions', [
                'transactions' => $transactions,
                'summary' => $summary,
                'filters' => [
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'status' => $request->status,
                ],
            ]);

            $filename = 'transaction_report_' . now()->format('Y-m-d_His') . '.pdf';
            return $pdf->download($filename);
        }

        // Return JSON
        return $this->successResponse('Transaction report retrieved', [
            'summary' => $summary,
            'transactions' => $transactions,
        ]);
    }

    // Similar implementation for topups, fees, balances...
}
```

---

## 📝 BLADE TEMPLATES

### Create: `resources/views/reports/transactions.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .info {
            margin-bottom: 20px;
        }
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TRANSACTION REPORT</h1>
        <p>Bridge Sistem Tiket Bus H2H</p>
    </div>

    <div class="info">
        <strong>Report Date:</strong> {{ now()->format('d F Y H:i') }}<br>
        @if($filters['date_from'])
            <strong>Period:</strong> {{ $filters['date_from'] }} to {{ $filters['date_to'] }}<br>
        @endif
        @if($filters['status'])
            <strong>Status:</strong> {{ strtoupper($filters['status']) }}<br>
        @endif
    </div>

    <div class="summary">
        <h3>Summary</h3>
        <p><strong>Total Transactions:</strong> {{ $summary['total_transactions'] }}</p>
        <p><strong>Total Amount:</strong> Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}</p>
        <p><strong>By Status:</strong></p>
        <ul>
            @foreach($summary['by_status'] as $status => $count)
                <li>{{ ucfirst($status) }}: {{ $count }}</li>
            @endforeach
        </ul>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Trx Code</th>
                <th>Mitra</th>
                <th>Route</th>
                <th>Travel Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $trx)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $trx->trx_code }}</td>
                <td>{{ $trx->mitra->name }}</td>
                <td>{{ $trx->route }}</td>
                <td>{{ $trx->travel_date }}</td>
                <td>Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                <td>{{ strtoupper($trx->status) }}</td>
                <td>{{ $trx->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated by Bridge Sistem Tiket Bus H2H</p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
```

---

## 🎨 TEMPLATE LAINNYA

### `resources/views/reports/topups.blade.php`
```blade
<!-- Similar structure dengan transactions.blade.php -->
<!-- Ganti field sesuai data topup -->
```

### `resources/views/reports/fees.blade.php`
```blade
<!-- Similar structure dengan transactions.blade.php -->
<!-- Ganti field sesuai data fee -->
```

### `resources/views/reports/balances.blade.php`
```blade
<!-- Similar structure dengan transactions.blade.php -->
<!-- Ganti field sesuai data balance -->
```

---

## 🚀 CARA PENGGUNAAN

### 1. View Report di Web (JSON)
```bash
GET /api/v1/reports/transactions?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer {token}
```

### 2. Export ke PDF
```bash
GET /api/v1/reports/transactions?date_from=2024-01-01&date_to=2024-01-31&export=pdf
Authorization: Bearer {token}
```

**Response:** Download file PDF

### 3. Filter by Status
```bash
GET /api/v1/reports/transactions?status=issued&export=pdf
Authorization: Bearer {token}
```

### 4. Admin Filter by Mitra
```bash
GET /api/v1/reports/transactions?mitra_id=1&export=pdf
Authorization: Bearer {token}
```

---

## 📋 ROUTES

Tambahkan di `routes/api.php`:

```php
// Admin only (reports)
Route::middleware('role.permission:admin')->prefix('reports')->group(function () {
    Route::get('/transactions', [ReportController::class, 'transactions']);
    Route::get('/topups', [ReportController::class, 'topups']);
    Route::get('/fees', [ReportController::class, 'fees']);
    Route::get('/balances', [ReportController::class, 'balances']);
});
```

---

## 🎯 FITUR TAMBAHAN (OPTIONAL)

### 1. Excel Export
```bash
composer require maatwebsite/excel
```

```php
if ($request->export === 'excel') {
    return Excel::download(new TransactionsExport($transactions), 'transactions.xlsx');
}
```

### 2. Email Report
```php
Mail::to($user->email)->send(new ReportMail($pdf));
```

### 3. Schedule Report
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        // Generate & email monthly report
    })->monthly();
}
```

### 4. Chart/Graph di PDF
```bash
composer require consoletvs/charts
```

---

## ⚡ OPTIMIZATION

### 1. Cache Report Data
```php
$data = Cache::remember('report_transactions_' . $cacheKey, 3600, function() {
    return Transaction::with(['mitra'])->get();
});
```

### 2. Queue PDF Generation
```php
dispatch(new GenerateReportPdf($filters))->onQueue('reports');
```

### 3. Limit Data
```php
// Max 1000 records per export
$transactions = $query->latest()->limit(1000)->get();
```

### 4. Compress PDF
```php
$pdf = Pdf::loadView('reports.transactions', $data)
    ->setPaper('a4', 'landscape')
    ->setOption('enable-local-file-access', true)
    ->setOption('dpi', 96); // Lower DPI = smaller file
```

---

## 🔒 SECURITY

### 1. Rate Limiting
```php
Route::middleware('throttle:10,1')->group(function() {
    // Max 10 PDF exports per minute
});
```

### 2. File Size Limit
```php
if ($query->count() > 1000) {
    return $this->errorResponse('Too many records. Please narrow your filter.', [], 400);
}
```

### 3. Sanitize Input
```php
$request->validate([
    'date_from' => 'nullable|date|before_or_equal:today',
    'date_to' => 'nullable|date|after_or_equal:date_from',
]);
```

---

## 📊 TESTING

### Test PDF Generation
```bash
php artisan tinker

>>> $pdf = Pdf::loadView('reports.transactions', ['transactions' => [], 'summary' => []]);
>>> $pdf->save(storage_path('app/test.pdf'));
```

### Test with Postman
```
GET http://127.0.0.1:8000/api/v1/reports/transactions?export=pdf
Headers:
  Authorization: Bearer {token}
```

---

## 🐛 TROUBLESHOOTING

### Error: "Class 'Pdf' not found"
```bash
composer require barryvdh/laravel-dompdf
php artisan config:clear
```

### Error: "Unable to load font"
```php
// config/dompdf.php
'font_dir' => storage_path('fonts/'),
'font_cache' => storage_path('fonts/'),
'chroot' => realpath(base_path()),
```

### PDF Blank/Empty
- Check blade syntax errors
- Check data passed to view
- Enable debug mode

### PDF Too Large
- Reduce image quality
- Limit records
- Use pagination

---

## 📁 FILE STRUCTURE

```
app/
├── Http/Controllers/Api/
│   └── ReportController.php
resources/
├── views/reports/
│   ├── transactions.blade.php
│   ├── topups.blade.php
│   ├── fees.blade.php
│   └── balances.blade.php
config/
└── dompdf.php
```

---

**Last Updated:** 2024
**Package:** barryvdh/laravel-dompdf v2.0
**Laravel Version:** 12.x
