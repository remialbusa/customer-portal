@component('mail::message')
{{-- Email header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ $appName }}
@endcomponent
@endslot

# You're invited to the service portal

Hello,

@if ($invite->isSnapshot())
Your hospital — **{{ $accountName }}** — is on file in our customer records, and a portal account has been reserved for you. This is the place to open and track service tickets, chat with the technician assigned to your equipment, and read signed service reports when the work is done.

@component('mail::panel')
**Snapshot of your account details**

| | |
|---|---|
| **Hospital / Account** | {{ $accountName }} |
| **Branch** | {{ $branch ?: '—' }} |
| **Region** | {{ $region ?: '—' }} |
| **Email on file** | {{ $email }} |

If any of the above is wrong, please reply to this email and we'll fix it before you register.
@endcomponent
@else
A portal account has been reserved for **{{ $email }}**. This is the place to open and track service tickets, chat with the technician assigned to your equipment, and read signed service reports when the work is done.

When you click the link below, you'll be asked for your hospital / organization name, branch, region, and your primary equipment details. We'll create your customer record on the spot, and your account is ready to go.
@endif

## What to do next

Click the button below to {{ $invite->isSnapshot() ? 'create your password and finish setting up' : 'start your registration' }} your account. The link is **single-use** and expires on **{{ $expiresAt->format('l, F j, Y \a\t g:i A') }}** ({{ $expiresAt->diffForHumans() }} from now).

@component('mail::button', ['url' => $url, 'color' => 'primary'])
{{ $invite->isSnapshot() ? 'Create your account' : 'Start registration' }}
@endcomponent

If the button doesn't work, paste this link into your browser:

[{{ $url }}]({{ $url }})

---

@if (! empty($invitedByName))
This invitation was issued by **{{ $invitedByName }}** on your service team. If you weren't expecting it, please ignore this email — the link will expire on its own and no account will be created.
@else
If you weren't expecting this invitation, you can safely ignore it — the link will expire on its own and no account will be created.
@endif

Need help? Reply to this email and your service coordinator will follow up.

Thanks,
**The {{ $appName }} team**

@slot('subcopy')
@component('mail::subcopy')
@if ($invite->isSnapshot())
You're receiving this email because a service coordinator at {{ $appName }} registered your hospital in our customer records.
@else
You're receiving this email because you (or someone at your organization) requested a registration link for this address.
@endif
The link above is your one-time registration key. If you'd rather not receive future emails, reply with "unsubscribe" and we'll remove you from the list.
@endcomponent
@endslot

@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ $appName }}. All rights reserved.
@endcomponent
@endslot
@endcomponent
