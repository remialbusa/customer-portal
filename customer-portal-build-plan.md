# Customer Portal Build Plan
**Stack:** Laravel 11 + Livewire + Monday.com API + Pusher
**Hosting:** cPanel Linux Shared Hosting
**Goal:** Customer portal + TSP work portal + executive KPI dashboard, all backed by Monday.com boards

---

## Build Status (working session phases)

> These are the working-session phases, tracked in the VS Code todo list. They do
> not map 1:1 to the build plan chapters below; they are the user-visible
> milestones as the portal is being built out. Build-plan chapters that
> are *partially* covered by a working phase are noted in the deliverable column.

| Working phase | Deliverable | Status |
|---|---|---|
| 1 | TSP sees assigned tickets from Monday (Monday integration) | ✅ |
| 2 | Customer ticket list + create form (Customer side) | ✅ |
| 2.1 | End User relation auto-created for new customers | ✅ |
| 3 | Real-time chat via Pusher (customer ↔ TSP) | ✅ (covers build-plan Phase 4 chat section) |
| 4 | Internal notes on tickets (TSP-only, long-text column) | ✅ (covers build-plan Phase 4 internal-notes section) |
| 5 | Time tracker (start/pause/stop, per-ticket + per-user totals) | ✅ |
| 6 | Customer invitations (Breeze invite flow) | ⏳ pending (covers build-plan Phase 2.5) |
| 7 | cPanel deploy + CI/CD lite | ⏳ pending (covers build-plan Phase 7 + 8) |

**Current focus:** Phase 5 — Time tracker.

**Live server (dev only):** `php artisan serve --host=127.0.0.1 --port=8000`
**Pusher:** app_id 2168370, cluster ap1
**Monday board (Tickets):** id 5029331350
**Internal Notes column on Tickets:** `long_text_mm4f8ve0` (long_text type)

---

## Context Summary

This project started as a Next.js/Node.js plan but the cPanel shared host does not support Node.js. The conversation pivoted to **Laravel + Livewire**, which runs natively on PHP-based shared hosting, keeps the Monday.com API token secure on the server side, and gives a modern reactive UI through Livewire. Real-time chat is handled by **Pusher** (external WebSocket service) so the shared hosting environment does not need to maintain long-running connections.

**Key security rule:** The Monday.com API token must never reach the browser. It stays in Laravel's `.env` file on the server, and the browser only talks to Laravel routes, which proxy the calls to Monday.com.

---

## User Roles & Access Matrix

The portal serves three distinct user roles. All three log into the **same application** but are routed to different areas based on role.

| Role | Who | Login URL | Lands On | Sees |
|---|---|---|---|---|
| **Customer** | Hospital / medtech staff | `/login` → `/dashboard` | Customer dashboard | Their own tickets, create new tickets, chat with TSP, profile settings |
| **TSP (FSE)** | Field Service Engineer | `/login` → `/tsp/dashboard` | TSP dashboard | Assigned tickets, internal notes, customer chat, schedule, personal KPIs |
| **TSP (ITS)** | I.T. Specialist | `/login` → `/tsp/dashboard` | TSP dashboard | Same as FSE — same screens, role determines reporting line |
| **Admin / Executive** | Management | `/login` → `/admin/kpi` | KPI dashboard | Hero stats, MTTR, MTBF, drill-down filters, team performance |

### Key architecture points

- **One login page**, three homepages. After authentication, Laravel redirects by role.
- **Single `users` table** with a `role` enum column: `customer`, `fse`, `its`, `admin`.
- **Authorization via Laravel Policies** — TSPs can only update tickets assigned to them; customers can only view their own tickets.
- **Two separate chat surfaces per ticket** — public (customer + TSP) and private (TSP only, stored in a Monday column not as an update).

### Internal notes vs customer chat (critical distinction)

| Surface | Visibility | Monday storage | Use case |
|---|---|---|---|
| Customer chat | Customer + TSP | `create_update` (default update) | "I'm heading to your site now" |
| Internal notes | TSP only | A dedicated long-text column on the ticket | "Possible hardware fault, ordering parts" |

Internal notes are written to a Monday column, never as a public update. This is the single most important TSP-side feature — without it, technicians either leak technical details to customers or stop taking notes altogether.

---

## TSP Portal — Features

### Dashboard (`/tsp/dashboard`)
- Open tickets count, in-progress count, SLA breaches count
- Today's schedule (calendar view)
- Personal performance: own MTTR, CSAT, FCR for current month
- Quick links to my assigned tickets

