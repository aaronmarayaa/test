<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleGeneratorController;

Route::get('/schedule', [ScheduleGeneratorController::class, 'home'])
    ->name('schedules.home');

Route::post('/schedule/generate', [ScheduleGeneratorController::class, 'generate'])
    ->name('schedules.generate');

Route::post('/schedules/generate-multiple', [ScheduleGeneratorController::class, 'generateMultiple'])
->name('schedules.generateMultiple');

Route::get('/schedule/preview', [ScheduleGeneratorController::class, 'preview'])
    ->name('schedules.preview');

Route::get('/schedule/summary', [ScheduleGeneratorController::class, 'summary'])
    ->name('schedules.summary');

Route::delete('/schedule/clear', [ScheduleGeneratorController::class, 'clear'])
    ->name('schedules.clear');

Route::get('/schedule/history', [ScheduleGeneratorController::class, 'history'])
    ->name('schedules.history');

Route::get('/schedule/history/{id}/logs', [ScheduleGeneratorController::class, 'conflictLogs'])
    ->name('schedules.conflict-logs');

Route::get('/management', function () {
    return view('schedules.management');
})->name('schedules.management');

Route::get('/schedules/management/courses', function () {
    return view('schedules.management.courses');
})->name('schedules.management.courses');

Route::get('/schedules/management/curricula', function () {
    return view('schedules.management.curricula');
})->name('schedules.management.curricula');

Route::get('/schedules/management/faculty-availability', function () {
    return view('schedules.management.faculty-availability');
})->name('schedules.management.faculty-availability');

Route::get('/schedules/management/faculty-subjects', function () {
    return view('schedules.management.faculty-subjects');
})->name('schedules.management.faculty-subjects');

Route::get('/schedules/management/instructors', function () {
    return view('schedules.management.instructors');
})->name('schedules.management.instructors');

Route::get('/schedules/management/rooms', function () {
    return view('schedules.management.rooms');
})->name('schedules.management.rooms');

Route::get('/schedules/management/school-years', function () {
    return view('schedules.management.school-years');
})->name('schedules.management.school-years');

Route::get('/schedules/management/sections', function () {
    return view('schedules.management.sections');
})->name('schedules.management.sections');

Route::get('/schedules/management/subjects', function () {
    return view('schedules.management.subjects');
})->name('schedules.management.subjects');
