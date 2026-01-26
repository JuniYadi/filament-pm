<?php

namespace Tests\Feature;

use App\Models\SocialiteUser;
use App\Models\User;
use DutchCodingCompany\FilamentSocialite\Models\Contracts\FilamentSocialiteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Tests\TestCase;

class SocialiteLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_socialite_user_implements_filament_socialite_interface(): void
    {
        $socialiteUser = new SocialiteUser;

        $this->assertInstanceOf(FilamentSocialiteUser::class, $socialiteUser);
    }

    public function test_get_user_returns_authenticatable_user(): void
    {
        $user = User::factory()->create();
        $socialiteUser = SocialiteUser::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '123456789',
        ]);

        $result = $socialiteUser->getUser();

        $this->assertSame($user->id, $result->getKey());
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Authenticatable::class, $result);
    }

    public function test_find_for_provider_finds_existing_socialite_user(): void
    {
        $user = User::factory()->create();
        $socialiteUser = SocialiteUser::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-user-123',
        ]);

        $oauthUser = $this->createMockOAuthUser('google-user-123');

        $result = SocialiteUser::findForProvider('google', $oauthUser);

        $this->assertNotNull($result);
        $this->assertSame($socialiteUser->id, $result->id);
        $this->assertSame('google', $result->provider);
        $this->assertSame('google-user-123', $result->provider_id);
    }

    public function test_find_for_provider_returns_null_when_not_found(): void
    {
        SocialiteUser::factory()->create([
            'provider' => 'google',
            'provider_id' => 'existing-user',
        ]);

        $oauthUser = $this->createMockOAuthUser('non-existent-user');

        $result = SocialiteUser::findForProvider('google', $oauthUser);

        $this->assertNull($result);
    }

    public function test_create_for_provider_creates_new_socialite_user(): void
    {
        $user = User::factory()->create();
        $oauthUser = $this->createMockOAuthUser('new-google-user');

        $result = SocialiteUser::createForProvider('google', $oauthUser, $user);

        $this->assertDatabaseHas('socialite_users', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'new-google-user',
        ]);

        $this->assertSame($user->id, $result->user_id);
        $this->assertSame('google', $result->provider);
        $this->assertSame('new-google-user', $result->provider_id);
    }

    public function test_find_or_create_integration(): void
    {
        $user = User::factory()->create();
        $oauthUser = $this->createMockOAuthUser('integration-user');

        // First call should create
        $first = SocialiteUser::findForProvider('google', $oauthUser);
        $this->assertNull($first);

        $created = SocialiteUser::createForProvider('google', $oauthUser, $user);
        $this->assertNotNull($created);

        // Second call should find
        $found = SocialiteUser::findForProvider('google', $oauthUser);
        $this->assertNotNull($found);
        $this->assertSame($created->id, $found->id);
    }

    /**
     * Create a mock OAuth user for testing.
     */
    private function createMockOAuthUser(string $id): SocialiteUserContract
    {
        $mock = $this->createMock(SocialiteUserContract::class);
        $mock->method('getId')->willReturn($id);

        return $mock;
    }
}
