<?php

namespace App\Models;

use Database\Factories\DebtReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_id', 'from_user_id', 'to_user_id', 'last_sent_at'])]
class DebtReminder extends Model
{
    /** @use HasFactory<DebtReminderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['last_sent_at' => 'datetime'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
