<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function faspProviderInfo(Request $request)
    {
        $res = [
            'name' => 'FediThreat',
            'privacyPolicy' => [
                [
                    'url' => url('/privacy.html'),
                    'language' => 'en'
                ]
            ],
            'capabilities' => [
                [
                    'id' => 'spam_check',
                    'version' => '1.0'
                ]
            ],
        ];

        if(config('fedithreat.admin_email')) {
            $res['contactEmail'] = config('fedithreat.admin_email');
        }

        if(config('fedithreat.fediverse_acct')) {
            $res['fediverseAccount'] = config('fedithreat.fediverse_acct');
        }

        return response()->json($res);
    }
}
