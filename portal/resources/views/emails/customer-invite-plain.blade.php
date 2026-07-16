You're invited to the {{ $appName }} service portal
====================================================

Hello,

Your hospital — {{ $accountName }} — is now on file in our customer records, and a portal account has been reserved for you. This is the place to open and track service tickets, chat with the technician assigned to your equipment, and read signed service reports when the work is done.

Snapshot of your account details
--------------------------------
  Hospital / Account : {{ $accountName }}
  Branch             : {{ $branch ?: '—' }}
  Region             : {{ $region ?: '—' }}
  Email on file      : {{ $email }}

If any of the above is wrong, please reply to this email and we'll fix it before you register.

What to do next
---------------
Create your password and finish setting up the account by visiting the link below. The link is single-use and expires on {{ $expiresAt->format('l, F j, Y \a\t g:i A') }} ({{ $expiresAt->diffForHumans() }} from now).

  {{ $url }}

@if (! empty($invitedByName))
This invitation was issued by {{ $invitedByName }} on your service team. If you weren't expecting it, please ignore this email — the link will expire on its own and no account will be created.
@else
If you weren't expecting this invitation, you can safely ignore it — the link will expire on its own and no account will be created.
@endif

Need help? Reply to this email and your service coordinator will follow up.

Thanks,
The {{ $appName }} team

--
© {{ date('Y') }} {{ $appName }}. All rights reserved.
You're receiving this email because a service coordinator at {{ $appName }} registered your hospital in our customer records. The link above is your one-time registration key. If you'd rather not receive future emails, reply with "unsubscribe" and we'll remove you from the list.
