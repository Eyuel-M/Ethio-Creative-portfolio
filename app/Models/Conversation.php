<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_user_id');
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeFor($q, User $user)
    {
        return $q->where(fn ($w) => $w->where('client_user_id', $user->id)->orWhere('freelancer_user_id', $user->id));
    }

    public function hasParticipant(User $user): bool
    {
        return in_array($user->id, [$this->client_user_id, $this->freelancer_user_id], true);
    }

    public function otherParty(User $user): User
    {
        return $user->id === $this->client_user_id ? $this->freelancer : $this->client;
    }

    public function unreadFor(User $user): int
    {
        return $this->messages()->whereNull('read_at')->where('user_id', '!=', $user->id)->count();
    }
}
