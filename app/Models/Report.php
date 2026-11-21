<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * What was reported, resolved for the moderator.
     *
     * @return array{title: string, url: ?string, owner: ?User, model: mixed}|null
     */
    public function target(): ?array
    {
        return match ($this->reportable_type) {
            'job' => ($j = JobPost::with('client.user')->find($this->reportable_id)) ? ['title' => $j->title, 'url' => route('jobs.show', $j), 'owner' => $j->client?->user, 'model' => $j, 'kind' => 'Job'] : null,
            'portfolio' => ($p = PortfolioProject::with('profile.user')->find($this->reportable_id)) ? ['title' => $p->title, 'url' => route('portfolio.show', $p), 'owner' => $p->profile?->user, 'model' => $p, 'kind' => 'Portfolio project'] : null,
            'profile' => ($f = FreelancerProfile::with('user')->where('id', $this->reportable_id)->orWhere('slug', $this->reportable_id)->first()) ? ['title' => $f->user->name.' — '.$f->title, 'url' => route('freelancers.show', $f), 'owner' => $f->user, 'model' => $f, 'kind' => 'Profile'] : null,
            'message' => ($m = Message::with('user')->find($this->reportable_id)) ? ['title' => \Illuminate\Support\Str::limit($m->body, 140), 'url' => null, 'owner' => $m->user, 'model' => $m, 'kind' => 'Message'] : null,
            default => null,
        };
    }
}
