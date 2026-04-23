<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SiteSetting::all()
            ->groupBy('group')
            ->map(fn($group) =>
                $group->mapWithKeys(fn($s) => [$s->key => $s->value])
            );

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings'   => ['required', 'array'],
            'settings.*' => ['nullable', 'string'],
        ]);

        foreach ($request->settings as $key => $value) {
            SiteSetting::set($key, $value);
        }

        return response()->json(['message' => 'Settings updated.']);
    }
}