### My Tickets (`/tsp/tickets`)
- List of all tickets assigned to this TSP
- Filters: status, priority, customer, SLA breach
- Sort: priority, SLA countdown, customer, created date
- Inline quick actions: start, pause, mark resolved
- Color-coded SLA timer (green / yellow / red)

### Ticket Detail (`/tsp/tickets/{id}`)
- Customer info (read-only)
- Machine / asset info (from Assets board link)
- Status timeline
- **Two chat panels:**
  - Customer chat (visible to both sides)
  - Internal notes (TSP-only, stored in Monday column)
- Time tracker (start / stop when on-site)
- Resolution form: root cause, parts used, fix description
- Photo / file attachments
- Reopen toggle

### Schedule (`/tsp/schedule`)
- Week view of upcoming site visits
- Travel time estimates between sites
- Mark availability / time off
- Pulls from Monday calendar items

### My Performance (`/tsp/performance`)
- Personal MTTR, CSAT, FCR
- Tickets resolved per week
- Comparison to team average (no rankings, just context)
- Personal SLA compliance

---

## Monday.com Board Structure

You need **three boards** in Monday.com, linked together.

### Board 1: Customer Details (already exists)
Tracks hospital / clinic accounts.

| Column | Type | Notes |
|---|---|---|
| Customer Name | Text | |
| Contact Person | Text | |
| Email | Email | Used to link to user account |
| Phone | Phone | |
| Address | Text | |
| Active Contracts | Link → Tickets | Reverse link |

### Board 2: Tickets (already exists)
The core ticket tracking board.

**Existing columns to map and capture:**

| Field Group | Columns |
|---|---|
| **Identifiers** | Ticket ID (auto-text), Subject, Description |
| **Customer & Location** | Customer (link → Customer Details), Site / Branch, Contact person, Address |
| **Product / Domain** | Product type (Machine / LIS / Both), Product model, Serial number, Software version |
| **Assignment** | Primary TSP (person), Primary team (FSE / ITS, auto-fill), Secondary TSP (person, optional), Region |
| **Classification** | Service type (Installation / Preventive / Corrective / Calibration / Training / Consultation), Priority (Critical / High / Medium / Low), Severity, Issue category |
| **Status** | Status (New → Assigned → In Progress → On Hold → Resolved → Closed) |
| **Timestamps** | Created at, Assigned at, First response at, On-site arrival at, Resolved at, Closed at, Total resolution time (formula), On-hold time (formula) |
| **Resolution** | Root cause, Action taken, Parts replaced, Reopen count, First Contact Resolution (checkbox) |
| **Cross-Support** | Cross-domain ticket (checkbox), Supporting team, Time spent by primary, Time spent by supporting, Joint resolution (checkbox) |
| **Customer Feedback** | CSAT rating (1–5), Customer comment, Would recommend |
| **Internal** | Asset (link → Assets board), Is Failure (checkbox, auto-true when Service type = Corrective), Internal Notes (long-text column for TSP-only notes) |

### Board 3: Assets / Machines (NEW — required for MTBF)

Asset-level tracking. Each physical machine your company services gets one record.

| Column | Type | Notes |
|---|---|---|
| Asset ID | Auto-text | e.g. `MC-0001` |
| Customer | Link → Customer Details | Who owns it |
| Machine Model | Text | e.g. "Mindray BC-6800" |
| Serial Number | Text | Required for asset-level tracking |
| Product Type | Dropdown: Machine / LIS | Maintains FSE / ITS axis |
| Install Date | Date | Start of MTBF observation window |
| Warranty End | Date | Filters whether to include in MTBF |
| Status | Dropdown: Active / Retired / Decommissioned | Stop counting failures after retirement |
| Location | Text | Customer site, building, room |
| Assigned Team | Dropdown: FSE / ITS | Default owner team |

**Why this is required:** MTBF is an asset-level metric, not a ticket-level metric. To compute it you need to know which machine each ticket belongs to, when the machine was installed, and which tickets count as failures (corrective only, not PMs / calibrations).

---

## Phase 0 — Accounts and Access (Do This First)

### Email your hosting support and ask:

