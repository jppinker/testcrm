<?php
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;
Route::get('/schedule', [ScheduleController::class, 'index']);
Route::get('/employees', fn () => response()->json(\Illuminate\Support\Facades\DB::table('employees')->orderBy('id')->get()));
Route::get('/templates', fn () => response()->json(\Illuminate\Support\Facades\DB::table('schedule_templates')->orderBy('id')->get()));
Route::post('/templates', [ScheduleController::class, 'createTemplate']);
Route::put('/templates/{id}', [ScheduleController::class, 'updateTemplate']);
Route::delete('/templates/{id}', [ScheduleController::class, 'deleteTemplate']);
Route::post('/shifts/apply', [ScheduleController::class, 'apply']);
Route::post('/shifts', [ScheduleController::class, 'saveShift']);
Route::put('/shifts/{id}', [ScheduleController::class, 'saveShift']);
Route::delete('/shifts/{id}', [ScheduleController::class, 'deleteShift']);
Route::post('/schedule/publish', function (\Illuminate\Http\Request $request) {
    $data = $request->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from']);
    $snapshot = \Illuminate\Support\Facades\DB::table('shifts')->whereBetween('date', [$data['from'], $data['to']])->get();
    $id = \Illuminate\Support\Facades\DB::table('schedule_publications')->insertGetId(['from_date' => $data['from'], 'to_date' => $data['to'], 'snapshot' => $snapshot->toJson(), 'published_at' => now()]);
    return response()->json(['id' => $id, 'count' => $snapshot->count()], 201);
});
