<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] string $token)
    {
        parent::__construct($token);

        $this->onQueue("emails");
    }

    public function toMail($notifiable): MailMessage
    {
        $expire = config("auth.passwords.".config("auth.defaults.passwords").".expire");

        return (new MailMessage)
            ->subject("Redefinir sua senha")
            ->greeting("Olá!")
            ->line("Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.")
            ->action("Redefinir senha", $this->resetUrl($notifiable))
            ->line("Este link expira em {$expire} minutos.")
            ->line("Se você não solicitou a redefinição de senha, nenhuma ação é necessária.");
    }
}
