<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertUploadFile implements ShouldBroadcast
{
   use Dispatchable, InteractsWithSockets, SerializesModels;

   /**
    * Create a new event instance.
    */
   public function __construct(
      public int $userId,
      public bool $status,
      public string $text = '',
      public string $color = ''
   ) {}
   /**
    * Get the channels the event should broadcast on.
    *
    * @return array<int, \Illuminate\Broadcasting\Channel>
    */
   public function broadcastOn(): array
   {
      return [
         new PrivateChannel("stepupload.{$this->userId}"),
      ];
   }

   public function broadcastWith(): array
   {
      return [
         'status' => $this->status,
         'text' => $this->text,
         'color' => $this->color,
      ];
   }
}
