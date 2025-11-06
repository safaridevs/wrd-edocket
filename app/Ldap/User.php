<?php

namespace App\Ldap;

use LdapRecord\Models\ActiveDirectory\User as BaseUser;

class User extends BaseUser
{
    /**
     * The object classes of the LDAP model.
     */
    public static array $objectClasses = [
        'top',
        'person',
        'organizationalperson',
        'user',
    ];

    /**
     * Get the user's email address.
     */
    public function getEmail(): string
    {
        $email = $this->getFirstAttribute('mail') ?? $this->getFirstAttribute('userPrincipalName') ?? '';
        \Log::info('LDAP User getEmail(): ' . $email);
        return $email;
    }

    /**
     * Get the user's display name.
     */
    public function getName(?string $dn = null): ?string
    {
        return $this->getFirstAttribute('displayname') ?? $this->getFirstAttribute('cn') ?? null;
    }

    /**
     * Get the user's groups for role mapping.
     */
    public function getGroups(): array
    {
        $groups = [];
        $memberOf = $this->getAttribute('memberof') ?? [];
        
        foreach ($memberOf as $group) {
            // Extract group name from DN (e.g., "CN=ALU-Clerks,OU=Groups,DC=domain,DC=local")
            if (preg_match('/^CN=([^,]+)/', $group, $matches)) {
                $groups[] = $matches[1];
            }
        }
        
        return $groups;
    }

    /**
     * Find a user by their email or sAMAccountName
     */
    public static function findByCredentials(array $credentials)
    {
        $query = static::query();
        
        $identifier = $credentials['email'] ?? $credentials['username'] ?? null;
        
        if (!$identifier) {
            return null;
        }
        
        // Try to find by email first
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = $query->where('mail', '=', $identifier)->first();
            if ($user) {
                return $user;
            }
            
            // Also try userPrincipalName
            $user = $query->where('userPrincipalName', '=', $identifier)->first();
            if ($user) {
                return $user;
            }
        }
        
        // Try sAMAccountName
        return $query->where('sAMAccountName', '=', $identifier)->first();
    }
}