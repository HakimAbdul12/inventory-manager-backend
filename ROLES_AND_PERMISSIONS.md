# Roles and Permissions System

This project implements a fully tenant-scoped Role-Based Access Control (RBAC) system. Unlike Spatie's global permissions, this system ensures that roles and permissions are strictly bound to a user's context within a specific workspace (tenant).

## Architecture

The system uses four main tables:
1. `tenant_permissions`: Global dictionary of available system capabilities (e.g., `inventory.create`).
2. `tenant_roles`: Roles that exist within a specific tenant (e.g., 'Manager' in Workspace A).
3. `tenant_role_permissions`: Pivot linking roles to permissions.
4. `tenant_user_roles`: Pivot linking users to roles *within* a tenant.

## Backend Usage

### Middleware
The primary way to protect routes is via the `tenant_permission` middleware. It accepts a permission key string.

```php
// routes/web.php
Route::put('/inventory/{id}', [InventoryController::class, 'update'])
    ->middleware('tenant_permission:inventory.edit');
```

### Manual Checks
You can manually check permissions using the `PermissionService`:

```php
use App\Services\PermissionService;

public function doSomething(Request $request, PermissionService $permissions)
{
    $tenant = app('current_tenant');
    
    if (!$permissions->userCan('special.action', $request->user(), $tenant)) {
        abort(403);
    }
}
```

### System Default Roles
When a new workspace is created, the system automatically copies over default "system" roles (`owner`, `admin`, `manager`, `clerk`, `viewer`) into the new workspace context. This is handled by `PermissionService::syncDefaultRoles()`.

System roles (`is_system = true`) cannot be deleted, but their permissions can be modified if necessary (though generally not recommended).

## Frontend Usage

Permissions are synced to the frontend on login/refresh via the standard user payload (`user.permissions`).

The state is managed globally via Zustand (`usePermissionStore`).

### The PermissionGate Component
To conditionally render UI elements based on a user's permissions, wrap them in the `<PermissionGate>` component:

```tsx
import { PermissionGate } from "@/components/ui/PermissionGate";

export function Actions() {
  return (
    <div>
      {/* Single permission check */}
      <PermissionGate permission="inventory.create">
        <Button>New Listing</Button>
      </PermissionGate>

      {/* Multiple permissions (Any) */}
      <PermissionGate anyOf={["inventory.edit", "inventory.delete"]}>
        <DropdownMenu>...</DropdownMenu>
      </PermissionGate>

      {/* With Fallback */}
      <PermissionGate 
        permission="workspace.settings" 
        fallback={<p>You cannot edit settings.</p>}
      >
        <SettingsForm />
      </PermissionGate>
    </div>
  );
}
```

### The usePermission Hook
If you need permission logic within a function (like an `onClick` handler), use the hook:

```tsx
import { usePermission, usePermissions } from "@/hooks/usePermission";

export function DeleteAction() {
  const canDelete = usePermission("inventory.delete");
  const { canAny, canAll } = usePermissions();

  const handleAction = () => {
    if (!canDelete) {
      toast.error("You don't have permission to do this.");
      return;
    }
    // do delete
  };
}
```

## Adding New Permissions
To add a new capability to the system:
1. Open `database/seeders/TenantPermissionSeeder.php`.
2. Add the new permission to the `$permissions` array.
3. Assign the new permission to the relevant default roles in the `$roles` array.
4. Run `php artisan db:seed --class=TenantPermissionSeeder`.
