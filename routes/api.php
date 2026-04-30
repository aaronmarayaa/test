<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\FacultySubjectController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\FacultyAvailabilityController;
use App\Http\Controllers\ScheduleGeneratorController;

// manage school year
Route::get('/school-years', [SchoolYearController::class, 'index']);
Route::get('/school-years/{id}', [SchoolYearController::class, 'show']);
Route::post('/school-years', [SchoolYearController::class, 'store']);
Route::put('/school-years/{id}', [SchoolYearController::class, 'update']);
Route::delete('/school-years/{id}', [SchoolYearController::class, 'destroy']);

// manage semester
Route::get('/semesters', [SemesterController::class, 'index']);
Route::get('/semesters/{id}', [SemesterController::class, 'show']);
Route::post('/semesters', [SemesterController::class, 'store']);
Route::put('/semesters/{id}', [SemesterController::class, 'update']);
Route::delete('/semesters/{id}', [SemesterController::class, 'destroy']);

// manage courses
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::post('/courses', [CourseController::class, 'store']);
Route::put('/courses/{id}', [CourseController::class, 'update']);
Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

// manage rooms
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{id}', [RoomController::class, 'show']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::put('/rooms/{id}', [RoomController::class, 'update']);
Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

// manage instructors
Route::get('/instructors', [InstructorController::class, 'index']);
Route::get('/instructors/{id}', [InstructorController::class, 'show']);
Route::post('/instructors', [InstructorController::class, 'store']);
Route::put('/instructors/{id}', [InstructorController::class, 'update']);
Route::delete('/instructors/{id}', [InstructorController::class, 'destroy']);

Route::patch('/instructors/{id}/archive', [InstructorController::class, 'archive']);
Route::patch('/instructors/{id}/unarchive', [InstructorController::class, 'unarchive']);

// manage sections
Route::get('/sections', [SectionController::class, 'index']);
Route::get('/sections/{id}', [SectionController::class, 'show']);
Route::post('/sections', [SectionController::class, 'store']);
Route::put('/sections/{id}', [SectionController::class, 'update']);
Route::delete('/sections/{id}', [SectionController::class, 'destroy']);

Route::patch('/sections/{id}/archive', [SectionController::class, 'archive']);
Route::patch('/sections/{id}/unarchive', [SectionController::class, 'unarchive']);

// manage subjects
Route::get('/subjects', [SubjectController::class, 'index']);
Route::get('/subjects/{id}', [SubjectController::class, 'show']);
Route::post('/subjects', [SubjectController::class, 'store']);
Route::put('/subjects/{id}', [SubjectController::class, 'update']);
Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

Route::patch('/subjects/{id}/archive', [SubjectController::class, 'archive']);
Route::patch('/subjects/{id}/unarchive', [SubjectController::class, 'unarchive']);

// manage faculty subject
Route::get('/faculty-subjects', [FacultySubjectController::class, 'index']);
Route::get('/faculty-subjects/{id}', [FacultySubjectController::class, 'show']);
Route::post('/faculty-subjects', [FacultySubjectController::class, 'store']);
Route::put('/faculty-subjects/{id}', [FacultySubjectController::class, 'update']);
Route::delete('/faculty-subjects/{id}', [FacultySubjectController::class, 'destroy']);

// manage curriculum
Route::get('/curricula', [CurriculumController::class, 'index']);
Route::get('/curricula/{id}', [CurriculumController::class, 'show']);
Route::post('/curricula', [CurriculumController::class, 'store']);
Route::put('/curricula/{id}', [CurriculumController::class, 'update']);
Route::delete('/curricula/{id}', [CurriculumController::class, 'destroy']);

Route::patch('/curricula/{id}/activate', [CurriculumController::class, 'activate']);
Route::patch('/curricula/{id}/deactivate', [CurriculumController::class, 'deactivate']);

// manage faculty availability
Route::get('/faculty-availabilities', [FacultyAvailabilityController::class, 'index']);
Route::get('/faculty-availabilities/{id}', [FacultyAvailabilityController::class, 'show']);
Route::post('/faculty-availabilities', [FacultyAvailabilityController::class, 'store']);
Route::put('/faculty-availabilities/{id}', [FacultyAvailabilityController::class, 'update']);
Route::delete('/faculty-availabilities/{id}', [FacultyAvailabilityController::class, 'destroy']);

// schedule
Route::post('/schedule/generate', [ScheduleGeneratorController::class, 'generateApi']);
Route::get('/schedule', [ScheduleGeneratorController::class, 'getSchedule']);
Route::delete('/schedule/clear', [ScheduleGeneratorController::class, 'clearApi']);