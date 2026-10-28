@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $hora12 = fn($h) => \Carbon\Carbon::createFromFormat('H:i', $h)->format('g:i a');
@endphp
<div class="flex flex-col gap-4">

    <h1 class="text-xl font-semibold text-ink">Reserva tu cita</h1>

    @if($errors->any())
        <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif

    {{-- 1. Servicios (carrito) --}}
    <section class="card card-pad">
        <h2 class="section-title mb-3"><span class="text-muted">1.</span> ¿Qué te vas a hacer?</h2>
        <div class="grid gap-2 sm:grid-cols-2">
            @forelse($catalogo as $s)
                @php $marcado = in_array($s->id, $servicios, true); @endphp
                <button type="button" wire:key="srv-{{ $s->id }}" wire:click="alternarServicio({{ $s->id }})"
                        class="flex items-center justify-between gap-3 rounded-xl border p-3 text-left transition {{ $marcado ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' : 'border-line bg-white hover:border-slate-300' }}">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-ink">{{ $s->nombre }}</span>
                        <span class="block text-xs text-muted">{{ $s->duracion_minutos }} min</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-sm font-semibold">{{ $dinero($s->precio) }}</span>
                        <span class="block text-xs {{ $marcado ? 'font-medium text-brand-700' : 'text-muted' }}">{{ $marcado ? '✓ Agregado' : '+ Agregar' }}</span>
                    </span>
                </button>
            @empty
                <p class="text-sm text-muted">Esta barbería aún no tiene servicios para reservar.</p>
            @endforelse
        </div>
    </section>

    @if($elegidos->isNotEmpty())
        {{-- 2. Barbero, día y hora --}}
        <section class="card card-pad flex flex-col gap-4">
            <h2 class="section-title"><span class="text-muted">2.</span> ¿Con quién y cuándo?</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="label" for="barbero">Barbero</label>
                    <select id="barbero" wire:model.live="barberoId" class="input">
                        <option value="0">El que esté libre</option>
                        @foreach($barberos as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="fecha">Día</label>
                    <input id="fecha" wire:model.live="fecha" type="date" min="{{ now()->toDateString() }}" max="{{ now()->addDays((int) config('agenda.dias_reserva', 30))->toDateString() }}" class="input">
                </div>
            </div>
            <div>
                <p class="label">Hora</p>
                @if(empty($horas))
                    <p class="rounded-lg bg-slate-50 p-3 text-sm text-muted">No hay horas libres ese día. Prueba con otro día u otro barbero.</p>
                @else
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        @foreach($horas as $h)
                            <button type="button" wire:key="hora-{{ $h }}" wire:click="$set('hora', '{{ $h }}')"
                                    class="rounded-lg px-2 py-2 text-sm font-medium ring-1 ring-inset {{ $hora === $h ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white text-ink ring-slate-200 hover:bg-slate-50' }}">
                                {{ $hora12($h) }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if($elegidos->isNotEmpty() && $hora)
        {{-- 3. Datos y pago --}}
        <form wire:submit="confirmar" class="card card-pad flex flex-col gap-4">
            <h2 class="section-title"><span class="text-muted">3.</span> Tus datos</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="nombre">Nombre</label>
                    <input id="nombre" wire:model="nombre" type="text" autocomplete="name" class="input">
                </div>
                <div>
                    <label class="label" for="telefono">Celular (WhatsApp)</label>
                    <input id="telefono" wire:model="telefono" type="tel" inputmode="tel" autocomplete="tel" class="input">
                </div>
                <div>
                    <label class="label" for="email">Correo <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="email" wire:model="email" type="email" inputmode="email" autocomplete="email" class="input">
                </div>
            </div>

            {{-- Resumen --}}
            <div class="rounded-xl bg-slate-50 p-4 text-sm">
                @foreach($elegidos as $s)
                    <div class="flex justify-between gap-2 py-0.5"><span>{{ $s->nombre }}</span><span>{{ $dinero($s->precio) }}</span></div>
                @endforeach
                <div class="mt-2 flex justify-between gap-2 border-t border-line pt-2 font-semibold"><span>Total</span><span>{{ $dinero($total) }}</span></div>
                <p class="mt-1 text-xs text-muted">
                    {{ ucfirst(\Carbon\Carbon::parse($fecha)->translatedFormat('l j \d\e F')) }} a las {{ $hora12($hora) }} · {{ $duracion }} min
                </p>
                @if($anticipo > 0)
                    <div class="mt-3 flex justify-between gap-2 rounded-lg bg-white p-3 ring-1 ring-inset ring-brand-600/20">
                        <span><span class="font-semibold text-ink">Pagas ahora</span><span class="block text-xs text-muted">Anticipo del {{ $barberia->anticipo_porcentaje }}%. El resto lo pagas en la barbería.</span></span>
                        <span class="text-lg font-semibold text-brand-700">{{ $dinero($anticipo) }}</span>
                    </div>
                @endif
            </div>

            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="acepta" class="mt-0.5 h-5 w-5 rounded border-line text-brand-700">
                <span>
                    Acepto la política de reservas y autorizo a {{ $barberia->nombre }} a usar mis datos para gestionar mi cita y enviarme mensajes.
                    @if($anticipo > 0)
                        <span class="mt-1 block text-xs text-muted">
                            Si cancelas con al menos {{ $barberia->horas_cancelacion }} horas de anticipación, el anticipo te queda como saldo a favor para tu próxima cita.
                            Si cancelas después o no llegas, la barbería se queda con el anticipo. Si la barbería cancela, conservas el anticipo completo.
                        </span>
                    @endif
                </span>
            </label>

            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full !min-h-[48px] text-base">
                <span wire:loading.remove wire:target="confirmar">{{ $anticipo > 0 ? 'Reservar y pagar ' . $dinero($anticipo) : 'Reservar' }}</span>
                <span wire:loading wire:target="confirmar">Un momento…</span>
            </button>
            @if($anticipo > 0)
                <p class="text-center text-xs text-muted">El pago se hace en Wompi (Bancolombia) con Nequi, PSE o tarjeta. Tienes {{ config('agenda.minutos_para_pagar') }} minutos para pagar; después la hora se libera.</p>
            @endif
        </form>
    @endif
</div>
