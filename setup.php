<?php
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Cette page est réservée à l’ordinateur local.');
}

$message = '';
$type = '';
$ready = formulaire_smtp_ready();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = preg_replace('/\s+/', '', (string) ($_POST['smtp_pass'] ?? ''));
    if ($password === '') {
        $message = 'Collez le mot de passe d’application Gmail.';
        $type = 'err';
    } else {
        $export = var_export([
            'to_email' => 'emersonfrancois77@gmail.com',
            'smtp_user' => 'emersonfrancois77@gmail.com',
            'smtp_pass' => $password,
        ], true);
        file_put_contents(__DIR__ . '/config.local.php', "<?php\nreturn {$export};\n");

        $test = send_via_smtp(formulaire_config(), 'Email test — Geek Elite Trading', "Ceci est un email test. L'envoi SMTP fonctionne.", '<p>Ceci est un email test. L’envoi SMTP fonctionne.</p>', [
            'nom' => 'Test',
            'prenom' => 'Geek Elite',
            'email' => 'emersonfrancois77@gmail.com',
            'phone' => '',
            'date_fr' => date('d/m/Y'),
            'received_at' => date('d/m/Y H:i'),
        ]);

        if (!empty($test['ok'])) {
            $ready = true;
            $message = 'Configuration enregistrée. Un email test vient d’être envoyé à emersonfrancois77@gmail.com.';
            $type = 'ok';
        } else {
            $message = 'Mot de passe enregistré, mais l’email test a échoué : ' . ($test['error'] ?? 'erreur SMTP');
            $type = 'err';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activer les emails — Geek Elite Trading</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="bg-grid" aria-hidden="true"></div>
  <div class="bg-glow" aria-hidden="true"></div>
  <main class="section">
    <div class="form-wrap" style="grid-template-columns:1fr; max-width:680px; margin:40px auto;">
      <div>
        <p class="eyebrow">Configuration email</p>
        <h2>Recevoir les inscriptions en temps réel</h2>
        <p>XAMPP ne peut pas envoyer d’email tout seul. Il faut autoriser Gmail une seule fois.</p>
        <ol class="checklist" style="margin-top:22px;">
          <li>Ouvrez <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noreferrer">myaccount.google.com/apppasswords</a></li>
          <li>Connectez-vous avec <strong>emersonfrancois77@gmail.com</strong></li>
          <li>Si Google le demande, activez la validation en 2 étapes</li>
          <li>Créez un mot de passe d’application nommé <strong>Geek Elite Trading</strong></li>
          <li>Collez les 16 lettres ci-dessous, puis enregistrez</li>
        </ol>
        <p style="margin-top:18px;color:var(--muted);">Statut actuel : <strong><?php echo $ready ? 'SMTP activé' : 'SMTP non configuré'; ?></strong></p>
      </div>
      <form method="post" class="form" style="margin-top:8px;">
        <label>
          Mot de passe d’application Gmail
          <input type="password" name="smtp_pass" autocomplete="off" required placeholder="xxxx xxxx xxxx xxxx">
        </label>
        <button type="submit" class="btn btn-gold btn-full">Enregistrer et envoyer un email test</button>
        <?php if ($message !== ''): ?>
          <p class="form-message <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <p class="form-note">Cette page fonctionne seulement sur cet ordinateur. Le mot de passe reste en local.</p>
      </form>
    </div>
  </main>
</body>
</html>
