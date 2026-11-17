<?php

namespace App\Http\Middleware;

use App\Models\CaseModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsolidatedCaseIsReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $case = $request->route('case');
        if (!$case instanceof CaseModel || !$case->isConsolidatedSource()) {
            return $next($request);
        }

        if ($request->route()?->getName() === 'cases.consolidate.store') {
            return $next($request);
        }

        $writeInterfaceRoutes = [
            'cases.edit',
            'cases.parties.manage',
            'cases.parties.edit',
            'cases.documents.manage',
            'cases.assign-attorney',
            'cases.assign-hydrology-expert',
            'cases.assign-alu-clerk',
            'cases.assign-wrd',
        ];

        if (!$request->isMethodSafe() || in_array($request->route()?->getName(), $writeInterfaceRoutes, true)) {
            abort(409, 'This case has been consolidated and is read-only. File or make changes in the combined case.');
        }

        return $next($request);
    }
}
