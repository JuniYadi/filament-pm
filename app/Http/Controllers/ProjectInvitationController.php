<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token): View
    {
        $invitation = ProjectInvitation::where('token', $token)
            ->with('project', 'inviter')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            abort(410, 'This invitation has expired.');
        }

        if ($invitation->status !== 'pending') {
            abort(400, 'This invitation is no longer valid.');
        }

        return view('invitations.show', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = ProjectInvitation::where('token', $token)
            ->with('project')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return back()->withErrors(['invitation' => 'This invitation has expired.']);
        }

        if ($invitation->status !== 'pending') {
            return back()->withErrors(['invitation' => 'This invitation is no longer valid.']);
        }

        // If user is not authenticated, store token and redirect to login/register
        if (! Auth::check()) {
            session(['invitation_token' => $token]);

            return redirect()->route('filament.auth.login');
        }

        $user = Auth::user();

        // Check if user email matches invitation email
        if ($user->email !== $invitation->email) {
            return back()->withErrors(['invitation' => 'This invitation is for a different email address.']);
        }

        // Check if user is already a member
        if ($invitation->project->members()->where('user_id', $user->id)->exists()) {
            return redirect()->route('filament.admin.resources.projects.view', [
                'record' => $invitation->project_id,
            ])->with('status', 'You are already a member of this project.');
        }

        // Accept the invitation
        $invitation->accept($user);

        return redirect()->route('filament.admin.resources.projects.view', [
            'record' => $invitation->project_id,
        ])->with('status', "You have joined {$invitation->project->name} as a {$invitation->role}.");
    }

    /**
     * Decline the invitation.
     */
    public function decline(string $token): RedirectResponse
    {
        $invitation = ProjectInvitation::where('token', $token)->firstOrFail();

        if (! Auth::check() || Auth::user()->email !== $invitation->email) {
            abort(403, 'You do not have permission to decline this invitation.');
        }

        $invitation->decline();

        return redirect()->route('filament.admin.pages.dashboard')
            ->with('status', 'Invitation declined.');
    }
}
