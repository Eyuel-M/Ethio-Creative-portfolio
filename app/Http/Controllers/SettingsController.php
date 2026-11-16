<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthController;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\FreelancerPaymentDetails;
use App\Models\Skill;
use App\Support\Media;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load(['freelancerProfile.skills', 'freelancerProfile.paymentDetails', 'clientProfile']);
        $pd = $user->freelancerProfile?->paymentDetails;

        return view('settings.edit', [
            'user' => $user,
            'categories' => Category::orderBy('sort_order')->get(),
            'allSkills' => Skill::orderBy('name')->pluck('name'),
            // Owner sees masked values only; full values are never re-rendered into HTML.
            'maskedCbe' => FreelancerPaymentDetails::mask($pd?->encrypted_cbe_account_number),
            'maskedTelebirr' => FreelancerPaymentDetails::mask($pd?->encrypted_telebirr_number),
            'tab' => $request->query('tab', $user->workspace() === 'client' ? 'client' : 'profile'),
        ]);
    }

    public function switchWorkspace(Request $request)
    {
        $data = $request->validate(['workspace' => 'required|in:freelancer,client']);
        $user = $request->user();
        abort_unless($data['workspace'] === 'freelancer' ? $user->is_freelancer : $user->is_client, 403);
        $user->forceFill(['active_workspace' => $data['workspace']])->save();

        return redirect()->route('dashboard');
    }

    public function enableRole(Request $request)
    {
        $data = $request->validate(['role' => 'required|in:freelancer,client']);
        AuthController::enableRole($request->user(), $data['role']);
        $request->user()->forceFill(['active_workspace' => $data['role']])->save();

        return redirect()->route('settings', ['tab' => $data['role'] === 'client' ? 'client' : 'profile'])->with('status', ucfirst($data['role']).' workspace enabled.');
    }

    public function updateAccount(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        $user->name = $data['name'];
        if ($request->hasFile('avatar')) {
            Media::delete($user->avatar_path);
            $user->avatar_path = Media::store($request->file('avatar'), 'avatars');
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('status', 'Account updated.');
    }

    public function updateFreelancer(Request $request)
    {
        $profile = $request->user()->freelancerProfile;
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'bio' => 'nullable|string|max:3000',
            'category_id' => 'required|exists:categories,id',
            'location' => 'nullable|string|max:120',
            'availability' => 'required|in:available,busy,unavailable',
            'experience_level' => 'required|in:entry,intermediate,expert',
            'years_experience' => 'nullable|integer|min:0|max:60',
            'hourly_rate' => 'nullable|numeric|min:0|max:1000000',
            'currency' => 'required|in:'.implode(',', config('marketplace.currencies')),
            'languages' => 'nullable|string|max:200',
            'tools' => 'nullable|string|max:300',
            'skills' => 'nullable|string|max:400',
            'website' => 'nullable|url|max:200',
            'contact_phone' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:190',
            'is_public' => 'nullable|boolean',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);
        $split = fn ($v) => collect(explode(',', (string) $v))->map(fn ($s) => trim($s))->filter()->take(15)->values()->all();

        $profile->fill([
            'title' => $data['title'], 'bio' => $data['bio'] ?? null, 'category_id' => $data['category_id'],
            'location' => $data['location'] ?? null, 'availability' => $data['availability'],
            'experience_level' => $data['experience_level'], 'years_experience' => $data['years_experience'] ?? null,
            'hourly_rate_minor' => Money::toMinor($data['hourly_rate'] ?? null), 'currency' => $data['currency'],
            'languages' => $split($data['languages'] ?? ''), 'tools' => $split($data['tools'] ?? ''),
            'website' => $data['website'] ?? null, 'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null, 'is_public' => $request->boolean('is_public'),
        ]);
        if ($request->hasFile('cover')) {
            Media::delete($profile->cover_path);
            $profile->cover_path = Media::store($request->file('cover'), 'covers');
        }
        $profile->save();
        $profile->skills()->sync(collect($split($data['skills'] ?? ''))->map(fn ($n) => Skill::firstOrCreate(['slug' => Str::slug($n)], ['name' => $n, 'category_id' => $profile->category_id])->id));

        return back()->with('status', 'Profile saved.');
    }

    public function updatePayment(Request $request)
    {
        $profile = $request->user()->freelancerProfile;
        $data = $request->validate([
            'cbe_account_number' => ['nullable', 'regex:/^\d{10,16}$/'],
            'telebirr_number' => ['nullable', 'regex:/^(\+251|0)?9\d{8}$/'],
            'preferred_method' => 'nullable|in:cbe,telebirr',
            'clear' => 'nullable|in:cbe,telebirr',
        ], [
            'cbe_account_number.regex' => 'Enter a valid CBE account number (digits only).',
            'telebirr_number.regex' => 'Enter a valid Ethiopian mobile number for Telebirr (e.g. 0911234567).',
        ]);

        $pd = $profile->paymentDetails ?? new FreelancerPaymentDetails(['freelancer_profile_id' => $profile->id]);
        $changed = [];
        if (filled($data['cbe_account_number'] ?? null)) {
            $pd->encrypted_cbe_account_number = $data['cbe_account_number'];
            $changed[] = 'cbe';
        }
        if (filled($data['telebirr_number'] ?? null)) {
            $pd->encrypted_telebirr_number = $data['telebirr_number'];
            $changed[] = 'telebirr';
        }
        if (($data['clear'] ?? null) === 'cbe') {
            $pd->encrypted_cbe_account_number = null;
            $changed[] = 'cbe_cleared';
        }
        if (($data['clear'] ?? null) === 'telebirr') {
            $pd->encrypted_telebirr_number = null;
            $changed[] = 'telebirr_cleared';
        }
        $pd->preferred_method = $data['preferred_method'] ?? $pd->preferred_method;
        if ($pd->preferred_method === 'cbe' && ! $pd->encrypted_cbe_account_number) {
            $pd->preferred_method = $pd->encrypted_telebirr_number ? 'telebirr' : null;
        }
        if ($pd->preferred_method === 'telebirr' && ! $pd->encrypted_telebirr_number) {
            $pd->preferred_method = $pd->encrypted_cbe_account_number ? 'cbe' : null;
        }
        $pd->updated_by = $request->user()->id;
        $pd->refreshStatus();
        $pd->save();
        // Audit records which fields changed — never the values.
        AuditLog::record('payment_details.updated', $pd, null, ['fields' => $changed, 'preferred' => $pd->preferred_method]);

        return redirect()->route('settings', ['tab' => 'payment'])->with('status', 'Payment details saved securely.');
    }

    public function updateClient(Request $request)
    {
        $client = $request->user()->clientProfile;
        $data = $request->validate([
            'type' => 'required|in:individual,company',
            'company_name' => 'nullable|required_if:type,company|string|max:160',
            'industry' => 'nullable|string|max:120',
            'website' => 'nullable|url|max:200',
            'location' => 'nullable|string|max:120',
            'contact_phone' => 'nullable|string|max:30',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:4096',
        ]);
        $client->fill(collect($data)->except('logo')->all());
        if ($request->hasFile('logo')) {
            Media::delete($client->logo_path);
            $client->logo_path = Media::store($request->file('logo'), 'logos');
        }
        $client->save();

        return redirect()->route('settings', ['tab' => 'client'])->with('status', 'Client profile saved.');
    }
}
