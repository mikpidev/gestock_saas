<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\ProductType;
use App\Models\InvoiceNumber;
use App\Models\Store;
use App\Models\TipoDte;
use App\Services\ConsultaService;
use App\Services\HaciendaAuthService;

class SaleTestService
{
    public function createTestSale(
        Store $store,
        array $data,
        ?int $userId = null
    ) {

        \Log::info('Creating test sale', $data);

        $discountPercent = $data['discount_amount'] ?? 0;

        $totalAmount = 0;
        $totalIva = 0;
        $totalGravada = 0;

        foreach ($data['products'] as $p) {

            $cantidad = $p['quantity'];
            $precioConIVA = $p['price'];

            $subtotalConIVA = $cantidad * $precioConIVA;

            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;

            $totalAmount += $subtotalConIVA;
            $totalGravada += $baseSinIVA;
            $totalIva += $ivaItem;
        }

        $discountAmount = $totalAmount * $discountPercent;
        $netAmount = $totalAmount - $discountAmount;

        $tipoDTE = TipoDte::find($data['tipo_documento_id'])->codigo;

        $invoiceNumber = InvoiceNumber::getNextNumber(
            $store->id,
            $tipoDTE
        );

        $sale = Sale::create([
            'customers_id' => $data['customers_id'],
            'sale_date' => $data['sale_date'],
            'dte_status' => 'PENDIENTE',
            'discount_amount' => round($discountAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'net_amount' => round($netAmount, 2),
            'store_id' => $store->id,
            'user_id' => $userId,
            'payment_method' => $data['payment_method'],
            'total_gravada' => $netAmount,
            'total_iva' => round($totalIva, 2),
            'numero_control' => $invoiceNumber->numero_control,
            'codigo_generacion' => $invoiceNumber->codigo_generacion,
            'invoice_number' => $invoiceNumber->number,
            'tipo_documento_id' => $data['tipo_documento_id'],
            'environment' => $store->environment,
        ]);

        foreach ($data['products'] as $product) {

            $subtotalConIVA =
                $product['quantity'] * $product['price'];

            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;

            $sale->details()->create([
                'product_type_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'subtotal' => $subtotalConIVA,
                'iva_item' => round($ivaItem, 2),
            ]);
        }

        // generar DTE

        // consultar MH

        // enviar correo

        return $sale;
    }
}