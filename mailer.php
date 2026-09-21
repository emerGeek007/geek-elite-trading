<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/config.php';

function send_inscription_email(array $data): array
{
    $config = formulaire_config();
    $subject = 'Nouvelle inscription — Geek Elite Trading';
    $body = build_text_body($data);
    $html = build_html_body($data);

    if (formulaire_smtp_ready()) {
        $smtp = send_via_smtp($config, $subject, $body, $html, $data);
        if ($smtp['ok']) {
            return $smtp;
        }
    }

    $formsubmit = send_via_formsubmit($config['to_email'], $subject, $body, $data);
    if ($formsubmit['ok'] || !empty($formsubmit['activation_required'])) {
        return $formsubmit;
    }

    if (isset($smtp)) {
        return $smtp;
    }

    return $formsubmit;
}

function send_via_smtp(array $config, string $subject, string $body, string $html, array $data): array
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = preg_replace('/\s+/', '', (string) $config['smtp_pass']);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) $config['smtp_port'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['smtp_user'], $config['from_name']);
        $mail->addAddress($config['to_email']);
        $mail->addReplyTo($data['email'], trim($data['prenom'] . ' ' . $data['nom']));
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $body;
        $mail->send();

        return ['ok' => true, 'channel' => 'smtp'];
    } catch (Exception $exception) {
        return [
            'ok' => false,
            'channel' => 'smtp',
            'error' => $mail->ErrorInfo ?: $exception->getMessage(),
        ];
    }
}

function send_via_formsubmit(string $to, string $subject, string $body, array $data): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'channel' => 'formsubmit', 'error' => 'cURL indisponible.'];
    }

    $ch = curl_init('https://formsubmit.co/ajax/' . rawurlencode($to));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: http://localhost',
            'Referer: http://localhost/formulaire/',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'name' => trim($data['prenom'] . ' ' . $data['nom']),
            'email' => $data['email'],
            'phone' => $data['phone'],
            'date' => $data['date_fr'],
            'message' => $body,
            '_subject' => $subject,
            '_template' => 'table',
            '_captcha' => 'false',
            '_replyto' => $data['email'],
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 25,
    ]);

    $result = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($result === false) {
        return ['ok' => false, 'channel' => 'formsubmit', 'error' => $error ?: 'Échec réseau.'];
    }

    $payload = json_decode($result, true);
    $success = $payload['success'] ?? false;
    $ok = $success === true || $success === 'true';
    $message = (string) ($payload['message'] ?? '');
    $activation = stripos($message, 'activation') !== false;

    return [
        'ok' => $ok,
        'channel' => 'formsubmit',
        'activation_required' => $activation,
        'error' => $ok ? '' : ($message ?: ('HTTP ' . $code)),
    ];
}

function build_text_body(array $data): string
{
    return implode("\n", [
        'Nouvelle demande de formation — Geek Elite Trading',
        '',
        'Nom : ' . $data['nom'],
        'Prénom : ' . $data['prenom'],
        'Email : ' . $data['email'],
        'Téléphone : ' . $data['phone'],
        'Date souhaitée : ' . $data['date_fr'],
        'Reçu le : ' . $data['received_at'],
    ]);
}

function build_html_body(array $data): string
{
    $nom = htmlspecialchars($data['nom'], ENT_QUOTES, 'UTF-8');
    $prenom = htmlspecialchars($data['prenom'], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8');
    $dateFr = htmlspecialchars($data['date_fr'], ENT_QUOTES, 'UTF-8');
    $receivedAt = htmlspecialchars($data['received_at'], ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<body style="font-family:Arial,sans-serif;background:#0b1119;color:#f4efe4;padding:24px;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:auto;background:#101822;border:1px solid #d4af37;border-radius:12px;">
    <tr>
      <td style="padding:24px;">
        <h2 style="margin:0 0 16px;color:#d4af37;">Geek Elite Trading</h2>
        <p style="margin:0 0 20px;">Nouvelle inscription à la formation trading.</p>
        <p><strong>Nom :</strong> {$nom}</p>
        <p><strong>Prénom :</strong> {$prenom}</p>
        <p><strong>Email :</strong> {$email}</p>
        <p><strong>Téléphone :</strong> {$phone}</p>
        <p><strong>Date souhaitée :</strong> {$dateFr}</p>
        <p style="color:#b7b0a3;font-size:13px;">Reçu le {$receivedAt}</p>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
