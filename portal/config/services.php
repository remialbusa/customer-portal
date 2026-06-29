<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'monday' => [
        'token'                  => env('MONDAY_API_TOKEN'),
        'api_url'                       => env('MONDAY_API_URL', 'https://api.monday.com/v2'),
        'customers_board_id'            => env('MONDAY_CUSTOMERS_BOARD_ID'),
        'tickets_board_id'              => env('MONDAY_TICKETS_BOARD_ID'),
        'service_report_board_id'       => env('MONDAY_SERVICE_REPORT_BOARD_ID'),

        // Tickets board column IDs (5029331350)
        'tickets_columns' => [
            'item_name'       => 'name',
            'account_name'    => 'lookup_mm4f1f6y',
            'description'     => 'long_text7',
            'end_user'        => 'board_relation_mm4f9mwv',
            'status'          => 'status95',
            'priority'        => 'priority',
            'request_type'    => 'request_type',
            'resolution_date' => 'date_mm4f3f4p',
            'email'           => 'email',
            'attached_files'  => 'files',
            'tsp'             => 'multiple_person_mm4fqar3',
            'internal_notes'  => 'long_text_mm4f8ve0',
        ],

        // Service Report (TSR) board column IDs (5029041107) — "EXTERNAL - TSR"
        'service_report_columns' => [
            'item_name'              => 'name',
            'service_number'         => 'board_relation_mm4gg1rm',   // link back to ticket ("Tickets - Customer")
            'service_status'         => 'color_mm3gbrby',            // OPEN / IN-PROGRESS / PENDING / ESCALATED / COMPLETED
            'problem_and_concerns'   => 'long_text_mks8824j',
            'job_done'               => 'long_text_mks8y6j7',
            'parts_replaced'         => 'text_mks8xtcq',
            'recommendation'         => 'long_text_mksdf1jb',
            'login_date'             => 'date_mks8wqcw',
            'service_start'          => 'date_mks8t42p',
            'service_end'            => 'date_mks8gbw0',
            'logout_date'            => 'date_mks8mvb2',
            'machine_system'         => 'single_selectn7mh0gm',
            'serial_number'          => 'long_text_mkw3zweq',
            'software_version'       => 'short_text7hjan9fo',
            'contract'               => 'single_selectnuarkqi',
            'tsp_workwith'           => 'multiple_person_mks8jn7f',
            'tsp_email'              => 'email_mks72yj4',
            'tsp_workwith_signature' => 'signature3hw5m6pa',
            'tsp_signature'          => 'signaturew5mfhn25',
            'customer_incharge'      => 'text_mkw29ykk',
            'customer_incharge_email'=> 'emailpxq2qbr6',
            'customer_incharge_sig'  => 'file_mks8jddc',
            'biomed_incharge'        => 'short_texton5gwzbm',
            'biomed_email'           => 'email81d8h472',
            'biomed_signature'       => 'signaturecfdbi0hq',
            'remarks'                => 'long_textfntkrfpg',
            'call_login_time'        => 'hour_mkzwcmjs',
            'tsr_copy_files'         => 'file_mky1mtxf',
            'pdf_status'             => 'color_mm3tc8rj',
        ],

        // Status label mapping: portal resolution → TSR Service Status label
        'service_status_labels' => [
            'open'           => 'OPEN',
            'in_progress'    => 'IN-PROGRESS',
            'pending'        => 'PENDING',
            'escalated'      => 'ESCALATED',
            'completed'      => 'COMPLETED',
        ],

        // Reverse: TSR label → ticket status95 label on the tickets board
        'service_status_to_ticket_status' => [
            'OPEN'         => null,             // leave ticket status unchanged
            'IN-PROGRESS'  => 'Working on it',
            'PENDING'      => 'Waiting for parts',
            'ESCALATED'    => 'Escalated',
            'COMPLETED'    => 'Resolved',
        ],

        // Customer Details board column IDs (5029327268)
        'customers_columns' => [
            'item_name'   => 'name',
            'branch'      => 'color_mm4ezjds',
            'account_name'=> 'text_mm4f5gk8',
            'email'       => 'email_mm4extq6',
            'brand'       => 'text_mm4f8w9f',
            'model'       => 'text_mm4fp6z2',
            'address'     => 'location_mm4e2wr3',
            'user_status' => 'status',
            'date'        => 'date4',
        ],
    ],

];
