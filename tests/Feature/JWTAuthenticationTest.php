<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration.
     */
    public function test_user_can_register()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'status',
                            'created_at',
                            'updated_at'
                        ],
                        'token',
                        'token_type',
                        'expires_in'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'user',
            'status' => 'active'
        ]);
    }

    /**
     * Test admin registration.
     */
    public function test_admin_can_register()
    {
        $adminData = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register-admin', $adminData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'status',
                            'created_at',
                            'updated_at'
                        ],
                        'token',
                        'token_type',
                        'expires_in'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active'
        ]);
    }

    /**
     * Test user login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'status' => 'active'
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'token',
                        'token_type',
                        'expires_in'
                    ]
                ]);
    }

    /**
     * Test user login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'error_code' => 'INVALID_CREDENTIALS'
                ]);
    }

    /**
     * Test user login with deactivated account.
     */
    public function test_user_cannot_login_with_deactivated_account()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'status' => 'inactive'
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Account is deactivated',
                    'error_code' => 'ACCOUNT_DEACTIVATED'
                ]);
    }

    /**
     * Test getting authenticated user profile.
     */
    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    /**
     * Test accessing protected route without token.
     */
    public function test_cannot_access_protected_route_without_token()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Token not provided',
                    'error_code' => 'TOKEN_MISSING'
                ]);
    }

    /**
     * Test accessing protected route with invalid token.
     */
    public function test_cannot_access_protected_route_with_invalid_token()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
                        ->getJson('/api/auth/me');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error_code' => 'TOKEN_INVALID'
                ]);
    }

    /**
     * Test user logout.
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);
    }

    /**
     * Test token refresh.
     */
    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'token',
                        'token_type',
                        'expires_in'
                    ]
                ]);
    }

    /**
     * Test password change.
     */
    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
            'status' => 'active'
        ]);
        $token = JWTAuth::fromUser($user);

        $passwordData = [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->postJson('/api/auth/change-password', $passwordData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Password changed successfully. Please login again.'
                ]);
    }

    /**
     * Test password change with wrong current password.
     */
    public function test_user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
            'status' => 'active'
        ]);
        $token = JWTAuth::fromUser($user);

        $passwordData = [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->postJson('/api/auth/change-password', $passwordData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'error_code' => 'INVALID_CURRENT_PASSWORD'
                ]);
    }
}
