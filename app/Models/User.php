<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'forenames',
        'surname',
        'is_staff',
        'is_admin',
        'password',
        'wants_phd_emails',
        'wants_postgrad_project_emails',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_staff' => 'boolean',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'is_silenced' => 'boolean',
        'wants_phd_emails' => 'boolean',
        'wants_postgrad_project_emails' => 'boolean',
    ];

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'supervisor_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'supervisor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', true);
    }

    public function scopeAdmin($query)
    {
        return $query->where('is_admin', '=', true);
    }

    public function scopeWantsPhdEmails($query)
    {
        return $query->where('wants_phd_emails', '=', true);
    }

    public function scopeWantsPostgradProjectEmails($query)
    {
        return $query->where('wants_postgrad_project_emails', '=', true);
    }

    public function scopeIsSilenced($query)
    {
        return $query->where('is_silenced', '=', true);
    }

    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    public function isSilenced(): bool
    {
        return (bool) $this->is_silenced;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function getFullNameAttribute(): string
    {
        return $this->forenames.' '.$this->surname;
    }

    public function wantsPhdEmails(): bool
    {
        return (bool) $this->wants_phd_emails;
    }

    public function wantsPostgradProjectEmails(): bool
    {
        return (bool) $this->wants_postgrad_project_emails;
    }
}
