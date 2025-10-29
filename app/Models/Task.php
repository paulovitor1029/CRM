<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id', 'sector_id', 'title', 'description', 'status', 'priority', 'due_at',
        'created_by', 'assignee_id', 'sla_policy_id', 'response_due_at', 'resolution_due_at',
        'first_response_at', 'completed_at', 'recurrence', 'depends_on_task_id'
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_response_at' => 'datetime',
        'completed_at' => 'datetime',
        'recurrence' => 'array',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function sla(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_task_label');
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }
}
