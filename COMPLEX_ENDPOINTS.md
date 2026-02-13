 # 🔥 ENDPOINT KOMPLEKS - PENJELASAN LENGKAP

## 1. 🎫 TRANSACTION FLOW (Paling Kompleks)

### A. POST `/api/v1/transactions/book` 
**File:** `TransactionController.php` (line 64-108)

**Tingkat Kesulitan:** ⭐⭐⭐⭐⭐

**Fungsi:** Booking tiket bus

**Cara Kerja:**
1. Validasi input (provider_code, travel_date, seats, passengers)
2. Generate transaction code unik: `TRX20240101120000`
3. Hitung total amount: jumlah seat × harga
4. **DB Transaction BEGIN** (penting untuk data consistency)
5. Insert ke table `transactions` dengan status `pending`
6. Loop insert passengers ke table `transaction_passengers`
7. **DB Transaction COMMIT**
8. Return booking info + expired time (30 menit)

**Kenapa Sulit:**
- Pakai **DB Transaction** untuk prevent data inconsistency
- Multi-table insert (transactions + transaction_passengers)
- Generate unique transaction code
- Handle rollback jika error

**Request:**
```json
{
  "provider_code": "BUS001",
  "travel_date": "2024-01-15",
  "seats": ["A1", "A2"],
  "passengers": [
    {"name": "John", "identity_number": "123456"},
    {"name": "Jane", "identity_number": "789012"}
  ]
}
```

---

### B. POST `/api/v1/transactions/pay`
**File:** `TransactionController.php` (line 113-157)

**Tingkat Kesulitan:** ⭐⭐⭐⭐⭐

**Fungsi:** Bayar booking pakai deposit mitra

**Cara Kerja:**
1. Cari transaction by trx_code
2. Validasi status harus `pending`
3. Cek balance mitra cukup atau tidak
4. **DB Transaction BEGIN**
5. **LOCK** balance mitra (prevent race condition)
6. Kurangi balance mitra
7. Update transaction status jadi `paid`
8. **DB Transaction COMMIT**
9. Return balance before & after

**Kenapa Sulit:**
- **Race Condition Risk** - 2 request bersamaan bisa double deduct
- Balance calculation harus akurat
- Perlu DB transaction untuk atomicity
- Handle insufficient balance

**Masalah Potensial:**
```
User A balance: 100.000
Request 1: Pay 80.000 (bersamaan)
Request 2: Pay 80.000 (bersamaan)
Tanpa lock = balance bisa -60.000 ❌
```

**Solusi:** Pakai `DB::transaction()` + `lockForUpdate()`

---

### C. POST `/api/v1/transactions/{trx_code}/issue`
**File:** `TransactionController.php` (line 183-227)

**Tingkat Kesulitan:** ⭐⭐⭐⭐

**Fungsi:** Issue tiket (cetak tiket) + hitung fee mitra

**Cara Kerja:**
1. Validasi status harus `paid`
2. **DB Transaction BEGIN**
3. Update status jadi `issued`
4. Hitung fee (5% dari amount)
5. Insert ke `transaction_fees` table
6. Insert ke `partner_fee_ledgers` table (untuk laporan)
7. **DB Transaction COMMIT**

**Kenapa Sulit:**
- Multi-table insert (3 tables)
- Fee calculation logic
- Perlu atomic operation

**Business Logic:**
- Fee = 5% dari transaction amount
- Fee masuk ke ledger mitra (income)
- Status flow: pending → paid → issued

---

### D. POST `/api/v1/transactions/{trx_code}/cancel`
**File:** `TransactionController.php` (line 232-271)

**Tingkat Kesulitan:** ⭐⭐⭐⭐

**Fungsi:** Cancel booking + refund jika sudah bayar

**Cara Kerja:**
1. Validasi status (hanya pending/paid yang bisa cancel)
2. **DB Transaction BEGIN**
3. Jika status `paid` → refund balance ke mitra
4. Update status jadi `cancelled`
5. **DB Transaction COMMIT**

**Kenapa Sulit:**
- Conditional refund logic
- Balance manipulation
- Perlu handle different status

---

## 2. 💰 TOPUP FLOW

### A. POST `/api/v1/topups/{id}/approve`
**File:** `TopupController.php` (line 73-109)

**Tingkat Kesulitan:** ⭐⭐⭐⭐⭐

**Fungsi:** Admin approve topup request dari mitra

**Cara Kerja:**
1. Validasi status harus `pending`
2. **DB Transaction BEGIN**
3. Get balance mitra sekarang
4. Tambah balance mitra
5. Update topup status jadi `success`
6. Insert ke `topup_histories` (audit trail)
7. **DB Transaction COMMIT**

**Kenapa Sulit:**
- **Critical Balance Operation** - salah hitung = rugi
- Multi-table update (mitra + topup + topup_histories)
- Perlu audit trail
- Race condition risk

**Business Impact:**
- Ini endpoint yang handle UANG MASUK
- Error = mitra bisa kehilangan uang
- Perlu extra validation & logging

---

## 3. 🔐 CALLBACK ENDPOINTS (Paling Berbahaya)

### POST `/api/v1/callbacks/provider/payment`
**File:** `CallbackController.php`

