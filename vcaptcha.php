<?php
/**
 * vcaptcha.php — vCaptcha PHP client library
 * Version: 1.0.0
 *
 * Drop this single file into your project. No dependencies required.
 *
 * Usage:
 *   require_once 'vcaptcha.php';
 *
 *   $result = vCaptcha::verify(
 *       secret:   'sk_your_secret_key',
 *       response: $_POST['vcaptcha-response'] ?? ''
 *   );
 *
 *   if (!$result->success) {
 *       // reject the form
 *       die('Captcha failed: ' . $result->errorMessage());
 *   }
 *
 *   // Optional: check the score (0-100)
 *   if ($result->score < 50) {
 *       // treat as suspicious even though it passed
 *   }
 */

class vCaptchaResult {
    public bool   $success;
    public int    $score;      // 0-100, higher = more likely human
    public string $method;     // 'passive' | 'interactive'
    public string $tier;       // customer's tier at time of verification
    public string $hostname;
    public string $challenge_ts;
    /** @var string[] */
    public array  $errorCodes;

    public function __construct(array $data) {
        $this->success      = (bool)($data['success']      ?? false);
        $this->score        = (int)($data['score']         ?? 0);
        $this->method       = (string)($data['method']     ?? '');
        $this->tier         = (string)($data['tier']       ?? '');
        $this->hostname     = (string)($data['hostname']   ?? '');
        $this->challenge_ts = (string)($data['challenge_ts'] ?? '');
        $this->errorCodes   = (array)($data['error-codes'] ?? []);
    }

    /**
     * Returns a human-readable error message for the first error code.
     */
    public function errorMessage(): string {
        $messages = [
            'missing-input-secret'   => 'The secret key is missing.',
            'invalid-input-secret'   => 'The secret key is invalid or does not match.',
            'missing-input-response' => 'The captcha response token is missing.',
            'invalid-input-response' => 'The captcha token is invalid or has expired.',
            'score-too-low'          => 'The trust score is too low.',
            'rate-limited'           => 'Too many verification requests. Please try again.',
            'bad-request'            => 'The verification request was malformed.',
            'timeout-or-duplicate'   => 'The token has already been used or has expired.',
        ];
        $code = $this->errorCodes[0] ?? 'unknown-error';
        return $messages[$code] ?? "Verification failed ($code).";
    }
}

class vCaptcha {

    /**
     * The vCaptcha siteverify endpoint.
     * Override if you're running the service on a custom domain.
     */
    public static string $verifyUrl = 'https://www.vcaptcha.com/siteverify';

    /** Timeout in seconds for the verification HTTP request. */
    public static int $timeout = 10;

    /**
     * Verify a captcha response token.
     *
     * @param string $secret   Your secret key (from the vCaptcha dashboard).
     * @param string $response The token from the visitor's browser (vcaptcha-response field).
     * @param string $remoteIp Optional: visitor's IP address for extra logging.
     *
     * @return vCaptchaResult
     */
    public static function verify(string $secret, string $response, string $remoteIp = ''): vCaptchaResult {
        // Guard: empty inputs
        if (trim($secret) === '') {
            return new vCaptchaResult([
                'success'     => false,
                'error-codes' => ['missing-input-secret'],
            ]);
        }
        if (trim($response) === '') {
            return new vCaptchaResult([
                'success'     => false,
                'error-codes' => ['missing-input-response'],
            ]);
        }

        // Build POST body
        $post_data = [
            'secret'   => $secret,
            'response' => $response,
        ];
        if ($remoteIp !== '') {
            $post_data['remoteip'] = $remoteIp;
        }

        // Make the request
        $raw = self::httpPost(self::$verifyUrl, $post_data);
        if ($raw === null) {
            return new vCaptchaResult([
                'success'     => false,
                'error-codes' => ['connection-failed'],
            ]);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return new vCaptchaResult([
                'success'     => false,
                'error-codes' => ['invalid-response'],
            ]);
        }

        return new vCaptchaResult($data);
    }

    /**
     * Convenience wrapper: returns true/false and throws nothing.
     * Use this when you only need a simple pass/fail check.
     */
    public static function check(string $secret, string $response, int $minScore = 0): bool {
        $result = self::verify($secret, $response);
        return $result->success && $result->score >= $minScore;
    }

    // ── HTTP helper (curl with file_get_contents fallback) ────────────────────

    private static function httpPost(string $url, array $data): ?string {
        $body = http_build_query($data);

        // Try curl first (preferred)
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                    'User-Agent: vCaptcha-PHP-Client/1.0',
                ],
                CURLOPT_TIMEOUT        => self::$timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $response = curl_exec($ch);
            $err      = curl_errno($ch);
            curl_close($ch);
            if ($err || $response === false) return null;
            return $response;
        }

        // Fallback: file_get_contents (requires allow_url_fopen = On)
        if (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                                 "Accept: application/json\r\n" .
                                 "User-Agent: vCaptcha-PHP-Client/1.0\r\n",
                    'content' => $body,
                    'timeout' => self::$timeout,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            return $response !== false ? $response : null;
        }

        return null;
    }
}
