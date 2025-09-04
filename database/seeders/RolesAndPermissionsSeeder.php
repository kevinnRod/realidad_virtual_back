<?php
namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RolesAndPermissionsSeeder extends Seeder
{
public function run(): void
{
// Crear roles
$admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
$researcher = Role::firstOrCreate(['name' => 'researcher', 'guard_name' => 'web']);
$student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);


// Crear permisos
$perms = [
'manage_users',
'manage_studies',
'run_sessions',
'view_results',
'fill_surveys',
];
foreach ($perms as $p) {
Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
}


// Mapear permisos por rol
$admin->syncPermissions(Permission::all());
$researcher->syncPermissions(Permission::whereIn('name', ['manage_studies','run_sessions','view_results'])->get());
$student->syncPermissions(Permission::whereIn('name', ['fill_surveys'])->get());


// Opcional: asignar admin al usuario 1 si existe
$userModel = app(\App\Models\User::class);
if ($user = $userModel->query()->find(1)) {
$user->assignRole('admin');
}
}
}