<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Use App\Http\Controllers\AuthController;
Use App\Http\Controllers\BilletController;
Use App\Http\Controllers\DocController;
Use App\Http\Controllers\FoundAndLostController;
Use App\Http\Controllers\ReservationController;
Use App\Http\Controllers\UnitController;
Use App\Http\Controllers\UserController;
Use App\Http\Controllers\WallController;
Use App\Http\Controllers\WarningController;

Route::get('/ping', function(){
    return ['pong'=>true];
});

Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function(){
    Route::post('/auth/validate', [AuthController::class, 'validateToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Mural de avisos
    Route::get('/walls', [WallController::class, 'getAll']);
    Route::post('/wall/{id}/like', [WallController::class, 'like']);

    // Documentos
    Route::get('/docs', [DocController::class, 'getAll']);

    // Livro de ocorrências
    Route::get('/warnings', [WarningController::class, 'getMyWarnings']);
    Route::post('/warning', [WarningController::class, 'setWarnings']);
    Route::post('/warning/file', [WarningController::class, 'addWarningFile']);

    // Boletos
    Route::get('/billets', [BilletController::class, 'getAll']);

    // Achados e perdidos
    Route::get('/foundandlost', [FoundAndLostController::class, 'getAll']);
    Route::post('/foundandlost', [FoundAndLostController::class, 'insert']);
    Route::put('/foundandlost/{id}', [FoundAndLostController::class, 'update']);

    // Unidade
    Route::get('/unit/{id}', [UnitController::class, 'getInfo']);
    Route::post('/unit/{id}/addperson', [UnitController::class, 'addPerson']);
    Route::post('/unit/{id}/addvehicle', [UnitController::class, 'addVehicle']);
    Route::post('/unit/{id}/addpet', [UnitController::class, 'addPet']);
    Route::post('/unit/{id}/removeperson', [UnitController::class, 'removePerson']);
    Route::post('/unit/{id}/removeveihicle', [UnitController::class, 'removeVeihicle']);
    Route::post('/unit/{id}/removepet', [UnitController::class, 'removePet']);

    // Reservas
    Route::get('/reservations', [ReservationController::class, 'getReservation']);
    Route::get('/myreservations', [ReservationController::class, 'getMyReservation']);

    Route::delete('/reservation/{id}', [ReservationController::class, 'delMyReservation']);
    Route::post('/reservation/{id}', [ReservationController::class, 'setReservation']);

    Route::get('/reservation/{id}/disableddates', [ReservationController::class, 'getDisabledDates']);
    Route::get('/reservation/{id}/times', [ReservationController::class, 'getTimes']);
});
