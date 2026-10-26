<?php

/**
 * Canonical definition of the acceptance-suite fixture.
 *
 * This file is the single source of truth for the seed dataset. Known
 * plaintexts live here; tests/seed/seed.sql (the loadable form, with Vault
 * Account credentials encrypted in the legacy CI3 format) is generated from
 * it by tests/tools/generate-seed.php — regenerate, never hand-edit seed.sql.
 *
 * Per-account encryption keys are derived deterministically from the account
 * id so regenerating produces byte-identical output (see generate-seed.php).
 */

return [
    // Baseline reference data, verbatim from the pre-migration dump.
    'access_rights' => [
        [1, 'admin'],
        [2, 'user'],
    ],
    'feature' => [
        [1, 'user', '#'],
        [2, 'account', '#'],
    ],
    'access_list' => [
        [1, 1],
        [2, 2],
    ],
    'account_media' => [
        [1, 'fab fa-instagram', 'Instagram'],
        [2, 'fab fa-twitter', 'Twitter'],
        [3, 'fab fa-facebook-f', 'Facebook'],
        [4, 'fab fa-google', 'Google'],
        [5, 'fab fa-medium', 'Medium'],
        [6, 'fab fa-quora', 'Quora'],
        [7, 'fab fa-trello', 'Trello'],
        [8, 'fab fa-line', 'Line'],
        [9, 'fab fa-cc-visa', 'Visa'],
        [10, 'fab fa-cc-mastercard', 'Mastercard'],
        [11, 'fab fa-cc-amex', 'American Express'],
        [12, 'fab fa-amazon', 'Amazon'],
        [13, 'fab fa-apple', 'Apple'],
        [14, 'fab fa-steam', 'Steam'],
    ],

    // One Admin User (Access Level 1), one regular User (Access Level 2), both
    // activated, and one unactivated User (`registered = 0`) so the login error
    // state for an unactivated account is reachable and provable against the
    // oracle (ticket #9). `password` is the plaintext the suite logs in with;
    // `password_hash` is the committed, portable hash seeded into the DB
    // (static so regeneration is byte-stable — same convention as the
    // pre-migration dump, which carried $2y$10$ hashes as literals).
    'users' => [
        ['id_user' => 1, 'email' => 'admin@email.com', 'name' => 'Admin', 'password' => 'admin1234', 'password_hash' => '$2y$12$GEVY4VmcO0XMwhUC1tFqhOmiaBNAXEKsY.WqrTFIG8rx94e/GjuUe', 'registered' => 1, 'access_level' => 1],
        ['id_user' => 2, 'email' => 'jack@email.com', 'name' => 'Jack', 'password' => 'user1234', 'password_hash' => '$2y$12$Tfp2pj2CF.wu84OJCESL/uzl6WU.A7uZ6rvf/pS1XvtlpTUTwllom', 'registered' => 1, 'access_level' => 2],
        ['id_user' => 3, 'email' => 'ghost@email.com', 'name' => 'Ghost', 'password' => 'ghost1234', 'password_hash' => '$2y$12$zSRvbLy7WPU9c5t2OjtE2eQLmGxTtZy6fwA3OqCOKLhTTYRe50NIm', 'registered' => 0, 'access_level' => 2],
    ],

    // Vault Accounts belonging to the regular User, across several Media
    // Types, stored encrypted in the legacy CI3 format. The plaintexts below
    // are what the detail page must render after decryption.
    'accounts' => [
        ['id_account' => 1, 'id_user' => 2, 'id_media' => 1, 'username_email' => 'jack.sparrow@seamail.example', 'password' => 'insta-pass-0123'],
        ['id_account' => 2, 'id_user' => 2, 'id_media' => 2, 'username_email' => 'jackdraws', 'password' => 'tweet-pass-3f2a'],
        ['id_account' => 3, 'id_user' => 2, 'id_media' => 12, 'username_email' => 'jack@amazon.example', 'password' => 'amaz0n-pr1me-K7'],
        ['id_account' => 4, 'id_user' => 2, 'id_media' => 14, 'username_email' => 'steamjack', 'password' => 'p2L!ay-L4ter'],
    ],
];
