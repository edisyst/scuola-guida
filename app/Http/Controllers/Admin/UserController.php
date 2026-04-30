<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private array $permissions = [
        'create_question',
        'edit_question',
        'delete_question',
        'manage_question',
    ];

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $users = User::latest()->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'permissions' => $this->permissions
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,editor,viewer',
            'permissions' => 'nullable|array',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);
        clearAdminBadgesCache();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente creato');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'permissions' => $this->permissions
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => "required|email|unique:users,email,$user->id",
            'password' => 'nullable|min:6',
            'role' => 'required|in:admin,editor,viewer',
            'permissions' => 'nullable|array',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        clearAdminBadgesCache();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente aggiornato');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Non puoi eliminarti');
        }

        $user->delete();
        clearAdminBadgesCache();

        return back()->with('success', 'Utente eliminato');
    }
}