**Tingkat Kesulitan:** ⭐⭐⭐⭐⭐

**Fungsi:** Terima callback dari payment provider

**Cara Kerja:**
1. **Verify Signature** (middleware `verify.signature`)
2. Validasi signature dari provider
3. Update payment status
4. Trigger notification

**Kenapa Sulit & Berbahaya:**
- **Security Critical** - tanpa signature verification = bisa di-fake
- External system dependency
- Idempotency required (callback bisa datang 2x)
- Async processing

**Signature Verification:**
```php
// app/Http/Middleware/VerifySignature.php
$signature = hash_hmac('sha256', $payload, env('CALLBACK_SECRET_KEY'));
if ($signature !== $request->header('X-Signature')) {
    abort(401);
}
```

**Kenapa Penting:**
- Tanpa ini, hacker bisa kirim fake callback
- Bisa approve payment tanpa bayar
- **CRITICAL SECURITY HOLE**

---

## 4. 📊 REPORT ENDPOINTS

### GET `/api/v1/reports/transactions`
**File:** `ReportController.php` (line 19-68)

**Tingkat Kesulitan:** ⭐⭐⭐

**Fungsi:** Generate laporan transaksi dengan filter

**Cara Kerja:**
1. Validasi filter (date, status, mitra_id)
2. Build query dengan conditional filters
3. Role-based filtering (mitra hanya lihat punya sendiri)
4. Generate summary (total, amount, by_status)
5. Pagination (50 per page)

**Kenapa Kompleks:**
- Multiple conditional queries
- Role-based access control
- Aggregation (COUNT, SUM, GROUP BY)
- Performance issue jika data banyak

**Optimization Needed:**
- Cache summary data
- Index database columns
- Limit date range

---

## 5. 🎯 DASHBOARD ENDPOINTS

### GET `/api/v1/dashboard/admin`
**File:** `DashboardController.php`

**Tingkat Kesulitan:** ⭐⭐⭐⭐

**Fungsi:** Dashboard admin dengan statistik

**Cara Kerja:**
1. Query multiple tables (mitra, transactions, topups)
2. Calculate statistics (total, pending, success)
3. Generate charts data
4. Recent activities

**Kenapa Kompleks:**
- Multiple database queries
- Heavy aggregation
- Performance bottleneck
- Need caching

**Optimization:**
```php
Cache::remember('admin_dashboard', 300, function() {
    // expensive queries here
});
```

---

## 🚨 CRITICAL ISSUES & SOLUTIONS

### Issue 1: Race Condition di Balance Update
**Problem:** 2 request bersamaan bisa corrupt balance

**Solution:**
```php
DB::transaction(function() {
    $mitra = Mitra::lockForUpdate()->find($id);
    $mitra->balance -= $amount;
    $mitra->save();
});
```

### Issue 2: Callback Idempotency
**Problem:** Callback bisa datang 2x, balance bisa double

**Solution:**
```php
// Check if already processed
if ($transaction->status === 'paid') {
    return response()->json(['message' => 'Already processed']);
}
```

### Issue 3: No Request Logging
**Problem:** Susah debug & audit

**Solution:** Buat middleware `LogApiRequest`

### Issue 4: No Rate Limiting
**Problem:** Bisa di-spam, DDoS attack

**Solution:**
```php
Route::middleware('throttle:60,1')->group(function() {
    // routes here
});
```

---

## 📝 REKOMENDASI IMPLEMENTASI

### Priority 1 (CRITICAL):
1. ✅ Add `lockForUpdate()` di balance operations
2. ✅ Implement idempotency key untuk callbacks
3. ✅ Add request logging middleware
4. ✅ Add rate limiting

### Priority 2 (HIGH):
5. ✅ Cache dashboard & reports
6. ✅ Add soft deletes
7. ✅ Implement queue untuk notifications
8. ✅ Add database indexes

### Priority 3 (MEDIUM):
9. ✅ Add API documentation
10. ✅ Implement automated tests
11. ✅ Add monitoring & alerts
12. ✅ Implement backup system

---

## 🔍 FILE LOCATIONS

```
app/Http/Controllers/Api/
├── TransactionController.php    ← Paling kompleks (book, pay, issue, cancel)
├── TopupController.php          ← Balance operations (approve, reject)
├── CallbackController.php       ← Security critical (signature verification)
├── ReportController.php         ← Heavy queries (reports, export)
├── DashboardController.php      ← Multiple aggregations
├── MitraController.php          ← Mitra management
└── AuthController.php           ← JWT authentication

app/Http/Middleware/
├── VerifySignature.php          ← Callback security
└── RolePermission.php           ← Authorization

routes/
└── api.php                      ← All route definitions
```

---

## 💡 TIPS DEVELOPMENT

1. **Selalu pakai DB Transaction** untuk multi-table operations
2. **Pakai lockForUpdate()** untuk balance operations
3. **Validate everything** - jangan percaya input user
4. **Log everything** - untuk debugging & audit
5. **Cache expensive queries** - dashboard & reports
6. **Test race conditions** - pakai concurrent requests
7. **Monitor performance** - slow query = bad UX
8. **Backup database** - sebelum deploy

---

**Last Updated:** 2024
**Maintainer:** Development Team
