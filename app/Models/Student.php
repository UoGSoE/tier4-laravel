<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\StudentNote;
use App\Proxies\CachedOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    public const TYPE_POSTGRAD_PROJECT = 'postgrad_project';
    public const TYPE_PHD = 'phd';

    protected $fillable = [
        'username',
        'email',
        'forenames',
        'surname',
        'type',
        'supervisor_id',
        'is_active',
        'is_silenced',
        'silenced_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_silenced' => 'boolean',
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'student_id');
    }

    public function latestMeeting()
    {
        return $this->hasOne(Meeting::class, 'student_id')->latestOfMany('meeting_at');
    }

    public function notes()
    {
        return $this->hasMany(StudentNote::class);
    }

    public function latestNote()
    {
        return $this->hasOne(StudentNote::class)->latestOfMany('updated_at');
    }

    public function scopeOverdue($query, int $numberOfDays = 28)
    {
        return $query->whereRelation('latestMeeting', 'meeting_at', '<', now()->subDays($numberOfDays));
    }

    public function scopePhd($query)
    {
        return $query->where('type', '=', self::TYPE_PHD);
    }

    public function scopePostgradProject($query)
    {
        return $query->where('type', '=', self::TYPE_POSTGRAD_PROJECT);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', true);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isntActive(): bool
    {
        return ! $this->isActive();
    }

    public function isSilenced(): bool
    {
        return (bool) $this->is_silenced;
    }

    public function isntSilenced(): bool
    {
        return ! $this->isSilenced();
    }

    public function getFullNameAttribute(): string
    {
        return $this->forenames . ' ' . $this->surname;
    }

    public function lastMeetingWith(User $user)
    {
        return $this->meetings()
            ->where('supervisor_id', '=', $user->id)
            ->latest()
            ->first();
    }

    public function isOverdue(): bool
    {
        $optionName = 'phd_meeting_reminder_days';
        if ($this->isPostgradProjectStudent()) {
            $optionName = 'postgrad_project_meeting_reminder_days';
            if (! now()->between($this->getPostgradStartNotificationDate(), $this->getPostgradEndNotificationDate())) {
                return false;
            }
        }

        return (bool) (abs($this->latestMeeting?->meeting_at->diffInDays(now())) > CachedOption::get($optionName, 28));
    }

    public function isPhdStudent(): bool
    {
        return $this->type === self::TYPE_PHD;
    }

    public function isPostgradProjectStudent(): bool
    {
        return $this->type === Student::TYPE_POSTGRAD_PROJECT;
    }

    protected function getPostgradStartNotificationDate(): Carbon
    {
        // note that this will fail at the beginning/end of the year as the year might be 'next year' or 'last year' - but
        // as this should only be handling the summer recess, it should be fine
        return Carbon::createFromFormat(
            'Y-m-d',
            now()->year . '-' . CachedOption::get('postgrad_project_start_month', 5) . '-' . CachedOption::get('postgrad_project_start_day', 1)
        );
    }

    protected function getPostgradEndNotificationDate(): Carbon
    {
        // note that this will fail at the beginning/end of the year as the year might be 'next year' or 'last year' - but
        // as this should only be handling the summer recess, it should be fine
        return Carbon::createFromFormat(
            'Y-m-d',
            now()->year . '-' . CachedOption::get('postgrad_project_end_month', 5) . '-' . CachedOption::get('postgrad_project_end_day', 1)
        );
    }
}
