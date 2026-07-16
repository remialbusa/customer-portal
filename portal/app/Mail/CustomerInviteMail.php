<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\CustomerInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for customer registration invites.
 *
 * Renders the markdown template `resources/views/emails/customer-invite.blade.php`
 * with the snapshot fields from the invite row (account name, branch,
 * region, address) so the email is a self-contained record of what the
 * customer should expect — no live lookups at send time.
 *
 * The actual URL is the only dynamic piece; it's signed by the
 * CustomerInvite token and is what the recipient clicks.
 */
class CustomerInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CustomerInvite $invite,
        public readonly string $url,
        public readonly ?string $invitedByName = null,
    ) {}

    public function envelope(): Envelope
    {
        // Snapshot invites carry the hospital name; fall back to a
        // generic subject for open (self-serve) invites where we
        // don't know the hospital yet.
        $subject = $this->invite->account_name
            ? "Your invitation to the {$this->invite->account_name} service portal"
            : 'Your invitation to the BioTechnical Solutions service portal';
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name', 'BioTechnical Solutions')
            ),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.customer-invite',
            with: [
                'invite'        => $this->invite,
                'url'           => $this->url,
                'accountName'   => $this->invite->account_name,
                'branch'        => $this->invite->branch,
                'region'        => $this->invite->region,
                'address'       => $this->invite->address,
                'email'         => $this->invite->email,
                'expiresAt'     => $this->invite->expires_at,
                'invitedByName' => $this->invitedByName,
                'appName'       => config('app.name', 'BioTechnical Solutions'),
            ],
        );
    }

    /**
     * Plain-text fallback for email clients that can't render HTML.
     */
    public function build()
    {
        return $this->view('emails.customer-invite-plain');
    }
}
