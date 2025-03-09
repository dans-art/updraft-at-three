<?php

if (!defined('ABSPATH')) {
    exit;
}

if (! class_exists('GithubUpdater')) {

    class GithubUpdater
    {

        private $github_url;
        private $repo_slug;
        private $plugin_slug;
        private $github_api_url;
        private $github_token;

        /**
         * Initialize the Github Updater.
         *
         * @param string $plugin_slug The slug of the plugin, e.g. `my-plugin/my-plugin.php`.
         * @param string $repo_slug The slug of the Github repository, e.g. `dans-art/updraft-at-three`.
         * @param string $github_url The URL to the Github repository, e.g. `https://github.com/dans-art/updraft-at-three`.
         * @param string $github_token The Github token to use for authentication. Optional.
         */
        function __construct($plugin_slug, $repo_slug, $github_url, $github_token = "")
        {
            $this->github_url = $github_url;
            $this->repo_slug = $repo_slug;
            $this->plugin_slug = $plugin_slug;
            $this->github_api_url = "https://api.github.com/repos/" . $this->repo_slug . '/releases/latest';
            $this->github_token = $github_token;
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
            add_filter('upgrader_pre_download', array($this, 'download_plugin'), 10, 2);
        }

        /**
         * Check for updates to the plugin.
         *
         * This function is run when WordPress checks for updates to plugins. If a new version is available, the update is added to the transient.
         *
         * @param object $transient The update transient.
         * @return object The update transient.
         */
        public function check_for_update($transient)
        {
            $plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_slug . '/' . $this->plugin_slug . '.php');
            if (empty($plugin['Version'])) {
                return $transient;
            }

            $current_version = $plugin['Version'];

            $github_repo = $this->get_github_repo();
            if (! $github_repo) {
                return $transient;
            }

            $latest_version = $github_repo->tag_name;

            if (version_compare($latest_version, $current_version, '>')) {
                $transient->response[$this->plugin_slug . '/' . $this->plugin_slug . '.php'] = (object) array(
                    'slug' => $this->plugin_slug,
                    'plugin' => $this->plugin_slug . '/' . $this->plugin_slug . '.php',
                    'new_version' => $latest_version,
                    'url' => $github_repo->html_url,
                    'package' => $this->get_zip_download_url($github_repo)
                );
            }

            return $transient;
        }

        /**
         * Gets the latest version of the plugin from GitHub.
         *
         * Queries the GitHub API to get the latest version of the plugin. If a GitHub token is set, it is used to authenticate.
         *
         * @return object The data from the latest version of the plugin on GitHub, or false if there is an error.
         */
        private function get_github_repo()
        {
            if (!empty($this->github_token)) {
                $args = array(
                    'headers' => array(
                        'Authorization' => 'token ' . $this->github_token,
                    ),
                );
            } else {
                $args = array();
            }

            $response = wp_remote_get($this->github_api_url, $args);
            if (is_wp_error($response)) {
                return false;
            }

            return json_decode(wp_remote_retrieve_body($response));
        }

        /**
         * Gets the URL for downloading the latest version of the plugin from GitHub.
         *
         * @param object $release_data The data from the latest version of the plugin on GitHub.
         * @return string The URL for downloading the latest version of the plugin.
         */
        private function get_zip_download_url($release_data)
        {
            return $this->github_url . '/archive/refs/tags/' . $release_data->tag_name . '.zip';
        }

        public function download_plugin($reply, $package)
        {
            if (strpos($package, 'github.com') !== false) {
                return $package;
            }
            return $reply;
        }
    }
}
