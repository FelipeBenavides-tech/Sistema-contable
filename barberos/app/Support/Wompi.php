<?php

namespace App\Support;

use App\Models\Barberia;
use Illuminate\Support\Facades\Http;

/**
 * Integración con Wompi (pasarela de pagos de Bancolombia) usando su
 * Web Checkout: el cliente paga en la página de Wompi y vuelve a Kaixa.
 * Cada barbería usa su propia cuenta, así el dinero le llega directo.
 */
class Wompi
{
    public const CHECKOUT = 'https://checkout.wompi.co/p/';

    public function __construct(private Barberia $barberia)
    {
    }

    public function configurado(): bool
    {
        return $this->barberia->wompi_configurado;
    }

    // Las llaves de pruebas empiezan por pub_test_ / prv_test_
    public function esPrueba(): bool
    {
        return str_starts_with((string) $this->barberia->wompi_llave_publica, 'pub_test_');
    }

    public function apiBase(): string
    {
        return $this->esPrueba() ? 'https://sandbox.wompi.co/v1' : 'https://production.wompi.co/v1';
    }

    /**
     * Firma de integridad: SHA256(referencia + monto en centavos + moneda [+ fecha de expiración] + secreto).
     */
    public function firmaIntegridad(string $referencia, int $centavos, string $moneda = 'COP', ?string $expiracion = null): string
    {
        return hash('sha256', $referencia . $centavos . $moneda . ($expiracion ?? '') . $this->barberia->wompi_secreto_integridad);
    }

    /**
     * Dirección del Web Checkout de Wompi para pagar un anticipo.
     */
    public function urlCheckout(string $referencia, int $centavos, string $redirect, ?\DateTimeInterface $expira = null, array $cliente = []): string
    {
        $expiracion = $expira ? \Carbon\Carbon::instance($expira)->utc()->format('Y-m-d\TH:i:s.v\Z') : null;

        $parametros = array_filter([
            'public-key'          => $this->barberia->wompi_llave_publica,
            'currency'            => 'COP',
            'amount-in-cents'     => $centavos,
            'reference'           => $referencia,
            'signature:integrity' => $this->firmaIntegridad($referencia, $centavos, 'COP', $expiracion),
            'redirect-url'        => $redirect,
            'expiration-time'     => $expiracion,
            'customer-data:email'        => $cliente['email'] ?? null,
            'customer-data:full-name'    => $cliente['nombre'] ?? null,
            'customer-data:phone-number' => $cliente['telefono'] ?? null,
        ], fn($v) => $v !== null && $v !== '');

        return self::CHECKOUT . '?' . http_build_query($parametros);
    }

    /**
     * Consulta una transacción por su id (se usa al volver del checkout,
     * por si el aviso de Wompi aún no ha llegado).
     */
    public function consultarTransaccion(string $id): ?array
    {
        try {
            $respuesta = Http::timeout(10)->acceptJson()->get($this->apiBase() . '/transactions/' . urlencode($id));
        } catch (\Throwable) {
            return null;
        }

        return $respuesta->successful() ? $respuesta->json('data') : null;
    }

    /**
     * Valida la firma de un evento de Wompi:
     * SHA256(valores de signature.properties + timestamp + secreto de eventos).
     */
    public static function eventoValido(array $evento, ?string $secretoEventos): bool
    {
        $propiedades = data_get($evento, 'signature.properties');
        $checksum    = data_get($evento, 'signature.checksum');
        $timestamp   = data_get($evento, 'timestamp');

        if (!$secretoEventos || !is_array($propiedades) || !$checksum || $timestamp === null) {
            return false;
        }

        $cadena = '';
        foreach ($propiedades as $propiedad) {
            $cadena .= (string) data_get($evento, 'data.' . $propiedad);
        }

        return hash_equals(strtolower($checksum), hash('sha256', $cadena . $timestamp . $secretoEventos));
    }
}
