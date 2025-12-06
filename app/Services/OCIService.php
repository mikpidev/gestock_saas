<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Oracle\Signer\Signer;
use Illuminate\Support\Facades\Log;


class OCIService
{

    public function uploadReportsToOCI($objectName, $filePath, $contentType = "application/octet-stream")

    {

        //Variables de entorno OCI
        $namespace = env('OCI_NAMESPACE');
        $bucket = env('OCI_BUCKET');
        $region = env('OCI_REGION');
        $userOcid = env('OCI_USER');
        $tenancyOcid = env('OCI_TENANCY');
        $fingerprint = env('OCI_FINGERPRINT');
        $keyFile = env('OCI_KEY_FILE');

      
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


        $body = file_get_contents($filePath);

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

        // convertir headers a formato curl 
        $curlHeaders = [];
        foreach ([
            "date" => $date,
            "host" => "objectstorage.$region.oraclecloud.com",
            "authorization" => $authHeader,
            "content-type" => $contentType,
            "content-length" => strlen($body)
        ] as $key => $value) {
            $curlHeaders[] = "{$key}: {$value}";
        }

        // enviar PUT directamente con curl (más confiable)

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

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

}