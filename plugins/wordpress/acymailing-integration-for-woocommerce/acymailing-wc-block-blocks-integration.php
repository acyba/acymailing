<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if (!defined('ABSPATH')) exit;
define('ACYMAILING_WC_BLOCK_VERSION', '0.1.0');

/**
 * Class for integrating with WooCommerce Blocks
 */
class AcymailingWcBlock implements IntegrationInterface
{
    /**
     * The name of the integration.
     *
     * @return string
     */
    public function get_name()
    {
        return 'acymailing-wc-block';
    }

    /**
     * When called invokes any initialization/setup for the integration.
     */
    public function initialize()
    {
        $this->register_newsletter_block_frontend_scripts();
        $this->register_main_integration();
    }

    /**
     * Registers the main JS file required to add filters and Slot/Fills.
     */
    public function register_main_integration()
    {
        $script_path = '/build/index.js';

        $script_url = plugins_url($script_path, __FILE__);

        $script_asset_path = dirname(__FILE__).'/build/index.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require $script_asset_path
            : [
                'dependencies' => [],
                'version' => $this->get_file_version($script_path),
            ];

        wp_register_script(
            'acymailing-wc-block-blocks-integration',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @return string[]
     */
    public function get_script_handles()
    {
        return ['acymailing-wc-block-blocks-integration'];
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles()
    {
        return ['acymailing-wc-block-blocks-integration'];
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data()
    {
        $config = acym_config();

        $text = $config->get('woocommerce_text', acym_translation('ACYM_SUBSCRIBE_NEWSLETTER'));

        $data = [
            'optInDefaultText' => $text,
            'isSubscriptionOptionActive' => $config->get('woocommerce_sub', 0) == 1,
        ];

        return $data;
    }

    public function register_newsletter_block_frontend_scripts()
    {
    }

    /**
     * Get the file modified time as a cache buster if we're in dev mode.
     *
     * @param string $file Local path to the file.
     *
     * @return string The cache buster value to use for the given file.
     */
    protected function get_file_version($file)
    {
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file)) {
            return filemtime($file);
        }

        return ACYMAILING_WC_BLOCK_VERSION;
    }
}
