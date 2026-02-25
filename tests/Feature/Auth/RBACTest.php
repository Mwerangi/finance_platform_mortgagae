<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    /** @test */
    public function user_can_be_assigned_a_role()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $role = Role::where('slug', 'credit-officer')->first();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('credit-officer'));
        $this->assertCount(1, $user->roles);
    }

    /** @test */
    public function user_can_have_multiple_roles()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        
        $creditOfficer = Role::where('slug', 'credit-officer')->first();
        $analyst = Role::where('slug', 'credit-analyst')->first();

        $user->assignRole($creditOfficer);
        $user->assignRole($analyst);

        $this->assertTrue($user->hasRole('credit-officer'));
        $this->assertTrue($user->hasRole('credit-analyst'));
        $this->assertCount(2, $user->roles);
    }

    /** @test */
    public function user_role_can_be_removed()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $role = Role::where('slug', 'credit-officer')->first();

        $user->assignRole($role);
        $this->assertTrue($user->hasRole('credit-officer'));

        $user->removeRole($role);
        $this->assertFalse($user->hasRole('credit-officer'));
    }

    /** @test */
    public function user_inherits_permissions_from_role()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $role = Role::where('slug', 'credit-officer')->first();

        $user->assignRole($role);

        // Credit officer should have application permissions
        $this->assertTrue($user->hasPermission('applications.create'));
        $this->assertTrue($user->hasPermission('applications.view'));
    }

    /** @test */
    public function user_can_check_for_any_permission()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $role = Role::where('slug', 'credit-officer')->first();

        $user->assignRole($role);

        $this->assertTrue($user->hasAnyPermission([
            'applications.create',
            'loans.approve' // Doesn't have this
        ]));
    }

    /** @test */
    public function super_admin_role_has_all_permissions()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $superAdmin = Role::where('slug', 'provider-super-admin')->first();

        $user->assignRole($superAdmin);

        $allPermissions = Permission::all();
        
        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $user->hasPermission($permission->slug),
                "Super Admin should have {$permission->slug} permission"
            );
        }
    }

    /** @test */
    public function institution_admin_cannot_access_provider_features()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $admin = Role::where('slug', 'institution-admin')->first();

        $user->assignRole($admin);

        // Should not have provider-specific permissions
        $this->assertFalse($user->hasPermission('deployments.view'));
        $this->assertFalse($user->hasPermission('institutions.create'));
    }

    /** @test */
    public function credit_officer_cannot_approve_loans()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $officer = Role::where('slug', 'credit-officer')->first();

        $user->assignRole($officer);

        $this->assertFalse($user->hasPermission('applications.approve'));
        $this->assertFalse($user->hasPermission('loans.approve'));
    }

    /** @test */
    public function supervisor_can_approve_applications()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $supervisor = Role::where('slug', 'underwriting-supervisor')->first();

        $user->assignRole($supervisor);

        $this->assertTrue($user->hasPermission('applications.approve'));
        $this->assertTrue($user->hasPermission('applications.reject'));
    }

    /** @test */
    public function collections_officer_has_limited_permissions()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $collector = Role::where('slug', 'collections-officer')->first();

        $user->assignRole($collector);

        // Should have collections permissions
        $this->assertTrue($user->hasPermission('collections.view'));
        $this->assertTrue($user->hasPermission('collections.log_action'));

        // Should not have application or loan creation permissions
        $this->assertFalse($user->hasPermission('applications.create'));
        $this->assertFalse($user->hasPermission('loans.create'));
    }

    /** @test */
    public function roles_can_be_synced()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        
        $officer = Role::where('slug', 'credit-officer')->first();
        $supervisor = Role::where('slug', 'underwriting-supervisor')->first();

        $user->assignRole($officer);
        $this->assertCount(1, $user->roles);

        // Sync to new role (replaces existing)
        $user->syncRoles([$supervisor->id]);
        
        $this->assertCount(1, $user->fresh()->roles);
        $this->assertTrue($user->hasRole('underwriting-supervisor'));
        $this->assertFalse($user->hasRole('credit-officer'));
    }
}
