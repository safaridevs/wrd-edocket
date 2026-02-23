<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function switchRole(Request $request)
    {
        $roles = [
            'admin', 'hu_admin', 'hu_clerk', 'alu_mgr', 'alu_clerk', 'alu_atty',
            'wrd', 'wrap_dir', 'hydrology_expert', 'party', 'unaffiliated'
        ];
        
        $role = $request->input('role');
        
        if (in_array($role, $roles)) {
            session(['impersonated_role' => $role]);
        }
        
        return back();
    }
    
    public function stopImpersonation()
    {
        session()->forget('impersonated_role');
        return back();
    }
}
