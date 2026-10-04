<?php

namespace App\Features\Attendance\Notifications;

use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\OvertimeRequest;
use App\Shared\Format;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells approvers that a leave or overtime request is waiting for them.
 */
class RequestSubmitted extends Notification implements ShouldQueue
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
        // Queued notifications are rehydrated without their relations.
        $this->request->loadMissing($this->request instanceof LeaveRequest ? ['employee', 'leaveType'] : ['employee']);
        $employee = $this->request->employee->full_name;

        if ($this->request instanceof LeaveRequest) {
            $what = sprintf('%s, %s to %s (%s day(s))', $this->request->leaveType->name,
                $this->request->start_date->format('M j'), $this->request->end_date->format('M j, Y'), (float) $this->request->days);
            $url = route('leaves.review');
            $subject = "Leave request from {$employee}";
        } else {
            $what = sprintf('%s overtime on %s', Format::minutes($this->request->minutes), $this->request->date->format('M j, Y'));
            $url = route('overtime.review');
            $subject = "Overtime request from {$employee}";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line("{$employee} filed a request: {$what}.")
            ->when($this->request->reason, fn (MailMessage $m) => $m->line('Reason: '.$this->request->reason))
            ->action('Review requests', $url);
    }
}
