// Add these routes to your existing routes/api.php file

// Ticket Reschedule Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Get reschedule-able tickets
    Route::get('/tickets/reschedule/available', [TicketRescheduleController::class, 'getRescheduleableTickets']);
    
    // Get available schedules for reschedule
    Route::get('/tickets/reschedule/schedules', [TicketRescheduleController::class, 'getAvailableSchedules']);
    
    // Get available seats for selected schedule
    Route::get('/tickets/reschedule/seats', [TicketRescheduleController::class, 'getAvailableSeats']);
    
    // Calculate reschedule fee
    Route::post('/tickets/reschedule/calculate-fee', [TicketRescheduleController::class, 'calculateRescheduleFee']);
    
    // Process reschedule
    Route::post('/tickets/reschedule', [TicketRescheduleController::class, 'rescheduleTicket']);
    
    // Get reschedule history
    Route::get('/tickets/{ticketId}/reschedule-history', [TicketRescheduleController::class, 'getRescheduleHistory']);
});

// Don't forget to add the import at the top:
// use App\Http\Controllers\Api\TicketRescheduleController;