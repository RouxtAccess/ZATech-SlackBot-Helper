<?php

return [
    'api_endpoint' => env('SLACK_API_ENDPOINT', 'https://slack.com/api/'),
    'bot_oauth_access' => env('SLACK_BOT_OAUTH_ACCESS_TOKEN'),
    'bot_id' => env('SLACK_BOT_ID'),

    'jobpostings_helper' => [
        'reaction' => 'zap',
        'channels' => ['C1RAZB24S', 'CA6AG7TKL', 'CQG9U8EBC'],
        'blocks' => [
            [
                'type' => 'section',
                'text' =>
                    [
                        'type' => "mrkdwn",
                        'text' => "Hello there!\n" .
                            "The Community would like you to think about using the *\"Post a Job\"* Slack Action (the lightning bolt :zap: to the left of a new message) to add your job posting.\n" .
                            "That way jobpostings are more consistent and all parties win :slightly_smiling_face:",
                    ]
            ],
            ['type' => 'divider'],
        ],
    ],

    'invite_helper' => [
        'users' => ['USLACKBOT'],
        'channels' => ['G01E86S6FPF'],
        'user_response' => [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' =>
                        [
                            'type' => "mrkdwn",
                            'text' => "Hi :slightly_smiling_face:\n" .
                            "Exciting that you're wanting to invite new people!\n" .
                            "Would you be able to rather send them to <https://zatech.co.za|zatech.co.za>?\n" .
                            "That way they can sign themselves up :slightly_smiling_face:\n" .
                            "\n" .
                            "Have a fantastic day! :rocket:",
                        ],
                ],
            ],
        ],
        'thread_response' => [
            'emojis' => ['robot_face', 'heavy_check_mark'],
            'blocks' => [
                [
                    'type' => 'section',
                    'text' =>
                        [
                            'type' => "mrkdwn",
                            'text' => "We sent the message to the user :)",
                        ]
                ],
            ],
        ],
    ],

    'cache' => [
        'default_ttl' => env('SLACK_CACHE_DEFAULT_TTL', 60*60*24*7),
    ]


];
