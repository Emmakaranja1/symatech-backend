<?php

namespace Tests\Feature\Redis;

use Tests\TestCase;
use App\Services\Redis\UserPreferencesService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected $preferencesService;
    protected $userId = 123;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preferencesService = app(UserPreferencesService::class);
    }

    public function test_can_set_preference()
    {
        $response = $this->postJson('/api/redis/preferences/set', [
            'user_id' => $this->userId,
            'key' => 'theme',
            'value' => 'dark'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Preference set successfully'
                ]);

        $storedValue = $this->preferencesService->getPreference($this->userId, 'theme');
        $this->assertEquals('dark', $storedValue);
    }

    public function test_can_get_preference()
    {
        $this->preferencesService->setPreference($this->userId, 'language', 'en');

        $response = $this->getJson('/api/redis/preferences/get?user_id=' . $this->userId . '&key=language');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user_id' => $this->userId,
                        'key' => 'language',
                        'value' => 'en',
                        'found' => true
                    ]
                ]);
    }

    public function test_can_get_all_preferences()
    {
        $preferences = [
            'theme' => 'dark',
            'language' => 'en',
            'notifications' => true
        ];

        foreach ($preferences as $key => $value) {
            $this->preferencesService->setPreference($this->userId, $key, $value);
        }

        $response = $this->getJson('/api/redis/preferences/all?user_id=' . $this->userId);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user_id' => $this->userId,
                        'count' => 3
                    ]
                ]);

        $data = $response->json('data');
        $this->assertEquals('dark', $data['preferences']['theme']);
        $this->assertEquals('en', $data['preferences']['language']);
        $this->assertEquals(true, $data['preferences']['notifications']);
    }

    public function test_can_remove_preference()
    {
        $this->preferencesService->setPreference($this->userId, 'theme', 'dark');

        $response = $this->deleteJson('/api/redis/preferences/remove', [
            'user_id' => $this->userId,
            'key' => 'theme'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Preference removed successfully'
                ]);

        $value = $this->preferencesService->getPreference($this->userId, 'theme');
        $this->assertNull($value);
    }

    public function test_can_clear_all_preferences()
    {
        $preferences = [
            'theme' => 'dark',
            'language' => 'en',
            'notifications' => true
        ];

        foreach ($preferences as $key => $value) {
            $this->preferencesService->setPreference($this->userId, $key, $value);
        }

        $response = $this->deleteJson('/api/redis/preferences/clear', [
            'user_id' => $this->userId
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All preferences cleared successfully'
                ]);

        $allPrefs = $this->preferencesService->getAllPreferences($this->userId);
        $this->assertEmpty($allPrefs);
    }

    public function test_can_set_multiple_preferences()
    {
        $preferences = [
            ['key' => 'theme', 'value' => 'dark'],
            ['key' => 'language', 'value' => 'en'],
            ['key' => 'notifications', 'value' => true]
        ];

        $response = $this->postJson('/api/redis/preferences/multiple', [
            'user_id' => $this->userId,
            'preferences' => $preferences
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Multiple preferences set successfully',
                    'data' => [
                        'user_id' => $this->userId,
                        'preferences_set' => 3
                    ]
                ]);

        $this->assertEquals('dark', $this->preferencesService->getPreference($this->userId, 'theme'));
        $this->assertEquals('en', $this->preferencesService->getPreference($this->userId, 'language'));
        $this->assertEquals(true, $this->preferencesService->getPreference($this->userId, 'notifications'));
    }

    public function test_preference_storage_and_retrieval()
    {
        $complexData = [
            'dashboard_layout' => [
                'widgets' => ['sales', 'orders', 'users'],
                'positions' => ['top', 'middle', 'bottom']
            ],
            'settings' => [
                'auto_refresh' => true,
                'refresh_interval' => 30
            ]
        ];

        $this->preferencesService->setPreference($this->userId, 'ui_config', $complexData);

        $retrievedData = $this->preferencesService->getPreference($this->userId, 'ui_config');
        $this->assertEquals($complexData, $retrievedData);
    }

    public function test_get_preference_with_default()
    {
        $response = $this->getJson('/api/redis/preferences/get?user_id=' . $this->userId . '&key=nonexistent&default=default_value');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user_id' => $this->userId,
                        'key' => 'nonexistent',
                        'value' => 'default_value',
                        'found' => false
                    ]
                ]);
    }

    public function test_set_preference_validation()
    {
        $response = $this->postJson('/api/redis/preferences/set', [
            'user_id' => 'invalid',
            'key' => '',
            'value' => null
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);
    }
}
