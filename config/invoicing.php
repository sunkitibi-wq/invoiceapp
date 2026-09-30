<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | All monetary amounts are stored as integers in the minor unit of this
    | currency (e.g. 12.34 USD is stored as 1234). Note that currencies such as
    | NGN and JPY have no minor unit, so amounts for those are stored as whole
    | major units.
    |
    */

    'currency' => env('INVOICING_CURRENCY', 'NGN'),

    'currency_locale' => env('INVOICING_CURRENCY_LOCALE', 'en_NG'),

    /*
    |--------------------------------------------------------------------------
    | Invoice Numbering
    |--------------------------------------------------------------------------
    |
    | Invoice numbers are built from a prefix, the year and a per-year running
    | sequence, e.g. INV-2026-0007. Set "period" to "all" for a single
    | sequence that never resets.
    |
    */

    'number_prefix' => env('INVOICING_NUMBER_PREFIX', 'INV'),

    'number_padding' => 4,

    'number_period' => 'year',

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    'default_tax_rate' => (float) env('INVOICING_DEFAULT_TAX_RATE', 0),

    'default_payment_terms_days' => (int) env('INVOICING_PAYMENT_TERMS_DAYS', 14),

    'default_notes' => env('INVOICING_DEFAULT_NOTES'),

    'default_terms' => env('INVOICING_DEFAULT_TERMS', 'Payment is due within the number of days stated on this invoice.'),

    /*
    |--------------------------------------------------------------------------
    | Sender Details
    |--------------------------------------------------------------------------
    |
    | The business details printed on every invoice and used as the default
    | sender for invoice emails.
    |
    */

    'from' => [
        'name' => env('INVOICING_FROM_NAME', env('APP_NAME', 'Toma')),
        'email' => env('INVOICING_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'phone' => env('INVOICING_FROM_PHONE'),
        'tax_number' => env('INVOICING_FROM_TAX_NUMBER'),
        'address_line_1' => env('INVOICING_FROM_ADDRESS_LINE_1'),
        'address_line_2' => env('INVOICING_FROM_ADDRESS_LINE_2'),
        'city' => env('INVOICING_FROM_CITY'),
        'state' => env('INVOICING_FROM_STATE'),
        'postal_code' => env('INVOICING_FROM_POSTAL_CODE'),
        'country' => env('INVOICING_FROM_COUNTRY'),
    ],

];
