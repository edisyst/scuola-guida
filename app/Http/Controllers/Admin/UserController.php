<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserService $service) {}

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

    public function store(StoreUserRequest $request)
    {
        $this->service->create($request->validated());

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

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->service->update($user, $request->validated());

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

        return back()->with('success', 'Utente eliminato');
    }

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
