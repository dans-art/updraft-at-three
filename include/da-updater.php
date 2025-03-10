<?php

if (!defined('ABSPATH')) {
    exit;
}

if (! class_exists('DaUpdater')) {

    class DaUpdater
    {

        private $plugin_slug;
        private $da_releases;

        /**
         * Initialize the updater. Adds the filter
         *
         * @param string $plugin_slug The slug of the plugin (usually the folder name)
         */
        function __construct($plugin_slug)
        {
            $this->plugin_slug = $plugin_slug;
            $this->da_releases = "https://dans-art.ch/downloads/$plugin_slug/releases.json";

            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
            add_filter('upgrader_pre_download', array($this, 'download_plugin'), 10, 2);
        }

        /**
         * Check for updates
         *
         * @param object $transient The plugin transient from WordPress
         *
         * @return object The modified transient with the update information
         */
        public function check_for_update($transient)
        {
            $plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_slug . '/' . $this->plugin_slug . '.php');
            if (empty($plugin['Version'])) {
                return $transient;
            }

            $current_version = $plugin['Version'];

            $da_releases = $this->get_da_releases();
            if (! $da_releases) {
                return $transient;
            }
            $newest_version = $da_releases->version;
            if (version_compare($newest_version, $current_version, '>')) {
                $transient->response[$this->plugin_slug . '/' . $this->plugin_slug . '.php'] = (object) array(
                    'slug' => $this->plugin_slug,
                    'plugin' => $this->plugin_slug . '/' . $this->plugin_slug . '.php',
                    'new_version' => $newest_version,
                    'url' => $da_releases->html_url,
                    'package' => $da_releases->download_url
                );
            }

            return $transient;
        }

        /**
         * Get the latest release information from the specified URL
         *
         * @return object|false The release information, or false on failure
         */
        private function get_da_releases()
        {

            $response = wp_remote_get($this->da_releases);
            if (is_wp_error($response)) {
                return false;
            }

            return json_decode(wp_remote_retrieve_body($response));
        }

        /**
         * Allow the package download to proceed if the package is hosted on
         * dans-art.ch; otherwise, return the original reply.
         *
         * @param string $reply The original reply
         * @param string $package The package URL
         *
         * @return string The modified reply
         */
        public function download_plugin($reply, $package)
        {
            if (strpos($package, 'dans-art.ch') !== false) {
                return $package;
            }
            return $reply;
        }
    }
}
