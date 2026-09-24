<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class GraphQLAuthTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    public function test_user_can_register(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation RegisterUser($name: String!, $email: String!, $password: String!) {
                register(name: $name, email: $email, password: $password) {
                    token
                    user {
                        id
                        name
                        email
                    }
                }
            }
        ', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
        ]);

        $response->assertJsonStructure([
            'data' => [
                'register' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
        ]);

        // Clean up
        User::where('email', 'john.doe@example.com')->delete();
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login.test@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation LoginUser($email: String!, $password: String!) {
                login(email: $email, password: $password) {
                    token
                    user {
                        id
                        email
                    }
                }
            }
        ', [
            'email' => 'login.test@example.com',
            'password' => 'secret123',
        ]);

        $response->assertJsonStructure([
            'data' => [
                'login' => [
                    'token',
                    'user' => [
                        'id',
                        'email',
                    ],
                ],
            ],
        ]);

        // Clean up
        $user->tokens()->delete();
        $user->delete();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'wrongpass@example.com',
            'password' => bcrypt('correctpass'),
        ]);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation LoginUser($email: String!, $password: String!) {
                login(email: $email, password: $password) {
                    token
                }
            }
        ', [
            'email' => 'wrongpass@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertGraphQLErrorMessage('Invalid email or password.');

        // Clean up
        $user->delete();
    }

    public function test_authenticated_user_can_query_me(): void
    {
        $user = User::factory()->create([
            'email' => 'me.query@example.com',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->graphQL(/** @lang GraphQL */ '
            query GetMe {
                me {
                    id
                    email
                }
            }
        ');

        $response->assertJson([
            'data' => [
                'me' => [
                    'id' => (string) $user->id,
                    'email' => $user->email,
                ],
            ],
        ]);

        // Clean up
        $user->tokens()->delete();
        $user->delete();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'logout.test@example.com',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->graphQL(/** @lang GraphQL */ '
            mutation LogoutUser {
                logout {
                    status
                    message
                }
            }
        ');

        $response->assertJson([
            'data' => [
                'logout' => [
                    'status' => 'SUCCESS',
                    'message' => 'Successfully logged out.',
                ],
            ],
        ]);

        // Clean up
        $user->tokens()->delete();
        $user->delete();
    }

    public function test_post_request_without_content_type_header_handles_gracefully(): void
    {
        $response = $this->call('POST', '/graphql', [], [], [], []);
        $this->assertNotEquals(500, $response->getStatusCode());
    }
}
