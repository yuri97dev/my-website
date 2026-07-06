<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue("emails");
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Verifique seu endereço de e-mail")
            ->greeting("Olá!")
            ->line("Clique no botão abaixo para verificar seu endereço de e-mail.")
            ->action("Verificar e-mail", $this->verificationUrl($notifiable))
            ->line("Se você não criou uma conta, nenhuma ação é necessária.");
    }
}
