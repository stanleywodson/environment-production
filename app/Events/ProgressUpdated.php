<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgressUpdated implements ShouldBroadcast
{
   use Dispatchable, InteractsWithSockets, SerializesModels;
   // public $queue = 'high-priority';
   /**
    * Create a new event instance.
    */
   public function __construct(
      public int $userId,
      public int $progress
   ) {}

   /**
    * Get the channels the event should broadcast on.
    *
    * @return array<int, \Illuminate\Broadcasting\Channel>
    */
   public function broadcastOn(): array
   {
      return [
         new PrivateChannel("progressbar.{$this->userId}"),
      ];
   }

   public function broadcastWith(): array
   {
      return [
         'progress' => $this->progress === 0 ? 1 : $this->progress,
      ];
   }
}
