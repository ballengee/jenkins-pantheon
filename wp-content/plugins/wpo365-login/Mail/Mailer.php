<?php

namespace Wpo\Mail;

use WP_Error;
use \Wpo\Core\WordPress_Helpers;
use \Wpo\Services\Graph_Service;
use \Wpo\Services\Options_Service;
use \Wpo\Mail\Mail_Authorization_Helpers;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Mail\Mailer')) {

    class Mailer
    {

        public $phpmailer_data;

        /**
         * 
         */
        public static function init(&$phpmailer)
        {
            if (!Options_Service::get_global_boolean_var('use_graph_mailer')) {
                return;
            }

            $phpmailer_data = clone $phpmailer;
            $phpmailer = new Mailer();
            $phpmailer->phpmailer_data = $phpmailer_data;
        }

        public function send()
        {
            $to = self::validate_email_addresses($this->phpmailer_data->getToAddresses(), 'ERROR');

            if (empty($to)) {

                if (class_exists('\Wpo\Mail\Mail_Db')) {
                    \Wpo\Mail\Mail_Db::update_mail_log(
                        false,
                        sprintf('Cannot sent an email when no valid recipient has been specified [%s]', print_r($this->phpmailer_data->getToAddresses(), true))
                    );
                }

                return false;
            }

            $cc = self::validate_email_addresses($this->phpmailer_data->getCcAddresses(), 'WARN');
            $bcc = self::validate_email_addresses($this->phpmailer_data->getBccAddresses(), 'WARN');
            $reply_tos = self::validate_email_addresses($this->phpmailer_data->getReplyToAddresses(), 'WARN');

            if (sizeof($reply_tos) > 0) {
                $_mail_reply_to = $reply_tos;
            } else {
                $_mail_reply_to = self::to_array_of_valid_email_addresses(Options_Service::get_global_string_var('mail_reply_to'));
            }

            $reply_to = self::validate_email_addresses($_mail_reply_to);

            $attachments = array();

            foreach ($this->phpmailer_data->getAttachments() as $attachment) {

                /**
                 * 0 => $path
                 * 1 => $filename
                 * 2 => $name
                 * 3 => $encoding
                 * 4 => $type
                 * 5 => false //isStringAttachment
                 * 6 => $disposition
                 * 7 => $name
                 */

                // The check for file_exists may be superflous because when the path not exists the attachment is not found in the array.

                $file_exists = false;
                $log_message = null;

                try {
                    $file_exists = \file_exists($attachment[0]);
                    $file_size = \filesize($attachment[0]);

                    if ($file_size > 3145728) {
                        throw new \Exception('Cannot send files larger than 3 Mb using Microsoft Graph');
                    }
                } catch (\Exception $e) {
                    $log_message = \sprintf(
                        '%s -> Attachment cannot be added (path: %s, name: %s, type: %s, error: %s)',
                        __METHOD__,
                        $attachment[0],
                        $attachment[2],
                        $attachment[4],
                        $e->getMessage()
                    );

                    if (class_exists('\Wpo\Mail\Mail_Db')) {
                        \Wpo\Mail\Mail_Db::update_mail_log(
                            false,
                            $log_message
                        );
                    }

                    self::mailer_log('ERROR', $log_message);
                    continue;
                }

                if ($file_exists && empty($attachment[5]) && !empty($attachment[0]) && !empty($attachment[2]) && !empty($attachment[4])) {
                    $content = \base64_encode(\file_get_contents($attachment[0]));

                    $attachments[] = array(
                        '@odata.type' => '#microsoft.graph.fileAttachment',
                        'name' => $attachment[2],
                        'contentType' => $attachment[4],
                        'contentBytes' => $content,
                    );
                } else {
                    $log_message = \sprintf(
                        '%s -> Attachment cannot be added (path: %s, name: %s, type: %s, exists: %s)',
                        __METHOD__,
                        $attachment[0],
                        $attachment[2],
                        $attachment[4],
                        $file_exists ? 'Yes' : 'No'
                    );

                    if (class_exists('\Wpo\Mail\Mail_Db')) {
                        \Wpo\Mail\Mail_Db::update_mail_log(
                            false,
                            $log_message
                        );
                    }

                    self::mailer_log('ERROR', $log_message);
                }
            }

            if (Options_Service::get_global_boolean_var('mail_send_to_bcc')) {
                $default_recipient = Options_Service::get_global_string_var('mail_default_recipient');

                if (filter_var($default_recipient, FILTER_VALIDATE_EMAIL)) {

                    foreach ($to as $to_recipient) {
                        $bcc[] = $to_recipient;
                    }

                    $to = array(array('emailAddress' => array('address' => $default_recipient)));

                    foreach ($cc as $cc_recipient) {
                        $bcc[] = $cc_recipient;
                    }

                    $cc = array();
                } else {
                    $log_message = sprintf('%s -> The administrator has configured the option to send mail as BCC but did not specify a valid default recipient [%s]', __METHOD__, $default_recipient);
                    self::mailer_log('ERROR', $log_message);

                    if (class_exists('\Wpo\Mail\Mail_Db')) {
                        \Wpo\Mail\Mail_Db::update_mail_log(
                            false,
                            $log_message
                        );
                    }
                }
            }

            /**
             * @since   15.0    Allow to send emails as text.
             */
            $content_type = $this->phpmailer_data->ContentType != 'text/plain' && Options_Service::get_global_string_var('mail_mime_type') == 'Html' ? 'Html' : 'Text';

            /**
             * @since   18.0    Allow to override mail user
             */
            $mail_user = Options_Service::get_global_string_var('mail_from');

            if (Options_Service::get_global_boolean_var('mail_allow_from')) {
                $from_address = $this->phpmailer_data->From;

                if (filter_var($from_address, FILTER_VALIDATE_EMAIL)) {
                    $mail_user_domain = (explode('@', $mail_user))[1];
                    $from_address_domain = (explode('@', $from_address))[1];

                    if (strcasecmp($mail_user_domain, $from_address_domain) === 0) {
                        $mail_user = $from_address;
                        self::mailer_log('DEBUG', sprintf('%s -> From address has been updated from %s to %s', __METHOD__, $mail_user, $from_address));
                    } else {
                        self::mailer_log('WARN', sprintf('%s -> From address could not be updated from %s to %s', __METHOD__, $mail_user, $from_address));
                    }
                }
            }

            $save_sent = Options_Service::get_global_boolean_var('mail_save_to_sent_items');
            $message_as_json = self::email_message_encode($this->phpmailer_data->Subject, $to, $this->phpmailer_data->Body, $cc, $bcc, $reply_to, $content_type, $save_sent, $attachments);
            $query = "/users/$mail_user/sendMail";
            $access_token = Mail_Authorization_Helpers::get_mail_access_token();

            if (is_wp_error($access_token)) {
                $message_sent_result = $access_token;
            } else {
                $message_sent_result = self::mg_fetch($query, $message_as_json, $access_token);
            }

            if (Graph_Service::is_fetch_result_ok($message_sent_result, 'Could not sent WordPress email using Microsoft Graph')) {
                self::mailer_log('DEBUG', sprintf(
                    '%s -> WordPress email sent successfully using Microsoft Graph',
                    __METHOD__
                ));

                if (class_exists('\Wpo\Mail\Mail_Db')) {
                    \Wpo\Mail\Mail_Db::update_mail_log(
                        true
                    );
                }

                return true;
            }

            if (class_exists('\Wpo\Mail\Mail_Db')) {

                if (is_wp_error($message_sent_result)) {
                    $log_message = $message_sent_result->get_error_message();
                } else {
                    $log_message = 'Failed to fetch data from Microsoft Graph [Check log for errors]';
                }

                \Wpo\Mail\Mail_Db::update_mail_log(
                    false,
                    $log_message
                );
            }

            return false;
        }

        /**
         * Sends a template based test mail to the mail address provided
         * 
         * @since   11.7
         * 
         * @param   $to         string|array    Email address(es)
         * 
         * @return  boolean     True if succesful otherwise false
         */
        public static function send_test_mail($to, $cc = array(), $bcc = array(), $sent_attachment = false)
        {
            self::mailer_log('DEBUG', __METHOD__ . ' -> Sending test email to ' .  print_r($to, true));

            $content_type = Options_Service::get_global_string_var('mail_mime_type') == 'Html' ? 'text/html' : 'text/plain';
            $template = $content_type == 'text/html' ? 'test-mail-html' : 'test-mail-text';

            ob_start();
            include($GLOBALS['WPO_CONFIG']['plugin_dir'] . '/templates/' . $template . '.php');
            $content = wp_kses(ob_get_clean(), WordPress_Helpers::get_allowed_html());

            $headers = array(\sprintf('Content-Type: %s; charset=UTF-8', $content_type));

            $_to = self::to_array_of_valid_email_addresses($to);
            $_cc = self::to_array_of_valid_email_addresses($cc);
            $_bcc = self::to_array_of_valid_email_addresses($bcc);

            foreach ($_cc as $__cc) {
                $headers[] = \sprintf('cc: %s', $__cc);
            }

            foreach ($_bcc as $__bcc) {
                $headers[] = \sprintf('bcc: %s', $__bcc);
            }

            $subject = '[' . wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) . '] Test email by WPO365 | LOGIN Graph Mailer';

            $attachments = array();

            if ($sent_attachment) {
                $attachments[] = \sprintf('%s/wpo365-test-email-attachment.pdf', dirname(__FILE__));
            }

            /**
             * @since   18.1
             * 
             * When sending test email always use configured from address.
             */
            remove_all_filters('wp_mail_from');
            add_filter('wp_mail_from', '\Wpo\Mail\Mailer::mail_from', 10, 1);

            return wp_mail($_to, $subject, $content, $headers, $attachments);
        }

        /**
         * A logger that writes immediately to the default log handling routine.
         * 
         * @since   17.0
         * 
         * @param   string  $level      DEBUG, WARN or ERROR
         * @param   mixed   $message    If not a string the object / array will be printed using print_r
         * @return  void
         */
        public static function mailer_log($level, $message)
        {
            error_log(
                \sprintf(
                    '[%s | %s] %s ( %s ): %s',
                    (\DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', '')))->format('m-d-Y H:i:s.u'),
                    empty($GLOBALS['WPO_CONFIG']['request_id']) ? '' : $GLOBALS['WPO_CONFIG']['request_id'],
                    empty($level) ? 'DEBUG' : $level,
                    \phpversion(),
                    \is_string($message) ? $message : print_r($message, true)
                )
            );
        }

        /**
         * Filters wp_mail_from hook and sets it to the send-mail-from account (since 18.1) whenever it 
         * detects the WordPress configured default email address.
         * 
         * @since   17.0
         * 
         * @param   string      $from_email     The current from address from wp_mail().
         * 
         * @return  string      The filtered $from_email if the corresponding option was checked.
         */
        public static function mail_from($from_email)
        {
            if (Options_Service::get_global_boolean_var('use_graph_mailer')) {

                if (false !== stripos($from_email, 'wordpress@')) {
                    // Get the site domain and get rid of www.
                    $sitename   = wp_parse_url(network_home_url(), PHP_URL_HOST);
                    $_from_email = 'wordpress@';

                    if (null !== $sitename) {
                        if ('www.' === substr($sitename, 0, 4)) {
                            $sitename = substr($sitename, 4);
                        }

                        $_from_email .= $sitename;
                    }

                    if (strcasecmp($from_email, $_from_email) === 0) {
                        return Options_Service::get_global_string_var('mail_from');
                    }
                }
            }

            return $from_email;
        }

        /**
         * Will try to turn the input into an array of valid email addresses.
         * 
         * @since   17.0
         * 
         * @param   mixed   $input  String or array to be converted.
         * @return  array   Array of valid email addresses.
         */
        private static function to_array_of_valid_email_addresses($input)
        {
            $email_addresses = array();
            $result = array();

            if (\is_string($input)) {
                $json = json_decode(stripslashes($input), true);

                if (json_last_error() == JSON_ERROR_NONE && is_array($json)) {
                    $email_addresses = $json;
                } else if (false !== stripos($input, ',')) {
                    $email_addresses = \explode(',', $input);
                } else {
                    $email_addresses = array($input);
                }
            }

            if (\is_array($input)) {
                $email_addresses = $input;
            }

            array_filter($email_addresses, function ($item) use (&$result) {

                if (!\is_string($item)) {
                    return false;
                }

                $trimmed_sanitized = sanitize_email(trim($item));

                if (filter_var($trimmed_sanitized, FILTER_VALIDATE_EMAIL)) {

                    $result[] = $trimmed_sanitized;
                    return true;
                }
            });

            return $result;
        }

        /**
         * Validates email addresses and formats those in Graph-compatible format.
         * 
         * @since 11.7
         * 
         * @param   mixed   $email_addresses    single email address, comma separated email address, semi colon separated email address, WP / Graph formatted email address array
         * @param   string  $level              Level for debug log entries
         * 
         * @return  array   Array with valid email address that must checked if empty
         */
        private static function validate_email_addresses($email_addresses, $level = 'DEBUG')
        {
            if (empty($email_addresses)) {
                self::mailer_log($level != 'ERROR' ? 'DEBUG' : $level, __METHOD__ . ' -> Cannot validate an empty email address');
                return array();
            }

            // Array that will contain all email addresses after harmonizing the input to a Graph-compatible format
            $_email_addresses = array();

            /**
             * @param   $unformatted    string  A single email address
             * 
             * @return  array   Assoc. array in the form WordPress provides and Graph expects it.
             */
            $format = function ($unformatted) {
                return array('emailAddress' => array('address' => $unformatted));
            };

            /**
             * Handle the case of email address provided as a string
             * 1. Single email address
             * 2. Comma seperated email addresses
             * 3. Semi colon separated email addresses
             */
            if (is_string($email_addresses)) {

                if (stripos($email_addresses, ',') !== false) {
                    $delimited = \explode(',', $email_addresses);

                    foreach ($delimited as $_delimited) {
                        $_email_addresses[] = $format($_delimited);
                    }
                } elseif (stripos($email_addresses, ';') !== false) {
                    $delimited = \explode(';', $email_addresses);

                    foreach ($delimited as $_delimited) {
                        $_email_addresses[] = $format($_delimited);
                    }
                } else {
                    $_email_addresses[] = $format($email_addresses);
                }
            }

            /**
             * Handle the case of email address provided as an array
             */
            elseif (is_array($email_addresses)) {

                foreach ($email_addresses as $_email_address) {

                    if (isset($_email_address['emailAddress']) && isset($_email_address['emailAddress']['address'])) {
                        $_email_addresses[] = $_email_address;
                        continue;
                    } elseif (is_array($_email_address) && sizeof($_email_address) == 2) {
                        $_email_addresses[] = array('emailAddress' => array('address' => $_email_address[0]));
                        continue;
                    } elseif (is_string($_email_address)) {
                        $_email_addresses[] = $format(trim($_email_address));
                        continue;
                    }

                    self::mailer_log($level, __METHOD__ . ' -> Email address format invalid (check log for details)');
                    self::mailer_log('DEBUG', $_email_address);
                }
            }

            /**
             * If format cannot be parsed then return an empty result
             */
            else {
                self::mailer_log($level, __METHOD__ . ' -> Email address format not recognized (check log for details)');
                self::mailer_log('DEBUG', $email_addresses);
            }

            // Array that will contain all formatted email addresses that will be returned
            $result = array();

            // Validate each email address
            foreach ($_email_addresses as $_email_address) {

                try {
                    if (!filter_var($_email_address['emailAddress']['address'], FILTER_VALIDATE_EMAIL)) {
                        self::mailer_log($level, __METHOD__ . ' -> Invalid email address found (' . $_email_address['emailAddress']['address'] . ')');
                        continue;
                    }

                    $result[] = $_email_address;
                } catch (\Exception $e) {
                    self::mailer_log($level, __METHOD__ . ' -> Invalid email address found (check log for details)');
                    self::mailer_log('DEBUG', $_email_address);
                    continue;
                }
            }

            return $result;
        }

        /**
         * 
         */
        private static function email_message_encode($subject, $to, $content, $cc = array(), $bcc = array(), $reply_to = array(), $content_type = 'Text', $save_sent = false, $attachments = array())
        {
            $message = array(
                'message' => array(
                    'subject' => $subject,
                    'body'    => array(
                        'contentType' => $content_type,
                        'content'     => $content,
                    ),
                    'toRecipients' => $to,
                ),
                'saveToSentItems' => $save_sent,
            );

            if (!empty($cc)) {
                $message['message']['ccRecipients'] = $cc;
            }

            if (!empty($bcc)) {
                $message['message']['bccRecipients'] = $bcc;
            }

            if (!empty($reply_to)) {
                $message['message']['replyTo'] = $reply_to;
            }

            if (!empty($attachments)) {
                $message['message']['attachments'] = $attachments;
            }

            return json_encode($message);
        }

        /**
         * @since       19.0
         * 
         * @param mixed $query 
         * @param mixed $message_as_json 
         * @param mixed $access_token 
         * 
         * @return      WP_Error|array 
         */
        private static function mg_fetch($query, $message_as_json, $access_token)
        {
            $headers = array('Content-Type' => 'application/json');
            $headers['Authorization'] = sprintf('Bearer %s', $access_token);

            $graph_version = Options_Service::get_global_string_var('graph_version');
            $graph_version = empty($graph_version) || $graph_version == 'current'
                ? 'v1.0'
                : 'beta';

            $url = sprintf(
                'https://graph.microsoft.com/%s/%s',
                $graph_version,
                ltrim($query, '/')
            );

            $skip_ssl_verify = !Options_Service::get_global_boolean_var('skip_host_verification');

            self::mailer_log('DEBUG', sprintf(
                '%s -> Fetching from Microsoft Graph to send WordPress emails using: %s',
                __METHOD__,
                $url
            ));

            $response = wp_remote_post(
                $url,
                array(
                    'body' => $message_as_json,
                    'method' => 'POST',
                    'timeout' => 15,
                    'headers' => $headers,
                    'sslverify' => $skip_ssl_verify,
                )
            );

            if (is_wp_error($response)) {
                $error_message = sprintf(
                    '%s -> Error occured whilst fetching from Microsoft Graph (%s): %s',
                    __METHOD__,
                    $url,
                    $response->get_error_message()
                );

                return new \WP_Error('GraphFetchException', $error_message);
            }

            $body = wp_remote_retrieve_body($response);
            $body = json_decode($body, true);
            $http_code = wp_remote_retrieve_response_code($response);
            return array('payload' => $body, 'response_code' => $http_code);
        }
    }
}
