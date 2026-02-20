<?php

namespace Database\Seeders;

use App\Models\Judge;
use App\Models\User;
use Illuminate\Database\Seeder;

class JudgeSeeder extends Seeder
{
    /**
     * Seed the application's database with development judges.
     */
    public function run(): void
    {
        $user = User::where(User::FIELD_EMAIL, 'admin@searchtweak.com')->firstOrFail();
        $teamId = $user->currentTeam->id;

        $judges = [
            [
                Judge::FIELD_NAME => 'GPT-4o Relevance Judge',
                Judge::FIELD_DESCRIPTION => 'High-accuracy search relevance evaluator powered by OpenAI GPT-4o',
                Judge::FIELD_PROVIDER => Judge::PROVIDER_OPENAI,
                Judge::FIELD_MODEL_NAME => 'gpt-4o',
                Judge::FIELD_API_KEY => 'sk-proj-openai-test-key-abc123',
                Judge::FIELD_SETTINGS => [Judge::SETTING_BATCH_SIZE => 10],
            ],
            [
                Judge::FIELD_NAME => 'Claude Sonnet Search Judge',
                Judge::FIELD_DESCRIPTION => 'Versatile search relevance judge using Anthropic Claude Sonnet 4.5 for nuanced relevance assessments',
                Judge::FIELD_PROVIDER => Judge::PROVIDER_ANTHROPIC,
                Judge::FIELD_MODEL_NAME => 'claude-sonnet-4-5-20250929',
                Judge::FIELD_API_KEY => 'sk-ant-anthropic-test-key-xyz789',
                Judge::FIELD_SETTINGS => [Judge::SETTING_BATCH_SIZE => 5],
            ],
            [
                Judge::FIELD_NAME => 'Gemini Pro Quality Judge',
                Judge::FIELD_DESCRIPTION => 'Cost-effective search quality judge powered by Google Gemini Pro with large context window',
                Judge::FIELD_PROVIDER => Judge::PROVIDER_GOOGLE,
                Judge::FIELD_MODEL_NAME => 'gemini-2.0-flash',
                Judge::FIELD_API_KEY => 'AIzaSy-google-test-key-def456',
                Judge::FIELD_SETTINGS => [Judge::SETTING_BATCH_SIZE => 0],
            ],
        ];

        foreach ($judges as $data) {
            Judge::create(array_merge($data, [
                Judge::FIELD_USER_ID => $user->id,
                Judge::FIELD_TEAM_ID => $teamId,
                Judge::FIELD_PROMPT_BINARY => Judge::getDefaultPrompt('binary'),
                Judge::FIELD_PROMPT_GRADED => Judge::getDefaultPrompt('graded'),
                Judge::FIELD_PROMPT_DETAIL => Judge::getDefaultPrompt('detail'),
            ]));
        }
    }
}
