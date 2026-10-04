<?php

namespace App\Features\Payroll\Notifications;

use App\Features\Payroll\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to each employee when the payroll run containing their payslip is
 * finalized. Amounts stay behind login.
 */
class PayslipReleased extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payslip $payslip) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $run = $this->payslip->loadMissing('run')->run;

        return (new MailMessage)
            ->subject("Your payslip for {$run->period()->label()} is available")
            ->line("{$run->name} has been released. Pay date: {$run->pay_date->format('M j, Y')}.")
            ->action('View payslip', route('payroll.payslips.show', $this->payslip))
            ->line('For your privacy, amounts are only shown after you sign in.');
    }
}
