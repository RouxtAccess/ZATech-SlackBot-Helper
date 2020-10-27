<?php

return [
    'api_endpoint' => env('SLACK_API_ENDPOINT', 'https://slack.com/api/'),
    'bot_oauth_access' => env('SLACK_BOT_OAUTH_ACCESS_TOKEN'),
    'bot_id' => env('SLACK_BOT_ID'),

    'jobpostings_helper' => [
        'reaction' => 'zap',
        'channels' => [],
        'blocks' => [
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => "mrkdwn",
                        'text' => "Hello there!\nThe Community would like you to think about using the Slack Action (the lightning bolt :zap: to the left of a new message) to add your job posting.\nThat way jobpostings are more consistent and all parties win :slightly_smiling_face:",
                    ]
            ],
            ['type' => 'divider'],
        ],
    ],

    'cache' => [
        'default_ttl' => env('SLACK_CACHE_DEFAULT_TTL', 60*60*24*7),
    ]


];
