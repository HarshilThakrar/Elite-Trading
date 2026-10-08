$users = \App\Models\User::with('roles')->get();
foreach ($users as $user) {
    echo "Email: " . $user->email . " | Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
}
