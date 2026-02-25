<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * MailerService — wrapper su Symfony Mailer configurato da variabili d'ambiente.
 *
 * Uso minimale:
 *   $mailer = MailerService::forSuite('backoffice');
 *   $mailer->send($email);
 *
 * Per il reset password:
 *   $mailer->sendResetPassword($email, $name, $url, $appName);
 */
class MailerService
{
    private Mailer $mailer;
    private Address $from;
    private string $suite;

    private function __construct(Mailer $mailer, Address $from, string $suite)
    {
        $this->mailer = $mailer;
        $this->from   = $from;
        $this->suite  = $suite;
    }

    /**
     * Crea un'istanza configurata per la suite indicata (backoffice | frontoffice).
     * Legge le variabili d'ambiente:
     *   {SUITE}_MAILER_DSN, {SUITE}_MAILER_FROM_ADDRESS, {SUITE}_MAILER_FROM_NAME
     */
    public static function forSuite(string $suite = 'backoffice'): self
    {
        $prefix = strtoupper($suite);

        $dsn       = (string)(getenv("{$prefix}_MAILER_DSN")       ?: 'null://null');
        $fromAddr  = (string)(getenv("{$prefix}_MAILER_FROM_ADDRESS") ?: 'noreply@example.com');
        $fromName  = (string)(getenv("{$prefix}_MAILER_FROM_NAME")    ?: 'GIL');

        $transport = Transport::fromDsn($dsn);
        $mailer    = new Mailer($transport);
        $from      = new Address($fromAddr, $fromName);

        return new self($mailer, $from, $suite);
    }

    /**
     * Invia un messaggio Email già composto.
     */
    public function send(Email $email): void
    {
        if (!$email->getFrom()) {
            $email->from($this->from);
        }
        $this->mailer->send($email);
    }

    /**
     * Invia l'email di reset password con template HTML inline.
     */
    public function sendResetPassword(
        string $toEmail,
        string $toName,
        string $resetUrl,
        string $appName = '',
        int    $expiresMinutes = 60
    ): void {
        if ($appName === '') {
            $appName = (string)(getenv('APP_ENTITY_NAME') ?: 'GIL');
        }

        $htmlBody = $this->renderResetTemplate($toName, $resetUrl, $appName, $expiresMinutes);
        $textBody = $this->renderResetTemplatePlain($toName, $resetUrl, $appName, $expiresMinutes);

        $email = (new Email())
            ->from($this->from)
            ->to(new Address($toEmail, $toName))
            ->subject("[$appName] Reimposta la tua password")
            ->html($htmlBody)
            ->text($textBody);

        $this->mailer->send($email);
    }

    // -------------------------------------------------------------------------
    // Template inline (HTML + testo)
    // -------------------------------------------------------------------------

    private function renderResetTemplate(
        string $toName,
        string $resetUrl,
        string $appName,
        int    $expiresMinutes
    ): string {
        $safeToName  = htmlspecialchars($toName,   ENT_QUOTES, 'UTF-8');
        $safeAppName = htmlspecialchars($appName,  ENT_QUOTES, 'UTF-8');
        $safeMins    = (int)$expiresMinutes;
        $safeUrl     = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $greeting    = $safeToName !== ''
            ? 'Ciao, <strong>' . $safeToName . '</strong>,'
            : 'Ciao,';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Reimposta la tua password - {$safeAppName}</title>
          <style>
            body { margin:0; padding:0; background:#f4f7fa; font-family: 'Helvetica Neue', Arial, sans-serif; color:#333; }
            .wrapper { max-width:560px; margin:40px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); }
            .header { background:#0b3d91; padding:28px 32px; text-align:center; }
            .header h1 { margin:0; color:#fff; font-size:20px; font-weight:600; letter-spacing:.5px; }
            .body { padding:32px; }
            .body p { line-height:1.6; margin:0 0 16px; }
            .btn { display:inline-block; margin:8px 0 24px; padding:14px 32px; background:#0b3d91; color:#fff; text-decoration:none; border-radius:6px; font-weight:600; font-size:15px; }
            .notice { font-size:13px; color:#777; border-top:1px solid #eee; margin-top:24px; padding-top:16px; }
            .footer { background:#f4f7fa; padding:18px 32px; text-align:center; font-size:12px; color:#999; }
          </style>
        </head>
        <body>
          <div class="wrapper">
            <div class="header">
              <h1>{$safeAppName}</h1>
            </div>
            <div class="body">
              <p>{$greeting}</p>
              <p>abbiamo ricevuto una richiesta di reset password per il tuo account. Clicca il pulsante qui sotto per impostare una nuova password:</p>
              <p style="text-align:center;">
                <a href="{$safeUrl}" class="btn">Reimposta password</a>
              </p>
              <p>Se il pulsante non funziona, copia e incolla questo link nel browser:</p>
              <p style="word-break:break-all; font-size:13px; color:#555;">{$safeUrl}</p>
              <div class="notice">
                <p>Questo link è valido per <strong>{$safeMins} minuti</strong> ed è monouso.
                Se non hai richiesto il reset, puoi ignorare questa email — il tuo account non subirà modifiche.</p>
              </div>
            </div>
            <div class="footer">
              &copy; {$safeAppName} · Email generata automaticamente, non rispondere.
            </div>
          </div>
        </body>
        </html>
        HTML;
    }

    private function renderResetTemplatePlain(
        string $toName,
        string $resetUrl,
        string $appName,
        int    $expiresMinutes
    ): string {
        $greeting = $toName !== '' ? "Ciao, $toName," : "Ciao,";
        return <<<TEXT
        {$greeting}

        abbiamo ricevuto una richiesta di reset password per il tuo account su {$appName}.

        Usa il link qui sotto per impostare una nuova password (valido {$expiresMinutes} minuti):

        {$resetUrl}

        Se non hai richiesto il reset, ignora questa email.

        -- {$appName}
        TEXT;
    }
}
