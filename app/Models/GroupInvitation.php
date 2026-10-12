<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_id', 'invited_by_id', 'email', 'token', 'expires_at', 'accepted_at'])]
class GroupInvitation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
