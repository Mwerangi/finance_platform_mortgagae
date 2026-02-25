<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the 8 default roles
        $roles = [
            [
                'name' => 'Provider Super Admin',
                'slug' => 'provider-super-admin',
                'description' => 'Full system access across all institutions',
                'is_system_role' => true,
                'permissions' => ['*'], // All permissions
            ],
            [
                'name' => 'Institution Admin',
                'slug' => 'institution-admin',
                'description' => 'Full access within their institution',
                'is_system_role' => true,
                'permissions' => [
                    'users.*',
                    'institutions.view',
                    'institutions.edit',
                    'institutions.manage-branding',
                    'loan-products.*',
                    'customers.*',
                    'applications.*',
                    'loans.*',
                    'repayments.*',
                    'collections.*',
                    'reports.*',
                    'audit.*',
                ],
            ],
            [
                'name' => 'Credit Manager',
                'slug' => 'credit-manager',
                'description' => 'Oversees credit operations and approves overrides',
                'is_system_role' => true,
                'permissions' => [
                    'users.view',
                    'loan-products.view',
                    'customers.*',
                    'applications.*',
                    'loans.view',
                    'loans.view-schedule',
                    'repayments.view',
                    'repayments.view-metrics',
                    'reports.*',
                ],
            ],
            [
                'name' => 'Credit Officer',
                'slug' => 'credit-officer',
                'description' => 'Processes loan applications and makes credit decisions',
                'is_system_role' => true,
                'permissions' => [
                    'loan-products.view',
                    'customers.view',
                    'customers.create',
                    'customers.edit',
                    'customers.manage-kyc',
                    'applications.*',
                    'loans.view',
                    'loans.view-schedule',
                    'repayments.view',
                    'reports.view',
                    'reports.export',
                ],
            ],
            [
                'name' => 'Supervisor',
                'slug' => 'supervisor',
                'description' => 'Supervises credit officers and approves exceptions',
                'is_system_role' => true,
                'permissions' => [
                    'users.view',
                    'loan-products.view',
                    'customers.view',
                    'customers.manage-kyc',
                    'applications.view',
                    'applications.run-analytics',
                    'applications.run-eligibility',
                    'applications.make-decisions',
                    'applications.approve-overrides',
                    'loans.view',
                    'loans.view-schedule',
                    'repayments.view',
                    'repayments.view-metrics',
                    'reports.*',
                ],
            ],
            [
                'name' => 'Collections Manager',
                'slug' => 'collections-manager',
                'description' => 'Oversees collections operations and strategy',
                'is_system_role' => true,
                'permissions' => [
                    'customers.view',
                    'loans.view',
                    'loans.view-schedule',
                    'repayments.*',
                    'collections.*',
                    'reports.view',
                    'reports.export',
                    'reports.view-dashboards',
                ],
            ],
            [
                'name' => 'Collections Officer',
                'slug' => 'collections-officer',
                'description' => 'Performs collections activities on delinquent loans',
                'is_system_role' => true,
                'permissions' => [
                    'customers.view',
                    'loans.view',
                    'loans.view-schedule',
                    'repayments.view',
                    'collections.view-queue',
                    'collections.log-actions',
                    'collections.manage-ptp',
                ],
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Reviews audit logs and compliance reports (read-only)',
                'is_system_role' => true,
                'permissions' => [
                    'users.view',
                    'institutions.view',
                    'loan-products.view',
                    'customers.view',
                    'applications.view',
                    'loans.view',
                    'loans.view-schedule',
                    'repayments.view',
                    'repayments.view-metrics',
                    'collections.view-queue',
                    'collections.view-reports',
                    'reports.*',
                    'audit.*',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            // Assign permissions
            if (in_array('*', $permissions)) {
                // Assign all permissions
                $role->permissions()->sync(Permission::all()->pluck('id'));
            } else {
                $permissionIds = [];
                foreach ($permissions as $permissionPattern) {
                    if (str_ends_with($permissionPattern, '.*')) {
                        // Wildcard: assign all permissions in group
                        $group = str_replace('.*', '', $permissionPattern);
                        $groupPermissions = Permission::where('group', $group)->pluck('id');
                        $permissionIds = array_merge($permissionIds, $groupPermissions->toArray());
                    } else {
                        // Specific permission
                        $permission = Permission::where('slug', $permissionPattern)->first();
                        if ($permission) {
                            $permissionIds[] = $permission->id;
                        }
                    }
                }
                $role->permissions()->sync(array_unique($permissionIds));
            }

            $this->command->info("Role '{$role->name}' created with " . $role->permissions()->count() . " permissions.");
        }

        $this->command->info('Roles seeded successfully!');
    }
}
