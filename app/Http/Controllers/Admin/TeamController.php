<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Super admins manage who has console access and with which role. */
class TeamController extends Controller
{
    public function index()
    {
        $admins = User::whereNotNull('admin_role')->orderByRaw("CASE WHEN admin_role = 'super_admin' THEN 0 ELSE 1 END")->orderBy('name')->get();

        return view('admin.team', ['admins' => $admins, 'roles' => config('admin.roles'), 'descriptions' => config('admin.role_descriptions')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in(array_keys(config('admin.roles')))],
            'reason' => 'required|string|min:3|max:300',
        ], ['email.exists' => 'No member has that email. They need an account first.']);
        $user = User::where('email', strtolower($data['email']))->firstOrFail();
        abort_if(! $user->isActive(), 422, 'Only active members can be admins.');
        $this->assign($user, $data['role'], $data['reason']);

        return back()->with('status', "{$user->name} is now {$user->adminRoleLabel()}.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['nullable', Rule::in(array_keys(config('admin.roles')))], 'reason' => 'required|string|min:3|max:300']);
        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own role.');
        if ($user->admin_role === 'super_admin' && ($data['role'] ?? null) !== 'super_admin') {
            abort_if(User::where('admin_role', 'super_admin')->count() <= 1, 422, 'Keep at least one super admin.');
        }
        $this->assign($user, $data['role'] ?? null, $data['reason']);

        return back()->with('status', $data['role'] ? 'Role updated.' : "{$user->name} no longer has admin access.");
    }

    private function assign(User $user, ?string $role, string $reason): void
    {
        $old = $user->admin_role;
        $user->forceFill(['admin_role' => $role])->save();
        AuditLog::record('admin.role_changed', $user, $reason, ['from' => $old, 'to' => $role]);
    }
}
