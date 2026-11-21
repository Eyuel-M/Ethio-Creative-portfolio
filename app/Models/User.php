<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'timezone'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_freelancer' => 'boolean',
            'is_client' => 'boolean',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function freelancerProfile(): HasOne
    {
        return $this->hasOne(FreelancerProfile::class);
    }

    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class);
    }

    public function savedJobs()
    {
        return $this->belongsToMany(JobPost::class, 'saved_jobs')->withTimestamps();
    }

    public function shortlist()
    {
        return $this->belongsToMany(FreelancerProfile::class, 'shortlists')->withPivot('note')->withTimestamps();
    }

    public function workspace(): string
    {
        if ($this->active_workspace === 'client' && $this->is_client) {
            return 'client';
        }
        if ($this->active_workspace === 'freelancer' && $this->is_freelancer) {
            return 'freelancer';
        }

        return $this->is_freelancer ? 'freelancer' : ($this->is_client ? 'client' : 'none');
    }

    public function isAdmin(): bool
    {
        return $this->admin_role !== null;
    }

    public function hasAdminRole(string ...$roles): bool
    {
        return $this->admin_role === 'super_admin' || in_array($this->admin_role, $roles, true);
    }

    /** Whether this admin may use a console section (see config/admin.php). */
    public function canAdmin(string $section): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }
        if ($this->admin_role === 'super_admin') {
            return true;
        }
        $roles = config("admin.sections.{$section}", []);

        return in_array('*', $roles, true) || in_array($this->admin_role, $roles, true);
    }

    /**
     * Admin mode: staff see the site as operators, not as freelancers or clients. Admins who also have a member
     * account can switch to member mode (kept in the session) and back.
     */
    public function inAdminMode(): bool
    {
        return $this->isAdmin() && ! (session('member_mode') && $this->hasMemberWorkspace());
    }

    public function hasMemberWorkspace(): bool
    {
        return $this->is_freelancer || $this->is_client;
    }

    public function adminRoleLabel(): ?string
    {
        return $this->admin_role ? (config('admin.roles')[$this->admin_role] ?? $this->admin_role) : null;
    }

    public function adminNotes()
    {
        return $this->hasMany(AdminNote::class)->latest();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Full name for signed-in viewers; first name only for visitors. Use for anything shown publicly. */
    public function displayName(): string
    {
        return auth()->check() ? $this->name : $this->firstName();
    }

    public function firstName(): string
    {
        return (string) \Illuminate\Support\Str::of($this->name)->trim()->explode(' ')->first();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? \App\Support\Media::url($this->avatar_path) : null;
    }
}
