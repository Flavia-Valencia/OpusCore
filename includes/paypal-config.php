<?php
// Configuracion de PayPal para el entorno sandbox.

define ('PAYPAL_MODE', 'sandbox');
define ('PAYPAL_CLIENT_ID', 'Af2BotGg3h9wRXyUvU4sJPB1MDX9Mp74DMzh-v2YuU0sVHTN1POJ0LJriJ4x8J0D0kU_DATVXJMLkad2');
define ('PAYPAL_SECRET','EFQA49apkOsMBqUQP0GF6PCE_IJUsDG7ZeGmpko8FrvmpBoqgpoaPteXKAxrvVb4Lel9Y5juI5deqEtN' );

define('PAYPAL_BASE_URL',
    PAYPAL_MODE === 'sandbox' ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com'
);

// Obtiene el token de acceso de PayPal usando las credenciales configuradas.
function paypalGetAccessToken(): string {
    $ch = curl_init(PAYPAL_BASE_URL . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_USERPWD        => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new RuntimeException('PayPal auth failed. HTTP ' . $httpCode);
    }
     return json_decode($response, true)['access_token'];
}
?>
