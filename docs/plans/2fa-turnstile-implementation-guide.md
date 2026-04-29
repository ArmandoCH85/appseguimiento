# Guía de Implementación: 2FA Nativo (Email OTP) + Cloudflare Turnstile en Laravel/Filament

Esta guía documenta la implementación del patrón **KISS (Keep It Simple, Stupid)** para autenticación de dos factores (2FA) y protección contra bots usando herramientas nativas de Laravel y Filament, sin depender de paquetes de terceros. 

Diseñada para que cualquier IA (Trae, Cursor, Copilot, Claude) pueda replicar la arquitectura en otros proyectos.

---

## 1. Base de Datos (Migraciones)

Se requiere agregar columnas para almacenar temporalmente el código de un solo uso (OTP) y su fecha de expiración.

```php
// database/migrations/xxxx_xx_xx_xxxxxx_add_otp_fields_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->string('otp_code')->nullable()->after('password');
    $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
});
```

---

## 2. Modelos (Eloquent)

**IMPORTANTE:** Las columnas deben agregarse al array `$fillable` para que el listener pueda actualizarlas, de lo contrario fallará silenciosamente.

```php
// app/Models/User.php
protected $fillable = [
    // ... otros campos
    'otp_code',
    'otp_expires_at',
];

protected function casts(): array
{
    return [
        // ... otros casts
        'otp_expires_at' => 'datetime',
    ];
}
```

---

## 3. Generación y Envío del Código (Mailable & Listener)

### 3.1 Mailable
Crear un mailable simple (`php artisan make:mail OtpMail`) que reciba el código en su constructor y lo renderice en la vista o mediante `htmlString`.

### 3.2 Listener del Evento Login
Interceptar el evento nativo `Illuminate\Auth\Events\Login`. Aquí se implementa el "Flash Session Guard" para evitar envíos duplicados causados por renderizados concurrentes de Livewire/Filament.

```php
// app/Listeners/SendOtpOnLogin.php
namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class SendOtpOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // 1. Verificar si la sesión ya fue aprobada
        if (session('2fa_passed', false)) {
            return;
        }

        // 2. Flash Session Guard (Evita ejecución duplicada en la misma petición)
        if (session()->has('otp_sent_in_this_request')) {
            return;
        }

        // 3. Reutilizar código si aún es válido (Evita spam)
        if ($user->otp_code && $user->otp_expires_at && now()->isBefore($user->otp_expires_at)) {
            return;
        }

        // 4. Generar y guardar
        $otp = (string) rand(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        session()->flash('otp_sent_in_this_request', true);
        Mail::to($user->email)->send(new OtpMail($otp));
    }
}
```

### 3.3 Registrar el Listener
```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    \Illuminate\Support\Facades\Event::listen(
        \Illuminate\Auth\Events\Login::class, 
        \App\Listeners\SendOtpOnLogin::class
    );
}
```

---

## 4. Protección de Rutas (Middleware)

Crear un middleware que verifique la sesión. Si el usuario está autenticado pero no ha pasado el 2FA, lo redirige.

```php
// app/Http/Middleware/Ensure2FA.php
public function handle(Request $request, Closure $next): Response
{
    if (!Auth::check()) return $next($request);

    if (!session('2fa_passed', false)) {
        // Evitar bucle infinito de redirecciones
        if ($request->routeIs('filament.*.pages.2fa') || $request->routeIs('filament.*.auth.logout')) {
            return $next($request);
        }
        
        $panel = filament()->getCurrentPanel()->getId();
        return redirect()->route("filament.{$panel}.pages.2fa");
    }

    return $next($request);
}
```

**Registro en PanelProvider:**
Añadir el middleware en la sección `authMiddleware` de cada panel (NO en `middleware` general).
```php
// app/Providers/Filament/AdminPanelProvider.php
->authMiddleware([
    Authenticate::class,
    \App\Http\Middleware\Ensure2FA::class,
])
```

---

## 5. Pantalla de Verificación 2FA (Filament Page)

Crear una página custom de Filament (`php artisan make:filament-page TwoFactorAuthPage`).

```php
// app/Filament/Pages/TwoFactorAuthPage.php
use Filament\Pages\Page;
use Filament\Schemas\Schema; // En v4/v5 usar Schema, no Form

class TwoFactorAuthPage extends Page
{
    protected static ?string $slug = '2fa';
    protected static bool $shouldRegisterNavigation = false;
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('otp_code')->required()->length(6)
        ])->statePath('data');
    }

    public function verify(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if ($user->otp_code !== $data['otp_code']) {
            // Notificar error
            return;
        }

        if (now()->isAfter($user->otp_expires_at)) {
            // Notificar expiración
            return;
        }

        // ÉXITO: Limpiar código (Seguridad) y aprobar sesión
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);
        session(['2fa_passed' => true]);
        $this->redirect(Dashboard::getUrl());
    }
}
```

---

## 6. Cloudflare Turnstile (Captcha Invisible) en Filament Login

Para evitar reescribir todo el formulario de Filament, sobrescribimos la clase BaseLogin e inyectamos el script en la vista.

### 6.1 Clase de Login
```php
// app/Filament/Pages/Auth/Login.php
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    public ?string $turnstileToken = null; // Variable Livewire

    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        if (! $this->turnstileToken) {
            throw ValidationException::withMessages(['email' => 'Espera al Captcha.']);
        }

        $captcha = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => env('TURNSTILE_SECRET_KEY'),
            'response' => $this->turnstileToken,
            'remoteip' => request()->ip(),
        ])->json();

        if (!($captcha['success'] ?? false)) {
            $this->dispatch('reset-captcha'); // Reiniciar widget si falla
            throw ValidationException::withMessages(['email' => 'Captcha inválido.']);
        }

        try {
            return parent::authenticate(); // Lógica nativa de Filament
        } catch (ValidationException $e) {
            $this->dispatch('reset-captcha'); // Reiniciar si la contraseña es incorrecta
            throw $e;
        }
    }
}
```

### 6.2 Vista (Blade)
```html
{{-- resources/views/filament/pages/auth/login.blade.php --}}
{{ $this->content }} {{-- Renderiza el formulario nativo --}}

{{-- Widget de Cloudflare --}}
<div wire:ignore class="mt-4 flex justify-center w-full">
    <div class="cf-turnstile" data-sitekey="{{ env('TURNSTILE_SITE_KEY') }}" data-callback="turnstileCallback"></div>
</div>

@push('scripts')
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
    function turnstileCallback(token) {
        @this.set('turnstileToken', token); // Enviar token a Livewire
    }
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('reset-captcha', () => {
            if (typeof turnstile !== 'undefined') turnstile.reset();
        });
    });
</script>
@endpush
```

### 6.3 Actualizar PanelProvider
Indicar a Filament que use nuestra clase custom de Login:
```php
->login(\App\Filament\Pages\Auth\Login::class)
```
