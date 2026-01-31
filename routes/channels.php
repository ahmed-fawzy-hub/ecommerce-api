<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('user.{userId}.orders', function (User $user,int $userId) {
    return  $user->id === $userId;
});
Broadcast::channel('admin.orders', function (User $user) {
    return $user->hasRole('admin');
});