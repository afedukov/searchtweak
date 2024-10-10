<?php

namespace App\Policies;

class Permissions
{
    /**
     * Teams
     *
     *  - edit team
     *  - add team member
     *  - remove team member
     *  - change team member role
     */
    public const string PERMISSION_MANAGE_TEAM = 'manage_team';

    public const string PERMISSION_MANAGE_API_TOKEN = 'manage_api_token';

    public const string PERMISSION_VIEW_TEAM_SUBSCRIPTION = 'view_team_subscription';

    public const string PERMISSION_SEND_TEAM_MESSAGES = 'send_team_messages';

    /**
     * Search Endpoints
     */
    public const string PERMISSION_MANAGE_SEARCH_ENDPOINTS = 'manage_search_endpoints';

    /**
     * Search Models
     */
    public const string PERMISSION_MANAGE_SEARCH_MODELS = 'manage_search_models';

    /**
     * Search Evaluations
     */
    public const string PERMISSION_MANAGE_SEARCH_EVALUATIONS = 'manage_search_evaluations';

    /**
     * User Feedback
     */
    public const string PERMISSION_MANAGE_USER_FEEDBACK = 'manage_user_feedback';
    public const string PERMISSION_GIVE_USER_FEEDBACK = 'give_user_feedback';

    /**
     * Leaderboard
     */
    public const string PERMISSION_VIEW_LEADERBOARD = 'view_leaderboard';
}
