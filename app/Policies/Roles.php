<?php

namespace App\Policies;

use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Role;

class Roles
{
    public const array ROLE_ADMIN = [
        'key' => 'admin',
        'name' => 'Administrator',
        'description' => 'Administrator users can perform any action.',
        'permissions' => [
            Permissions::PERMISSION_MANAGE_TEAM,
            Permissions::PERMISSION_VIEW_TEAM_SUBSCRIPTION,
            Permissions::PERMISSION_SEND_TEAM_MESSAGES,
            Permissions::PERMISSION_MANAGE_SEARCH_ENDPOINTS,
            Permissions::PERMISSION_MANAGE_SEARCH_MODELS,
            Permissions::PERMISSION_MANAGE_SEARCH_EVALUATIONS,
            Permissions::PERMISSION_MANAGE_USER_FEEDBACK,
            Permissions::PERMISSION_GIVE_USER_FEEDBACK,
            Permissions::PERMISSION_VIEW_LEADERBOARD,
            Permissions::PERMISSION_MANAGE_API_TOKEN,
        ],
    ];

    public const array ROLE_EVALUATOR = [
        'key' => 'evaluator',
        'name' => 'Evaluator',
        'description' => 'Evaluator users have the ability to evaluate search results.',
        'permissions' => [
            Permissions::PERMISSION_GIVE_USER_FEEDBACK,
            Permissions::PERMISSION_VIEW_LEADERBOARD,
        ],
    ];

    public static function all(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_EVALUATOR,
        ];
    }

    /**
     * @return array<Role>
     */
    public static function getRoles(): array
    {
        return collect(Jetstream::$roles)->transform(function ($role) {
            return with($role->jsonSerialize(), function ($data) {
                return (new Role(
                    $data['key'],
                    $data['name'],
                    $data['permissions']
                ))->description($data['description']);
            });
        })->values()->all();
    }
}
