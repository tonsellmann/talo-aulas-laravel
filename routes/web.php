<?php

use App\Http\Controllers\SairController;
use App\Http\Controllers\WebhookPixController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $usuario = auth()->user();

    return $usuario ? redirect()->route($usuario->papel->inicio()) : redirect()->route('entrar');
})->name('inicio');

Route::get('/entrar', Livewire\Entrar::class)->middleware('guest')->name('entrar');
Route::post('/sair', SairController::class)->middleware('auth')->name('sair');

Route::post('/webhooks/pix', WebhookPixController::class)->name('webhooks.pix');

Route::middleware(['auth', 'papel:aluna'])->group(function () {
    Route::get('/minhas-aulas', Livewire\Aluna\Inicio::class)->name('aluna.inicio');
    Route::get('/agenda', Livewire\Aluna\Agenda::class)->name('aluna.agenda');
    Route::get('/extrato', Livewire\Aluna\Extrato::class)->name('aluna.extrato');
    Route::get('/plano', Livewire\Aluna\Plano::class)->name('aluna.plano');
    Route::get('/oferta/{entrada}', Livewire\Aluna\Oferta::class)->name('aluna.oferta');
});

Route::middleware(['auth', 'papel:recepcao'])->prefix('recepcao')->name('recepcao.')->group(function () {
    Route::get('/', Livewire\Recepcao\Hoje::class)->name('hoje');
    Route::get('/semana', Livewire\Recepcao\Semana::class)->name('semana');
    Route::get('/alunas', Livewire\Recepcao\Alunas::class)->name('alunas');
    Route::get('/alunas/{aluna}', Livewire\Recepcao\FichaDaAluna::class)->name('aluna');
    Route::get('/cobrancas', Livewire\Recepcao\Cobrancas::class)->name('cobrancas');
    Route::get('/mensagens', Livewire\Recepcao\Mensagens::class)->name('mensagens');
    Route::get('/relatorios', Livewire\Recepcao\Relatorios::class)->name('relatorios');
});

Route::middleware(['auth', 'papel:instrutora'])->prefix('instrutora')->name('instrutora.')->group(function () {
    Route::get('/', Livewire\Instrutora\Aulas::class)->name('aulas');
});