1. What PHP version is available? (need 8.2 or higher)
2. Is SSH access enabled on the plan? If yes, is it jailed or full?
3. Can cron jobs be set up? (most shared hosts allow this)
4. What are the max execution time and memory limit for PHP?
5. Is outbound HTTPS (port 443) to `api.monday.com` and `pusher.com` allowed?
6. Can additional FTP/SFTP users be created with restricted folder access?
7. What is the upload file size limit? (affects ticket attachments)

### In parallel, gather:

- [ ] **Monday.com API token** — go to `monday.com` → Avatar → Developers → My access tokens → Generate. Store in a password manager.
- [ ] **Customer Details board ID** — `_______` (from the board URL: `monday.com/boards/<ID>`)
- [ ] **Tickets board ID** — `_______`
- [ ] **Assets / Machines board ID** — `_______` (create the board first)
- [ ] **Internal Notes column ID** on Tickets board — the long-text column for TSP-only notes
- [ ] **Asset link column ID** on Tickets board — the link to the Assets board
- [ ] **All other column IDs** for both boards — click each column header in Monday and copy the API-friendly column ID
- [ ] **Pusher account** — sign up at `pusher.com` → create a Channels app → choose the closest region. Save: `app_id`, `key`, `secret`, `cluster`.
- [ ] **Domain** — confirm ownership and enable Let's Encrypt SSL in cPanel (Security → SSL/TLS Status → Run AutoSSL).
- [ ] **Email account** — create `noreply@yourdomain.com` mailbox in cPanel (used for invitation emails).

---

## Phase 1 — Local Dev Setup

### Toolchain Installation

