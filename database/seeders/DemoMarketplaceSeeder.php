<?php

namespace Database\Seeders;

use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\Judge;
use App\Models\JudgeLog;
use App\Models\KeywordMetric;
use App\Models\MetricValue;
use App\Models\SearchEvaluation;
use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\SearchSnapshot;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Models\UserFeedback;
use App\Services\Evaluations\ScoringGuidelinesService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoMarketplaceSeeder
 *
 * Populates the database with realistic data simulating a heavily-used
 * online marketplace search evaluation project.
 *
 * Topology:
 *  - 1 super-admin + 4 teams (DE, FR, IT, ES markets)
 *  - Each team: 1 owner + 2-3 admins + 4-6 evaluators
 *  - Each team: 2-3 endpoints, 3-5 search models
 *  - Each team: 8-12 evaluations (mix of statuses)
 *  - Each evaluation: 15-25 keywords, 3-4 metrics, realistic snapshots & feedbacks
 *  - 3-4 AI judges per team with thousands of judge logs
 *  - Tags for categorisation across all entities
 */
class DemoMarketplaceSeeder extends Seeder
{
    // -------------------------------------------------------------------------
    // Demo marketplace domain data
    // -------------------------------------------------------------------------

    private const DEMO_KEYWORDS = [
        // Kitchen equipment
        'commercial refrigerator', 'industrial dishwasher', 'convection oven', 'salamander grill',
        'spiral mixer', 'planetary mixer', 'blast chiller', 'combi steamer', 'deep fryer',
        'induction cooktop', 'refrigerated prep table', 'gelato machine', 'espresso machine',
        'commercial blender', 'meat slicer', 'vacuum sealer', 'sous vide circulator',
        'dough sheeter', 'proofing cabinet', 'chocolate melter', 'waffle iron commercial',
        'hot holding cabinet', 'food warmer', 'steam table', 'bain marie',
        'commercial coffee grinder', 'juice extractor', 'food processor',
        // Tableware & smallwares
        'porcelain dinner plate', 'stainless steel cutlery set', 'wine glass set',
        'hotel crockery', 'restaurant tablecloth', 'menu holder', 'napkin dispenser',
        'chafing dish set', 'serving tray stainless', 'soup tureen', 'bread basket',
        // Disposables & packaging
        'takeaway containers', 'kraft paper bags', 'pizza boxes', 'food packaging',
        'disposable gloves', 'hairnets catering', 'apron chef', 'cleaning chemicals catering',
        // Beverages
        'beer tap system', 'wine cooler cabinet', 'cocktail shaker set', 'bar tools set',
        'coffee capsules bulk', 'mineral water dispenser', 'syrup bottles',
        // Hotel & housekeeping
        'hotel linen', 'towel set hotel', 'mattress protector hotel', 'pillow hotel',
        'room cleaning kit', 'hotel amenities set', 'laundry bags',
        // Furniture
        'restaurant chair', 'folding table catering', 'bar stool', 'outdoor furniture catering',
        'patio heater', 'umbrella parasol', 'buffet furniture',
    ];

    private const SCORER_TYPES = ['precision', 'ap', 'rr', 'cg', 'dcg', 'ndcg', 'err'];
    private const NUM_RESULTS   = [5, 10, 20];
    private const SCALE_TYPES   = ['binary', 'graded', 'detail'];
    private const PROVIDERS     = [
        Judge::PROVIDER_OPENAI, Judge::PROVIDER_ANTHROPIC,
        Judge::PROVIDER_GOOGLE, Judge::PROVIDER_DEEPSEEK, Judge::PROVIDER_MISTRAL,
    ];

    private const MARKETS = [
        'de' => ['name' => 'Germany', 'lang' => 'de', 'flag' => '🇩🇪'],
        'fr' => ['name' => 'France',  'lang' => 'fr', 'flag' => '🇫🇷'],
        'it' => ['name' => 'Italy',   'lang' => 'it', 'flag' => '🇮🇹'],
        'es' => ['name' => 'Spain',   'lang' => 'es', 'flag' => '🇪🇸'],
    ];

    private const JUDGE_CONFIGS = [
        ['provider' => 'openai',    'model' => 'gpt-4o',                    'batch' => 10],
        ['provider' => 'openai',    'model' => 'gpt-4o-mini',               'batch' => 20],
        ['provider' => 'anthropic', 'model' => 'claude-3-5-sonnet-20241022','batch' => 5],
        ['provider' => 'anthropic', 'model' => 'claude-3-haiku-20240307',   'batch' => 15],
        ['provider' => 'google',    'model' => 'gemini-2.0-flash',          'batch' => 10],
        ['provider' => 'deepseek',  'model' => 'deepseek-chat',             'batch' => 8],
        ['provider' => 'mistral',   'model' => 'mistral-large-latest',      'batch' => 5],
    ];

    private array $tagColors = ['blue', 'green', 'red', 'yellow', 'purple', 'pink', 'orange', 'indigo', 'teal', 'cyan'];

    // Seeded entities per team (populated during run)
    private array $teamData = [];

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    public function run(): void
    {
        $this->command->info('🛒  Starting Demo Marketplace seeder...');

        // Disable model observers to avoid triggering jobs/events during seeding
        $this->withoutObservers(function () {
            $this->seedTeamsAndUsers();
            $this->seedTagsPerTeam();
            $this->seedEndpointsAndModels();
            $this->seedJudges();
            $this->seedEvaluations();
        });

        $this->command->info('✅  Demo Marketplace seeder finished!');
    }

    // -------------------------------------------------------------------------
    // Teams & Users
    // -------------------------------------------------------------------------

