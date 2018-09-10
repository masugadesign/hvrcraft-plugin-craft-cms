<?php

namespace Masuga\Hvrcraft;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Dashboard;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use Masuga\Hvrcraft\models\Settings;
use Masuga\Hvrcraft\services\HvrcraftService;
use yii\base\Event;

class Hvrcraft extends Plugin
{

	/**
	 * Enables the CP sidebar nav link for this plugin. Craft loads the plugin's
	 * index template by default.
	 * @var boolean
	 */
	public $hasCpSection = false;

	/**
	 * Enables the plugin settings form.
	 * @var boolean
	 */
	public $hasCpSettings = true;

	/**
	 * The name of the plugin as it appears in the Craft control panel and
	 * plugin store.
	 * @return string
	 */
	public function getName(): string
	{
		 return Craft::t('hvrcraft', 'Hvrcraft');
	}

	/**
	 * The brief description of the plugin that appears in the control panel
	 * on the plugin settings page.
	 * @return string
	 */
	public function getDescription(): string
	{
		return Craft::t('hvrcraft', 'This plugin allows the site to relay plugin and update data to hvrcraft.com.');
	}

	/**
	 * This method returns the plugin's Settings model instance.
	 * @return Settings
	 */
	protected function createSettingsModel(): Settings
	{
		return new Settings();
	}

	/**
	 * This method returns the settings form HTML content.
	 * @return string
	 */
	protected function settingsHtml(): string
	{
		return Craft::$app->getView()->renderTemplate('hvrcraft/_settings', [
			'settings' => $this->getSettings()
		]);
	}

	/**
	 * The plugin's initialization function is responsible for registering event
	 * handlers, routes and other plugin components.
	 */
	public function init()
	{
		parent::init();
		// Initialize each of the services used by this plugin.
		$this->setComponents([
			'hvrcraft' => HvrcraftService::class,
		]);
		// Register the site front-end routes.
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
			$event->rules['synchronize-hvrcraft'] = 'hvrcraft/communication/synchronize';
		});
		// Call home after a plugin is installed.
		Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
			$this->hvrcraft->wakeupHvrcraft();
		});
		// Call home after a plugin is uninstalled.
		Event::on(Plugins::class, Plugins::EVENT_AFTER_UNINSTALL_PLUGIN, function (PluginEvent $event) {
			$this->hvrcraft->wakeupHvrcraft();
		});
		// Call home after hvrcraft plugin's settings are saved.
		Event::on(Plugins::class, Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS, function (PluginEvent $event) {
			$plugin = $event->plugin;
			if ( $plugin->handle === 'hvrcraft' ) {
				$this->hvrcraft->wakeupHvrcraft();
			}
		});
	}

}
