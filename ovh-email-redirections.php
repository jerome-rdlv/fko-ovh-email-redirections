<?php
/*
Plugin Name: OVH Email Redirections
Plugin URI: https://gitlab.rue-de-la-vieille.fr/fantaskao/ovh-email-redirections.git
Description: Manage email redirection of choosen domains in WordPress settings pages
Author: Jérôme Mulsant
Version: 0.1
Author URI: https://rue-de-la-vieille.fr
 */

namespace Rdlv\Fko\OvhEmailAliases;

use WP_Error;

new OvhEmailAliases();

class OvhEmailAliases
{
    const OPTION_PAGE_PARENT = 'options-general.php';
    const OPTION_PAGE_SLUG = 'email-aliases';

    const TEXTDOMAIN = 'ovh-email-aliases';
    const OPTION_FORMAT = 'ovh_email_aliases_%s';

    const CREATE_APP_URL = 'https://eu.api.ovh.com/createApp/';

    const API_BASE_URL = 'https://eu.api.ovh.com/1.0';
    const API_TOKEN_PATH = '/auth/credential';
    const API_DOMAIN_PATH = '/domain';
    const API_REDIRECTIONS_PATH = '/email/domain/%s/redirection';
    const API_REDIRECTION_PATH = '/email/domain/%s/redirection/%s';

    /** @var string Application Key */
    private $ak;
    
    /** @var string Application Secret */
    private $as;
    
    /** @var string Consumer Key (API Token) */
    private $ck;
    
    function __construct()
    {
        load_plugin_textdomain(self::TEXTDOMAIN);

        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);