    private function seedTeamsAndUsers(): void
    {
        $this->command->info('  👥  Seeding teams & users...');

        foreach (self::MARKETS as $code => $market) {
            // ---------------------------------------------------------------
            // Team owner — registered 60-90 days ago
            // ---------------------------------------------------------------
            $ownerRegisteredAt = now()->subDays(rand(60, 90));

            $ownerEmail = "owner.{$code}@demo-marketplace.com";
            $owner = User::firstOrCreate(
                ['email' => $ownerEmail],
                [
                    'name'              => "{$market['flag']} {$market['name']} Owner",
                    'password'          => Hash::make('password'),
                    'email_verified_at' => $ownerRegisteredAt->copy()->addMinutes(rand(5, 60)),
                    'last_active_at'    => now()->subMinutes(rand(5, 120)),
                    'newsletter'        => true,
                    'created_at'        => $ownerRegisteredAt,
                    'updated_at'        => $ownerRegisteredAt,
                ]
            );
            // Ensure timestamps are set even if record already existed
            $owner->forceFill([
                'created_at' => $ownerRegisteredAt,
                'updated_at' => $ownerRegisteredAt,
            ])->saveQuietly();

            // ---------------------------------------------------------------
            // Team — created the same day as the owner (a few minutes later)
            // ---------------------------------------------------------------
            $teamCreatedAt = $ownerRegisteredAt->copy()->addMinutes(rand(10, 120));
            $teamName = "Demo {$market['name']} Team";
            /** @var Team $team */
            $team = Team::firstOrCreate(
                ['name' => $teamName, 'user_id' => $owner->id],
                ['personal_team' => false]
            );
            $team->forceFill([
                'created_at' => $teamCreatedAt,
                'updated_at' => $teamCreatedAt,
            ])->saveQuietly();

            // Make owner a member (Jetstream) and backdate the pivot row
            if (!$team->hasUser($owner)) {
                $team->users()->attach($owner->id, ['role' => 'admin']);
            }
            if ($owner->current_team_id !== $team->id) {
                $owner->forceFill(['current_team_id' => $team->id])->saveQuietly();
            }
            DB::table('team_user')
                ->where('team_id', $team->id)->where('user_id', $owner->id)
                ->update(['created_at' => $teamCreatedAt, 'updated_at' => $teamCreatedAt]);

            $admins     = [];
            $evaluators = [];

            // ---------------------------------------------------------------
            // Admins — join 1-10 days after team creation
            // ---------------------------------------------------------------
            $adminCount = rand(2, 3);
            for ($i = 1; $i <= $adminCount; $i++) {
                $adminRegisteredAt = $teamCreatedAt->copy()->addDays(rand(1, 10));

                $adminEmail = "admin{$i}.{$code}@demo-marketplace.com";
                $admin = User::firstOrCreate(
                    ['email' => $adminEmail],
                    [
                        'name'              => "Admin {$i} ({$market['name']})",
                        'password'          => Hash::make('password'),
                        'email_verified_at' => $adminRegisteredAt->copy()->addMinutes(rand(5, 120)),
                        'last_active_at'    => now()->subHours(rand(1, 48)),
                        'newsletter'        => (bool) rand(0, 1),
                        'created_at'        => $adminRegisteredAt,
                        'updated_at'        => $adminRegisteredAt,
                    ]
                );
                $admin->forceFill([
                    'created_at' => $adminRegisteredAt,
                    'updated_at' => $adminRegisteredAt,
                ])->saveQuietly();

                if (!$team->hasUser($admin)) {
                    $team->users()->attach($admin->id, ['role' => 'admin']);
                }
                if ($admin->current_team_id !== $team->id) {
                    $admin->forceFill(['current_team_id' => $team->id])->saveQuietly();
                }
                DB::table('team_user')
                    ->where('team_id', $team->id)->where('user_id', $admin->id)
                    ->update(['created_at' => $adminRegisteredAt, 'updated_at' => $adminRegisteredAt]);

                $admins[] = $admin;
            }

            // ---------------------------------------------------------------
            // Evaluators — gradually join over the following 0-50 days
            // ---------------------------------------------------------------
            $evaluatorCount = rand(4, 6);
            $evaluatorNames = [
                'Anna', 'Marco', 'Sophie', 'Carlos', 'Lena', 'Tomás',
                'Giulia', 'Pierre', 'Elena', 'Dmitri', 'Marie', 'Hans',
            ];
            $usedNames = [];
            for ($i = 0; $i < $evaluatorCount; $i++) {
                $namePick = $evaluatorNames[array_rand($evaluatorNames)];
                while (in_array($namePick, $usedNames)) {
                    $namePick = $evaluatorNames[array_rand($evaluatorNames)];
                }
                $usedNames[] = $namePick;

                // Each evaluator joins at a progressively later date
                $evalRegisteredAt = $teamCreatedAt->copy()->addDays(rand(0, 50));

                $evalEmail = strtolower("{$namePick}.{$code}") . rand(10, 99) . '@demo-marketplace.com';
                $evaluator = User::firstOrCreate(
                    ['email' => $evalEmail],
                    [
                        'name'              => "{$namePick} ({$market['name']})",
                        'password'          => Hash::make('password'),
                        'email_verified_at' => $evalRegisteredAt->copy()->addMinutes(rand(5, 180)),
                        'last_active_at'    => now()->subMinutes(rand(10, 600)),
                        'newsletter'        => (bool) rand(0, 1),
                        'created_at'        => $evalRegisteredAt,
                        'updated_at'        => $evalRegisteredAt,
                    ]
                );
                $evaluator->forceFill([
                    'created_at' => $evalRegisteredAt,
                    'updated_at' => $evalRegisteredAt,
                ])->saveQuietly();

                if (!$team->hasUser($evaluator)) {
                    $team->users()->attach($evaluator->id, ['role' => 'evaluator']);
                }
                if ($evaluator->current_team_id !== $team->id) {
                    $evaluator->forceFill(['current_team_id' => $team->id])->saveQuietly();
                }
                DB::table('team_user')
                    ->where('team_id', $team->id)->where('user_id', $evaluator->id)
                    ->update(['created_at' => $evalRegisteredAt, 'updated_at' => $evalRegisteredAt]);

                $evaluators[] = $evaluator;
            }

            $this->teamData[$code] = [
                'team'        => $team,
                'owner'       => $owner,
                'admins'      => $admins,
                'evaluators'  => $evaluators,
                'market'      => $market,
                'tags'        => [],
                'endpoints'   => [],
                'models'      => [],
                'judges'      => [],
                'evaluations' => [],
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Tags
    // -------------------------------------------------------------------------

    private function seedTagsPerTeam(): void
    {
        $this->command->info('  🏷️   Seeding tags...');

        $tagSets = [
            ['name' => 'Kitchen Equipment',  'color' => 'red'],
            ['name' => 'Tableware',          'color' => 'blue'],
            ['name' => 'Beverages',          'color' => 'cyan'],
            ['name' => 'Disposables',        'color' => 'yellow'],
            ['name' => 'Hotel Supplies',     'color' => 'purple'],
            ['name' => 'High Priority',      'color' => 'orange'],
            ['name' => 'Regression Test',    'color' => 'pink'],
            ['name' => 'A/B Test',           'color' => 'indigo'],
            ['name' => 'Production',         'color' => 'green'],
            ['name' => 'Experimental',       'color' => 'teal'],
        ];

        foreach ($this->teamData as $code => &$data) {
            $team = $data['team'];
            $tags = [];
            foreach ($tagSets as $tagDef) {
                $tag = Tag::firstOrCreate(
                    ['team_id' => $team->id, 'name' => $tagDef['name']],
                    ['color' => $tagDef['color']]
                );
                $tags[$tagDef['name']] = $tag;
            }
            $data['tags'] = $tags;
        }
    }

    // -------------------------------------------------------------------------
    // Endpoints & Models
    // -------------------------------------------------------------------------

    private function seedEndpointsAndModels(): void
    {
        $this->command->info('  🔌  Seeding endpoints & search models...');

        $endpointDefs = [
            [
                'name_tpl'   => 'Demo Search API v2 ({country})',
                'desc_tpl'   => 'Primary demo marketplace search API for {country} market (v2)',
                'url_tpl'    => 'https://search-api.demo-marketplace.com/v2/{lang}/search',
                'method'     => 'GET',
                'mapper'     => implode("\n", [
                    'id: data.items.*.productId',
                    'name: data.items.*.title',
                    'image: data.items.*.thumbnailUrl',
                    'price: data.items.*.pricing.grossPrice',
                    'brand: data.items.*.brand.name',
                    'category: data.items.*.category.name',
                    'sku: data.items.*.sku',
                ]),
                'active' => true,
            ],
            [
                'name_tpl'   => 'Demo Search API v3 ({country})',
                'desc_tpl'   => 'Next-gen semantic search endpoint for {country} — experimental',
                'url_tpl'    => 'https://search-api.demo-marketplace.com/v3/{lang}/semantic',
                'method'     => 'POST',
                'mapper'     => implode("\n", [
                    'id: results.*.id',
                    'name: results.*.name',
                    'image: results.*.media.primaryImage',
                    'price: results.*.price.gross',
                    'score: results.*.relevanceScore',
                ]),
                'active' => true,
            ],
            [
                'name_tpl'   => 'Legacy Catalog API ({country})',
                'desc_tpl'   => 'Legacy catalog search — to be deprecated after v3 goes live',
                'url_tpl'    => 'https://catalog-legacy.demo-marketplace.com/{lang}/find',
                'method'     => 'GET',
                'mapper'     => implode("\n", [
                    'id: catalog.hits.*.id',
                    'name: catalog.hits.*.label',
                    'image: catalog.hits.*.image',
                    'price: catalog.hits.*.prices.0',
                ]),
                'active' => false,
            ],
        ];

        $modelDefs = [
            [
                'name_tpl'  => 'Baseline BM25 ({country})',
                'desc'      => 'Standard BM25 keyword search — current production baseline',
                'params'    => ['q' => '#query#', 'algorithm' => 'bm25', 'size' => '20'],
                'pinned'    => true,
                'keywordSet'=> 'kitchen',
            ],
            [
                'name_tpl'  => 'LTR v2 ({country})',
                'desc'      => 'Learning-to-rank model trained on 6 months of click data',
                'params'    => ['q' => '#query#', 'model' => 'ltr_v2', 'size' => '20'],
                'pinned'    => true,
                'keywordSet'=> 'mixed',
            ],
            [
                'name_tpl'  => 'Semantic Dense ({country})',
                'desc'      => 'ANN dense retrieval via text embeddings (ada-002)',
                'params'    => ['q' => '#query#', 'mode' => 'semantic', 'size' => '20'],
                'pinned'    => false,
                'keywordSet'=> 'mixed',
            ],
            [
                'name_tpl'  => 'Hybrid Search ({country})',
                'desc'      => 'Combination of BM25 + semantic with RRF fusion',
                'params'    => ['q' => '#query#', 'mode' => 'hybrid', 'alpha' => '0.7'],
                'pinned'    => false,
                'keywordSet'=> 'full',
            ],
            [
                'name_tpl'  => 'Personalised Rerank ({country})',
                'desc'      => 'Cross-encoder reranking with user-behaviour personalisation layer',
                'params'    => ['q' => '#query#', 'mode' => 'personalised', 'depth' => '100'],
                'pinned'    => false,
                'keywordSet'=> 'full',
            ],
        ];

        $keywordSets = [
            'kitchen' => [
                'commercial refrigerator', 'industrial dishwasher', 'convection oven',
                'espresso machine', 'deep fryer', 'combi steamer', 'blast chiller',
                'commercial blender', 'meat slicer', 'spiral mixer',
            ],
            'mixed' => [
                'commercial refrigerator', 'industrial dishwasher', 'convection oven',
                'porcelain dinner plate', 'stainless steel cutlery set', 'beer tap system',
                'hotel linen', 'restaurant chair', 'kraft paper bags', 'sous vide circulator',
                'espresso machine', 'wine glass set', 'chafing dish set', 'meat slicer',
                'commercial blender',
            ],
            'full' => array_slice(self::DEMO_KEYWORDS, 0, 25),
        ];

        foreach ($this->teamData as $code => &$data) {
            $team     = $data['team'];
            $allUsers = array_merge([$data['owner']], $data['admins']);
            $market   = $data['market'];

            $endpoints = [];
            foreach ($endpointDefs as $idx => $def) {
                $endpointName = str_replace('{country}', $market['name'], $def['name_tpl']);
                $url = str_replace(['{lang}', '{country}'], [$market['lang'], $market['name']], $def['url_tpl']);

                $endpoint = SearchEndpoint::firstOrCreate(
                    ['team_id' => $team->id, 'name' => $endpointName],
                    [
                        'user_id'      => $allUsers[array_rand($allUsers)]->id,
                        'type'         => SearchEndpoint::TYPE_SEARCH_API,
                        'url'          => $url,
                        'method'       => $def['method'],
                        'description'  => str_replace('{country}', $market['name'], $def['desc_tpl']),
                        'headers'      => [
                            'Accept-Language' => $market['lang'],
                            'X-Market'        => strtoupper($code),
                            'Authorization'   => 'Bearer ' . Str::random(32),
                        ],
                        'mapper_type'  => SearchEndpoint::MAPPER_TYPE_DOT_ARRAY,
                        'mapper_code'  => $def['mapper'],
                        'settings'     => [],
                        'archived_at'  => $def['active'] ? null : now()->subMonths(rand(1, 3)),
                    ]
                );
                $endpoints[] = $endpoint;
            }
            $data['endpoints'] = $endpoints;

            // Build models (use only active endpoints)
            $activeEndpoints = array_filter($endpoints, fn($e) => $e->archived_at === null);
            $activeEndpoints = array_values($activeEndpoints);

            $models = [];
            $modelCount = rand(3, 5);
            $selectedDefs = array_slice($modelDefs, 0, $modelCount);
            foreach ($selectedDefs as $mDef) {
                $modelName  = str_replace('{country}', $market['name'], $mDef['name_tpl']);
                $endpoint   = $activeEndpoints[array_rand($activeEndpoints)];
                $keywords   = $keywordSets[$mDef['keywordSet']];

                $model = SearchModel::firstOrCreate(
                    ['team_id' => $team->id, 'name' => $modelName],
                    [
                        'user_id'     => $allUsers[array_rand($allUsers)]->id,
                        'endpoint_id' => $endpoint->id,
                        'description' => $mDef['desc'],
                        'headers'     => [],
                        'params'      => $mDef['params'],
                        'body'        => '',
                        'body_type'   => SearchModel::BODY_TYPE_JSON,
                        'settings'    => [SearchModel::SETTING_KEYWORDS => $keywords],
                        'pinned'      => $mDef['pinned'],
                    ]
                );
                $models[] = $model;
            }
            $data['models'] = $models;
        }
    }

    // -------------------------------------------------------------------------
    // Judges
    // -------------------------------------------------------------------------

    private function seedJudges(): void
    {
        $this->command->info('  🤖  Seeding AI judges...');

        foreach ($this->teamData as $code => &$data) {
            $team     = $data['team'];
            $allUsers = array_merge([$data['owner']], $data['admins']);
            $judgeCount = rand(3, 5);
            $configs  = $this->pickRandom(self::JUDGE_CONFIGS, $judgeCount);

            $judges = [];
            foreach ($configs as $idx => $cfg) {
                $judgeName = ucfirst($cfg['provider']) . ' ' . $cfg['model'] . ' Judge #' . ($idx + 1);
                $judge = Judge::firstOrCreate(
                    ['team_id' => $team->id, 'name' => $judgeName],
                    [
                        'user_id'       => $allUsers[array_rand($allUsers)]->id,
                        'description'   => 'Automated relevance judge using ' . $cfg['model'] . ' for demo marketplace search quality assessment in ' . $data['market']['name'],
                        'provider'      => $cfg['provider'],
                        'model_name'    => $cfg['model'],
                        'api_key'       => 'seed-fake-key-' . Str::random(20),
                        'prompt_binary' => Judge::getDefaultPrompt('binary'),
                        'prompt_graded' => Judge::getDefaultPrompt('graded'),
                        'prompt_detail' => Judge::getDefaultPrompt('detail'),
                        'settings'      => [Judge::SETTING_BATCH_SIZE => $cfg['batch']],
                        'archived_at'   => ($idx === count($configs) - 1) ? now()->subWeeks(rand(1, 4)) : null,
                    ]
                );

                // Assign random tags to judge
                if (!empty($data['tags'])) {
                    $judgeTags = $this->pickRandom(array_values($data['tags']), rand(1, 3));
                    foreach ($judgeTags as $tag) {
                        DB::table('judge_tags')->insertOrIgnore([
                            'judge_id'   => $judge->id,
                            'tag_id'     => $tag->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $judges[] = $judge;
            }
            $data['judges'] = $judges;
        }
    }

    // -------------------------------------------------------------------------
    // Evaluations (the big one)
    // -------------------------------------------------------------------------

    private function seedEvaluations(): void
    {
        $this->command->info('  📊  Seeding evaluations (this may take a while)...');

        $evaluationTemplates = [
            // Finished evaluations (most common)
            [
                'name_tpl'   => '{model} — Relevance Eval #{n}',
                'desc_tpl'   => 'Full relevance assessment for {model} on {market} market keywords',
                'status'     => SearchEvaluation::STATUS_FINISHED,
                'scale'      => 'graded',
                'archived'   => false,
                'pinned'     => false,
                'kwCount'    => [15, 22],
                'metrics'    => [
                    ['scorer_type' => 'precision', 'num_results' => 10],
                    ['scorer_type' => 'ndcg',      'num_results' => 10],
                    ['scorer_type' => 'ap',         'num_results' => 10],
                    ['scorer_type' => 'err',        'num_results' => 10],
                ],
            ],
            [
                'name_tpl'   => '{model} — Binary Quick Check #{n}',
                'desc_tpl'   => 'Binary relevance spot-check for {model}',
                'status'     => SearchEvaluation::STATUS_FINISHED,
                'scale'      => 'binary',
                'archived'   => false,
                'pinned'     => false,
                'kwCount'    => [10, 15],
                'metrics'    => [
                    ['scorer_type' => 'precision', 'num_results' => 5],
                    ['scorer_type' => 'rr',         'num_results' => 5],
                ],
            ],
            [
                'name_tpl'   => '{model} — Detail Evaluation #{n}',
                'desc_tpl'   => 'Detailed 5-point scale evaluation for nuanced quality assessment',
                'status'     => SearchEvaluation::STATUS_FINISHED,
                'scale'      => 'detail',
                'archived'   => false,
                'pinned'     => false,
                'kwCount'    => [12, 18],
                'metrics'    => [
                    ['scorer_type' => 'ndcg', 'num_results' => 10],
                    ['scorer_type' => 'dcg',  'num_results' => 10],
                    ['scorer_type' => 'cg',   'num_results' => 10],
                ],
            ],
            // Active evaluations
            [
                'name_tpl'   => '{model} — Ongoing A/B Test #{n}',
                'desc_tpl'   => 'Active A/B relevance evaluation currently collecting human feedback',
                'status'     => SearchEvaluation::STATUS_ACTIVE,
                'scale'      => 'graded',
                'archived'   => false,
                'pinned'     => true,
                'kwCount'    => [18, 25],
                'metrics'    => [
                    ['scorer_type' => 'ndcg',      'num_results' => 10],
                    ['scorer_type' => 'precision',  'num_results' => 10],
                ],
            ],
            // Pending evaluations
            [
                'name_tpl'   => '{model} — Scheduled Eval #{n}',
                'desc_tpl'   => 'Scheduled evaluation queued for next evaluation cycle',
                'status'     => SearchEvaluation::STATUS_PENDING,
                'scale'      => 'graded',
                'archived'   => false,
                'pinned'     => false,
                'kwCount'    => [15, 20],
                'metrics'    => [
                    ['scorer_type' => 'ndcg', 'num_results' => 10],
                    ['scorer_type' => 'ap',   'num_results' => 10],
                ],
            ],
            // Archived evaluations
            [
                'name_tpl'   => '{model} — Archived Baseline #{n}',
                'desc_tpl'   => 'Archived baseline evaluation from previous quarter',
                'status'     => SearchEvaluation::STATUS_FINISHED,
                'scale'      => 'graded',
                'archived'   => true,
                'pinned'     => false,
                'kwCount'    => [12, 18],
                'metrics'    => [
                    ['scorer_type' => 'ndcg', 'num_results' => 10],
                    ['scorer_type' => 'cg',   'num_results' => 10],
                ],
            ],
        ];

        $guidelinesService = app(ScoringGuidelinesService::class);

        foreach ($this->teamData as $code => &$data) {
            $team       = $data['team'];
            $allUsers   = array_merge([$data['owner']], $data['admins']);
            $evaluators = $data['evaluators'];
            $judges     = $data['judges'];
            $market     = $data['market'];
            $tags       = $data['tags'];

            $evaluations = [];
            $evalN = 1;

            foreach ($data['models'] as $model) {
                // Number of evaluations per model: 3-5
                $evalCount = rand(3, 5);

                // Shuffle templates to get variety
                $templatePool = $evaluationTemplates;
                shuffle($templatePool);

                for ($i = 0; $i < $evalCount; $i++) {
                    $tpl = $templatePool[$i % count($templatePool)];

                    $evalName = strtr($tpl['name_tpl'], [
                        '{model}'  => $model->name,
                        '{market}' => $market['name'],
                        '{n}'      => $evalN,
                    ]);
                    $evalDesc = strtr($tpl['desc_tpl'], [
                        '{model}'  => $model->name,
                        '{market}' => $market['name'],
                    ]);

                    // Pick keyword count
                    $kwCount = rand($tpl['kwCount'][0], $tpl['kwCount'][1]);
                    $keywords = $this->pickRandom(self::DEMO_KEYWORDS, $kwCount);

                    $status = $tpl['status'];

                    // Created dates spread over the last 12 months
                    $createdAt  = now()->subDays(rand(1, 365));
                    $finishedAt = null;
                    if ($status === SearchEvaluation::STATUS_FINISHED) {
                        $finishedAt = $createdAt->copy()->addHours(rand(2, 72));
                    }

                    $evaluation = SearchEvaluation::create([
                        SearchEvaluation::FIELD_USER_ID              => $allUsers[array_rand($allUsers)]->id,
                        SearchEvaluation::FIELD_MODEL_ID             => $model->id,
                        SearchEvaluation::FIELD_SCALE_TYPE           => $tpl['scale'],
                        SearchEvaluation::FIELD_STATUS               => $status,
                        SearchEvaluation::FIELD_PROGRESS             => $this->calcProgress($status),
                        SearchEvaluation::FIELD_NAME                 => $evalName,
                        SearchEvaluation::FIELD_DESCRIPTION          => $evalDesc,
                        SearchEvaluation::FIELD_SETTINGS             => [
                            SearchEvaluation::SETTING_REUSE_STRATEGY    => SearchEvaluation::REUSE_STRATEGY_NONE,
                            SearchEvaluation::SETTING_SHOW_POSITION     => (bool) rand(0, 1),
                            SearchEvaluation::SETTING_FEEDBACK_STRATEGY => rand(1, 2),
                            SearchEvaluation::SETTING_AUTO_RESTART      => false,
                            SearchEvaluation::SETTING_SCORING_GUIDELINES => $guidelinesService->getDefaultScoringGuidelines()[$tpl['scale']],
                        ],
                        SearchEvaluation::FIELD_MAX_NUM_RESULTS      => $status !== SearchEvaluation::STATUS_PENDING ? 20 : null,
                        SearchEvaluation::FIELD_SUCCESSFUL_KEYWORDS  => $status !== SearchEvaluation::STATUS_PENDING ? $kwCount : 0,
                        SearchEvaluation::FIELD_FAILED_KEYWORDS      => 0,
                        SearchEvaluation::FIELD_ARCHIVED             => $tpl['archived'],
                        SearchEvaluation::FIELD_PINNED               => $tpl['pinned'],
                        SearchEvaluation::FIELD_FINISHED_AT          => $finishedAt,
                        SearchEvaluation::FIELD_CREATED_AT           => $createdAt,
                        SearchEvaluation::FIELD_UPDATED_AT           => $finishedAt ?? $createdAt->copy()->addMinutes(rand(30, 300)),
                    ]);

                    // Assign tags to evaluation
                    $evalTags = $this->pickRandom(array_values($tags), rand(1, 3));
                    foreach ($evalTags as $tag) {
                        DB::table('evaluation_tags')->insertOrIgnore([
                            'evaluation_id' => $evaluation->id,
                            'tag_id'        => $tag->id,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }

                    // --- Metrics ---
                    $metricModels = [];
                    foreach ($tpl['metrics'] as $mDef) {
                        $prevValue = $status === SearchEvaluation::STATUS_FINISHED
                            ? round(rand(30, 75) / 100, 4)
                            : null;
                        $value = $status === SearchEvaluation::STATUS_FINISHED
                            ? round(rand(35, 85) / 100, 4)
                            : 0;

                        $metric = EvaluationMetric::withoutEvents(fn() => EvaluationMetric::create([
                            EvaluationMetric::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                            EvaluationMetric::FIELD_SCORER_TYPE          => $mDef['scorer_type'],
                            EvaluationMetric::FIELD_VALUE                => $value,
                            EvaluationMetric::FIELD_PREVIOUS_VALUE       => $prevValue,
                            EvaluationMetric::FIELD_NUM_RESULTS          => $mDef['num_results'],
                            EvaluationMetric::FIELD_SETTINGS             => [],
                            EvaluationMetric::FIELD_FINISHED_AT          => $finishedAt,
                        ]));

                        // Historical metric values (last 15 runs)
                        if ($status === SearchEvaluation::STATUS_FINISHED) {
                            $this->seedMetricValues($metric, $createdAt);
                        }

                        $metricModels[] = $metric;
                    }

                    // --- Keywords, Snapshots, Feedbacks ---
                    if ($status !== SearchEvaluation::STATUS_PENDING) {
                        $this->seedKeywordsAndSnapshots(
                            $evaluation,
                            $keywords,
                            $metricModels,
                            $evaluators,
                            $judges,
                            $status,
                            $tpl['scale'],
                            $createdAt
                        );
                    }

                    // --- Judge logs ---
                    if (!empty($judges) && $status !== SearchEvaluation::STATUS_PENDING) {
                        $this->seedJudgeLogs($judges, $evaluation, $team->id, $tpl['scale'], $createdAt);
                    }

                    $evaluations[] = $evaluation;
                    $evalN++;
                }
            }

            $data['evaluations'] = $evaluations;

            // Set a baseline evaluation for the team (the first finished one)
            $baseline = collect($evaluations)->first(fn($e) => $e->status === SearchEvaluation::STATUS_FINISHED);
            if ($baseline) {
                $team->forceFill(['baseline_evaluation_id' => $baseline->id])->saveQuietly();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Keywords, snapshots, feedbacks
    // -------------------------------------------------------------------------

    private function seedKeywordsAndSnapshots(
        SearchEvaluation $evaluation,
        array $keywords,
        array $metrics,
        array $evaluators,
        array $judges,
        int $status,
        string $scaleType,
        Carbon $createdAt
    ): void {
        $feedbackStrategy = $evaluation->settings[SearchEvaluation::SETTING_FEEDBACK_STRATEGY] ?? 1;

        $demoProducts = $this->getDemoProducts();

        foreach ($keywords as $keyword) {
            /** @var EvaluationKeyword $kwModel */
            $kwModel = EvaluationKeyword::create([
                EvaluationKeyword::FIELD_SEARCH_EVALUATION_ID => $evaluation->id,
                EvaluationKeyword::FIELD_KEYWORD              => $keyword,
                EvaluationKeyword::FIELD_TOTAL_COUNT          => rand(80, 5000),
                EvaluationKeyword::FIELD_EXECUTION_CODE       => 200,
                EvaluationKeyword::FIELD_EXECUTION_MESSAGE    => 'OK',
                EvaluationKeyword::FIELD_FAILED               => false,
            ]);

            // 10-20 snapshots per keyword
            $snapshotCount = rand(10, 20);
            $products = $this->pickRandom($demoProducts, $snapshotCount);

            $kwValue = round(rand(20, 90) / 100, 4);

            // Per-keyword metric values
            foreach ($metrics as $metric) {
                KeywordMetric::create([
                    KeywordMetric::FIELD_EVALUATION_KEYWORD_ID => $kwModel->id,
                    KeywordMetric::FIELD_EVALUATION_METRIC_ID  => $metric->id,
                    KeywordMetric::FIELD_VALUE                 => $kwValue,
                ]);
            }

            // Snapshots
            foreach ($products as $pos => $product) {
                // Create snapshot WITHOUT triggering the observer (which creates feedbacks)
                $snapshot = SearchSnapshot::withoutEvents(fn() => SearchSnapshot::create([
                    SearchSnapshot::FIELD_EVALUATION_KEYWORD_ID => $kwModel->id,
                    SearchSnapshot::FIELD_POSITION              => $pos + 1,
                    SearchSnapshot::FIELD_DOC_ID                => $product['id'],
                    SearchSnapshot::FIELD_IMAGE                 => $product['image'],
                    SearchSnapshot::FIELD_NAME                  => $product['name'],
                    SearchSnapshot::FIELD_DOC                   => [
                        'id'       => $product['id'],
                        'name'     => $product['name'],
                        'price'    => $product['price'],
                        'brand'    => $product['brand'],
                        'category' => $product['category'],
                        'sku'      => $product['sku'],
                    ],
                ]));

                // Create feedbacks manually (bypass observer)
                $this->seedFeedbacks(
                    $snapshot,
                    $feedbackStrategy,
                    $evaluators,
                    $judges,
                    $status,
                    $scaleType
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // Feedbacks
    // -------------------------------------------------------------------------

    private function seedFeedbacks(
        SearchSnapshot $snapshot,
        int $feedbackStrategy,
        array $evaluators,
        array $judges,
        int $status,
        string $scaleType
    ): void {
        $gradeRange = match($scaleType) {
            'binary'  => [0, 1],
            'detail'  => [0, 4],
            default   => [0, 3], // graded
        };

        // Create N feedback slots per snapshot (based on feedback strategy, 1-2)
        $slotCount = max(1, $feedbackStrategy);

        for ($slot = 0; $slot < $slotCount; $slot++) {
            $isFinished = $status === SearchEvaluation::STATUS_FINISHED;
            $isActive   = $status === SearchEvaluation::STATUS_ACTIVE;

            // Human feedback (50% of slots for evaluators)
            $useHuman = !empty($evaluators) && (rand(0, 1) === 1 || ($isFinished && rand(0, 100) < 80));
            // Judge feedback (40% of slots when we have judges and evaluation is active/finished)
            $useJudge = !empty($judges) && !$useHuman && rand(0, 100) < 60;

            if ($useHuman) {
                $evaluator = $evaluators[array_rand($evaluators)];
                $graded    = $isFinished ? (rand(0, 100) < 95) : (rand(0, 100) < 60);
                $grade     = $graded ? rand($gradeRange[0], $gradeRange[1]) : null;
                $reason    = $graded && rand(0, 1) ? $this->fakeReason($grade, $gradeRange[1]) : null;

                DB::table('user_feedbacks')->insert([
                    'user_id'            => $evaluator->id,
                    'judge_id'           => null,
                    'search_snapshot_id' => $snapshot->id,
                    'grade'              => $grade,
                    'reason'             => $reason,
                    'created_at'         => now()->subDays(rand(0, 90)),
                    'updated_at'         => now()->subDays(rand(0, 30)),
                ]);
            } elseif ($useJudge) {
                $judge  = $judges[array_rand($judges)];
                $graded = $isFinished ? (rand(0, 100) < 90) : (rand(0, 100) < 40);
                $grade  = $graded ? rand($gradeRange[0], $gradeRange[1]) : null;

                DB::table('user_feedbacks')->insert([
                    'user_id'            => null,
                    'judge_id'           => $judge->id,
                    'search_snapshot_id' => $snapshot->id,
                    'grade'              => $grade,
                    'reason'             => null,
                    'created_at'         => now()->subDays(rand(0, 60)),
                    'updated_at'         => now()->subDays(rand(0, 20)),
                ]);
            } else {
                // Ungraded / unassigned slot
                DB::table('user_feedbacks')->insert([
                    'user_id'            => null,
                    'judge_id'           => null,
                    'search_snapshot_id' => $snapshot->id,
                    'grade'              => null,
                    'reason'             => null,
                    'created_at'         => now()->subDays(rand(0, 120)),
                    'updated_at'         => now()->subDays(rand(0, 60)),
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Metric values (historical spark-line data)
    // -------------------------------------------------------------------------

    private function seedMetricValues(EvaluationMetric $metric, Carbon $createdAt): void
    {
        $baseValue = round(rand(30, 75) / 100, 4);
        $count     = rand(8, 15);

        $rows = [];
        for ($i = $count; $i >= 0; $i--) {
            $delta    = (rand(-8, 12)) / 100;
            $value    = max(0.01, min(1.0, round($baseValue + $delta, 4)));
            $baseValue = $value;
            $ts = $createdAt->copy()->addDays($count - $i);

            $rows[] = [
                'evaluation_metric_id' => $metric->id,
                'value'                => $value,
                'created_at'           => $ts,
                'updated_at'           => $ts,
            ];
        }

        DB::table('metric_values')->insert($rows);
    }

    // -------------------------------------------------------------------------
    // Judge logs
    // -------------------------------------------------------------------------

    private function seedJudgeLogs(
        array $judges,
        SearchEvaluation $evaluation,
        int $teamId,
        string $scaleType,
        Carbon $baseDate
    ): void {
        // 20-80 log entries per evaluation per judge
        foreach ($judges as $judge) {
            $logCount = rand(20, 80);
            $rows     = [];

            for ($i = 0; $i < $logCount; $i++) {
                $success = rand(0, 100) < 95; // 95% success rate
                $latency = rand(300, 4000);
                $prompt  = rand(800, 3000);
                $compl   = rand(50, 400);
                $ts      = $baseDate->copy()->addMinutes(rand(0, 60 * 72));

                $rows[] = [
                    'judge_id'              => $judge->id,
                    'team_id'               => $teamId,
                    'search_evaluation_id'  => $evaluation->id,
                    'provider'              => $judge->provider,
                    'model'                 => $judge->model_name,
                    'http_status_code'      => $success ? 200 : $this->pickRandom([400, 429, 500, 503], 1)[0],
                    'request_url'           => 'https://api.' . $judge->provider . '.com/v1/chat/completions',
                    'request_body'          => json_encode([
                        'model'       => $judge->model_name,
                        'messages'    => [['role' => 'user', 'content' => '[seeded prompt]']],
                        'temperature' => 0.0,
                    ]),
                    'response_body'         => $success ? json_encode(['choices' => [['message' => ['content' => '[seeded response]']]]]) : null,
                    'error_message'         => $success ? null : 'Rate limit exceeded or server error',
                    'latency_ms'            => $success ? $latency : null,
                    'prompt_tokens'         => $success ? $prompt : null,
                    'completion_tokens'     => $success ? $compl : null,
                    'total_tokens'          => $success ? ($prompt + $compl) : null,
                    'batch_size'            => rand(1, 10),
                    'scale_type'            => $scaleType,
                    'created_at'            => $ts,
                    'updated_at'            => $ts,
                ];
            }

            // Insert in chunks to avoid oversized queries
            foreach (array_chunk($rows, 50) as $chunk) {
                DB::table('judge_logs')->insert($chunk);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Run callable with all model observers disabled.
     */
    private function withoutObservers(callable $callback): void
    {
        // We run the callback normally; events that fire synchronously are
        // harmless in a seeding context (no queue workers = no async side-effects).
        // For SearchSnapshot we call ::withoutEvents() explicitly where needed.
        $callback();
    }

    private function calcProgress(int $status): float
    {
        return match($status) {
            SearchEvaluation::STATUS_FINISHED => 100.0,
            SearchEvaluation::STATUS_ACTIVE   => round(rand(10, 85), 2),
            default                           => 0.0,
        };
    }

    /**
     * Pick $n random unique elements from $array.
     */
    private function pickRandom(array $array, int $n): array
    {
        if ($n >= count($array)) {
            return $array;
        }
        $keys = array_rand($array, $n);
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        return array_map(fn($k) => $array[$k], $keys);
    }

    private function fakeReason(int $grade, int $maxGrade): string
    {
        $reasons = [
            0 => [
                'Result is completely irrelevant — no connection to the search query context.',
                'Product does not match the search intent at all.',
                'Wrong category — consumer product shown for professional query.',
            ],
            1 => [
                'Marginally relevant but missing key professional specs.',
                'Product type matches but wrong capacity for catering use.',
                'Could be relevant with better targeting.',
            ],
            2 => [
                'Good match but price point not shown.',
                'Relevant product, description could be more detailed.',
                'Correct category and type.',
            ],
            3 => [
                'Highly relevant — professional grade, correct specs.',
                'Excellent match with full product details.',
                'Top result — exactly what the buyer would want.',
            ],
            4 => [
                'Perfect match — best-in-class product for this query.',
                'Ideal relevance score — premium professional equipment.',
            ],
        ];

        $bucket = min($grade, $maxGrade, 4);
        $bucket = max($bucket, 0);
        $opts   = $reasons[$bucket] ?? $reasons[2];
        return $opts[array_rand($opts)];
    }

    /**
     * Return a pool of fake demo marketplace products for snapshots.
     */
    private function getDemoProducts(): array
    {
        $brands = ['Rational', 'Hobart', 'Electrolux Professional', 'Baron', 'Unox',
                   'Giorik', 'Henny Penny', 'Robot Coupe', 'Sammic', 'Manitowoc',
                   'True Refrigeration', 'Fagor', 'Comenda', 'Meiko', 'Welbilt'];

        $categories = [
            'Cooking Equipment', 'Refrigeration', 'Dishwashing', 'Food Preparation',
            'Beverage Equipment', 'Tableware', 'Disposables', 'Furniture', 'Hotel Supplies',
        ];

        $products = [];
        for ($i = 1; $i <= 200; $i++) {
            $brand    = $brands[array_rand($brands)];
            $category = $categories[array_rand($categories)];
            $products[] = [
                'id'       => 'DEMO-' . str_pad((string)$i, 6, '0', STR_PAD_LEFT),
                'name'     => $brand . ' ' . $this->fakeProductName($category) . ' Pro ' . chr(65 + ($i % 26)) . rand(100, 999),
                'image'    => 'https://cdn.demo-marketplace.com/products/' . $i . '.jpg',
                'price'    => round(rand(49, 9999) + rand(0, 99) / 100, 2),
                'brand'    => $brand,
                'category' => $category,
                'sku'      => strtoupper(Str::random(3)) . '-' . rand(10000, 99999),
            ];
        }

        return $products;
    }

    private function fakeProductName(string $category): string
    {
        $names = [
            'Cooking Equipment'  => ['Combi Oven', 'Salamander Grill', 'Deep Fryer', 'Induction Hob', 'Oven'],
            'Refrigeration'      => ['Chest Freezer', 'Display Fridge', 'Blast Chiller', 'Cold Room Unit'],
            'Dishwashing'        => ['Rack Washer', 'Undercounter Washer', 'Flight Dishwasher'],
            'Food Preparation'   => ['Food Processor', 'Meat Slicer', 'Vacuum Sealer', 'Spiral Mixer'],
            'Beverage Equipment' => ['Espresso Machine', 'Beer Tap', 'Juice Extractor', 'Coffee Grinder'],
            'Tableware'          => ['Dinner Set', 'Cutlery Set', 'Wine Glass', 'Serving Dish'],
            'Disposables'        => ['Takeaway Box', 'Paper Bag', 'Food Container', 'Packaging Roll'],
            'Furniture'          => ['Dining Chair', 'Folding Table', 'Bar Stool', 'Outdoor Set'],
            'Hotel Supplies'     => ['Linen Set', 'Towel Bundle', 'Mattress Cover', 'Amenity Kit'],
        ];

        $opts = $names[$category] ?? ['Product'];
        return $opts[array_rand($opts)];
    }
}
