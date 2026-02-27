<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $users = User::where('id', '!=', $me->id)
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Chat', [
            'users' => $users,
        ]);
    }
}