        // to be called after saving options
        add_filter('wp_redirect', [$this, 'get_token']);
        
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('ovh-email-aliases', plugins_url('main.css', __FILE__));
        });
    }

    public function admin_menu()
    {
        add_submenu_page(
            self::OPTION_PAGE_PARENT,
            __('Gestion des redirections email', self::TEXTDOMAIN),
            __('Redirections email', self::TEXTDOMAIN),
            'edit_posts',
            self::OPTION_PAGE_SLUG,
            [$this, 'admin_page']
        );
    }

    public function admin_init()
    {
        register_setting('ovh-email-aliases', sprintf(self::OPTION_FORMAT, 'api_key'));
        register_setting('ovh-email-aliases', sprintf(self::OPTION_FORMAT, 'api_secret'));
        register_setting('ovh-email-aliases', sprintf(self::OPTION_FORMAT, 'domains'));
        
        $this->ak = get_option(sprintf(self::OPTION_FORMAT, 'api_key'));
        $this->as = get_option(sprintf(self::OPTION_FORMAT, 'api_secret'));
        $this->ck = get_option(sprintf(self::OPTION_FORMAT, 'api_token'));
        
        $this->save_redirections();
    }
    
    private function save_redirections()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ovh-email-aliases')) {
            return;
        }
        
        $posted_redirections = [];
        
        // add new redirections
        if (!empty($_POST['new'])) {
            foreach ($_POST['new'] as $domain => $data) {
                if (!isset($posted_redirections[$domain])) {
                    $posted_redirections[$domain] = [];
                }
                $addresses = $this->get_addresses($data['to']);
                foreach ($addresses as $address) {
                    $from = $data['from'] .'@'. $domain;
                    $posted_redirections[$domain][$from .':'. $address] = [
                        'from' => $from,
                        'to' => $address,
                    ];
                }
                
            }
        }
        
        // save existing redirections
        if (!empty($_POST['aliases'])) {
            foreach ($_POST['aliases'] as $domain => $data) {
                foreach ($data as $from => $to) {
                    if (!isset($posted_redirections[$domain])) {
                        $posted_redirections[$domain] = [];
                    }
                    $addresses = $this->get_addresses($to);
                    foreach ($addresses as $address) {
                        $hash = $from .'@'. $domain .':'. $address;
                        $posted_redirections[$domain][$hash] = [
                            'from' => $from .'@'. $domain,
                            'to' => $address,
                        ];
                    }
                }
            }
        }
        
        $api_redirections = $this->get_raw_redirections();
        
        $domains = $this->get_domains();
        
        foreach ($domains as $domain => $enabled) {

            $posted_keys = array_keys($posted_redirections[$domain]);
            $api_keys = array_keys($api_redirections[$domain]);

            $to_create = array_diff($posted_keys, $api_keys);
            $to_delete = array_diff($api_keys, $posted_keys);
        }
        
        return;
    }

    /**
     * @param $data
     * @return array
     */
    private function get_addresses($to)
    {
        $to = array_filter(
            array_map(function ($address) {
                $address = trim($address);
                return is_email($address) ? $address : null;
            }, preg_split('/(\\r\\n|\\n|\\r)/', $to))
        );
        return $to;
    }
    
    public function admin_page()
    {
        $this->get_timestamp();
        include __DIR__ . '/settings.php';
    }

    private function get_api_url($path)
    {
        return self::API_BASE_URL . $path;
    }
    
    public function get_token($location)
    {
        if (!isset($_REQUEST['get_token'])) {
            return $location;
        }
        if (empty($_REQUEST[sprintf(self::OPTION_FORMAT, 'api_key')])) {
            return $location;
        }
        $key = $_REQUEST[sprintf(self::OPTION_FORMAT, 'api_key')];

        $body = $this->request(
            'POST',
            self::API_TOKEN_PATH,
            [
                'headers' => [
                    'X-Ovh-Application' => $key,
                    'Content-Type'      => 'application/json',
                ],
            ],
            json_encode([
                'accessRules' => [
//                    ['method' => 'GET', 'path' => '/domain'],
//                    ['method' => 'GET', 'path' => '/email/domain/*/redirection'],
                    ['method' => 'GET', 'path' => '/*'],
                ],
                'redirection' => get_home_url(null, $location),
            ])
        );
        
        if (is_wp_error($body)) {
            return $location . '&error='. urlencode($body->get_error_message());
        }

        if (empty($body['consumerKey']) || empty($body['validationUrl'])) {
            return $location . '&error=' . urlencode(sprintf(
                    __('Mauvaise valeur de retour de l’API: %s', self::TEXTDOMAIN),
                    json_encode($body)
                ));
        }
        
        // store token
        update_option(sprintf(self::OPTION_FORMAT, 'api_token'), $body['consumerKey']);

        add_filter('wp_redirect_status', function () {
            return 302;
        });

        return $body['validationUrl'];
    }
    
    private function request($method, $path, $args = null, $body = null)
    {
        $query = $this->get_api_url($path);
        $timestamp = $this->get_timestamp();
        $signature = '$1$'. sha1(sprintf(
            '%s+%s+%s+%s+%s+%s',
            $this->as,
            $this->ck,
            $method,
            $query,
            $body,
            $timestamp
        ));
        
        $defaults = [
            'method' => $method,
            'headers' => [
                'X-Ovh-Application' => $this->ak,
                'X-Ovh-Timestamp' => $timestamp,
                'X-Ovh-Signature' => $signature,
                'X-Ovh-Consumer' => $this->ck,
            ],
            'body' => $body,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $http = _wp_http_get_object();
        $response = $http->request($query, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }

        $body = null;
        if (isset($response['body'])) {
            $body = json_decode($response['body'], true);
        }
        
        if ($response['response']['code'] !== 200) {
            $message = $response['response']['message'];
            if ($body && isset($body['message'])) {
                $message .= ': '. $body['message'];
            }
            return new WP_Error($response['response']['code'], $message);
        }
        if ($body === null) {
            return new WP_Error(1, sprintf(__('Mauvaise valeur de retour de l’API: %s', self::TEXTDOMAIN), $response['body']));
        }
        return $body;
    }
    
    private function get_timestamp()
    {
        return time();
    }

    /**
     * @return array|WP_Error Array of available domains
     */
    public function get_domains()
    {
        $api_domains = $this->request('GET', self::API_DOMAIN_PATH);
        $selected_domains = get_option(sprintf(OvhEmailAliases::OPTION_FORMAT, 'domains'));
        
        if (is_wp_error($api_domains)) {
            return $api_domains;
        }
        if (!$api_domains) {
            return new WP_Error(1, __('Aucun domaine n’a été trouvé pour ce compte.', OvhEmailAliases::TEXTDOMAIN));
        }
        
        $domains = [];
        foreach ($api_domains as $domain) {
            $domains[$domain] = isset($selected_domains[$domain]);
        }
        return $domains;
    }

    /**
     * @return array|WP_Error
     */
    public function get_redirections()
    {
        $raw = $this->get_raw_redirections();
        
        if (is_wp_error($raw)) {
            return $raw;
        }
        
        $domains = [];
        
        foreach ($raw as $domain => $redirections) {
            if (!is_wp_error($redirections)) {
                $domains[$domain] = [];
                foreach ($redirections as $id => $redirection) {
                    $from = $redirection['from'];
                    if (!isset($domains[$domain][$from])) {
                        $domains[$domain][$from] = [];
                    }
                    $domains[$domain][$from][] = $redirection['to'];
                }
            }
            $from = $redirection['from'];
            if (!isset($redirections[$domain][$from])) {
                $redirections[$domain][$from] = [];
            }
            $redirections[$domain][$from][] = $redirection['to'];
        }
        
        return $domains;
    }
    
    private function get_raw_redirections()
    {
        $domains = get_option(sprintf(OvhEmailAliases::OPTION_FORMAT, 'domains'));
        if (!$domains) {
            return new WP_Error(1, __('Aucun domaine', self::TEXTDOMAIN));
        }
        $redirections = [];
        foreach ($domains as $domain => $enabled) {
            $response = $this->request('GET', sprintf(self::API_REDIRECTIONS_PATH, $domain));
            if (is_wp_error($response)) {
                $redirections[$domain] = $response;
            } else {
                $redirections[$domain] = [];
                foreach ($response as $id) {
                    $redirection = $this->request('GET', sprintf(self::API_REDIRECTION_PATH, $domain, $id));
                    $hash = is_wp_error($redirection) ? $id : $redirection['from'] . ':' . $redirection['to'];
                    $redirections[$domain][$hash] = $redirection;
                }
                ksort($redirections[$domain]);
            }
        }
        
        ksort($redirections);
        
        return $redirections;
    }

    /**
     * @param $error WP_Error
     */
    public function print_error($error)
    {
        echo '<div class="notice notice-error inline"><p>'. $error->get_error_message() .'</p></div>';
    }
}