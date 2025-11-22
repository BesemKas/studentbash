<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TicketVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Ticket $ticket
    ) {
        // Log when notification is queued
        Log::info('[TicketVerifiedNotification] Notification queued', [
            'ticket_id' => $this->ticket->id,
            'queue_connection' => config('queue.default'),
            'email' => $this->ticket->email,
            'holder_name' => $this->ticket->holder_name,
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Log when notification is being processed (job is running)
        Log::info('[TicketVerifiedNotification] Processing notification - generating email', [
            'ticket_id' => $this->ticket->id,
            'email' => $this->ticket->email,
            'holder_name' => $this->ticket->holder_name,
            'queue_connection' => config('queue.default'),
            'mail_mailer' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),
            'mail_from' => config('mail.from.address'),
        ]);

        $mailMessage = (new MailMessage)
            ->subject('Your Ticket Has Been Verified - Student Bash')
            ->greeting('Hello ' . $this->ticket->holder_name . '!')
            ->line('Great news! Your payment has been verified and your ticket is now active.')
            ->line('**Ticket Details:**')
            ->line('Ticket Type: ' . ($this->ticket->ticketType ? $this->ticket->ticketType->name : 'Unknown'))
            ->line('Event: ' . ($this->ticket->event ? $this->ticket->event->name : 'Unknown'))
                            ->line('Payment Reference: ' . $this->ticket->payment_ref)
            ->line('QR Code: ' . $this->ticket->qr_code_text)
            ->line('Your ticket is now ready to use at the event. Please keep your QR code safe and present it at the gate.')
            ->action('View My Tickets', url('/my-tickets'))
            ->line('Thank you for your purchase! We look forward to seeing you at the Student Bash.');

        // Log when email message is successfully created
        Log::info('[TicketVerifiedNotification] Email message created successfully', [
            'ticket_id' => $this->ticket->id,
            'email' => $this->ticket->email,
        ]);

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_type' => $this->ticket->ticketType ? $this->ticket->ticketType->name : 'Unknown',
            'event' => $this->ticket->event ? $this->ticket->event->name : 'Unknown',
            'payment_ref' => $this->ticket->payment_ref,
        ];
    }
}
