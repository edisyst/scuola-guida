<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isViewer(), 403);
        abort_if(!feature('web_push_enabled'), 404);

        $data = $request->validate([
            'endpoint'         => ['required', 'string', 'max:500'],
            'keys.auth'        => ['nullable', 'string'],
            'keys.p256dh'      => ['nullable', 'string'],
            'contentEncoding'  => ['nullable', 'string'],
        ]);

        auth()->user()->updatePushSubscription(
            endpoint:        $data['endpoint'],
            key:             $data['keys']['p256dh'] ?? null,
            token:           $data['keys']['auth'] ?? null,
            contentEncoding: $data['contentEncoding'] ?? 'aesgcm',
        );

        return response()->json(['status' => 'subscribed'], 201);
    }

    public function destroy(Request $request): Response
    {
        abort_unless(auth()->user()->isViewer(), 403);
        abort_if(!feature('web_push_enabled'), 404);

        $data = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        auth()->user()->deletePushSubscription($data['endpoint']);

        return response()->noContent();
    }
}
