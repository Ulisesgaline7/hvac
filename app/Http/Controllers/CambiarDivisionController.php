<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class CambiarDivisionController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_if(! $request->user() || $request->user()->cliente_id, 403);

        $id = (int) $request->input('division_id');

        if ($id > 0 && Division::activas()->whereKey($id)->exists()) {
            $request->session()->put('division_id', $id);
        }

        return redirect('/admin');
    }
}
