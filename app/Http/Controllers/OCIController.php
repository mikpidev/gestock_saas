<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OCIService;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\Store;
use App\Services\DocumentService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;

class OCIController extends Controller
{
    protected OCIService $ociService;
    protected DocumentService $dteService;


    public function __construct(OCIService $ociService, DocumentService $dteService)
    {
        $this->ociService = $ociService;
        $this->dteService = $dteService;
    }
    //Test controller for OCI configuration

    public function uploadTest()
    {

        $namespace = env('OCI_NAMESPACE');
        $bucket = env('OCI_BUCKET');
        $region = env('OCI_REGION');
        $userOcid = env('OCI_USER');
        $tenancyOcid = env('OCI_TENANCY');
        $fingerprint = env('OCI_FINGERPRINT');
        $keyFile = env('OCI_KEY_FILE');

        $objectName = "laravel_test.txt";
        $fileContent = "This is a test file uploaded from Laravel to OCI Object Storage.";

        $url = "https://objectstorage.{$region}.oraclecloud.com/n/{$namespace}/b/{$bucket}/o/{$objectName}";
        $date = gmdate('D, d M Y H:i:s T');

        //Cargar la clave privada
        $privateKey = openssl_pkey_get_private(file_get_contents($keyFile));
        if (!$privateKey) {
            return "No se pudo cargar la private key";
        }

        $signingString =

            "(request-target): put /n/{$namespace}/b/{$bucket}/o/{$objectName}\n" .
            "date: {$date}\n" .
            "host: objectstorage.{$region}.oraclecloud.com";

        //firmar la cadena

        openssl_sign($signingString, $signature, $privateKey, "SHA256");
        $signature = base64_encode($signature);

        //headers Auth

        $authHeader = sprintf(
            'Signature keyId="%s/%s/%s",algorithm="rsa-sha256",headers="(request-target) date host",signature="%s"',
            $tenancyOcid,
            $userOcid,
            $fingerprint,
            $signature
        );


        // enviar PUT directamente con curl (más confiable)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "date: {$date}",
            "host: objectstorage.$region.oraclecloud.com",
            "authorization: {$authHeader}",
            "content-type: text/plain",
            "content-length: " . strlen($fileContent)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response = curl_exec($ch);

        if ($response === false) {
            return [
                "curl_error" => curl_error($ch),
                "curl_errno" => curl_errno($ch),
                "url" => $url
            ];
        }
    }

    public function emailSend(Store $store, Sale $sale)
    {
        try {


            // (opcional pero recomendado)
            if ($sale->store_id !== $store->id) {
                return response()->json([
                    'error' => 'La venta no pertenece a la tienda'
                ], 403);
            }

            $sale->load([
                'store.taxInfo',
                'customer',
                'details.productType',
                'creditNotes.creditNoteDetails.productType',
                'debitNotes.debitNoteDetails.productType',
                'tipoDte'
            ]);

            $storeName = $sale->store->store_name;

            // Validar email cliente
            $to = $sale->customer->correo ?? null;

            if (!$to) {
                Log::error("El cliente ID {$sale->customer->id} no tiene email.");
                return response()->json([
                    'error' => 'El cliente no tiene correo'
                ], 422);
            }

            // 🔹 Tipo DTE
            $tipo = $sale->tipoDte->codigo ?? null;

            switch ($tipo) {
                case '01':
                    $json = $this->dteService->buildDTEJsonFE($sale);
                    break;
                case '03':
                    $json = $this->dteService->buildDTEJsonCF($sale);
                    break;
                case '14':
                    $json = $this->dteService->buildDTEJsonSE($sale);
                    break;
                default:
                    return response()->json([
                        'error' => 'Tipo DTE no soportado'
                    ], 400);
            }

            // 🔹 JSON en memoria
            $jsonContent = json_encode(
                $json,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );

            // 🔹 QR
            $urlQR = "https://admin.factura.gob.sv/consultaPublica"
                . "?ambiente=00"
                . "&codGen={$json['identificacion']['codigoGeneracion']}"
                . "&fechaEmi=" . date('Y-m-d', strtotime($json['identificacion']['fecEmi']));

            $renderer = new ImageRenderer(
                new RendererStyle(150),
                new SvgImageBackEnd()
            );

            $writer = new Writer($renderer);
            $qrImage = base64_encode($writer->writeString($urlQR));

            // 🔹 PDF en memoria
            $pdf = Pdf::loadView('reportes.ventas', [
                'tipoDteDescripcion' => [
                    '01' => 'Factura',
                    '03' => 'Crédito Fiscal',
                    '14' => 'Factura Sujeto Excluido',
                ][$tipo] ?? 'Documento',
                'dte'      => $json,
                'store' => $storeName,
                'emisor'   => $json['emisor'],
                'receptor' => $tipo === '14'
                    ? $json['sujetoExcluido']
                    : $json['receptor'],
                'resumen'  => $json['resumen'],
                'qrImage'  => $qrImage
            ]);

            $pdfBinary = $pdf->output();

            // 🔹 Adjuntos (SIN archivos físicos)
            $attachments = [
                [
                    'data' => $pdfBinary,
                    'name' => "DTE_{$sale->codigo_generacion}.pdf",
                    'mime' => 'application/pdf',
                ],
                [
                    'data' => $jsonContent,
                    'name' => "DTE_{$sale->codigo_generacion}.json",
                    'mime' => 'application/json',
                ],
            ];

            // 🔹 Envío por OCI
            $subject = "Documento Tributario Electrónico {$sale->codigo_generacion}";
            $body = "Estimado(a) {$sale->customer->nombre}, adjunto encontrará su comprobante electrónico.";

            $this->ociService->emailSubmissionToOCI(
                $to,
                $subject,
                $body,
                $attachments
            );
            return response()->json([
                'success' => true,
                'message' => 'Correo enviado correctamente',
                'redirect' => route('stores.sales.index', $store->id),
            ]);

        } catch (\Throwable $e) {

            Log::error('Error enviando DTE por correo', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'No se pudo enviar el correo'
            ], 500);
        }
    }
}
