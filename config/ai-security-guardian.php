<?php

return [
    'enabled' => env('AI_SECURITY_ENABLED', true),

    'ui' => [
        'enabled' => env('AI_SECURITY_UI_ENABLED', true),
        'prefix' => env('AI_SECURITY_UI_PREFIX', 'ai-security'),
        'middleware' => ['web', 'auth'],

        // When true, the dashboard is reachable without passing the
        // `viewAiSecurity` gate while the app is in the local/testing
        // environment. Keep this false on any reachable host: the dashboard
        // exposes a map of the application's known vulnerabilities.
        'allow_unauthenticated_local' => env('AI_SECURITY_UI_ALLOW_UNAUTH_LOCAL', false),

        'theme' => env('AI_SECURITY_UI_THEME', 'auto'),
        'rtl' => env('AI_SECURITY_UI_RTL', false),
        'locale' => env('AI_SECURITY_UI_LOCALE', env('APP_LOCALE', 'en')),
        'available_locales' => array_values(array_filter(array_map('trim', explode(',', env('AI_SECURITY_UI_AVAILABLE_LOCALES', 'en,ar'))))),
        'rtl_locales' => array_values(array_filter(array_map('trim', explode(',', env('AI_SECURITY_UI_RTL_LOCALES', 'ar'))))),
    ],

    'project_type' => env('AI_SECURITY_PROJECT_TYPE', 'laravel-saas-erp'),

    'provider' => env('AI_SECURITY_PROVIDER', 'openai'),

    'ai' => [
        'enabled' => env('AI_SECURITY_AI_ENABLED', true),
        'deep_scan_enabled' => env('AI_SECURITY_DEEP_SCAN_ENABLED', true),
        'max_findings_per_request' => env('AI_SECURITY_AI_MAX_FINDINGS', 12),
        'max_text_length' => env('AI_SECURITY_AI_MAX_TEXT_LENGTH', 360),
        'max_references' => env('AI_SECURITY_AI_MAX_REFERENCES', 3),
        'max_completion_tokens' => env('AI_SECURITY_AI_MAX_COMPLETION_TOKENS', 1200),
        'cache_ttl' => env('AI_SECURITY_AI_CACHE_TTL', 1440),
    ],

    'providers' => [
        'openai' => [
            'api_key' => env('AI_SECURITY_OPENAI_API_KEY'),
            'model' => env('AI_SECURITY_OPENAI_MODEL', 'gpt-4.1'),
            'timeout' => env('AI_SECURITY_OPENAI_TIMEOUT', 120),
            'retries' => env('AI_SECURITY_OPENAI_RETRIES', 3),
            'max_completion_tokens' => env('AI_SECURITY_OPENAI_MAX_COMPLETION_TOKENS', 1200),
        ],

        'claude' => [
            'api_key' => env('AI_SECURITY_CLAUDE_API_KEY'),
            'model' => env('AI_SECURITY_CLAUDE_MODEL', 'claude-3-5-sonnet-latest'),
            'timeout' => env('AI_SECURITY_CLAUDE_TIMEOUT', 120),
            'retries' => env('AI_SECURITY_CLAUDE_RETRIES', 3),
            'max_completion_tokens' => env('AI_SECURITY_CLAUDE_MAX_COMPLETION_TOKENS', 1200),
        ],

        'gemini' => [
            'api_key' => env('AI_SECURITY_GEMINI_API_KEY'),
            'model' => env('AI_SECURITY_GEMINI_MODEL', 'gemini-1.5-pro'),
            'timeout' => env('AI_SECURITY_GEMINI_TIMEOUT', 120),
            'retries' => env('AI_SECURITY_GEMINI_RETRIES', 3),
            'max_completion_tokens' => env('AI_SECURITY_GEMINI_MAX_COMPLETION_TOKENS', 1200),
        ],

        'deepseek' => [
            'api_key' => env('AI_SECURITY_DEEPSEEK_API_KEY'),
            'model' => env('AI_SECURITY_DEEPSEEK_MODEL', 'deepseek-chat'),
            'timeout' => env('AI_SECURITY_DEEPSEEK_TIMEOUT', 120),
            'retries' => env('AI_SECURITY_DEEPSEEK_RETRIES', 3),
            'max_completion_tokens' => env('AI_SECURITY_DEEPSEEK_MAX_COMPLETION_TOKENS', 1200),
        ],

        'custom' => [
            'base_url' => env('AI_SECURITY_CUSTOM_BASE_URL'),
            'api_key' => env('AI_SECURITY_CUSTOM_API_KEY'),
            'model' => env('AI_SECURITY_CUSTOM_MODEL'),
            'timeout' => env('AI_SECURITY_CUSTOM_TIMEOUT', 120),
            'retries' => env('AI_SECURITY_CUSTOM_RETRIES', 3),
            'max_completion_tokens' => env('AI_SECURITY_CUSTOM_MAX_COMPLETION_TOKENS', 1200),
        ],
    ],

    'scan' => [
        'daily' => true,
        'time' => env('AI_SECURITY_SCAN_TIME', '03:00'),
        'deep_scan_weekly' => true,
    ],

    'sources' => [
        'composer_audit' => true,
        'github_advisory' => true,
        'nvd' => true,
        'opencve' => true,
    ],

    'auto_fix' => [
        'enabled' => env('AI_SECURITY_AUTO_FIX', false),
        'safe_fixes_only' => true,
        'production_direct_fix' => false,
        'create_pull_request' => true,
        'require_tests_pass' => true,
        'require_human_approval' => true,
    ],

    'notifications' => [
        'mail' => [
            'enabled' => env('AI_SECURITY_MAIL_ENABLED', true),
            'to' => env('AI_SECURITY_MAIL_TO'),
        ],

        'telegram' => [
            'enabled' => env('AI_SECURITY_TELEGRAM_ENABLED', false),
            'bot_token' => env('AI_SECURITY_TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('AI_SECURITY_TELEGRAM_CHAT_ID'),
        ],

        'slack' => [
            'enabled' => env('AI_SECURITY_SLACK_ENABLED', false),
            'webhook_url' => env('AI_SECURITY_SLACK_WEBHOOK_URL'),
        ],
    ],
    
    'database' => [
        'connection' => env('AI_SECURITY_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
    ],
];
