<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
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
        abort_unless(auth()->user()->canCreateUser(), 403);

        return view('admin.users.create', $this->permissionViewData());
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canCreateUser(), 403);

        $data = $request->validate([
            'name'          => 'required',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'role'          => 'required|in:' . implode(',', array_keys(User::ROLES)),
            'permissions'   => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', User::allPermissions()),
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);
        clearAdminBadgesCache();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente creato');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->canEditUser(), 403);

        return view('admin.users.edit', array_merge(
            ['user' => $user],
            $this->permissionViewData()
        ));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->canEditUser(), 403);

        $data = $request->validate([
            'name'          => 'required',
            'email'         => "required|email|unique:users,email,$user->id",
            'password'      => 'nullable|min:6',
            'role'          => 'required|in:' . implode(',', array_keys(User::ROLES)),
            'permissions'   => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', User::allPermissions()),
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // se nessun permesso → salva array vuoto, non null
        $data['permissions'] = $data['permissions'] ?? [];

        $user->update($data);
        clearAdminBadgesCache();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente aggiornato');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->canDeleteUser(), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Non puoi eliminarti');
        }

        $user->delete();
        clearAdminBadgesCache();

        return back()->with('success', 'Utente eliminato');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function permissionViewData(): array
    {
        return [
            'entities'     => User::ENTITIES,
            'actions'      => User::ACTIONS,
            'entityLabels' => User::LABELS,
            'actionLabels' => User::ACTION_LABELS,
            'roles'        => User::ROLES,
        ];
    }
}
