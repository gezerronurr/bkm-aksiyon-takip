<?php
class BKM_Aksiyon {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('BKM_AKSIYON_VERSION')) {
            $this->version = BKM_AKSIYON_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'bkm-aksiyon-takip';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-bkm-aksiyon-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-bkm-aksiyon-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-bkm-aksiyon-public.php';

        $this->loader = new BKM_Aksiyon_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new BKM_Aksiyon_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_ajax_handlers');
    }

    private function define_public_hooks() {
        $plugin_public = new BKM_Aksiyon_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}