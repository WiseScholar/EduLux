<?php

if (!defined('ACCESS_GRANTED')) {
    exit('Direct access not allowed');
}

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function send_in_app_notifications(PDO $pdo, array $user_ids, string $message, string $link_url): int
{
    if (empty($user_ids)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($user_ids), '(?, ?, ?, NOW())'));
    $values = [];
    foreach ($user_ids as $user_id) {
        $values[] = $user_id;
        $values[] = $message;
        $values[] = $link_url;
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, link_url, created_at) 
        VALUES $placeholders
    ");

    $stmt->execute($values);
    return count($user_ids);
}

function send_web_push_notifications(PDO $pdo, array $subscriptions, array $payload): int
{
    if (empty($subscriptions)) {
        return 0;
    }

    try {
        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth, ['localKeyCache' => false]);

        foreach ($subscriptions as $sub_data) {
            $sub = Subscription::create([
                'endpoint' => $sub_data['endpoint'],
                'publicKey' => $sub_data['p256dh'],
                'authToken' => $sub_data['auth'],
            ]);
            $webPush->queueNotification($sub, json_encode($payload));
        }

        $success_count = 0;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $success_count++;
            } else {
                // Clean up expired subscriptions
                if ($report->isSubscriptionExpired()) {
                    $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")
                        ->execute([$report->getSubscription()->getEndpoint()]);
                }
            }
        }

        return $success_count;

    } catch (Exception $e) {
        error_log("WebPush Error: " . $e->getMessage());
        return 0;
    }
}

function get_push_subscriptions(PDO $pdo, array $user_ids): array
{
    if (empty($user_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT endpoint, p256dh, auth
        FROM push_subscriptions 
        WHERE user_id IN ($placeholders)
    ");
    $stmt->execute($user_ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}