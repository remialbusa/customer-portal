<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\MondayClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index(Request $request, MondayClient $monday): View
    {
        $user = $request->user();

        // Pull tickets from Monday that belong to this customer.
        $tickets = $monday->ticketsForCustomer($user->email);

        // Newest first
        usort($tickets, fn ($a, $b) => strcmp($b['id'], $a['id']));

        return view('customer.dashboard', [
            'user'    => $user,
            'tickets' => $tickets,
        ]);
    }
}
