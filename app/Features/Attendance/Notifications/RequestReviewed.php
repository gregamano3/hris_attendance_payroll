<?php

namespace App\Features\Attendance\Notifications;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Shared\Format;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the employee that their leave or overtime request was decided.
 */
class RequestReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest|OvertimeRequest $request) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->request->loadMissing($this->request instanceof LeaveRequest ? ['reviewer', 'leaveType'] : ['reviewer']);
        $decision = strtolower($this->request->status->label());
        [$what, $url] = $this->request instanceof LeaveRequest
            ? [sprintf('%s from %s to %s', $this->request->leaveType->name, $this->request->start_date->format('M j'), $this->request->end_date->format('M j, Y')), route('leaves.index')]
            : [sprintf('%s overtime on %s', Format::minutes($this->request->minutes), $this->request->date->format('M j, Y')), route('overtime.index')];

        return (new MailMessage)
            ->subject('Your request was '.$decision)
            ->when($this->request->status === LeaveStatus::Rejected, fn (MailMessage $m) => $m->error())
            ->line("Your {$what} was {$decision} by {$this->request->reviewer?->name}.")
            ->when($this->request->review_remarks, fn (MailMessage $m) => $m->line('Remarks: '.$this->request->review_remarks))
            ->action('View my requests', $url);
    }
}
