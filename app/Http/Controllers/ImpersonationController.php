<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function switchRole(Request $request)
    {
        $roles = [
            'alu_clerk', 'alu_atty', 'alu_mgr', 'hu_admin', 'hu_clerk', 
            'party', 'admin', 'hydrology_expert', 'wrd_expert', 'wrap_director'
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