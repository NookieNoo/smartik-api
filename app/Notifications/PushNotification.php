<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\AndroidFcmOptions;
use NotificationChannels\Fcm\Resources\AndroidNotification;
use NotificationChannels\Fcm\Resources\ApnsConfig;
use NotificationChannels\Fcm\Resources\ApnsFcmOptions;

class PushNotification extends Notification implements ThrottledNotification, ShouldQueue
{
    use Queueable;

    public ?string $throttleKey = null;
    public int     $throttle    = 10;
    public ?array  $data        = null;
    public string  $title       = "";
    public string  $body        = "";

    public function throttleKeyId (): string
    {
        return $this->throttleKey() ?? $this->throttleKey ?? class_basename($this);
    }

    public function throttleDecaySeconds (): int
    {
        return $this->throttle() ?? $this->throttle;
    }

    public function throttleKey (): ?string
    {
        return null;
    }

    public function throttle (): ?int
    {
        return null;
    }

    public function data (): ?array
    {
        return null;
    }

    public function title (): ?string
    {
        return null;
    }

    public function body (): ?string
    {
        return null;
    }

    public function via ($notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm ($notifiable)
    {
        $data = $this->data() ?? $this->data;
        $title = $this->title() ?? $this->title;
        $body = $this->body() ?? $this->body;;

        return FcmMessage::create()
            ->setData($data)
            ->setNotification(
                \NotificationChannels\Fcm\Resources\Notification::create()
                    ->setTitle($title)
                    ->setBody($body)
            )
            ->setAndroid(
                AndroidConfig::create()
                    ->setNotification(
                        AndroidNotification::create()
                            ->setChannelId('default')
                    )
            );
    }
}