**Windows:**
- [ ] PHP 8.2+: install Laravel Herd (https://herd.laravel.com)
- [ ] Composer: getcomposer.org/download
- [ ] Node.js 18+ LTS: nodejs.org
- [ ] Git: git-scm.com
- [ ] Code editor: VS Code (recommended)

**Mac:**
- PHP via Herd or `brew install php@8.2`
- Same as Windows for the rest

**Linux (Ubuntu/Debian):**
```bash
sudo apt install php8.2 php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql composer nodejs npm git
```

### Create the Laravel Project

```bash
cd ~/projects
composer create-project laravel/laravel portal
cd portal
```

### Install Required Packages

```bash
# Authentication scaffolding
composer require laravel/breeze --dev
php artisan breeze:install livewire
npm install && npm run build

# Pusher (server side)
composer require pusher/pusher-php-server

# Pusher + Echo (client side)
npm install --save-dev laravel-echo pusher-js

# Authorization scaffolding (for ticket policies)
composer require --dev laravel/pint
```

### Configure `.env` (local)

```env
APP_NAME=CustomerPortal
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite

# Monday.com
MONDAY_API_TOKEN=your_token_here
MONDAY_CUSTOMERS_BOARD_ID=your_id_here
MONDAY_TICKETS_BOARD_ID=your_id_here
MONDAY_ASSETS_BOARD_ID=your_id_here

# Pusher (server)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# Pusher (client, picked up by Vite)
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### Verify

```bash
php artisan serve
```
Open `http://localhost:8000` — the Laravel welcome page should load.

---

## Phase 2 — Roles & User Schema

Add the role system and TSP-relevant fields to the user table.

### 2.1 — Add role columns

```bash
php artisan make:migration add_role_to_users_table --table=users
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['customer', 'fse', 'its', 'admin'])
              ->default('customer')
              ->after('phone');
        $table->string('monday_person_id')->nullable()->after('role');
        $table->string('team')->nullable();
        $table->string('region')->nullable();
        $table->json('skills')->nullable();
    });
}
```

```bash
php artisan migrate
```

### 2.2 — Add helper methods to User model

In `app/Models/User.php`, add to `$fillable`:
```php
'role', 'monday_person_id', 'team', 'region', 'skills',
```

Add methods:
```php
public function isCustomer(): bool { return $this->role === 'customer'; }
public function isTsp(): bool { return in_array($this->role, ['fse', 'its']); }
public function isFse(): bool { return $this->role === 'fse'; }
public function isIts(): bool { return $this->role === 'its'; }
public function isAdmin(): bool { return $this->role === 'admin'; }
```

### 2.3 — Update login redirect by role

In `app/Http/Controllers/Auth/AuthenticatedSessionController.php`:
```php
return match($user->role) {
    'admin'  => redirect()->route('admin.kpi'),
    'fse', 'its' => redirect()->route('tsp.dashboard'),
    default => redirect()->route('dashboard'),
};
```

---

## Phase 2.5 — Customer Invitation System

Public `/register` is disabled. Only TSPs and admins can invite customers via a secure token link.

### Why invitation-only

| Risk of self-service | Why invitation-only fixes it |
|---|---|
| Random people can create accounts | Only TSP/admin can invite |
| No link to a real Monday customer | Invitation is tied to a Monday customer record |
| Email squatting | Email must be unique and pre-validated |
| Compliance gap on medical data | Every account has an inviter on record |

### 2.5.1 — Create the invitations table

```bash
php artisan make:model Invitation -m
```

Migration:
```php
public function up(): void
{
    Schema::create('invitations', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->string('name');
        $table->string('phone')->nullable();
        $table->string('role')->default('customer');
        $table->string('monday_customer_id')->nullable();
        $table->string('token')->unique();
        $table->timestamp('expires_at');
        $table->timestamp('accepted_at')->nullable();
        $table->foreignId('invited_by')->constrained('users');
        $table->timestamps();
    });
}
```

### 2.5.2 — Add `status` to users

```bash
php artisan make:migration add_status_to_users_table --table=users
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('status', ['pending', 'active', 'suspended'])
              ->default('pending')
              ->after('role');
    });
}
```

```bash
php artisan migrate
```

### 2.5.3 — Disable public registration

In `routes/auth.php` (created by Breeze), remove or comment out:

```php
// Route::get('register', [RegisteredUserController::class, 'create'])
//             ->name('register');
// Route::post('register', [RegisteredUserController::class, 'store']);
```

### 2.5.4 — Block pending users from logging in

In `AuthenticatedSessionController::store`, before authenticating:

```php
if ($user->status !== 'active') {
    auth()->logout();
    return back()->withErrors([
        'email' => 'Your account is ' . $user->status . '. Contact your administrator.',
    ]);
}
```

### 2.5.5 — Invitation controller

```bash
php artisan make:controller CustomerInvitationController
```

`app/Http/Controllers/CustomerInvitationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Mail\CustomerInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CustomerInvitationController extends Controller
{
    public function create(Request $request, \App\Services\MondayClient $monday)
    {
        abort_unless(auth()->user()->isTsp() || auth()->user()->isAdmin(), 403);

        $boardId = (int) config('services.monday.customers_board_id');
        $customers = $monday->getBoardItems($boardId);

        return view('customers.invite', compact('customers'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isTsp() || auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'monday_customer_id' => 'required|string',
        ]);

        $token = Str::random(48);

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'role' => 'customer',
            'monday_customer_id' => $validated['monday_customer_id'],
            'token' => Hash::make($token),
            'expires_at' => Carbon::now()->addHours(72),
            'invited_by' => auth()->id(),
        ]);

        Mail::to($validated['email'])->send(
            new CustomerInvitationMail($invitation, $token)
        );

        return back()->with('success', "Invitation sent to {$validated['email']}");
    }
}
```

### 2.5.6 — Email template

```bash
php artisan make:mail CustomerInvitationMail
```

`app/Mail/CustomerInvitationMail.php`:

```php
<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $plainToken
    ) {}

    public function build()
    {
        return $this->subject('You\'re invited to the Customer Portal')
                    ->markdown('emails.customer-invitation');
    }
}
```

`resources/views/emails/customer-invitation.blade.php`:

```blade
@component('mail::message')
# Welcome to the Customer Portal

Hi {{ $invitation->name }},

You've been invited to access your service tickets, chat with our technical team, and manage your account.

This invitation expires in **72 hours**.

@component('mail::button', ['url' => url('/accept-invitation/' . $plainToken)])
Accept Invitation & Set Password
@endcomponent

If you didn't expect this email, you can safely ignore it.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

### 2.5.7 — Accept invitation controller

```bash
php artisan make:controller AcceptInvitationController
```

`app/Http/Controllers/AcceptInvitationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AcceptInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = Invitation::where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->get()
            ->first(fn($inv) => Hash::check($token, $inv->token));

        abort_unless($invitation, 404, 'Invalid or expired invitation.');

        return view('auth.accept-invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function store(Request $request, string $token)
    {
        $invitation = Invitation::where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->get()
            ->first(fn($inv) => Hash::check($token, $inv->token));

        abort_unless($invitation, 404, 'Invalid or expired invitation.');

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $invitation->name,
            'email' => $invitation->email,
            'phone' => $validated['phone'] ?? $invitation->phone,
            'password' => Hash::make($validated['password']),
            'role' => $invitation->role,
            'monday_person_id' => $invitation->monday_customer_id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome! Your account is ready.');
    }
}
```

`resources/views/auth/accept-invitation.blade.php`:

```blade
<x-guest-layout>
    <h2 class="text-2xl font-bold mb-4">Welcome, {{ $invitation->name }}!</h2>
    <p class="mb-6 text-gray-600">
        Set your password to activate your account.
    </p>

    <form method="POST" action="{{ url('/accept-invitation/' . $token) }}">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium">Email</label>
            <input type="email" value="{{ $invitation->email }}" disabled
                   class="mt-1 block w-full bg-gray-100 border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Phone (optional)</label>
            <input type="text" name="phone" value="{{ $invitation->phone }}"
                   class="mt-1 block w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Password</label>
            <input type="password" name="password" required
                   class="mt-1 block w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">At least 8 characters.</p>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                   class="mt-1 block w-full border rounded px-3 py-2">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Activate Account
        </button>
    </form>
</x-guest-layout>
```

### 2.5.8 — Invite UI for TSP / Admin

`resources/views/customers/invite.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Invite Customer</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded shadow">
                @if(session('success'))
                    <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('customers.invite.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium">Customer (from Monday)</label>
                        <select name="monday_customer_id" required class="mt-1 block w-full border rounded px-3 py-2">
                            <option value="">Select customer...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium">Contact Name</label>
                        <input type="text" name="name" required
                               class="mt-1 block w-full border rounded px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium">Email</label>
                        <input type="email" name="email" required
                               class="mt-1 block w-full border rounded px-3 py-2">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium">Phone (optional)</label>
                        <input type="text" name="phone"
                               class="mt-1 block w-full border rounded px-3 py-2">
                    </div>

                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Send Invitation
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
```

### 2.5.9 — Invitation routes

In `routes/web.php`:

```php
use App\Http\Controllers\CustomerInvitationController;
use App\Http\Controllers\AcceptInvitationController;

// Public routes (no auth)
Route::get('/accept-invitation/{token}', [AcceptInvitationController::class, 'show'])
    ->name('invitation.show');
Route::post('/accept-invitation/{token}', [AcceptInvitationController::class, 'store'])
    ->name('invitation.accept');

// Authenticated routes (TSP and admin only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customers/invite', [CustomerInvitationController::class, 'create'])
        ->name('customers.invite');
    Route::post('/customers/invite', [CustomerInvitationController::class, 'store'])
        ->name('customers.invite.store');
});
```

### 2.5.10 — Mail configuration

**Local dev** — use Mailpit (catches emails without sending):

```bash
# In another terminal:
mailpit
```

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourcompany.com"
MAIL_FROM_NAME="${APP_NAME}"
```

