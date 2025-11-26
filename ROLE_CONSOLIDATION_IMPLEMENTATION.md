# Role Consolidation with User Titles Implementation

## Overview
This implementation achieves role consolidation without changing existing database values by:
1. Adding a `title` column to store user titles (e.g., "WRAP Director", "ALU Managing Attorney")
2. Creating role mapping methods in the User model
3. Displaying titles alongside names throughout the application

## Key Changes Made

### 1. Database Migration
- **File**: `database/migrations/2024_01_15_000000_add_title_to_users_table.php`
- **Purpose**: Adds nullable `title` column to `users` table

### 2. User Model Updates
- **File**: `app/Models/User.php`
- **Changes**:
  - Added `title` to `$fillable` array
  - Added `getConsolidatedRole()` method for role mapping
  - Added `getDisplayName()` method to show "Name, Title" format

```php
public function getConsolidatedRole(): string
{
    $role = $this->getCurrentRole();
    
    // Map specific roles to consolidated roles
    $roleMap = [
        'alu_clerk' => 'alu',
        'alu_attorney' => 'alu', 
        'alu_managing_atty' => 'alu',
        'hu_admin' => 'hu',
        'hu_clerk' => 'hu',
        'hu_examiner' => 'hu',
    ];
    
    return $roleMap[$role] ?? $role;
}

public function getDisplayName(): string
{
    return $this->title ? "{$this->name}, {$this->title}" : $this->name;
}
```

### 3. View Updates
- **Files Updated**:
  - `resources/views/cases/show.blade.php` - Case assignments and audit trail
  - `resources/views/admin/users.blade.php` - User management interface
  - `resources/views/cases/assign-attorney.blade.php` - Attorney assignment
- **Changes**: Replaced `{{ $user->name }}` with `{{ $user->getDisplayName() }}`

### 4. Controller Updates
- **File**: `app/Http/Controllers/AdminController.php`
- **Changes**: Added `title` field validation to user update method
- **File**: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- **Changes**: Added title extraction from LDAP during user creation

## Role Consolidation Strategy

### Current Implementation
- **Database**: Keeps existing specific role values (alu_clerk, alu_attorney, etc.)
- **Application Logic**: Uses `getConsolidatedRole()` method when needed
- **Permissions**: Existing permission methods continue to work unchanged

### Future Migration Path
When ready to fully consolidate roles:

1. **Update existing records**:
```sql
UPDATE users SET role = 'alu' WHERE role IN ('alu_clerk', 'alu_attorney', 'alu_managing_atty');
UPDATE users SET role = 'hu' WHERE role IN ('hu_admin', 'hu_clerk', 'hu_examiner');
```

2. **Update permission methods** to use consolidated roles
3. **Update validation rules** to accept only consolidated roles

## User Title Examples

| User | Role | Title | Display Name |
|------|------|-------|--------------|
| Sara Johnson | wrap_director | WRAP Director | Sara Johnson, WRAP Director |
| Owen Smith | alu_managing_atty | ALU Managing Attorney | Owen Smith, ALU Managing Attorney |
| Jane Doe | alu_clerk | Senior Law Clerk | Jane Doe, Senior Law Clerk |
| John Brown | hu_admin | HU Administrator | John Brown, HU Administrator |

## Benefits

1. **No Breaking Changes**: Existing code continues to work
2. **Gradual Migration**: Can implement role consolidation incrementally  
3. **Enhanced Display**: Users see meaningful titles in case listings
4. **LDAP Integration**: Automatically extracts titles from Active Directory
5. **Flexible Titles**: Titles can be customized beyond role names

## Usage

### Setting User Titles
- **Admin Interface**: Edit user and set title field
- **LDAP Users**: Titles automatically extracted from AD `title` attribute
- **Manual Users**: Set title during user creation/editing

### Displaying Users
- Use `{{ $user->getDisplayName() }}` in views
- Shows "Name, Title" format when title exists
- Falls back to just name when no title set

### Role Checking
- Current role methods continue to work: `$user->isALUClerk()`
- New consolidated method available: `$user->getConsolidatedRole()`
- Permission methods unchanged: `$user->canCreateCase()`