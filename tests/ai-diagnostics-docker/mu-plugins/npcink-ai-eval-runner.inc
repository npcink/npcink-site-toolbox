<?php
/**
 * Exposes the controlled case runner only inside the disposable loopback site.
 */

defined('ABSPATH') || exit(1);

add_action(
    'wp_loaded',
    static function (): void {
        if (!isset($_GET['npcink_ai_eval_case'])) {
            return;
        }

        $expected_token = (string) getenv('NPCINK_AI_EVAL_TOKEN');
        $provided_token = isset($_SERVER['HTTP_X_NPCINK_EVAL_TOKEN'])
            ? (string) wp_unslash($_SERVER['HTTP_X_NPCINK_EVAL_TOKEN'])
            : '';

        if ($expected_token === '' || !hash_equals($expected_token, $provided_token)) {
            status_header(403);
            exit('Forbidden');
        }

        $case_id = sanitize_key(wp_unslash($_GET['npcink_ai_eval_case']));
        $args = array($case_id);

        header('Content-Type: text/plain; charset=utf-8');
        require '/eval/run-case.php';
        exit;
    },
    PHP_INT_MAX
);