View captured emails at http://localhost:8025.

**Production on cPanel:**

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_account_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Create the `noreply@yourdomain.com` mailbox in cPanel → Email Accounts first.

### 2.5.11 — Edge cases handled

| Case | Handling |
|---|---|
| Customer already has an account | Email validation blocks duplicate invites |
| Invitation link expires after 72 hours | Token check in controller; resend creates new invitation |
| Customer never receives email | TSP can resend; verify SPF/DKIM/DMARC on the sending domain |
| Multiple staff at the same hospital | Each gets their own account, all linked to the same Monday customer |
| Someone tries to brute-force the invite token | 48-char hashed token, only one match per request, rate-limited |
| Customer registers before being invited | `/register` route does not exist |
| Pending user tries to log in | Blocked at the authentication step with a clear error message |

### 2.5.12 — Internal staff onboarding (separate flow)

FSEs, ITSs, and admins are NOT created via invitation. They are seeded or created by an existing admin via a seeder / CLI command. This keeps internal accounts off the public invitation path.

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Juan Dela Cruz',
    'email' => 'juan@yourcompany.com',
    'password' => bcrypt('temporary-password-they-must-change'),
    'role' => 'fse',
    'monday_person_id' => '1234567',
    'team' => 'FSE',
    'region' => 'NCR',
    'status' => 'active',
    'email_verified_at' => now(),
]);
```

Tell them to log in and change their password via Settings.

---

## Phase 3 — Monday.com Integration

Files to create:

- `app/Services/MondayClient.php` — GraphQL wrapper
- `app/Services/MtbfCalculator.php` — MTBF calculation logic
- `app/Http/Controllers/TicketController.php` — customer-side ticket endpoints
- `app/Http/Controllers/CustomerController.php` — customer profile endpoints
- `app/Http/Controllers/Tsp/DashboardController.php` — TSP dashboard
- `app/Http/Controllers/Tsp/TicketController.php` — TSP ticket endpoints (with internal notes)
- `app/Http/Controllers/Tsp/ScheduleController.php` — TSP calendar
- `app/Http/Controllers/Tsp/PerformanceController.php` — TSP personal KPIs
- `app/Http/Controllers/Admin/KpiController.php` — executive dashboard
- `app/Policies/TicketPolicy.php` — authorization rules per role
- `app/Livewire/TicketList.php` — reactive ticket list
- `app/Livewire/TicketCreate.php` — ticket creation form
- `app/Livewire/TicketChat.php` — customer-facing real-time chat
- `app/Livewire/Settings.php` — profile and preferences

---

## Phase 4 — Build the App

### Customer Portal
- [ ] **Auth:** Breeze login / register / email verification
- [ ] **Dashboard:** Livewire component pulling the customer's tickets from Monday, grouped by status
- [ ] **Tickets page:** list view + create form
- [ ] **Ticket detail page:** status, history, customer chat, file attachments
- [ ] **Settings:** profile, password, phone, notification preferences, push updates back to Monday

### TSP Portal (new)
- [ ] **Dashboard:** open count, in-progress count, SLA breaches, today's schedule, personal performance
- [ ] **My Tickets queue:** filters, sort, inline actions, color-coded SLA timer
- [ ] **Ticket detail:** customer info, machine info, status timeline, **customer chat panel**, **internal notes panel** (writes to Monday column, not update), time tracker, resolution form
- [ ] **Schedule:** week calendar, travel estimates, availability toggle
- [ ] **My Performance:** personal MTTR, CSAT, FCR, weekly trend

### Real-Time Chat
- [ ] Broadcast events on Pusher channel `private-ticket.{ticketId}` (private — only ticket participants can subscribe)
- [ ] Two channels per ticket: `customer-chat` and `tsp-internal`
- [ ] Server-side channel authorization in `routes/channels.php`

---

## Phase 5 — Security Hardening (Non-Negotiable)

- [ ] **Force HTTPS** — middleware redirect on all routes; `URL::forceScheme('https')` in production
- [ ] **Laravel Gates and Policies** — every ticket query scoped by role:
  - Customers: only their own tickets (matched by email or linked Monday person)
  - TSPs: only tickets assigned to them (matched by `monday_person_id`)
  - Admins: all tickets
- [ ] **CSRF protection** — Livewire handles this by default, verify all forms
- [ ] **Rate limiting** — `throttle:10,1` on auth and ticket-create routes; `throttle:60,1` on portal pages
- [ ] **Input validation** — Form Request classes for every form
- [ ] **Hide `.env` and `storage/`** — block via `.htaccess`
- [ ] **Rotate Monday token** if it ever leaks; treat it like a password
- [ ] **Log auth events** to `storage/logs` for audit trail
- [ ] **Pusher private channels** — `private-` prefix; authorize subscription server-side in `routes/channels.php`
- [ ] **Internal notes isolation** — internal notes stored in a dedicated Monday column, never written via `create_update`. Customer-side API never returns this column.

---

## Phase 6 — Executive KPI Dashboard Spec

Based on the executive whiteboard sketch.

### Layout

```
[ UPDATE THRU "TSP SEARCH" ]          ← main interaction: pick a TSP to refresh view
  ↓
