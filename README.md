# vcaptcha-php

Official PHP client library for [vCaptcha](https://www.vcaptcha.com) — passive bot detection for web forms.

## Requirements

- PHP 7.4 or higher
- `curl` extension **or** `allow_url_fopen = On` in php.ini

## Installation

Download `vcaptcha.php` and drop it into your project. No Composer required.

```bash
wget https://www.vcaptcha.com/downloads/vcaptcha.php
```

Or copy it manually from this repository.

## Quick start

```php
require_once 'vcaptcha.php';

$result = vCaptcha::verify(
    secret:   $_ENV['VCAPTCHA_SECRET'],
    response: $_POST['vcaptcha-response'] ?? '',
    remoteIp: $_SERVER['REMOTE_ADDR'] ?? '',
);

if (!$result->success) {
    http_response_code(400);
    die('Verification failed: ' . $result->errorMessage());
}

// Optional: check the trust score (0-100, higher = more human)
if ($result->score < 35) {
    http_response_code(400);
    die('Submission flagged as suspicious.');
}

// All clear — process the form
```

## Simple boolean check

```php
$passed = vCaptcha::check(
    secret:   $_ENV['VCAPTCHA_SECRET'],
    response: $_POST['vcaptcha-response'] ?? '',
    minScore: 35,
);

if (!$passed) {
    http_response_code(400);
    die('Verification failed.');
}
```

## Widget setup

Add the widget to your form page:

```html
<!-- Invisible mode (recommended) -->
<form method="POST" action="/contact" data-vcaptcha="YOUR_SITE_KEY">
  <!-- your form fields -->
  <button type="submit">Send</button>
</form>
<script src="https://www.vcaptcha.com/1/api.js?k=YOUR_SITE_KEY" async defer></script>
```

```html
<!-- Checkbox mode -->
<form method="POST" action="/contact">
  <!-- your form fields -->
  <div class="vcaptcha" data-sitekey="YOUR_SITE_KEY"></div>
  <button type="submit">Send</button>
</form>
<script src="https://www.vcaptcha.com/1/api.js?k=YOUR_SITE_KEY" async defer></script>
```

The widget automatically injects a hidden `vcaptcha-response` field into the form when the visitor passes verification.

## Result object

| Property | Type | Description |
|----------|------|-------------|
| `$result->success` | bool | Whether verification passed |
| `$result->score` | int | Trust score 0–100 (higher = more human) |
| `$result->method` | string | `'passive'` or `'interactive'` |
| `$result->hostname` | string | Hostname from the request |
| `$result->errorCodes` | array | Error codes if `success` is false |
| `$result->errorMessage()` | string | Human-readable error message |

## Error codes

| Code | Description |
|------|-------------|
| `missing-input-secret` | Secret key not provided |
| `invalid-input-secret` | Secret key is invalid |
| `missing-input-response` | Token not provided |
| `invalid-input-response` | Token is invalid or expired |
| `score-too-low` | Score below minimum threshold |
| `rate-limited` | Too many requests |
| `timeout-or-duplicate` | Token already used or expired |
| `connection-failed` | Could not reach vCaptcha servers |

## Configuration

```php
// Override the verify endpoint (e.g. for testing)
vCaptcha::$verifyUrl = 'https://www.vcaptcha.com/siteverify';

// Set request timeout in seconds (default: 10)
vCaptcha::$timeout = 5;
```

## Getting your keys

Sign up at [vcaptcha.com](https://www.vcaptcha.com) and create a site key from your dashboard. You'll get:
- **Site key** — public, goes in your HTML
- **Secret key** — private, used server-side only. Never expose it in client-side code.

## License

GPL v2 or later. See [LICENSE](LICENSE).

The vCaptcha backend service is proprietary. This client library is open source.
