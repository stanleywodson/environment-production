<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('progressbar.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('stepupload.{id}', function ($user, $id) {
   return (int) $user->id === (int) $id;
});

Broadcast::channel('invalidfiles.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
