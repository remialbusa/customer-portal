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
            'response_status' => 'color_mm4vbp35',   // "NOT YET" → "RESPONDED" once a TSP is assigned
            'time_tracking'   => 'duration_mm4hesrz', // Monday native time_tracking widget — "Response Time"
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

    /*
    |--------------------------------------------------------------------------
    | Equipment catalog
    |--------------------------------------------------------------------------
    |
    | The list of known brands and the models we service. Used by the
    | customer ticket creation form's Brand / Model <select> dropdowns.
    | Customers can still type a free-form value via the "Other" entry
    | — selecting "Other" reveals a free-text input that posts the
    | custom value as the brand or model.
    |
    | Keys are display labels. Models are flat strings; brand-specific
    | model lists live under each brand.
    |
    | NOTE: this is a curated catalog, not a live source of truth. If
    | the customer types a model that doesn't appear in the catalog,
    | it still saves — we just won't suggest it next time.
    */
    'equipment' => [
        'brands' => [
            'Mindray' => [
                'BC-6800', 'BC-6700', 'BC-6600', 'BC-6200', 'BC-6000',
                'BC-5390', 'BC-5380', 'BC-5310', 'BC-5300', 'BC-5150',
                'BC-5140', 'BC-5120', 'BC-5100', 'BC-5000', 'BC-3600', 'BC-3000',
                'BC-2900', 'BC-2800', 'BC-2600', 'BC-2300', 'BC-2100',
                'BS-2000M', 'BS-800M', 'BS-600M', 'BS-480', 'BS-380',
                'BA-88A', 'CF-92', 'DC-80', 'DC-70', 'DC-60',
            ],
            'Sysmex' => [
                'XN-550', 'XN-1000', 'XN-1500', 'XN-2000', 'XN-3000',
                'XN-3100', 'XN-3300', 'XN-3500', 'XN-4500', 'XN-5500',
                'XN-9100', 'XN-9200', 'XT-2000i', 'XT-1800i',
                'XS-800i', 'XS-1000i', 'XE-5000', 'XE-2100', 'XP-300',
                'UF-1000i', 'UF-5000', 'CS-5100', 'CA-660', 'CA-7000',
            ],
            'Horiba' => [
                'Micros 60', 'Micros CRP', 'ABX Pentra 60', 'ABX Pentra 80',
                'ABX Pentra 120', 'ABX Pentra XL 80', 'ABX Pentra XL 120',
                'ABX Pentra DF 120', 'ABX Micros 45', 'ABX Micros ES 60',
                'Yumizen H550', 'Yumizen H630', 'Yumizen H750',
            ],
            'Abbott' => [
                'Cell-Dyn 3200', 'Cell-Dyn 3500', 'Cell-Dyn 3700',
                'Cell-Dyn 4000', 'Cell-Dyn Ruby', 'Cell-Dyn Sapphire',
                'Cell-Dyn Emerald 22', 'Cell-Dyn Emerald 22AL',
                'Alinity ci-series', 'Alinity hq', 'Alinity hs',
                'ARCHITECT c4000', 'ARCHITECT c8000', 'ARCHITECT i1000',
                'ARCHITECT i2000', 'ARCHITECT i6000', 'ARCHITECT i8000',
            ],
            'Siemens' => [
                'ADVIA 120', 'ADVIA 2120', 'ADVIA 360', 'ADVIA 560',
                'ADVIA Centaur CP', 'ADVIA Centaur XPT', 'ADVIA Chemistry XPT',
                'Atellica CH 930', 'Atellica IM 1300', 'Atellica IM 1600',
                'Dimension EXL 200', 'Dimension Vista 1500', 'Dimension Xpand Plus',
            ],
            'Beckman Coulter' => [
                'DxH 500', 'DxH 600', 'DxH 800', 'DxH 900', 'DxH 1000',
                'DxI 600', 'DxI 800', 'Access 2', 'UniCel DxI 600',
                'AU480', 'AU5800', 'AU680', 'AU5800', 'AU2700', 'AU5400',
                'Power Processor', 'LH 500', 'LH 750', 'LH 785',
            ],
            'Roche' => [
                'cobas 6000', 'cobas 8000', 'cobas c 501', 'cobas c 701',
                'cobas c 702', 'cobas e 411', 'cobas e 601', 'cobas e 602',
                'cobas 6500', 'cobas 6800', 'cobas 8800', 'cobas p 312',
                'cobas p 471', 'cobas p 512', 'cobas p 612', 'cobas p 671',
            ],
            'Diatron' => [
                'Abacus 3', 'Abacus 3CP', 'Abacus 380', 'Abacus 5',
                'Arcus', 'Aquila', 'Olympus', 'Puls Cell 1',
            ],
            'Other' => null, // sentinel: free-text input
        ],
    ],

];
