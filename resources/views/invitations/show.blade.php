<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Invitation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center">
            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                You're Invited!
            </h1>

            <p class="text-gray-600 mb-6">
                You have been invited to join
                <span class="font-semibold text-gray-900">{{ $invitation->project->name }}</span>
                @if($invitation->role)
                    as a <span class="font-semibold text-blue-600">{{ $invitation->role }}</span>
                @endif
            </p>

            @if($invitation->project->description)
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <h2 class="text-sm font-medium text-gray-700 mb-2">About this project:</h2>
                    <p class="text-sm text-gray-600">{{ $invitation->project->description }}</p>
                </div>
            @endif

            <div class="text-sm text-gray-500 mb-6">
                <p>Invited by {{ $invitation->inviter->name }}</p>
                <p>Expires: {{ $invitation->expires_at ? $invitation->expires_at->diffForHumans() : 'Never' }}</p>
            </div>

            <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}" class="space-y-3">
                @csrf
                @if(auth()->check())
                    @if(auth()->user()->email !== $invitation->email)
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">
                            This invitation is for <strong>{{ $invitation->email }}</strong>,
                            but you are logged in as <strong>{{ auth()->user()->email }}</strong>.
                            Please log out and sign in with the correct account.
                        </div>
                    @endif
                @endif

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-150 ease-in-out">
                    Accept Invitation
                </button>

                <a href="{{ route('filament.admin.pages.dashboard') }}"
                    class="block w-full text-center text-gray-600 hover:text-gray-800 font-medium py-3 px-4 rounded-lg transition duration-150 ease-in-out">
                    Decline
                </a>
            </form>
        </div>
    </div>
</body>
</html>
