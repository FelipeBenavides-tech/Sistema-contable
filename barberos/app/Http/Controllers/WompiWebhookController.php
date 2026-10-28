<?php

namespace App\Http\Controllers;

use App\Models\PagoEnLinea;
use App\Support\Wompi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Avisos de Wompi cuando cambia el estado de una transacción.
 * Wompi reintenta si no recibe un 200, así que solo se responde con error
 * cuando la firma no es válida.
 */
class WompiWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $evento = $request->json()->all();

        if (($evento['event'] ?? null) !== 'transaction.updated') {
            return response()->noContent(200);
        }

        $transaccion = data_get($evento, 'data.transaction', []);
        $pago = PagoEnLinea::with('barberia')->where('referencia', $transaccion['reference'] ?? '')->first();

        // Referencia que no es de Kaixa (por ejemplo, otro cobro de la misma cuenta)
        if (!$pago) {
            return response()->noContent(200);
        }

        if (!Wompi::eventoValido($evento, $pago->barberia->wompi_secreto_eventos)) {
            Log::warning('Wompi: aviso con firma inválida', ['referencia' => $pago->referencia]);
            return response()->json(['error' => 'firma inválida'], 401);
        }

        $pago->aplicarTransaccion($transaccion);

        return response()->noContent(200);
    }
}