[Summary] ← [Summary] ← [Summary] ← [Summary]    ← progressive drill-down tiles
                                          ↓
[HERO  stat | stat | stat | stat | stat | stat | stat | stat]
              ↓                              ↓
          [ MTTR ]                       [ MTBF ]
              ↓                              ↓
   TSP / Filter By Service Type    Filter / Machine Model
```

### Hero stats strip — 8 quick-glance numbers

1. Total open tickets
2. Tickets resolved this month
3. Active TSPs (FSE + ITS count)
4. CSAT this month
5. SLA compliance %
6. PM-to-corrective ratio
7. Average FRT (first response time)
8. Average MTTR (current filter)

### Big KPI cards

**MTTR card** (Mean Time To Repair):
- Number: `Xh Ym`
- Sub-breakdown: by Service Type (PM / Corrective / Installation / etc.)
- Trend: vs last month (↑ ↓)
- Filter: dropdowns for TSP + Service Type

**MTBF card** (Mean Time Between Failures):
- Number: `Xd` (days between failures, by model filter)
- Sub-breakdown: top 3 most-failing models
- Trend: vs last quarter
- Filter: dropdown for Machine Model

### Drill-down summary tiles

Reading left-to-right (the way the arrows flow):
- **Tile 1 (deepest):** Machine Model view — e.g. "Mindray BC-6800"
- **Tile 2:** Service Type view — e.g. "Corrective"
- **Tile 3:** TSP view — e.g. "Juan Dela Cruz (FSE)"
- **Tile 4 (broadest):** All TSPs, all models

Click a tile to drill in. The arrows show the trail back out.

### Calculation logic

**MTTR (ticket-level):**
```
MTTR = AVG(resolved_at − created_at) per filter
```

**MTBF (asset-level, per machine model):**
```
For each asset in the model cohort:
  observation_days = (today − install_date) if Active, else (retired_at − install_date)
  
