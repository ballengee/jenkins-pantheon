<?php

namespace Wpo\Tests;

use \Wpo\Core\Wpmu_Helpers;
use \Wpo\Services\Options_Service;
use \Wpo\Services\Request_Service;
use \Wpo\Tests\Test_Access_Tokens;
use \Wpo\Tests\Test_Configuration;
use \Wpo\Tests\Test_OpenId_Connect;
use \Wpo\Tests\Test_Extensions;
use \Wpo\Tests\Test_Result;
use \Wpo\Tests\Test_Saml2;
use \Wpo\Tests\Test_B2c;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Tests\Self_Test')) {

    class Self_Test
    {

        private $test_results = array();

        public function __construct()
        {
            $this->run_tests();
        }

        public function run_tests()
        {
            if (isset($_REQUEST['flushPermaLinks'])) {
                $flush = filter_var($_REQUEST['flushPermaLinks'], FILTER_VALIDATE_BOOLEAN);

                if ($flush) {
                    flush_rewrite_rules();
                }
            } else {
                $request_service = Request_Service::get_instance();
                $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
                $state = $request->get_item('state');

                if (!empty($state) && stripos($state, 'flushPermaLinks=true') > 0) {
                    flush_rewrite_rules();
                }
            }

            $test_sets = array(new Test_Extensions());
            $no_sso = Options_Service::get_global_boolean_var('no_sso');
            $use_saml = Options_Service::get_global_boolean_var('use_saml');
            $use_b2c = Options_Service::get_global_boolean_var('use_b2c');
            $oidc_flow = Options_Service::get_global_string_var('oidc_flow');

            if (!$no_sso) {
                if ($use_saml) {
                    $test_sets[] = new Test_Saml2();
                } elseif ($use_b2c) {
                    $test_sets[] = new Test_B2c();
                } else {
                    $test_sets[] = new Test_OpenId_Connect();
                }
            }

            // In case of the Authorization Code Flow we need to exchange the code for an ID token
            if (!$no_sso && !$use_saml && $oidc_flow == 'code') {

                if ($use_b2c && class_exists('\Wpo\Services\Id_Token_Service_B2c')) {
                    \Wpo\Services\Id_Token_Service_B2c::process_openidconnect_code();
                } else {
                    \Wpo\Services\Id_Token_Service::process_openidconnect_code();
                }
            }

            if (Options_Service::get_global_boolean_var('test_configuration')) {
                $test_sets[] = new Test_Configuration();
            }

            if (Options_Service::get_global_boolean_var('test_access_token')) {
                $test_sets[] = new Test_Access_Tokens();
            }

            if (Options_Service::get_global_boolean_var('use_wp_rest_aad')) {
                $test_sets[] = new Test_Rest_Protection();
            }

            $test_sets[] = $this;

            foreach ($test_sets as $test_set) {
                $tests = preg_grep('/^test_/', get_class_methods($test_set));

                foreach ($tests as $test) {
                    $result = $test_set->$test();

                    // A test may return void when skipped
                    if (!empty($result)) {
                        $this->test_results[] = $result;
                    }
                }
            }

            Wpmu_Helpers::mu_set_transient('wpo365_self_test_results', $this->test_results, 21600);
        }

        public function test_no_errors()
        {
            $test_result = new Test_Result('Debug log contains no errors', Test_Result::CAPABILITY_CONFIG, Test_Result::SEVERITY_CRITICAL);
            $test_result->passed = true;

            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
            $request_log = $request->get_item('request_log');

            $error_entries = array_filter($request_log['log'], function ($entry) {
                return (false !== stripos($entry['level'], 'error'));
            });

            if (!empty($error_entries)) {
                $test_result->passed = false;
                $test_result->message = 'The error log for this self-test request contains errors. Please review the <a href="#debug">debug log</a>.';
                $test_result->more_info = '';
            }

            return $test_result;
        }
    }
}
