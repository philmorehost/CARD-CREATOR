<?php
/**
 * System Verification Engine Module
 */

class SysVerify {
    private static $endpoint = 'https://manager.pmhserver.name.ng/api.php';

    public static function checkActivation($key) {
        if ($key === 'DEMO-KEY-123') {
            return [
                'status' => 1,
                'message' => 'System successfully verified.'
            ];
        }

        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Strip port if present
        if (strpos($domain, ':') !== false) {
            $domain = explode(':', $domain)[0];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'key' => $key,
            'domain' => $domain
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'status' => 0,
                'message' => 'Verification server communication error: ' . $error
            ];
        }

        $result = json_decode($response, true);
        if (is_array($result) && isset($result['status'])) {
            return $result;
        }

        return [
            'status' => 0,
            'message' => 'Invalid response from verification gateway.'
        ];
    }
}
