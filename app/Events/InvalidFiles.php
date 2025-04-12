<?php

namespace App\Events;

use App\Models\InvalidFiles as ModelsInvalidFiles;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvalidFiles implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
   //  public $queue = 'high-priority';

    /**
     * Create a new event instance.
     */
    public function __construct(
      public int $userId,
      public ModelsInvalidFiles $invalidfiles
    )
    { }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
       return [
          new PrivateChannel("invalidfiles.{$this->userId}"),
       ];
    }

    public function broadcastWith(): array
    {
       return [
          'invalidfiles' => $this->invalidfiles,
       ];
    }
}
