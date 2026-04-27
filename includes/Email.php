<?php
/**
 * Professional Email System
 * Uses PHPMailer with SMTP settings from admin dashboard
 * Sends beautifully designed emails for all e-commerce events
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once BASE_PATH . 'vendor/autoload.php';

class Email {
    private $db;
    private $settings;
    private $siteName;
    private $siteUrl;
    private $primaryColor;
    private $logoUrl;

    public function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
    }

    private function loadSettings() {
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings WHERE `key` IN (
            'smtp_host','smtp_port','smtp_username','smtp_password','smtp_encryption',
            'mail_from_address','mail_from_name','site_name','site_url','admin_email',
            'primary_color','logo','phone','address'
        )");
        $this->settings = [];
        foreach ($rows as $r) {
            $this->settings[$r['key']] = $r['value'];
        }
        $this->siteName = $this->settings['site_name'] ?? SITE_NAME;
        if (empty($this->siteName) || in_array(strtolower($this->siteName), ['your store', 'yourstore', ''])) {
            $this->siteName = 'Cat Eyes';
        }
        $this->siteUrl = $this->settings['site_url'] ?? SITE_URL;
        $this->primaryColor = $this->settings['primary_color'] ?? '#2563EB';
        $this->logoUrl = '';
        if (!empty($this->settings['logo'])) {
            $logoBase = UPLOADS_URL;
            if (strpos($logoBase, 'localhost') !== false) {
                if (defined('HOSTINGER_SITE_URL')) {
                    $logoBase = rtrim(HOSTINGER_SITE_URL, '/') . '/assets/uploads/';
                } elseif (strpos($this->siteName, 'Cat Eye') !== false) {
                    $logoBase = 'https://cateyesbeauty.com/assets/uploads/';
                }
            }
            $this->logoUrl = $logoBase . 'settings/' . $this->settings['logo'];
        }
    }

    private function getSetting($key, $default = '') {
        return $this->settings[$key] ?? $default;
    }

    private function getAdminEmail() {
        $email = $this->getSetting('admin_email');
        if (empty($email)) {
            $email = $this->getSetting('mail_from_address');
        }
        if (empty($email)) {
            $adminEmail = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'admin_email'")['value'] ?? 'admin@localhost';
            $email = $adminEmail ?: '';
        }
        return $email ?: 'admin@localhost';
    }

    // ─── CORE SEND METHOD ───────────────────────────────────────────────

    public function send($to, $subject, $body, $fromName = null, $fromEmail = null) {
        if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) {
            return true;
        }

        $smtpHost = $this->getSetting('smtp_host');
        $smtpPort = $this->getSetting('smtp_port', 587);
        $smtpUser = $this->getSetting('smtp_username');
        $smtpPass = $this->getSetting('smtp_password');
        $smtpEnc  = $this->getSetting('smtp_encryption', 'tls');
        $from = $fromName ?? $this->getSetting('mail_from_name', $this->siteName);
        $mailFromName  = (empty($from) || in_array(strtolower(trim($from)), ['your store', 'yourstore'])) ? $this->siteName : $from;
        $mailFromEmail = $fromEmail ?? $this->getSetting('mail_from_address', 'noreply@localhost');
         if (empty($to)) {
            error_log("Email sending failed: Recipient email is empty.");
            return false;
        }

        if (!empty($smtpHost) && !empty($smtpUser)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->SMTPDebug  = 3; // Enable verbose debug output
                $mail->Debugoutput = function($str, $level) { error_log("SMTP DEBUG: $str"); };
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->SMTPSecure = ($smtpEnc === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = (int)$smtpPort;
                $mail->CharSet    = 'UTF-8';

                // Skip SSL verification on local environments to avoid issues with missing CA certs
                if (defined('IS_LOCAL') && IS_LOCAL) {
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
                }

                $mail->setFrom($mailFromEmail, $mailFromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
                return false;
            }
        } else {
            // Use standard PHP mail()
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . $mailFromName . " <" . $mailFromEmail . ">" . "\r\n";
            
            try {
                return mail($to, $subject, $body, $headers);
            } catch (Exception $e) {
                error_log("PHP mail() Error: " . $e->getMessage());
                return false;
            }
        }
    }

    // ─── BASE HTML TEMPLATE ─────────────────────────────────────────────

    private function wrapTemplate($content, $preheader = '') {
        $c = $this->primaryColor;
        $name = htmlspecialchars($this->siteName);
        $url  = htmlspecialchars($this->siteUrl);
        $year = date('Y');
        $phone   = htmlspecialchars($this->getSetting('phone'));
        $address = htmlspecialchars($this->getSetting('address'));

        $logoBlock = '';
        if ($this->logoUrl) {
            $logoBlock = '<img src="' . htmlspecialchars($this->logoUrl) . '" alt="' . $name . '" style="max-height:55px;margin-bottom:12px;">';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{$name}</title>
<!--[if mso]><style>table,td{font-family:Georgia,serif!important}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f8f5f0;font-family:Georgia,'Times New Roman',Times,serif;-webkit-font-smoothing:antialiased;">
<span style="display:none;font-size:1px;color:#f8f5f0;max-height:0;overflow:hidden;">{$preheader}</span>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f8f5f0;">
<tr><td style="padding:40px 15px;">

<!-- Decorative outer border -->
<table role="presentation" width="620" cellspacing="0" cellpadding="0" border="0" align="center" style="max-width:620px;width:100%;">
<tr><td style="padding:3px;background:linear-gradient(135deg,{$c} 0%,#6b0fb8 50%,{$c} 100%);border-radius:16px;">

<!-- Main card -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border-radius:14px;overflow:hidden;">

<!-- Header -->
<tr>
<td style="background:linear-gradient(135deg,{$c} 0%,#5a0d96 60%,#3d0a6b 100%);padding:0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr><td style="padding:32px 40px 28px;text-align:center;">
        {$logoBlock}
        <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:400;letter-spacing:2px;font-family:Georgia,'Times New Roman',Times,serif;text-transform:uppercase;">{$name}</h1>
        <div style="width:60px;height:2px;background-color:rgba(255,255,255,0.4);margin:12px auto 0;"></div>
    </td></tr>
    </table>
</td>
</tr>

<!-- Ornamental divider -->
<tr>
<td style="padding:20px 40px 0;text-align:center;background-color:#ffffff;">
    <span style="font-size:18px;color:#d4af37;letter-spacing:8px;">&#10022; &#10022; &#10022;</span>
</td>
</tr>

<!-- Body -->
<tr>
<td style="padding:24px 44px 16px;">
    {$content}
</td>
</tr>

<!-- Payment badges -->
<tr>
<td style="padding:0 44px 20px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr><td style="border-top:1px solid #e8e0d5;padding-top:20px;text-align:center;">
        <p style="margin:0 0 10px;font-size:11px;color:#9a8e80;text-transform:uppercase;letter-spacing:2px;font-family:Georgia,serif;">We Accept</p>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
        <tr>
            <td style="padding:0 6px;"><div style="display:inline-block;background:#f8f5f0;border:1px solid #e8e0d5;border-radius:6px;padding:6px 12px;"><span style="font-size:13px;font-weight:700;color:#1a1f71;font-family:Arial,sans-serif;letter-spacing:1px;">VISA</span></div></td>
            <td style="padding:0 6px;"><div style="display:inline-block;background:#f8f5f0;border:1px solid #e8e0d5;border-radius:6px;padding:6px 10px;"><span style="font-size:10px;color:#eb001b;">&#9679;</span><span style="font-size:10px;color:#f79e1b;">&#9679;</span> <span style="font-size:11px;font-weight:600;color:#333;font-family:Arial,sans-serif;">Mastercard</span></div></td>
            <td style="padding:0 6px;"><div style="display:inline-block;background:#f8f5f0;border:1px solid #e8e0d5;border-radius:6px;padding:6px 12px;"><span style="font-size:12px;font-weight:700;color:#fff;font-family:Arial,sans-serif;background:#292929;padding:2px 6px;border-radius:3px;">tabby</span></div></td>
        </tr>
        </table>
    </td></tr>
    </table>
</td>
</tr>

<!-- Footer -->
<tr>
<td style="padding:0 44px 32px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr><td style="border-top:1px solid #e8e0d5;padding-top:24px;text-align:center;">
        <p style="margin:0 0 6px;font-size:12px;color:#9a8e80;font-family:Georgia,serif;letter-spacing:0.5px;">Need help? We're here for you</p>
        <p style="margin:0 0 6px;font-size:13px;color:#6b5e52;font-family:Georgia,serif;">{$phone}</p>
        <p style="margin:0 0 14px;font-size:12px;color:#9a8e80;font-family:Georgia,serif;font-style:italic;">{$address}</p>
        <div style="font-size:14px;color:#d4af37;letter-spacing:6px;margin-bottom:12px;">&#8212; &#10043; &#8212;</div>
        <p style="margin:0;font-size:11px;color:#b8ad9e;font-family:Georgia,serif;letter-spacing:0.5px;">
            &copy; {$year} {$name}. All rights reserved.<br>
            <a href="{$url}" style="color:{$c};text-decoration:none;font-style:italic;">{$name}</a>
        </p>
    </td></tr>
    </table>
</td>
</tr>

</table>
</td></tr>
</table>

</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function button($text, $url, $color = null) {
        $c = $color ?? $this->primaryColor;
        $u = htmlspecialchars($url);
        $t = htmlspecialchars($text);
        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:28px auto;">
<tr>
<td style="border-radius:50px;background:linear-gradient(135deg,{$c} 0%,#5a0d96 100%);box-shadow:0 4px 15px rgba(140,23,223,0.25);">
    <a href="{$u}" target="_blank" style="display:inline-block;padding:15px 40px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;letter-spacing:1.5px;border-radius:50px;font-family:Georgia,'Times New Roman',serif;text-transform:uppercase;">{$t}</a>
</td>
</tr>
</table>
HTML;
    }

    private function infoRow($label, $value) {
        return '<tr>
            <td style="padding:12px 18px;border-bottom:1px solid #f0ebe4;font-size:12px;color:#9a8e80;width:40%;font-family:Georgia,serif;text-transform:uppercase;letter-spacing:0.5px;">' . htmlspecialchars($label) . '</td>
            <td style="padding:12px 18px;border-bottom:1px solid #f0ebe4;font-size:14px;color:#2d2520;font-weight:600;font-family:Georgia,serif;">' . htmlspecialchars($value) . '</td>
        </tr>';
    }

    private function infoTable($rows) {
        $html = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#faf7f2;border-radius:10px;overflow:hidden;margin:18px 0;border:1px solid #e8e0d5;border-left:4px solid #d4af37;">';
        foreach ($rows as $label => $value) {
            $html .= $this->infoRow($label, $value);
        }
        $html .= '</table>';
        return $html;
    }

    private function badge($text, $color) {
        return '<span style="display:inline-block;padding:5px 16px;background-color:' . $color . ';color:#ffffff;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;font-family:Georgia,serif;">' . htmlspecialchars($text) . '</span>';
    }

    // ─── 1. WELCOME / REGISTRATION EMAIL ────────────────────────────────

    public function welcome($userId, $password = null) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
        if (!$user) return false;

        $name = htmlspecialchars($user['first_name']);
        $dashUrl = $this->siteUrl . 'account/dashboard.php';
        $loginUrl = $this->siteUrl . 'account/login.php';
        $shopUrl = $this->siteUrl;

        $passwordInfo = '';
        if ($password) {
            $passwordInfo = <<<HTML
<div style="margin:20px 0;padding:16px 20px;background-color:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b;">
    <p style="margin:0 0 4px;font-size:13px;color:#92400e;"><strong>Your Temporary Credentials:</strong></p>
    <p style="margin:0;font-size:14px;color:#92400e;">
        Email: <strong>{$user['email']}</strong><br>
        Password: <strong>{$password}</strong>
    </p>
    <p style="margin:10px 0 0;font-size:12px;color:#92400e;font-style:italic;">Please change your password after logging in.</p>
</div>
HTML;
        }

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#127881;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Welcome to {$this->siteName}!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">We're thrilled to have you on board, {$name}.</p>
</div>

{$passwordInfo}

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Your account has been created successfully. You now have access to:
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;">
<tr>
    <td style="padding:12px 16px;background-color:#eff6ff;border-radius:8px;margin-bottom:8px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding-right:12px;font-size:20px;">&#128722;</td>
            <td style="font-size:14px;color:#1e40af;"><strong>Exclusive Shopping</strong> &mdash; Browse and purchase products with ease</td>
        </tr>
        </table>
    </td>
</tr>
<tr><td style="height:8px;"></td></tr>
<tr>
    <td style="padding:12px 16px;background-color:#f0fdf4;border-radius:8px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding-right:12px;font-size:20px;">&#128197;</td>
            <td style="font-size:14px;color:#166534;"><strong>Service Bookings</strong> &mdash; Book professional services online</td>
        </tr>
        </table>
    </td>
</tr>
<tr><td style="height:8px;"></td></tr>
<tr>
    <td style="padding:12px 16px;background-color:#fefce8;border-radius:8px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding-right:12px;font-size:20px;">&#128230;</td>
            <td style="font-size:14px;color:#854d0e;"><strong>Order Tracking</strong> &mdash; Track your orders in real time</td>
        </tr>
        </table>
    </td>
</tr>
</table>

{$this->button('Go to Login', $loginUrl)}

<p style="font-size:14px;color:#6b7280;text-align:center;">
    Or <a href="{$shopUrl}" style="color:{$this->primaryColor};text-decoration:none;font-weight:600;">start shopping now</a>
</p>
HTML;

        $subject = "Welcome to {$this->siteName}!";
        $body = $this->wrapTemplate($content, "Welcome aboard, {$name}! Your account is ready.");

        $this->send($user['email'], $subject, $body);
        $this->notifyAdminNewRegistration($user);
        return true;
    }

    // ─── 2. EMAIL VERIFICATION ──────────────────────────────────────────

    public function emailVerification($userId, $token) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
        if (!$user) return false;

        $name = htmlspecialchars($user['first_name']);
        $verifyUrl = $this->siteUrl . 'account/verify.php?token=' . urlencode($token);

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#eff6ff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#9993;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Verify Your Email</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">One more step to activate your account.</p>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Hi {$name},<br><br>
    Thank you for registering with <strong>{$this->siteName}</strong>. Please click the button below to verify your email address and activate your account.
</p>

{$this->button('Verify Email Address', $verifyUrl, '#059669')}

<p style="font-size:13px;color:#9ca3af;text-align:center;">
    If the button doesn't work, copy and paste this link into your browser:<br>
    <a href="{$verifyUrl}" style="color:{$this->primaryColor};word-break:break-all;">{$verifyUrl}</a>
</p>

<div style="margin-top:20px;padding:14px 18px;background-color:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b;">
    <p style="margin:0;font-size:13px;color:#92400e;">
        <strong>Note:</strong> This verification link expires in 24 hours. If you did not create this account, please ignore this email.
    </p>
</div>
HTML;

        $subject = "Verify Your Email - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Please verify your email address to activate your account.");
        return $this->send($user['email'], $subject, $body);
    }

    // ─── 3. FORGOT PASSWORD ─────────────────────────────────────────────

    public function forgotPassword($user, $token) {
        $name = htmlspecialchars($user['first_name']);
        $resetUrl = $this->siteUrl . 'account/reset-password.php?token=' . urlencode($token);

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#fef2f2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128274;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Password Reset Request</h2>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Hi {$name},<br><br>
    We received a request to reset the password for your <strong>{$this->siteName}</strong> account.
    Click the button below to set a new password:
</p>

{$this->button('Reset My Password', $resetUrl, '#dc2626')}

<p style="font-size:13px;color:#9ca3af;text-align:center;">
    If the button doesn't work, copy and paste this link:<br>
    <a href="{$resetUrl}" style="color:{$this->primaryColor};word-break:break-all;">{$resetUrl}</a>
</p>

<div style="margin-top:20px;padding:14px 18px;background-color:#fef2f2;border-radius:8px;border-left:4px solid #ef4444;">
    <p style="margin:0;font-size:13px;color:#991b1b;">
        <strong>Security Notice:</strong> This link expires in 1 hour. If you did not request a password reset, someone may have entered your email by mistake. You can safely ignore this email and your password will remain unchanged.
    </p>
</div>
HTML;

        $subject = "Password Reset - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Reset your password for {$this->siteName}.");
        return $this->send($user['email'], $subject, $body);
    }

    // ─── 4. ORDER CONFIRMATION (Customer) ───────────────────────────────

    public function orderConfirmation($orderId, $recipientOverride = null) {
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $orderId]);
        if (!$order) return false;

        require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);

        $custName = htmlspecialchars($order['billing_first_name']);
        $orderNum = htmlspecialchars($order['order_number']);
        $orderDate = date('F j, Y \a\t g:i A', strtotime($order['created_at'] . ' UTC'));
        $trackUrl = $this->siteUrl . 'account/order-details.php?id=' . $orderId;

        $currCode = $order['currency_code'] ?? ($order['currency'] ?? 'AED');
        $itemsTableHtml = $this->getOrderItemsTable($orderId, $currencyHelper, $currCode);

        $subtotal = $currencyHelper->formatForCurrency($order['subtotal'], $currCode);
        $tax = $currencyHelper->formatForCurrency($order['tax_amount'], $currCode);
        $shipping = $currencyHelper->formatForCurrency($order['shipping_cost'], $currCode);
        $discount = $currencyHelper->formatForCurrency($order['discount_amount'], $currCode);
        $totalAmt = $currencyHelper->formatForCurrency($order['total_amount'], $currCode);

        $discountRow = '';
        if ($order['discount_amount'] > 0) {
            $discountRow = '<tr><td style="padding:8px 18px;font-size:13px;color:#6b8f5e;font-family:Georgia,serif;">Discount</td><td style="padding:8px 18px;text-align:right;font-size:13px;color:#6b8f5e;font-family:Georgia,serif;">-' . $discount . '</td></tr>';
        }

        // Use actual order status
        $status = strtolower($order['order_status']);
        $statusColor = ($status === 'confirmed') ? '#10b981' : '#d4af37';
        $statusLabel = ucfirst($status);

        $content = <<<HTML
<div style="text-align:center;margin-bottom:28px;">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#f0ebe4 0%,#faf7f2 100%);border-radius:50%;display:inline-block;line-height:80px;margin-bottom:16px;border:2px solid #d4af37;">
        <span style="font-size:38px;">&#10004;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#2d2520;font-size:26px;font-family:Georgia,'Times New Roman',serif;font-weight:400;letter-spacing:1px;">Order Confirmed</h2>
    <p style="margin:0;color:#9a8e80;font-size:15px;font-family:Georgia,serif;font-style:italic;">Thank you for your purchase, {$custName}.</p>
</div>

<!-- Order Number Card -->
<div style="background:linear-gradient(135deg,#faf7f2 0%,#f5f0e8 100%);padding:20px 24px;border-radius:10px;text-align:center;margin-bottom:28px;border:1px solid #e8e0d5;">
    <p style="margin:0 0 4px;font-size:11px;color:#9a8e80;text-transform:uppercase;letter-spacing:2px;font-family:Georgia,serif;">Order Number</p>
    <p style="margin:0;font-size:22px;font-weight:700;color:#2d2520;letter-spacing:2px;font-family:Georgia,'Times New Roman',serif;">{$orderNum}</p>
    <p style="margin:10px 0 0;font-size:12px;color:#9a8e80;font-family:Georgia,serif;font-style:italic;">{$orderDate}</p>
</div>

<!-- Ornamental divider -->
<div style="text-align:center;margin-bottom:20px;">
    <span style="font-size:14px;color:#d4af37;letter-spacing:6px;">&#8212; &#10043; &#8212;</span>
</div>

{$itemsTableHtml}

<!-- Totals -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#faf7f2;border-radius:0 0 10px 10px;margin-bottom:28px;border-top:1px solid #e8e0d5;">
<tr><td style="padding:8px 18px;font-size:13px;color:#9a8e80;font-family:Georgia,serif;">Subtotal</td><td style="padding:8px 18px;text-align:right;font-size:13px;color:#2d2520;font-family:Georgia,serif;">{$subtotal}</td></tr>
<tr><td style="padding:8px 18px;font-size:13px;color:#9a8e80;font-family:Georgia,serif;">Tax</td><td style="padding:8px 18px;text-align:right;font-size:13px;color:#2d2520;font-family:Georgia,serif;">{$tax}</td></tr>
<tr><td style="padding:8px 18px;font-size:13px;color:#9a8e80;font-family:Georgia,serif;">Shipping</td><td style="padding:8px 18px;text-align:right;font-size:13px;color:#2d2520;font-family:Georgia,serif;">{$shipping}</td></tr>
{$discountRow}
<tr><td colspan="2" style="border-top:2px solid #d4af37;"></td></tr>
<tr>
    <td style="padding:14px 18px;font-size:16px;font-weight:700;color:#2d2520;font-family:Georgia,'Times New Roman',serif;">Total</td>
    <td style="padding:14px 18px;text-align:right;font-size:20px;font-weight:700;color:{$this->primaryColor};font-family:Georgia,'Times New Roman',serif;">{$totalAmt}</td>
</tr>
</table>

<!-- Status -->
<div style="text-align:center;margin-bottom:20px;">
    <span style="font-size:12px;color:#9a8e80;font-family:Georgia,serif;text-transform:uppercase;letter-spacing:1px;">Order Status: </span>
    {$this->badge($statusLabel, $statusColor)}
</div>

{$this->button('View Order Details', $trackUrl)}

<p style="font-size:14px;color:#9a8e80;text-align:center;font-family:Georgia,serif;font-style:italic;line-height:1.6;">
    We will notify you when your order ships.<br>Thank you for choosing {$this->siteName}.
</p>
HTML;

        $subject = "Order Confirmed #{$orderNum} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Your order #{$orderNum} has been confirmed.");
        
        // Use billing_email, fallback to guest_email (if column exists)
        $recipientEmail = $recipientOverride ?: (!empty($order['billing_email']) ? $order['billing_email'] : ($order['guest_email'] ?? ''));
        
        if (empty($recipientEmail)) {
            error_log("Order Confirmation: No recipient email found for order ID: $orderId");
            return false;
        }

        return $this->send($recipientEmail, $subject, $body);
    }

    public function orderStatusUpdate($orderId, $newStatus, $trackingNumber = '', $recipientOverride = null) {
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $orderId]);
        if (!$order) return false;

        if (!class_exists('Currency')) require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);

        $custName = htmlspecialchars($order['billing_first_name']);
        $orderNum = htmlspecialchars($order['order_number']);
        $trackUrl = $this->siteUrl . 'account/order-details.php?id=' . $orderId;

        $statusConfig = [
            'pending'    => ['color' => '#f59e0b', 'icon' => '&#9203;',  'title' => 'Order Pending',      'msg' => 'Your order is awaiting processing.'],
            'confirmed'  => ['color' => '#3b82f6', 'icon' => '&#9989;',  'title' => 'Order Confirmed',     'msg' => 'Great news! Your order has been confirmed and is being prepared.'],
            'processing' => ['color' => '#8b5cf6', 'icon' => '&#9881;',  'title' => 'Order Processing',    'msg' => 'Your order is currently being processed and prepared for shipment.'],
            'shipped'    => ['color' => '#06b6d4', 'icon' => '&#128666;','title' => 'Order Shipped!',      'msg' => 'Your order is on its way to you!'],
            'delivered'  => ['color' => '#059669', 'icon' => '&#127881;','title' => 'Order Delivered!',     'msg' => 'Your order has been delivered successfully. Enjoy!'],
            'cancelled'  => ['color' => '#ef4444', 'icon' => '&#10060;', 'title' => 'Order Cancelled',     'msg' => 'Your order has been cancelled. If you have questions, please contact us.'],
        ];

        $cfg = $statusConfig[$newStatus] ?? $statusConfig['pending'];

        $trackingHtml = '';
        if ($newStatus === 'shipped' && !empty($trackingNumber)) {
            $tn = htmlspecialchars($trackingNumber);
            $trackingHtml = <<<HTML
<div style="margin:20px 0;padding:16px 20px;background-color:#f0f9ff;border-radius:8px;border-left:4px solid #0ea5e9;">
    <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">Tracking Number</p>
    <p style="margin:0;font-size:18px;font-weight:700;color:#1f2937;letter-spacing:1px;">{$tn}</p>
</div>
HTML;
        }

        $currCode = $order['currency_code'] ?? ($order['currency'] ?? 'AED');
        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:{$cfg['color']}15;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">{$cfg['icon']}</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">{$cfg['title']}</h2>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Hi {$custName},<br><br>
    {$cfg['msg']}
</p>

{$this->infoTable([
    'Order Number' => $orderNum,
    'Status' => ucfirst($newStatus),
    'Total Amount' => $currencyHelper->formatForCurrency($order['total_amount'], $currCode),
    'Updated' => date('F j, Y \a\t g:i A'),
])}

{$this->getOrderItemsTable($orderId, $currencyHelper, $currCode)}

{$trackingHtml}

{$this->button('View Order Details', $trackUrl)}
HTML;

        $subject = "Order Status Updated: {$newStatus} #{$orderNum}";
        $body = $this->wrapTemplate($content, "Your order #{$orderNum} is now " . $newStatus);
        $recipientEmail = $recipientOverride ?: (!empty($order['billing_email']) ? $order['billing_email'] : ($order['guest_email'] ?? ''));
        return $this->send($recipientEmail, $subject, $body);
    }

    // ─── 6. ADMIN: NEW ORDER NOTIFICATION ───────────────────────────────

    public function notifyAdminNewOrder($orderId) {
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $orderId]);
        if (!$order) return false;

        if (!class_exists('Currency')) require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);

        $adminEmail = $this->getAdminEmail();
        $orderNum = htmlspecialchars($order['order_number']);
        $custName = htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']);
        $custEmail = !empty($order['billing_email']) ? $order['billing_email'] : ($order['guest_email'] ?? 'Guest');
        $currCode = $order['currency_code'] ?? ($order['currency'] ?? 'AED');
        $totalFormatted = $currencyHelper->formatForCurrency($order['total_amount'], $currCode);
        $viewUrl = $this->siteUrl . 'admin/orders/view.php?id=' . $orderId;

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#fef3c7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128179;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">New Order Received!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">A new order has been placed on your store.</p>
</div>

{$this->infoTable([
    'Order Number' => $orderNum,
    'Customer' => $custName,
    'Email' => $custEmail,
    'Total Amount' => $totalFormatted,
    'Payment Status' => ucfirst($order['payment_status']),
    'Date' => date('F j, Y g:i A', strtotime($order['created_at'] . ' UTC')),
])}

{$this->getOrderItemsTable($orderId, $currencyHelper, $currCode)}

{$this->button('View Order in Dashboard', $viewUrl, '#059669')}
HTML;

        $subject = "New Order #{$orderNum} - {$totalFormatted}";
        $body = $this->wrapTemplate($content, "New order received: #{$orderNum} for {$totalFormatted}");
        return $this->send($adminEmail, $subject, $body);
    }

    // ─── 7. ADMIN: NEW REGISTRATION NOTIFICATION ────────────────────────

    public function notifyAdminNewRegistration($user) {
        $adminEmail = $this->getAdminEmail();
        $custName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
        $custEmail = htmlspecialchars($user['email']);
        $viewUrl = $this->siteUrl . 'admin/users.php';

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#eff6ff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128100;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">New Customer Registration</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">A new customer has registered on your store.</p>
</div>

{$this->infoTable([
    'Customer Name' => $custName,
    'Email' => $custEmail,
    'Phone' => $user['phone'] ?? 'N/A',
    'Registered' => date('F j, Y g:i A'),
])}

{$this->button('View Customers', $viewUrl)}
HTML;

        $subject = "New Customer: {$custName} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "New customer registration: {$custName}");
        return $this->send($adminEmail, $subject, $body);
    }

    // ─── 8a. BOOKING RECEIVED (Customer - when first created as pending) ──

    public function bookingReceived($booking) {
        $custName = htmlspecialchars($booking['customer_name']);
        $bookingNum = htmlspecialchars($booking['booking_number']);
        $serviceName = htmlspecialchars($booking['service_name'] ?? 'Service');
        $bookingsUrl = $this->siteUrl . 'account/mybookings.php';

        $bookingDate = date('l, F j, Y', strtotime($booking['booking_date']));
        $bookingTime = date('g:i A', strtotime($booking['booking_time']));
        if (!class_exists('Currency')) require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);
        $currCode = $booking['currency_code'] ?? 'AED';
        
        $finalAmount = $currencyHelper->formatForCurrency($booking['total_amount'], $currCode);
        
        $priceDetails = [];
        if (!empty($booking['coupon_discount_amount']) && $booking['coupon_discount_amount'] > 0) {
            $discountAmount = (float)$booking['coupon_discount_amount'];
            $totalAmount = isset($booking['total_amount']) ? (float)$booking['total_amount'] : 0.0;
            $originalAmount = isset($booking['original_amount']) ? (float)$booking['original_amount'] : 0.0;
            if ($originalAmount <= 0 && $totalAmount > 0) {
                $originalAmount = $totalAmount + $discountAmount;
            }

            $discountPercent = isset($booking['coupon_discount_percentage']) ? (float)$booking['coupon_discount_percentage'] : 0.0;
            if ($discountPercent <= 0 && $originalAmount > 0) {
                $discountPercent = round(($discountAmount / $originalAmount) * 100, 2);
            }

            $couponCode = !empty($booking['coupon_code']) ? htmlspecialchars($booking['coupon_code']) : '';
            $metaParts = [];
            if ($discountPercent > 0) {
                $percentText = rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.');
                $metaParts[] = $percentText . '%';
            }
            $metaParts[] = $couponCode ?: 'Discount';

            $priceDetails['Original Amount'] = $currencyHelper->formatForCurrency($originalAmount, $currCode);
            $priceDetails['Discount'] = '-' . $currencyHelper->formatForCurrency($discountAmount, $currCode) . ' (' . implode(' - ', $metaParts) . ')';
        }
        $priceDetails['Total Amount'] = $finalAmount;

        $infoRows = [
            'Booking Number' => $bookingNum,
            'Service' => $serviceName,
            'Date' => $bookingDate,
            'Time' => $bookingTime,
        ];
        $infoRows = array_merge($infoRows, $priceDetails);
        $infoRows['Status'] = 'Pending Confirmation';

        $nextStepsTitle = __('next_steps', 'What happens next?');
        $step1 = __('step_1', 'Our team will review your booking request shortly');
        $step2 = __('step_2', 'You will receive a reminder call about booking information');
        $step3 = __('step_3', 'Please save this email and your booking number for reference');

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#eff6ff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128203;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Booking Request Received!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">We have received your booking and it's being reviewed.</p>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Dear {$custName},<br><br>
    Thank you for choosing <strong>{$this->siteName}</strong>! Your booking request for <strong>{$serviceName}</strong> has been received successfully.
</p>

{$this->infoTable($infoRows)}

<div style="margin:20px 0;padding:16px 20px;background-color:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b;">
    <p style="margin:0;font-size:14px;color:#92400e;">
        <strong>{$nextStepsTitle}</strong><br>
        &bull; {$step1}<br>
        &bull; {$step2}<br>
        &bull; {$step3}
    </p>
</div>

{$this->button('View My Bookings', $bookingsUrl)}

<p style="font-size:14px;color:#6b7280;text-align:center;">
    If you have any questions, please don't hesitate to contact us.
</p>
HTML;

        $subject = "Booking Received #{$bookingNum} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Your booking #{$bookingNum} has been received and is pending confirmation.");
        return $this->send($booking['customer_email'], $subject, $body);
    }

    // ─── 8. BOOKING CONFIRMATION (Customer) ─────────────────────────────

    public function bookingConfirmation($booking) {
        $custName = htmlspecialchars($booking['customer_name']);
        $bookingNum = htmlspecialchars($booking['booking_number']);
        $serviceName = htmlspecialchars($booking['service_name'] ?? 'Service');
        $bookingsUrl = $this->siteUrl . 'account/mybookings.php';

        $bookingDate = date('l, F j, Y', strtotime($booking['booking_date']));
        $bookingTime = date('g:i A', strtotime($booking['booking_time']));
        if (!class_exists('Currency')) require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);
        $currCode = $booking['currency_code'] ?? 'AED';

        $finalAmount = $currencyHelper->formatForCurrency($booking['total_amount'], $currCode);

        $priceDetails = [];
        if (!empty($booking['coupon_discount_amount']) && $booking['coupon_discount_amount'] > 0) {
            $discountAmount = (float)$booking['coupon_discount_amount'];
            $totalAmount = isset($booking['total_amount']) ? (float)$booking['total_amount'] : 0.0;
            $originalAmount = isset($booking['original_amount']) ? (float)$booking['original_amount'] : 0.0;
            if ($originalAmount <= 0 && $totalAmount > 0) {
                $originalAmount = $totalAmount + $discountAmount;
            }

            $discountPercent = isset($booking['coupon_discount_percentage']) ? (float)$booking['coupon_discount_percentage'] : 0.0;
            if ($discountPercent <= 0 && $originalAmount > 0) {
                $discountPercent = round(($discountAmount / $originalAmount) * 100, 2);
            }

            $couponCode = !empty($booking['coupon_code']) ? htmlspecialchars($booking['coupon_code']) : '';
            $metaParts = [];
            if ($discountPercent > 0) {
                $percentText = rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.');
                $metaParts[] = $percentText . '%';
            }
            $metaParts[] = $couponCode ?: 'Discount';

            $priceDetails['Original Amount'] = $currencyHelper->formatForCurrency($originalAmount, $currCode);
            $priceDetails['Discount'] = '-' . $currencyHelper->formatForCurrency($discountAmount, $currCode) . ' (' . implode(' - ', $metaParts) . ')';
        }
        $priceDetails['Total Amount'] = $finalAmount;

        $infoRows = [
            'Booking Number' => $bookingNum,
            'Service' => $serviceName,
            'Date' => $bookingDate,
            'Time' => $bookingTime,
        ];
        $infoRows = array_merge($infoRows, $priceDetails);

        $nextStepsTitle = __('next_steps', 'What happens next?');
        $step1 = __('step_1', 'Our team will review your booking request shortly');
        $step2 = __('step_2', 'You will receive a reminder call about booking information');
        $step3 = __('step_3', 'Please save this email and your booking number for reference');

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128197;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Booking Confirmed!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">Your appointment has been confirmed.</p>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Dear {$custName},<br><br>
    We are pleased to confirm your booking for <strong>{$serviceName}</strong>. Here are the details:
</p>

{$this->infoTable($infoRows)}

<div style="margin:20px 0;padding:16px 20px;background-color:#f0fdf4;border-radius:8px;border-left:4px solid #22c55e;">
    <p style="margin:0;font-size:14px;color:#166534;">
        <strong>{$nextStepsTitle}</strong><br>
        &bull; {$step1}<br>
        &bull; {$step2}<br>
        &bull; {$step3}
    </p>
</div>

{$this->button('View My Bookings', $bookingsUrl, '#059669')}

<p style="font-size:14px;color:#6b7280;text-align:center;">
    We look forward to seeing you!
</p>
HTML;

        $subject = "Booking Confirmed #{$bookingNum} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Your booking #{$bookingNum} is confirmed for {$bookingDate}.");
        return $this->send($booking['customer_email'], $subject, $body);
    }

    // ─── 9. BOOKING CANCELLATION (Customer) ─────────────────────────────

    public function bookingCancellation($booking, $reason = '') {
        $custName = htmlspecialchars($booking['customer_name']);
        $bookingNum = htmlspecialchars($booking['booking_number']);
        $serviceName = htmlspecialchars($booking['service_name'] ?? 'Service');
        $contactUrl = $this->siteUrl;

        $bookingDate = date('l, F j, Y', strtotime($booking['booking_date']));
        $bookingTime = date('g:i A', strtotime($booking['booking_time']));

        $reasonHtml = '';
        if (!empty($reason)) {
            $reasonText = htmlspecialchars($reason);
            $reasonHtml = <<<HTML
<div style="margin:20px 0;padding:16px 20px;background-color:#fef2f2;border-radius:8px;border-left:4px solid #ef4444;">
    <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#991b1b;">Reason for Cancellation:</p>
    <p style="margin:0;font-size:14px;color:#7f1d1d;">{$reasonText}</p>
</div>
HTML;
        }

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#fef2f2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#10060;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Booking Cancelled</h2>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Dear {$custName},<br><br>
    We regret to inform you that your booking has been cancelled.
</p>

{$this->infoTable([
    'Booking Number' => $bookingNum,
    'Service' => $serviceName,
    'Date' => $bookingDate,
    'Time' => $bookingTime,
])}

{$reasonHtml}

<p style="font-size:15px;color:#374151;line-height:1.7;">
    If you have already made a payment, a refund will be processed according to our refund policy.
    We sincerely apologize for any inconvenience caused.
</p>

{$this->button('Book Again', $contactUrl . 'services.php')}

<p style="font-size:14px;color:#6b7280;text-align:center;">
    Questions? Please don't hesitate to <a href="{$contactUrl}" style="color:{$this->primaryColor};text-decoration:none;font-weight:600;">contact us</a>.
</p>
HTML;

        $subject = "Booking Cancelled #{$bookingNum} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Your booking #{$bookingNum} has been cancelled.");
        return $this->send($booking['customer_email'], $subject, $body);
    }

    // ─── 10. ADMIN: NEW BOOKING NOTIFICATION ────────────────────────────

    public function notifyAdminNewBooking($booking) {
        $adminEmail = $this->getAdminEmail();
        $bookingNum = htmlspecialchars($booking['booking_number']);
        $custName = htmlspecialchars($booking['customer_name']);
        $custEmail = htmlspecialchars($booking['customer_email']);
        $serviceName = htmlspecialchars($booking['service_name'] ?? 'Service');
        $bookingDate = date('l, F j, Y', strtotime($booking['booking_date']));
        $bookingTime = date('g:i A', strtotime($booking['booking_time']));
        if (!class_exists('Currency')) require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);
        $currCode = $booking['currency_code'] ?? 'AED';
        $amount = $currencyHelper->formatForCurrency($booking['total_amount'], $currCode);

        $viewUrl = $this->siteUrl . 'admin/bookings/view.php?id=' . ($booking['id'] ?? '');

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#fef3c7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128197;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">New Booking Received!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">A new service booking has been placed.</p>
</div>

{$this->infoTable([
    'Booking Number' => $bookingNum,
    'Customer' => $custName,
    'Email' => $custEmail,
    'Service' => $serviceName,
    'Date' => $bookingDate,
    'Time' => $bookingTime,
    'Amount' => $amount,
    'Status' => 'Pending Confirmation',
])}

<div style="margin:20px 0;padding:14px 18px;background-color:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b;">
    <p style="margin:0;font-size:14px;color:#92400e;">
        <strong>Action Required:</strong> Please review and confirm or cancel this booking from the admin dashboard.
    </p>
</div>

{$this->button('Review Booking', $viewUrl, '#f59e0b')}
HTML;

        $subject = "New Booking #{$bookingNum} - {$serviceName}";
        $body = $this->wrapTemplate($content, "New booking received: #{$bookingNum} for {$serviceName}");
        return $this->send($adminEmail, $subject, $body);
    }

    // ─── 11. BOOKING STATUS UPDATE (Customer) ───────────────────────────

    public function bookingStatusUpdate($booking, $newStatus) {
        if ($newStatus === 'confirmed') {
            return $this->bookingConfirmation($booking);
        }
        if ($newStatus === 'cancelled') {
            return $this->bookingCancellation($booking);
        }

        $custName = htmlspecialchars($booking['customer_name']);
        $bookingNum = htmlspecialchars($booking['booking_number']);
        $serviceName = htmlspecialchars($booking['service_name'] ?? 'Service');
        $bookingsUrl = $this->siteUrl . 'account/mybookings.php';

        $statusConfig = [
            'pending'   => ['color' => '#f59e0b', 'icon' => '&#9203;',  'title' => 'Booking Pending'],
            'completed' => ['color' => '#059669', 'icon' => '&#127881;','title' => 'Service Completed!'],
        ];
        $cfg = $statusConfig[$newStatus] ?? $statusConfig['pending'];

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:{$cfg['color']}15;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">{$cfg['icon']}</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">{$cfg['title']}</h2>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Dear {$custName},<br><br>
    Your booking for <strong>{$serviceName}</strong> has been updated.
</p>

{$this->infoTable([
    'Booking Number' => $bookingNum,
    'Service' => $serviceName,
    'Status' => ucfirst($newStatus),
    'Date' => date('l, F j, Y', strtotime($booking['booking_date'])),
    'Time' => date('g:i A', strtotime($booking['booking_time'])),
])}

{$this->button('View My Bookings', $bookingsUrl)}

<p style="font-size:14px;color:#6b7280;text-align:center;">
    Thank you for choosing {$this->siteName}!
</p>
HTML;

        $subject = "{$cfg['title']} #{$bookingNum} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Your booking #{$bookingNum} status: {$newStatus}");
        return $this->send($booking['customer_email'], $subject, $body);
    }

    // ─── 12. NEWSLETTER CAMPAIGN (Bulk) ─────────────────────────────────

    public function newsletterCampaign($toEmail, $subject, $htmlContent, $unsubscribeUrl = '') {
        if (empty($unsubscribeUrl)) {
            $unsubscribeUrl = $this->siteUrl . 'newsletter-handler.php?email=' . urlencode($toEmail) . '&action=unsubscribe';
        }

        $content = <<<HTML
<div style="font-size:15px;color:#374151;line-height:1.7;">
    {$htmlContent}
</div>

<div style="margin-top:30px;padding-top:20px;border-top:1px solid #e5e7eb;text-align:center;">
    <p style="font-size:12px;color:#9ca3af;margin:0;">
        You received this because you subscribed to {$this->siteName} newsletter.<br>
        <a href="{$unsubscribeUrl}" style="color:#6b7280;text-decoration:underline;">Unsubscribe</a> from future emails.
    </p>
</div>
HTML;

        $body = $this->wrapTemplate($content, strip_tags($subject));
        return $this->send($toEmail, $subject, $body);
    }

    // ─── 13. NEWSLETTER WELCOME ─────────────────────────────────────────

    public function newsletterWelcome($email) {
        $homeUrl = $this->siteUrl;
        $unsubUrl = $this->siteUrl . 'newsletter-handler.php?email=' . urlencode($email) . '&action=unsubscribe';

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#127881;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Welcome to Our Newsletter!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">You're now part of our community.</p>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Thank you for subscribing to the <strong>{$this->siteName}</strong> newsletter!
    You'll be the first to know about:
</p>

<div style="margin:20px 0;padding:16px 20px;background-color:#f0fdf4;border-radius:8px;border-left:4px solid #22c55e;">
    <p style="margin:0;font-size:14px;color:#166534;">
        &bull; Exclusive offers and discounts<br>
        &bull; New product launches<br>
        &bull; Special promotions and sales<br>
        &bull; Tips, news, and updates
    </p>
</div>

{$this->button('Visit Our Store', $homeUrl)}

<div style="margin-top:20px;text-align:center;">
    <p style="font-size:12px;color:#9ca3af;margin:0;">
        If you didn't subscribe, <a href="{$unsubUrl}" style="color:#6b7280;text-decoration:underline;">click here to unsubscribe</a>.
    </p>
</div>
HTML;

        $subject = "Welcome to {$this->siteName} Newsletter!";
        $body = $this->wrapTemplate($content, "You've subscribed to our newsletter.");
        return $this->send($email, $subject, $body);
    }

    // ─── 14. NEWSLETTER GOODBYE ─────────────────────────────────────────

    public function newsletterGoodbye($email) {
        $homeUrl = $this->siteUrl;

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#fef2f2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128075;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">You've Been Unsubscribed</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">We're sorry to see you go.</p>
</div>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    You have been successfully unsubscribed from the <strong>{$this->siteName}</strong> newsletter.
    You will no longer receive promotional emails from us.
</p>

<p style="font-size:15px;color:#374151;line-height:1.7;">
    Changed your mind? You can always subscribe again from our website.
</p>

{$this->button('Visit Our Store', $homeUrl)}
HTML;

        $subject = "You've been unsubscribed - {$this->siteName}";
        $body = $this->wrapTemplate($content, "You've been unsubscribed from our newsletter.");
        return $this->send($email, $subject, $body);
    }

    // ─── 15. ADMIN: CONTACT FORM NOTIFICATION ──────────────────────────

    public function notifyAdminContactForm($contactData) {
        $adminEmail = $this->getAdminEmail();

        // Also check contact_email from settings
        $contactEmail = $this->getSetting('contact_email', '');
        if (empty($contactEmail)) {
            $contactEmail = $adminEmail;
        }

        $custName = htmlspecialchars($contactData['name'] ?? 'Unknown');
        $custEmail = htmlspecialchars($contactData['email'] ?? '');
        $custPhone = htmlspecialchars($contactData['phone'] ?? 'N/A');
        $subjectLine = htmlspecialchars($contactData['subject'] ?? 'No Subject');
        $messageText = nl2br(htmlspecialchars($contactData['message'] ?? ''));
        $sentAt = date('F j, Y \a\t g:i A');

        $content = <<<HTML
<div style="text-align:center;margin-bottom:28px;">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#f0ebe4 0%,#faf7f2 100%);border-radius:50%;display:inline-block;line-height:80px;margin-bottom:16px;border:2px solid #d4af37;">
        <span style="font-size:38px;">&#9993;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#2d2520;font-size:26px;font-family:Georgia,'Times New Roman',serif;font-weight:400;letter-spacing:1px;">New Contact Message</h2>
    <p style="margin:0;color:#9a8e80;font-size:15px;font-family:Georgia,serif;font-style:italic;">A visitor has reached out through your contact form.</p>
</div>

{$this->infoTable([
    'From' => $custName,
    'Email' => $custEmail,
    'Phone' => $custPhone,
    'Subject' => $subjectLine,
    'Received' => $sentAt,
])}

<!-- Message Content -->
<div style="margin:24px 0;padding:20px 24px;background:linear-gradient(135deg,#faf7f2 0%,#f5f0e8 100%);border-radius:10px;border:1px solid #e8e0d5;border-left:4px solid {$this->primaryColor};">
    <p style="margin:0 0 8px;font-size:11px;color:#9a8e80;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;">Message</p>
    <p style="margin:0;font-size:14px;color:#2d2520;line-height:1.8;font-family:Georgia,serif;">{$messageText}</p>
</div>

<!-- Quick Reply -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:28px auto;">
<tr>
<td style="border-radius:50px;background:linear-gradient(135deg,{$this->primaryColor} 0%,#5a0d96 100%);box-shadow:0 4px 15px rgba(140,23,223,0.25);">
    <a href="mailto:{$custEmail}?subject=Re: {$subjectLine}" target="_blank" style="display:inline-block;padding:15px 40px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;letter-spacing:1.5px;border-radius:50px;font-family:Georgia,'Times New Roman',serif;text-transform:uppercase;">Reply to {$custName}</a>
</td>
</tr>
</table>

<p style="font-size:13px;color:#9a8e80;text-align:center;font-family:Georgia,serif;font-style:italic;">
    This message was sent via the contact form on your website.
</p>
HTML;

        $subject = "New Contact: {$subjectLine} — from {$custName}";
        $body = $this->wrapTemplate($content, "New contact message from {$custName}: {$subjectLine}");
        return $this->send($contactEmail, $subject, $body);
    }

    // ─── 16. CONTACT CONFIRMATION (Customer) ───────────────────────────

    public function contactConfirmation($contactData) {
        $custName = htmlspecialchars($contactData['name'] ?? 'Valued Customer');
        $custEmail = $contactData['email'] ?? '';
        $subjectLine = htmlspecialchars($contactData['subject'] ?? 'Your Message');
        $messageText = nl2br(htmlspecialchars($contactData['message'] ?? ''));
        $phone = htmlspecialchars($this->getSetting('phone'));

        if (empty($custEmail)) return false;

        $content = <<<HTML
<div style="text-align:center;margin-bottom:28px;">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#f0ebe4 0%,#faf7f2 100%);border-radius:50%;display:inline-block;line-height:80px;margin-bottom:16px;border:2px solid #d4af37;">
        <span style="font-size:38px;">&#10004;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#2d2520;font-size:26px;font-family:Georgia,'Times New Roman',serif;font-weight:400;letter-spacing:1px;">Message Received</h2>
    <p style="margin:0;color:#9a8e80;font-size:15px;font-family:Georgia,serif;font-style:italic;">We've received your message and will respond shortly.</p>
</div>

<p style="font-size:15px;color:#2d2520;line-height:1.8;font-family:Georgia,serif;">
    Dear {$custName},<br><br>
    Thank you for reaching out to <strong>{$this->siteName}</strong>. We have received your message regarding "<em>{$subjectLine}</em>" and our team will review it promptly.
</p>

<!-- Original message -->
<div style="margin:24px 0;padding:20px 24px;background:linear-gradient(135deg,#faf7f2 0%,#f5f0e8 100%);border-radius:10px;border:1px solid #e8e0d5;border-left:4px solid #d4af37;">
    <p style="margin:0 0 8px;font-size:11px;color:#9a8e80;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;">Your Message</p>
    <p style="margin:0;font-size:14px;color:#6b5e52;line-height:1.8;font-family:Georgia,serif;font-style:italic;">{$messageText}</p>
</div>

<!-- What to expect -->
<div style="margin:20px 0;padding:18px 22px;background-color:#faf7f2;border-radius:10px;border:1px solid #e8e0d5;">
    <p style="margin:0;font-size:14px;color:#2d2520;font-family:Georgia,serif;line-height:1.8;">
        <strong style="color:#d4af37;">What happens next?</strong><br>
        &bull; Our team will review your message within 24 hours<br>
        &bull; You will receive a detailed response via email<br>
        &bull; For urgent inquiries, call us at <strong>{$phone}</strong>
    </p>
</div>

{$this->button('Visit Our Store', $this->siteUrl)}

<p style="font-size:14px;color:#9a8e80;text-align:center;font-family:Georgia,serif;font-style:italic;line-height:1.6;">
    We appreciate you choosing {$this->siteName}.<br>We look forward to assisting you.
</p>
HTML;

        $subject = "We received your message — {$this->siteName}";
        $body = $this->wrapTemplate($content, "Thank you for contacting us, {$custName}. We'll respond shortly.");
        return $this->send($custEmail, $subject, $body);
    }

    // ─── 17. ADMIN: LOW STOCK NOTIFICATION ────────────────────────────

    public function lowStockNotification($productId, $variantId = null) {
        // Fetch product with translation
        $product = $this->db->fetchOne("
            SELECT p.*, pt.name
            FROM products p
            LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
            WHERE p.id = :id
        ", ['id' => $productId]);
        
        if (!$product) return false;

        $variant = null;
        if ($variantId) {
            $variant = $this->db->fetchOne("SELECT * FROM product_variants WHERE id = :id", ['id' => $variantId]);
        }

        $adminEmail = $this->getAdminEmail();
        
        $productName = htmlspecialchars($product['name'] ?? 'Product');
        $sku = $variant ? $variant['sku'] : $product['sku'];
        $displayName = $productName . ' (SKU: ' . $sku . ')';
        
        if ($variant) {
            // Try to get variant attributes for better name
            $variantInfo = $this->db->fetchAll("
                SELECT pt.name as attr_name, po.name as option_name
                FROM product_variant_options pvo
                JOIN product_attributes pt ON pvo.attribute_id = pt.id
                JOIN product_attribute_options po ON pvo.option_id = po.id
                WHERE pvo.variant_id = :v_id
            ", ['v_id' => $variantId]);
            
            if ($variantInfo) {
                $opts = [];
                foreach ($variantInfo as $info) {
                    $opts[] = $info['attr_name'] . ': ' . $info['option_name'];
                }
                $displayName .= ' [' . implode(', ', $opts) . ']';
            }
        }

        $stock = $variant ? (int)$variant['stock_quantity'] : (int)$product['stock_quantity'];
        $threshold = (int)$product['low_stock_threshold'];
        $viewUrl = $this->siteUrl . 'admin/products/product-edit.php?id=' . $productId;

        $status = ($stock <= 0) ? 'OUT OF STOCK' : 'LOW STOCK';
        $color = ($stock <= 0) ? '#dc2626' : '#f59e0b';

        $typeLabel = $variant ? 'Variant' : 'Product';

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:{$color}15;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#9888;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">{$status} Alert!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">Inventory level for a {$typeLabel} has dropped below the threshold.</p>
</div>

{$this->infoTable([
    $typeLabel => $displayName,
    'Current Stock' => $stock,
    'Threshold' => $threshold,
    'Status' => $status,
])}

<div style="margin:20px 0;padding:14px 18px;background-color:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b;">
    <p style="margin:0;font-size:14px;color:#92400e;">
        <strong>Action Needed:</strong> Please restock this {$typeLabel} to avoid losing sales.
    </p>
</div>

{$this->button('Restock Product', $viewUrl, $color)}
HTML;

        $subject = "[{$status}] {$sku} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Inventory alert: {$sku} is now {$status}.");
        return $this->send($adminEmail, $subject, $body);
    }

    /**
     * Helper to generate order items table with images for emails
     * Uses absolute URL so images display in email clients (Gmail, etc.)
     */
    private function getOrderItemsTable($orderId, $currencyHelper, $currencyCode = 'AED') {
        $items = $this->db->fetchAll(
            "SELECT oi.*, p.main_image as product_image FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :oid",
            ['oid' => $orderId]
        );

        if (empty($items)) return '';

        // Use absolute URL for email images - must be publicly accessible (not localhost)
        $baseUrl = rtrim($this->siteUrl, '/');
        
        // Fix for local testing: Use live site URL if available so images render in email clients (Gmail, etc.)
        if (strpos($baseUrl, 'localhost') !== false) {
            if (defined('HOSTINGER_SITE_URL')) {
                $baseUrl = rtrim(HOSTINGER_SITE_URL, '/');
            } elseif (strpos($this->siteName, 'Cat Eye') !== false) {
                // Fallback for this brand
                $baseUrl = 'https://cateyesbeauty.com';
            }
        }

        $itemsHtml = '';
        foreach ($items as $item) {
            $imgFile = $item['product_image'] ?? null;
            $usePlaceholder = false;
            
            if (empty($imgFile)) {
                $imgRow = $this->db->fetchOne("SELECT image_path FROM product_images WHERE product_id = :pid ORDER BY sort_order ASC LIMIT 1", ['pid' => $item['product_id']]);
                if ($imgRow && !empty($imgRow['image_path'])) {
                    $imgFile = $imgRow['image_path'];
                } else {
                    $imgFile = 'placeholder.jpg';
                    $usePlaceholder = true;
                }
            }
            
            // Clean up the image filename
            $cleanImgFile = ltrim(str_replace(['assets/uploads/products/', 'assets/images/'], '', $imgFile), '/');
            
            // Construct absolute URL
            if ($usePlaceholder) {
                $imgUrl = $baseUrl . '/assets/images/' . $cleanImgFile;
            } else {
                $imgUrl = $baseUrl . '/assets/uploads/products/' . $cleanImgFile;
            }

            $itemName = htmlspecialchars($item['product_name']);
            $qty = (int)$item['quantity'];
            $price = $currencyHelper->formatForCurrency($item['product_price'], $currencyCode);
            $total = $currencyHelper->formatForCurrency($item['total_price'], $currencyCode);
            $itemsHtml .= <<<HTML
<tr>
    <td style="padding:14px 18px;border-bottom:1px solid #f0ebe4;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="width:56px;padding-right:14px;">
                <img src="{$imgUrl}" alt="{$itemName}" width="56" height="56" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:2px solid #e8e0d5;display:block;">
            </td>
            <td>
                <strong style="font-size:14px;color:#2d2520;font-family:Georgia,serif;">{$itemName}</strong><br>
                <span style="font-size:12px;color:#9a8e80;font-family:Georgia,serif;">Qty: {$qty} &times; {$price}</span>
            </td>
        </tr>
        </table>
    </td>
    <td style="padding:14px 18px;border-bottom:1px solid #f0ebe4;text-align:right;font-size:14px;font-weight:600;color:#2d2520;font-family:Georgia,serif;">
        {$total}
    </td>
</tr>
HTML;
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;border:1px solid #e8e0d5;border-radius:10px;overflow:hidden;">
<tr>
    <td style="padding:12px 18px;background-color:#faf7f2;font-size:11px;font-weight:600;color:#9a8e80;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;border-bottom:2px solid #d4af37;">Items Ordered</td>
    <td style="padding:12px 18px;background-color:#faf7f2;font-size:11px;font-weight:600;color:#9a8e80;text-align:right;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;border-bottom:2px solid #d4af37;">Amount</td>
</tr>
{$itemsHtml}
</table>
HTML;
    }

    /**
     * Helper to generate booking items table for multiple bookings email
     */
    private function getBookingItemsTable($bookings, $currencyHelper) {
        if (empty($bookings)) return '';

        $baseUrl = rtrim($this->siteUrl, '/');
        if (strpos($baseUrl, 'localhost') !== false) {
             if (defined('HOSTINGER_SITE_URL')) {
                $baseUrl = rtrim(HOSTINGER_SITE_URL, '/');
            } elseif (strpos($this->siteName, 'Cat Eye') !== false) {
                 $baseUrl = 'https://cateyesbeauty.com';
            }
        }

        $itemsHtml = '';
        foreach ($bookings as $booking) {
            $serviceName = htmlspecialchars($booking['service_name'] ?? 'Service');
            $bookingDate = date('l, F j, Y', strtotime($booking['booking_date']));
            $bookingTime = date('g:i A', strtotime($booking['booking_time']));
            $amount = $currencyHelper->formatForCurrency($booking['total_amount'] ?? 0, $booking['currency_code'] ?? 'AED');
            $imgFile = $booking['service_image'] ?? null;
            
            $imgUrl = $baseUrl . '/assets/images/placeholder.jpg';
            if ($imgFile) {
                $cleanImgFile = ltrim(str_replace(['assets/uploads/services/', 'assets/images/'], '', $imgFile), '/');
                $imgUrl = $baseUrl . '/assets/uploads/services/' . $cleanImgFile;
            }

            $itemsHtml .= <<<HTML
<tr>
    <td style="padding:14px 18px;border-bottom:1px solid #f0ebe4;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="width:56px;padding-right:14px;">
                <img src="{$imgUrl}" alt="{$serviceName}" width="56" height="56" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:2px solid #e8e0d5;display:block;">
            </td>
            <td>
                <strong style="font-size:14px;color:#2d2520;font-family:Georgia,serif;">{$serviceName}</strong><br>
                <span style="font-size:12px;color:#9a8e80;font-family:Georgia,serif;">{$bookingDate} at {$bookingTime}</span>
            </td>
        </tr>
        </table>
    </td>
    <td style="padding:14px 18px;border-bottom:1px solid #f0ebe4;text-align:right;font-size:14px;font-weight:600;color:#2d2520;font-family:Georgia,serif;">
        {$amount}
    </td>
</tr>
HTML;
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;border:1px solid #e8e0d5;border-radius:10px;overflow:hidden;">
<tr>
    <td style="padding:12px 18px;background-color:#faf7f2;font-size:11px;font-weight:600;color:#9a8e80;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;border-bottom:2px solid #d4af37;">Bookings</td>
    <td style="padding:12px 18px;background-color:#faf7f2;font-size:11px;font-weight:600;color:#9a8e80;text-align:right;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;border-bottom:2px solid #d4af37;">Amount</td>
</tr>
{$itemsHtml}
</table>
HTML;
    }

    public function multiBookingConfirmation($bookings) {
        if (empty($bookings)) return false;
        
        $firstBooking = $bookings[0];
        $custName = htmlspecialchars($firstBooking['customer_name']);
        $bookingsUrl = $this->siteUrl . 'account/mybookings.php';
        $emailTo = $firstBooking['customer_email'];

        if (!class_exists('Currency')) require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);
        
        $itemsTableHtml = $this->getBookingItemsTable($bookings, $currencyHelper);
        
        // Calculate grand total and summary info
        $grandTotal = 0;
        $currCode = $firstBooking['currency_code'] ?? 'AED';
        foreach ($bookings as $b) {
            $grandTotal += (float)($b['total_amount'] ?? 0);
        }
        $totalFormatted = $currencyHelper->formatForCurrency($grandTotal, $currCode);

        $nextStepsTitle = __('next_steps', 'What happens next?');
        $step1 = __('step_1', 'Our team will review your booking requests shortly');
        $step2 = __('step_2', 'You will receive a confirmation for each appointment shortly');
        $step3 = __('step_3', 'Please save this email for your reference');

        $content = <<<HTML
<div style="text-align:center;margin-bottom:28px;">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#f0ebe4 0%,#faf7f2 100%);border-radius:50%;display:inline-block;line-height:80px;margin-bottom:16px;border:2px solid #d4af37;">
        <span style="font-size:38px;">&#10004;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#2d2520;font-size:26px;font-family:Georgia,'Times New Roman',serif;font-weight:400;letter-spacing:1px;">Bookings Received</h2>
    <p style="margin:0;color:#9a8e80;font-size:15px;font-family:Georgia,serif;font-style:italic;">Your multiple service bookings have been confirmed, {$custName}.</p>
</div>

<!-- Grand Total Card -->
<div style="background:linear-gradient(135deg,#faf7f2 0%,#f5f0e8 100%);padding:16px 24px;border-radius:10px;text-align:center;margin-bottom:28px;border:1px solid #e8e0d5;border-left:4px solid #d4af37;">
    <p style="margin:0 0 4px;font-size:11px;color:#9a8e80;text-transform:uppercase;letter-spacing:2px;font-family:Georgia,serif;">Total Amount Paid</p>
    <p style="margin:0;font-size:24px;font-weight:700;color:{$this->primaryColor};letter-spacing:1px;font-family:Georgia,'Times New Roman',serif;">{$totalFormatted}</p>
</div>

{$itemsTableHtml}

<div style="margin:20px 0;padding:16px 20px;background-color:#f0fdf4;border-radius:8px;border-left:4px solid #22c55e;">
    <p style="margin:0;font-size:14px;color:#166534;">
        <strong>{$nextStepsTitle}</strong><br>
        &bull; {$step1}<br>
        &bull; {$step2}<br>
        &bull; {$step3}
    </p>
</div>

{$this->button('View My Bookings', $bookingsUrl, '#059669')}

<p style="font-size:14px;color:#6b7280;text-align:center;">
    We look forward to seeing you at your scheduled times!
</p>
HTML;

        $subject = "Bookings Received — {$totalFormatted} - {$this->siteName}";
        $body = $this->wrapTemplate($content, "Your multiple service bookings have been confirmed.");
        return $this->send($emailTo, $subject, $body);
    }

    /**
     * Helper to generate detailed booking items table for admin emails
     */
    private function getAdminBookingItemsTable($bookings, $currencyHelper) {
        if (empty($bookings)) return '';

        $baseUrl = rtrim($this->siteUrl, '/');
        $itemsHtml = '';
        foreach ($bookings as $booking) {
            $serviceName = htmlspecialchars($booking['service_name'] ?? 'Service');
            $bookingNum = htmlspecialchars($booking['booking_number']);
            $bookingDate = date('l, F j, Y', strtotime($booking['booking_date']));
            $bookingTime = date('g:i A', strtotime($booking['booking_time']));
            $amount = $currencyHelper->formatForCurrency($booking['total_amount'] ?? 0, $booking['currency_code'] ?? 'AED');
            $viewUrl = $this->siteUrl . 'admin/service-bookings/index.php?search=' . $bookingNum;

            $itemsHtml .= <<<HTML
<tr>
    <td style="padding:14px 18px;border-bottom:1px solid #f0ebe4;vertical-align:top;">
        <strong style="font-size:14px;color:#2d2520;font-family:Georgia,serif;">#{$bookingNum} - {$serviceName}</strong><br>
        <span style="font-size:12px;color:#9a8e80;font-family:Georgia,serif;">{$bookingDate} at {$bookingTime}</span>
    </td>
    <td style="padding:14px 18px;border-bottom:1px solid #f0ebe4;text-align:right;vertical-align:top;font-size:13px;font-weight:600;color:#2d2520;font-family:Georgia,serif;">
        {$amount}<br>
        <a href="{$viewUrl}" style="font-size:11px;color:{$this->primaryColor};text-decoration:none;">View Detail &rarr;</a>
    </td>
</tr>
HTML;
        }

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;border:1px solid #e8e0d5;border-radius:10px;overflow:hidden;">
<tr>
    <td style="padding:12px 18px;background-color:#faf7f2;font-size:11px;font-weight:600;color:#9a8e80;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;border-bottom:2px solid #d4af37;">Booking Details</td>
    <td style="padding:12px 18px;background-color:#faf7f2;font-size:11px;font-weight:600;color:#9a8e80;text-align:right;text-transform:uppercase;letter-spacing:1.5px;font-family:Georgia,serif;border-bottom:2px solid #d4af37;">Amount</td>
</tr>
{$itemsHtml}
</table>
HTML;
    }

    public function notifyAdminMultiBooking($bookings) {
        if (empty($bookings)) return false;
        
        $adminEmail = $this->getAdminEmail();
        $firstBooking = $bookings[0];
        $custName = htmlspecialchars($firstBooking['customer_name']);
        $custEmail = htmlspecialchars($firstBooking['customer_email']);
        $custPhone = htmlspecialchars($firstBooking['customer_phone'] ?? 'N/A');

        if (!class_exists('Currency')) require_once INCLUDES_PATH . 'Currency.php';
        $currencyHelper = new Currency($this->db);
        
        $itemsTableHtml = $this->getAdminBookingItemsTable($bookings, $currencyHelper);
        
        // Calculate grand total 
        $grandTotal = 0;
        $currCode = $firstBooking['currency_code'] ?? 'AED';
        foreach ($bookings as $b) {
            $grandTotal += (float)($b['total_amount'] ?? 0);
        }
        $totalFormatted = $currencyHelper->formatForCurrency($grandTotal, $currCode);

        $content = <<<HTML
<div style="text-align:center;margin-bottom:24px;">
    <div style="width:72px;height:72px;background-color:#fef3c7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:36px;">&#128197;</span>
    </div>
    <h2 style="margin:0 0 8px;color:#1f2937;font-size:24px;">Multiple Bookings Received!</h2>
    <p style="margin:0;color:#6b7280;font-size:15px;">A new appointment session has been confirmed with multiple services from {$custName}.</p>
</div>

<div style="margin:20px 0;padding:18px 22px;background-color:#faf7f2;border-radius:10px;border:1px solid #e8e0d5;">
    <p style="margin:0;font-size:14px;color:#2d2520;font-family:Georgia,serif;line-height:1.6;">
        <strong style="color:#d4af37;">Customer Information:</strong><br>
        <strong>Name:</strong> {$custName}<br>
        <strong>Email:</strong> {$custEmail}<br>
        <strong>Phone:</strong> {$custPhone}<br>
        <strong>Grand Total:</strong> <span style="color:{$this->primaryColor};font-weight:700;">{$totalFormatted}</span>
    </p>
</div>

{$itemsTableHtml}

<div style="margin:20px 0;padding:14px 18px;background-color:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b;">
    <p style="margin:0;font-size:14px;color:#92400e;">
        <strong>Action Required:</strong> Please review these appointments in the admin dashboard.
    </p>
</div>

{$this->button('Manage All Bookings', $this->siteUrl . 'admin/service-bookings/index.php', '#f59e0b')}
HTML;

        $subject = "Multiple Bookings! {$custName} — Total: {$totalFormatted}";
        $body = $this->wrapTemplate($content, "New multi-service booking session from {$custName}.");
        return $this->send($adminEmail, $subject, $body);
    }
}
