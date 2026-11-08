<?php

namespace App\Notifications\Channels;

use App\Models\PushSuscripcion;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    public function send($notifiable, Notification $notification): void
    {
        try {
            if (! method_exists($notification, 'toPush') || ! config('webpush.private_key')) {
                return;
            }

            $subs = PushSuscripcion::where('user_id', $notifiable->getKey())->get();
            if ($subs->isEmpty()) {
                return;
            }

            $push = new WebPush(['VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => config('webpush.public_key'),
                'privateKey' => config('webpush.private_key'),
            ]]);

            $payload = json_encode($notification->toPush($notifiable));

            foreach ($subs as $s) {
                $push->queueNotification(Subscription::create([
                    'endpoint' => $s->endpoint,
                    'publicKey' => $s->p256dh,
                    'authToken' => $s->auth,
                    'contentEncoding' => 'aes128gcm',
                ]), $payload);
            }

            foreach ($push->flush() as $r) {
                if ($r->isSubscriptionExpired()) {
                    PushSuscripcion::where('endpoint_hash', hash('sha256', $r->getEndpoint()))->delete();
                } elseif (! $r->isSuccess()) {
                    Log::warning('Push no entregado: ' . $r->getReason());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Push falló: ' . $e->getMessage());
        }
    }
}
