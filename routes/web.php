<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Evento;

/*
|--------------------------------------------------------------------------
| FUNCIÓN IR
|--------------------------------------------------------------------------
*/

function calcularIR($tipo)
{
    return match($tipo){
        'inundacion' => 8,
        'vendaval' => 6,
        'deslizamiento' => 9,
        'erosion_costera' => 7,
        'sequia' => 5,
        'marejada' => 8,
        'incendio_forestal' => 9,
        'sismo' => 10,
        default => 1
    };
}

/*
|--------------------------------------------------------------------------
| RUTAS
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PERFIL
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | MAPA
    |--------------------------------------------------------------------------
    */

    Route::get('/mapa', function () {
        return view('mapa');
    })->name('mapa');

    /*
    |--------------------------------------------------------------------------
    | API EVENTOS
    |--------------------------------------------------------------------------
    */

    Route::get('/apieventos', function () {

        return Evento::with('usuario')->get();

    });

    /*
    |--------------------------------------------------------------------------
    | CREAR EVENTO
    |--------------------------------------------------------------------------
    */

    Route::post('/crearevento', function (Illuminate\Http\Request $request) {

        $validated = $request->validate([
            'tipo'        => 'required|string|max:100',
            'descripcion' => 'required|string|max:500',
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'fecha'       => 'required|date|before_or_equal:today',
        ]);

        $evento = Evento::create([
            'tipo'        => $validated['tipo'],
            'descripcion' => $validated['descripcion'],
            'lat'         => $validated['lat'],
            'lng'         => $validated['lng'],
            'fecha'       => $validated['fecha'],
            'user_id'     => auth()->id(),
            'ir'          => calcularIR($validated['tipo']),
            'estado'      => 'activo'
        ]);

        return response()->json([
            'success' => true,
            'evento'  => $evento->load('usuario'),
        ], 201);

    });

    /*
    |--------------------------------------------------------------------------
    | LISTA INCIDENTES
    |--------------------------------------------------------------------------
    */

    Route::get('/incidentes', function () {

        $eventos = Evento::latest()->get();

        return view('incidentes', compact('eventos'));

    });

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR INCIDENTE
    |--------------------------------------------------------------------------
    */

    Route::put('/incidente/{id}', function (
        Illuminate\Http\Request $request,
        $id
    ) {

        $evento = Evento::findOrFail($id);

        $evento->update([
            'tipo' => $request->tipo,
            'descripcion' => $request->descripcion,
            'ir' => calcularIR($request->tipo)
        ]);

        return back();

    });

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR INCIDENTE
    |--------------------------------------------------------------------------
    */

    Route::delete('/incidente/{id}', function ($id) {

        Evento::findOrFail($id)->delete();

        return back();

    });

});

require __DIR__.'/auth.php';