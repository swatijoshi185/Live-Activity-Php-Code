function liveActivityPush($liveActivityToken, $event)
{
    $base_path = {Token Key file path}; //.p8 file path
    $TEAM_ID = ''; //your TEAM ID
    $AUTH_KEY_ID = ''; // your Auth Key ID
    $DEVICE_TOKEN = $liveActivityToken; //myToken from the activity push token (Will be sent from mobile application developer)
    if (config('app.env') == 'production') {
        $APNS_HOST_NAME = 'api.push.apple.com';
    } else {
        $APNS_HOST_NAME = 'api.sandbox.push.apple.com';
    }

    $JWT_ISSUE_TIME = time();
    $JWT_HEADER = rtrim(strtr(base64_encode('{"alg": "ES256", "kid": "'.$AUTH_KEY_ID.'"}'), '+/', '-_'), '=');
    $JWT_CLAIMS = rtrim(strtr(base64_encode('{"iss": "'.$TEAM_ID.'", "iat": '.$JWT_ISSUE_TIME.'}'), '+/', '-_'), '=');
    $JWT_HEADER_CLAIMS = $JWT_HEADER.'.'.$JWT_CLAIMS;
    $TOKEN_KEY_FILE_NAME = $base_path;
    $privateKey = openssl_pkey_get_private("file://{$TOKEN_KEY_FILE_NAME}");
    $signature = '';
    openssl_sign($JWT_HEADER_CLAIMS, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $signedHeaderClaims = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    $JWT_SIGNED_HEADER_CLAIMS = $signedHeaderClaims;
    $AUTHENTICATION_TOKEN = $JWT_HEADER.'.'.$JWT_CLAIMS.'.'.$JWT_SIGNED_HEADER_CLAIMS;

    $APNS_TOPIC = {Your App Bundle ID}.push-type.liveactivity;
    $AUTHORIZATION_HEADER = 'authorization: bearer ' . $AUTHENTICATION_TOKEN;

    //CURL for Live Activity push notification
    $curl = curl_init();
    $headers = array(
        'apns-topic: ' . $APNS_TOPIC,
        'apns-push-type: liveactivity',
        $AUTHORIZATION_HEADER
    );

    $data = array(
        'aps' => array(
            'timestamp' => time(),
            'event' => $event, //'update' or 'end' > as per requirement of functionality
            'content-state' => array(
                // Here key value will change as per project requirement
                // Here only use keys whose values are dynamic. Do not user static key values.
                'key' => 'value',
            ),
            'alert' => array(
                'title' => '',
                'body' => ''
            )
        )
    );

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://$APNS_HOST_NAME/3/device/$DEVICE_TOKEN",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        return $response;
    }
}
