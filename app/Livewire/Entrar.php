<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::entrada')]
#[Title('Entrar')]
class Entrar extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $senha = '';

    public function entrar(): void
    {
        $this->validate();
        $chave = Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($chave, 5)) {
            throw ValidationException::withMessages(['email' => 'Muitas tentativas. Tente de novo em '.RateLimiter::availableIn($chave).' segundos.']);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->senha], remember: true)) {
            RateLimiter::hit($chave);
            throw ValidationException::withMessages(['email' => 'E-mail ou senha não conferem.']);
        }

        RateLimiter::clear($chave);
        session()->regenerate();
        $this->redirectRoute(Auth::user()->papel->inicio(), navigate: true);
    }

    /** Na demonstração, um toque entra como a pessoa escolhida. */
    public function entrarComo(string $email): void
    {
        abort_unless(config('talo.demonstracao'), 404);
        $pessoa = User::query()->where('email', $email)->firstOrFail();
        Auth::login($pessoa);
        session()->regenerate();
        $this->redirectRoute($pessoa->papel->inicio(), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.entrar');
    }
}
