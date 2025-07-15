<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PartnerApplicationController extends Controller
{
    public function store(Request $req)
    {
        $data = $req->validate([
            'full_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'cooperation_type_id' => 'required|integer',
            'partner_type_id' => 'nullable|integer',
            'company_name' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $data['user_id'] = $req->user()->id;

        $app = PartnerApplication::create($data);

        return response()->json($app, 201);
    }
}
