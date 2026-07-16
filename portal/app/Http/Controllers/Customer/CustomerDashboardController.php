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

        // Compute ticket stats for the dashboard stat cards.
        $stats = ['total' => count($tickets), 'open' => 0, 'in_progress' => 0, 'resolved' => 0];
        foreach ($tickets as $t) {
            $s = strtolower((string) ($t['status_text'] ?? ''));
            if (str_contains($s, 'resolved') || str_contains($s, 'closed') || str_contains($s, 'done') || str_contains($s, 'complete')) {
                $stats['resolved']++;
            } elseif (str_contains($s, 'progress')) {
                $stats['in_progress']++;
            } elseif ($s !== '' && $s !== '—') {
                $stats['open']++;
            }
        }

        return view('customer.dashboard', [
            'user'    => $user,
            'tickets' => $tickets,
            'stats'   => $stats,
        ]);
    }
}
