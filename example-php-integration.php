<?php
/**
 * example-integration.php
 *
 * A complete working example showing how a customer integrates vCaptcha
 * into their own PHP site. Copy and adapt this into your own project.
 *
 * File layout in the customer's project:
 *   contact.php          ← the form page (shows the widget)
 *   contact_post.php     ← the form handler (verifies the token)
 *   lib/vcaptcha.php     ← drop the client library here
 */

// ─────────────────────────────────────────────────────────────────────────────
// contact.php — THE FORM PAGE
// ─────────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact us</title>
</head>
<body>

<h1>Contact us</h1>

<form method="POST" action="/contact_post.php">

  <label>Name<br>
    <input type="text" name="name" required>
  </label><br><br>

  <label>Email<br>
    <input type="email" name="email" required>
  </label><br><br>

  <label>Message<br>
    <textarea name="message" required></textarea>
  </label><br><br>

  <!--
    vCaptcha widget.
    Replace YOUR_SITE_KEY with the site key from your vCaptcha dashboard.
    The widget automatically injects a hidden <input name="vcaptcha-response">
    into this form when the visitor passes verification.
  -->
  <div class="vcaptcha" data-sitekey="YOUR_SITE_KEY"></div>

  <br>
  <button type="submit">Send message</button>

</form>

<!--
  Load the vCaptcha widget script.
  Put this before </body> with async defer — it will auto-mount on the div above.
-->
<script src="https://www.vcaptcha.com/1/api.js?k=YOUR_SITE_KEY" async defer></script>

</body>
</html>

<?php
// ─────────────────────────────────────────────────────────────────────────────
// contact_post.php — THE FORM HANDLER
// ─────────────────────────────────────────────────────────────────────────────

// In a real file, this would be a standalone PHP file, not inside the heredoc above.
// Shown here as a comment block for clarity.

/*

<?php
require_once __DIR__ . '/lib/vcaptcha.php';

// Your secret key — store this in an environment variable, not in source code.
define('VCAPTCHA_SECRET', getenv('VCAPTCHA_SECRET') ?: 'sk_your_secret_key_here');

// Get the token the widget injected into the form
$captcha_token = trim($_POST['vcaptcha-response'] ?? '');

// Verify with vCaptcha
$result = vCaptcha::verify(
    secret:   VCAPTCHA_SECRET,
    response: $captcha_token,
    remoteIp: $_SERVER['REMOTE_ADDR'] ?? ''
);

if (!$result->success) {
    http_response_code(400);
    die('Captcha verification failed: ' . $result->errorMessage());
}

// Optional: reject low-trust submissions even if they technically passed
if ($result->score < 35) {
    http_response_code(400);
    die('Your submission was flagged as suspicious. Please try again.');
}

// All good — process the form
$name    = strip_tags(trim($_POST['name']    ?? ''));
$email   = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$message = strip_tags(trim($_POST['message'] ?? ''));

if (!$name || !$email || !$message) {
    http_response_code(400);
    die('Please fill in all fields.');
}

// Send email, save to DB, etc.
mail('you@yoursite.com', "Contact from $name", $message, "From: $email");

// Redirect to a thank-you page
header('Location: /thank-you.php');
exit;

*/