MTBF_days = SUM(observation_days) / SUM(failure_count)
  where failure_count = count(tickets where asset = X AND is_failure = true)
```

**Why cohort-level:** Individual machines don't fail enough to give a stable number. Average across the fleet of the same model. This is how every med-device manufacturer computes it.

### Implementation notes

- [ ] Admin auth middleware (`role === admin`)
- [ ] Separate route group: `/admin/*` with prefix and `admin.` name
- [ ] Nightly Artisan command runs the MTBF calculation and caches results (Monday API has rate limits, don't query live on every page load)
- [ ] Filters are server-side (Livewire reactive), no full page reload

---

## Phase 7 — Deploy to cPanel

### File Layout on the Server

```
/home/<user>/portal/         ← entire Laravel project (NOT publicly accessible)
   app/
   bootstrap/
   config/
   database/
   public/                   ← contents of this go to public_html
   resources/
   routes/
   storage/
   vendor/
   .env

/home/<user>/public_html/portal/   ← only the contents of Laravel's /public folder
   index.php
   .htaccess
   build/   (Vite output)
   ...
```

### Steps

1. Build locally:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```
2. Upload everything **except** `/public` into `/home/<user>/portal/`
3. Upload the **contents of** `/public` into `/home/<user>/public_html/portal/`
4. Edit `public_html/portal/index.php` so the two `require` paths point one directory up:
   ```php
   require __DIR__.'/../../portal/vendor/autoload.php';
   $app = require_once __DIR__.'/../../portal/bootstrap/app.php';
   ```
5. Set folder permissions: `storage/` and `bootstrap/cache/` → `775`
6. Add a cron job in cPanel:
   ```
   * * * * * cd /home/<user>/portal && php artisan schedule:run >> /dev/null 2>&1
   ```
7. In production `.env`:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `QUEUE_CONNECTION=sync` (no queue worker on shared hosting)
8. Run any local migrations (e.g. session/cache tables):
   ```bash
   php artisan migrate --force
   ```

---

## Phase 8 — CI/CD on cPanel (Lite Version)

cPanel shared hosting does not support full CI/CD. The workable approach is **GitHub Actions + SFTP**.

### What cPanel gives natively
- **cPanel Git Version Control** — clone a repo, pull updates from a button. No hooks, no automation.
- **SSH** (if enabled) — manual `git pull` only.
- **Cron jobs** — 1-minute granularity, no daemon watchers.

This is not CI/CD. It's "I can update from Git if I log in and click things."

### Recommended Setup

**1. Push the Laravel project to a private GitHub repo**

`.gitignore` must include:
- `.env`
- `vendor/`
- `node_modules/`
- `public/build/`
- `storage/logs/*`
- `storage/framework/cache/*`
- `storage/framework/sessions/*`
- `storage/framework/views/*`

**2. Create a GitHub Actions workflow** at `.github/workflows/deploy.yml`

On every push to `main`:
- Spin up PHP 8.2 container
- `composer install`
- `npm install && npm run build`
- `php artisan test`
- If green: SFTP the project to cPanel (excluding `vendor/`, `node_modules/`)
- Trigger a remote `php artisan migrate --force` via SSH

**3. cPanel side prep**
- Create a dedicated SFTP user (do not use the main cPanel login)
- Lock it to only the `portal/` directory
- Store credentials as **GitHub Secrets**

### Known Gotchas

| Limitation | Workaround |
|---|---|
| Cannot run `composer install` on cPanel | Build `vendor/` locally, upload it |
| Cannot run `npm run build` on cPanel | Build assets locally, upload `public/build/` |
| No zero-downtime deploys | Accept 10–30s downtime, or swap a `maintenance.html` |
| No easy rollback | Keep last 3 deploys in `_backups/`, restore by renaming |
| `storage/` and `bootstrap/cache/` perms clobbered on upload | Add post-deploy `chmod -R 775` step |
| `.env` must not be in repo | Manually upload once, exclude from SFTP sync after |

### Webhook Alternative (Not Recommended)

GitHub webhook → `deploy.php` on the server → runs `git pull`. Cheaper (no GitHub Actions minutes) but no tests, no builds, and you need a bare git repo on cPanel, which most shared hosts block.

---

## Phase 9 — Test and Go Live

### Functionality
- [ ] Public `/register` is disabled (visiting it returns 404)
- [ ] TSP can go to `/customers/invite` and send an invitation
- [ ] Invitation email is received (test with Mailpit locally)
- [ ] Customer clicks link, sets password, lands on `/dashboard`
- [ ] Invitation expires after 72 hours
- [ ] Pending user cannot log in (clear error message shown)
- [ ] Customer can register, log in, see their tickets
- [ ] Customer can create a ticket → appears in Monday
- [ ] Customer can chat with TSP → updates appear in Monday
- [ ] TSP can log in → lands on `/tsp/dashboard`
- [ ] TSP can only see tickets assigned to them
- [ ] TSP can add internal notes → does NOT show in customer chat
- [ ] TSP can mark ticket resolved → status updates in Monday
- [ ] Admin can log in → lands on `/admin/kpi`
- [ ] Admin sees all 8 hero stats
- [ ] Admin sees MTTR and MTBF cards with correct values
- [ ] Filters (TSP, Service Type, Machine Model) work
- [ ] Non-admin gets 403 on `/admin/*`

### Security
- [ ] HTTPS works (no browser warning)
- [ ] `.env` is not accessible at the URL
- [ ] Customer A cannot see Customer B's tickets (test with two accounts)
- [ ] TSP A cannot update TSP B's tickets
- [ ] Internal notes column is never returned by customer-facing API endpoints
- [ ] Login form is rate-limited (try logging in wrong 10 times)

### Performance
- [ ] Pages load in under 2 seconds
- [ ] No errors in browser console
- [ ] No errors in `storage/logs/laravel.log`

### Backup
- [ ] Schedule weekly backup of `/home/<user>/portal/storage/`
- [ ] Document Monday.com board IDs and column IDs somewhere safe (password manager)

---

## Realistic Time Estimate

| Profile | Estimate |
|---|---|
| Experienced with Laravel | 4–5 weeks part-time |
| New to Laravel | 8–12 weeks part-time |
| Biggest time sink | Monday.com GraphQL column mapping |

The biggest scope adds vs the original 2-role plan:
- TSP portal (queue, ticket detail with internal notes, schedule, performance) — adds ~1.5x the work of the customer portal
- Assets board + MTBF calculation — adds ~1 week
- Role system + policies + login routing — adds ~3 days
- Customer invitation system + email setup — adds ~3 days

---

## Quick Reference: Stack Summary

| Layer | Technology |
|---|---|
| Backend / Frontend | Laravel 11 + Livewire |
| Authentication | Laravel Breeze with role-based redirect |
| Authorization | Laravel Gates & Policies |
| Customer Chat | Pusher (private channel per ticket) |
| Internal Notes | Stored in a dedicated Monday long-text column (NOT in updates) |
| Database / CRM | Monday.com Boards: Customer Details, Tickets, Assets |
| Real-Time Chat | Laravel Echo + Pusher Channels |
| KPI Computation | Nightly Artisan command, cached results |
| Hosting | Linux Shared Hosting with cPanel |
| Deployment | GitHub Actions + SFTP |
| Local Dev | PHP 8.2, Composer, Node 18+, Herd or native LAMP stack |

---

## Subdomain Plan

| Subdomain | Purpose | Auth |
|---|---|---|
| `portal.yourdomain.com` | Customer + TSP portal (one app, role-routed) | Breeze login |
| `admin.yourdomain.com` | Executive KPI dashboard | Same login, admin role required |

Both point to the same Laravel app — the `admin.` subdomain just enforces stricter middleware.