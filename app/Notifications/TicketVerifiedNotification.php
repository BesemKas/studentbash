<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Ticket $ticket
    ) {
        //
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
        return (new MailMessage)
            ->subject('Your Ticket Has Been Verified - Student Bash')
            ->greeting('Hello ' . $this->ticket->holder_name . '!')
            ->line('Great news! Your payment has been verified and your ticket is now active.')
            ->line('**Ticket Details:**')
            ->line('Ticket Type: ' . $this->ticket->ticket_type)
                            ->line('Payment Reference: ' . $this->ticket->payment_ref)
            ->line('QR Code: ' . $this->ticket->qr_code_text)
            ->line('Your ticket is now ready to use at the event. Please keep your QR code safe and present it at the gate.')
            ->action('View My Tickets', url('/my-tickets'))
            ->line('Thank you for your purchase! We look forward to seeing you at the Student Bash.');
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
            'ticket_type' => $this->ticket->ticket_type,
                    'payment_ref' => $this->ticket->payment_ref,
        ];
    }
}
