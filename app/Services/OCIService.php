<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Oracle\Signer\Signer;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OCIService
{
    protected array $config;
    protected Mailer $mailer;

    //constructor
    public function __construct()
    {
        //oci SMTP
        $this->config = config('services.oci_smtp');
    }

    public function uploadReportsToOCI($objectName, $filePath, $contentType = "application/octet-stream")

    {

        //verificar objectname y filepath
        \Log::info('OCI Upload - Object Name: ' . $objectName . ', File Path: ' . $filePath . ', Size:' . filesize($filePath) . ' bytes');

        //Variables de entorno OCI
        $namespace = config('services.oci.namespace');
        $bucket = config('services.oci.bucket');
        $region = config('services.oci.region');
        $userOcid = config('services.oci.user_id');
        $tenancyOcid = config('services.oci.tenancy_id');
        $fingerprint = config('services.oci.fingerprint');
        $keyFile = config('services.oci.key_file');


        $url = "https://objectstorage.{$region}.oraclecloud.com/n/{$namespace}/b/{$bucket}/o/{$objectName}";
        $date = gmdate('D, d M Y H:i:s T');


        //Cargar la clave privada
        $privateKey = openssl_pkey_get_private(file_get_contents($keyFile));
        if (!$privateKey) {
            return "No se pudo cargar la private key";
        }



        //crear path en bucket si no existe
        if (!file_exists($filePath)) mkdir($filePath, 0777, true);

        $body = file_get_contents($filePath);
        //$Bodysize = filesize($filePath);
        $contentLength = strlen($body);
        $contentSha256 = base64_encode(hash('sha256', $body, true));

        $signingString =
            "(request-target): put /n/{$namespace}/b/{$bucket}/o/{$objectName}\n" .
            "host: objectstorage.{$region}.oraclecloud.com\n" .
            "date: {$date}\n" .
            "content-type: {$contentType}\n" .
            "content-length: {$contentLength}\n" .
            "x-content-sha256: {$contentSha256}";

        \Log::info("OCI Upload -  Content-Length: " . $contentLength);

       //firmar la cadena

        openssl_sign($signingString, $signature, $privateKey, "SHA256");
        $signature = base64_encode($signature);

        //headers Auth
        $authHeader = sprintf(
            'Signature keyId="%s/%s/%s",algorithm="rsa-sha256",headers="(request-target) host date content-type content-length x-content-sha256",signature="%s"',
            $tenancyOcid,
            $userOcid,
            $fingerprint,
            $signature
        );

        // convertir headers a formato curl 
        $curlHeaders = [];
        foreach (
            [
                "date" => $date,
                "host" => "objectstorage.$region.oraclecloud.com",
                "authorization" => $authHeader,
                "content-type" => $contentType,
                "content-length" => $contentLength,
                "x-content-sha256" => $contentSha256
            ] as $key => $value
        ) {
            $curlHeaders[] = "{$key}: {$value}";
        }


        // enviar PUT directamente con curl (más confiable)

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //verificar headers antes de enviar
        \Log::info('OCI Upload - Headers: ' . json_encode($curlHeaders));

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);

        if ($response === false) {
            \Log::error('OCI CURL ERROR', [
                'error' => $error
            ]);
        } else {
            \Log::info('OCI RESPONSE', [
                'status' => $status,
                'body' => $response
            ]);
        }



        curl_close($ch);


        if ($response === false) {
            return [
                "curl_error" => curl_error($ch),
                "curl_errno" => curl_errno($ch),
                "url" => $url
            ];
        }

        return [
            "status" => $status,
            "response" => $response,
            "error" => $error,
            "url" => $url
        ];
    }


    public function emailSubmissionToOCI(
        string $to,
        string $subject,
        string $body,
        array $attachments = []
    ): void {
        try {

            $fromEmail = config('services.oci_smtp.from_email');
            $fromName  = config('services.oci_smtp.from_name');

            if (!$fromEmail) {
                throw new \Exception('OCI from_email no configurado');
            }

            Mail::raw($body, function (Message $message) use (
                $to,
                $subject,
                $attachments,
                $fromEmail,
                $fromName
            ) {
                $message
                    ->from($fromEmail, $fromName)
                    ->to($to)
                    ->subject($subject);

                foreach ($attachments as $file) {
                    if (
                        empty($file['data']) ||
                        empty($file['name']) ||
                        empty($file['mime'])
                    ) {
                        throw new \Exception('Adjunto inválido');
                    }

                    $message->attachData(
                        $file['data'],
                        $file['name'],
                        ['mime' => $file['mime']]
                    );
                }
            });
        } catch (\Throwable $e) {

            Log::error('Error enviando correo por OCI', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('No se pudo enviar el correo por OCI');
        }
    }
}
