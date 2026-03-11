<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MitraController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TopupController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\FeeLedgerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CallbackController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SeatController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\TerminalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // auth
    Route::prefix('auth')->group(function () {
        // Public
        Route::post('/login', [AuthController::class, 'login']);
        
        // Protected
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::get('/permissions', [AuthController::class, 'permissions']);
        });
    });

    // protected
    Route::middleware('auth:api')->group(function () {

        // admin
        Route::middleware('role.permission:admin')->group(function () {
            
            // Dashboard Admin
            Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->middleware('permission:dashboard.admin');

            // User Management
            Route::prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view');
                Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create');
                Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:users.view');
                Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:users.update');
                Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
            });

            // Role Permission Management
            Route::prefix('roles')->group(function () {
                Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.view');
                Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.create');
                Route::get('/{id}', [RoleController::class, 'show'])->middleware('permission:roles.view');
                Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:roles.update');
                Route::post('/{id}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:permissions.assign');
            });
            Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:permissions.view');
            
            // Mitra Management
            Route::prefix('mitra')->group(function () {
                Route::post('/register', [MitraController::class, 'register'])->middleware('permission:mitra.create');
                Route::get('/', [MitraController::class, 'index'])->middleware('permission:mitra.view');
                Route::get('/{id}', [MitraController::class, 'show'])->middleware('permission:mitra.view');
                Route::post('/{id}/approve', [MitraController::class, 'approve'])->middleware('permission:mitra.approve');
                Route::post('/{id}/reject', [MitraController::class, 'reject'])->middleware('permission:mitra.reject');
                Route::post('/{id}/deactivate', [MitraController::class, 'deactivate'])->middleware('permission:mitra.deactivate');
                Route::post('/{id}/reactivate', [MitraController::class, 'reactivate'])->middleware('permission:mitra.reactivate');
                Route::put('/{id}/fee', [MitraController::class, 'updateFee'])->middleware('permission:mitra.fee');
            });

            // Vehicle Management (Admin only - no specific permissions yet)
            Route::prefix('vehicles')->group(function () {
                Route::post('/', [VehicleController::class, 'store']);
                Route::post('/bulk', [VehicleController::class, 'bulkStore']);
                Route::put('/{id}', [VehicleController::class, 'update']);
                Route::delete('/{id}', [VehicleController::class, 'destroy']);
            });

            // Route Management (Admin only)
            Route::prefix('routes')->group(function () {
                Route::get('/', [RouteController::class, 'index']);
                Route::post('/', [RouteController::class, 'store']);
                Route::get('/{id}', [RouteController::class, 'show']);
                Route::put('/{id}', [RouteController::class, 'update']);
                Route::delete('/{id}', [RouteController::class, 'destroy']);
            });

            // City Management (Admin only)
            Route::prefix('cities')->group(function () {
                Route::get('/', [CityController::class, 'index']);
                Route::post('/', [CityController::class, 'store']);
                Route::get('/{id}', [CityController::class, 'show']);
                Route::put('/{id}', [CityController::class, 'update']);
                Route::delete('/{id}', [CityController::class, 'destroy']);
            });

            // Terminal Management (Admin only)
            Route::prefix('terminals')->group(function () {
                Route::get('/', [TerminalController::class, 'index']);
                Route::post('/', [TerminalController::class, 'store']);
                Route::get('/{id}', [TerminalController::class, 'show']);
                Route::put('/{id}', [TerminalController::class, 'update']);
                Route::delete('/{id}', [TerminalController::class, 'destroy']);
            });

            // Schedule Management (Admin only)
            Route::prefix('schedules')->group(function () {
                Route::get('/', [ScheduleController::class, 'index']);
                Route::post('/', [ScheduleController::class, 'store']);
                Route::get('/{id}', [ScheduleController::class, 'show']);
                Route::put('/{id}', [ScheduleController::class, 'update']);
                Route::delete('/{id}', [ScheduleController::class, 'destroy']);
            });

            // Seat Management (Admin only)
            Route::prefix('seats')->group(function () {
                Route::get('/', [SeatController::class, 'index']);
                Route::post('/', [SeatController::class, 'store']);
                Route::post('/generate', [SeatController::class, 'generateSeats']);
                Route::get('/{id}', [SeatController::class, 'show']);
                Route::put('/{id}', [SeatController::class, 'update']);
                Route::delete('/{id}', [SeatController::class, 'destroy']);
            });

            // Topup Management (Admin)
            Route::prefix('topups')->group(function () {
                Route::post('/{id}/approve', [TopupController::class, 'approve'])->middleware('permission:topups.approve');
                Route::post('/{id}/reject', [TopupController::class, 'reject'])->middleware('permission:topups.reject');
            });

            Route::prefix('reports')->group(function () {
                Route::get('/transactions', [ReportController::class, 'transactions'])->middleware('permission:reports.transactions');
                Route::get('/topups', [ReportController::class, 'topups'])->middleware('permission:reports.topups');
                Route::get('/fees', [ReportController::class, 'fees'])->middleware('permission:reports.fees');
                Route::get('/balances', [ReportController::class, 'balances'])->middleware('permission:reports.balances');
                
                // Export pdf
                Route::get('/export/{type}', [ReportController::class, 'exportData'])
                    ->where('type', 'transactions|topups|fees|balances');
                Route::post('/export/combined', [ReportController::class, 'exportCombinedData']);
            });
        });

        // mitra
        Route::middleware('role.permission:mitra')->group(function () {
            
            // Dashboard Mitra
            Route::get('/dashboard/mitra', [DashboardController::class, 'mitra'])->middleware('permission:dashboard.partner');

            // Topup Management (Mitra)
            Route::prefix('topups')->group(function () {
                Route::post('/', [TopupController::class, 'store'])->middleware('permission:topups.create');
            });
            
            // Transaction Management (Mitra)
            Route::prefix('transactions')->group(function () {
                Route::post('/search', [TransactionController::class, 'search'])->middleware('permission:transactions.view');
                Route::post('/seat-map', [TransactionController::class, 'seatMap'])->middleware('permission:transactions.view');
                Route::post('/book', [TransactionController::class, 'book'])->middleware('permission:transactions.create');
                Route::post('/pay', [TransactionController::class, 'pay'])->middleware('permission:transactions.pay');
                Route::post('/{trx_code}/issue', [TransactionController::class, 'issue'])->middleware('permission:transactions.issue');
            });
        });

        // Topup Management (admin & mitra)
        Route::middleware('role.permission:admin,mitra')->prefix('topups')->group(function () {
            Route::get('/', [TopupController::class, 'index'])->middleware('permission:topups.view');
            Route::get('/{id}', [TopupController::class, 'show'])->middleware('permission:topups.view');
        });

        // Balance & Ledger (admin & mitra)
        Route::middleware('role.permission:admin,mitra')->group(function () {
            Route::get('/balance', [BalanceController::class, 'index'])->middleware('permission:balance.view');
            Route::get('/balance/histories', [BalanceController::class, 'histories'])->middleware('permission:balance.histories');
            Route::get('/fee/ledgers', [FeeLedgerController::class, 'index'])->middleware('permission:fee-ledgers.view');
            Route::get('/fee/config', [FeeLedgerController::class, 'feeConfig']);
            
            // Vehicle Management (admin & mitra)
            Route::prefix('vehicles')->group(function () {
                Route::get('/', [VehicleController::class, 'index']);
                Route::get('/{id}', [VehicleController::class, 'show']);
            });
        });

        // Transactions (admin & mitra)
        Route::middleware('role.permission:admin,mitra')->prefix('transactions')->group(function () {
            Route::get('/{trx_code}', [TransactionController::class, 'show'])->middleware('permission:transactions.view');
            Route::post('/{trx_code}/cancel', [TransactionController::class, 'cancel'])->middleware('permission:transactions.cancel');
        });

        // Tickets (admin & mitra)
        Route::middleware('role.permission:admin,mitra')->prefix('tickets')->group(function () {
            Route::get('/{id}', [TicketController::class, 'show']);
            Route::post('/{id}/reschedule', [TicketController::class, 'reschedule']);
        });

        // Reports (admin & mitra)
        Route::middleware('role.permission:admin,mitra')->prefix('reports')->group(function () {
            Route::get('/transactions', [ReportController::class, 'transactions'])->middleware('permission:reports.transactions');
            Route::get('/topups', [ReportController::class, 'topups'])->middleware('permission:reports.topups');
            Route::get('/fees', [ReportController::class, 'fees'])->middleware('permission:reports.fees');
            Route::get('/balances', [ReportController::class, 'balances'])->middleware('permission:reports.balances');
        });
    });

    // Callback (No Auth)
    Route::prefix('callbacks')->group(function () {
        Route::post('/provider/payment', [CallbackController::class, 'payment']);
        Route::post('/provider/ticket', [CallbackController::class, 'ticket']);
    });
});
