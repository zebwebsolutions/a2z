<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobilePushDevice;
use Illuminate\Http\Request;

class MobilePushDeviceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['expo_token' => ['required', 'string', 'max:255', 'regex:/^(ExponentPushToken|ExpoPushToken)\[[a-zA-Z0-9_-]+\]$/']]);
        $tokenId = $request->user()->currentAccessToken()?->id;
        abort_unless($tokenId, 403);
        MobilePushDevice::updateOrCreate(['expo_token' => $data['expo_token']], [
            'user_id' => $request->user()->id, 'personal_access_token_id' => $tokenId,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        MobilePushDevice::where('user_id', $request->user()->id)
            ->where('personal_access_token_id', $request->user()->currentAccessToken()?->id)->delete();

        return response()->noContent();
    }
}
