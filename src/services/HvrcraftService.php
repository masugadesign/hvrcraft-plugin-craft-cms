<?php

namespace Masuga\Hvrcraft\services;

use Craft;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use yii\base\Component;
use Masuga\Hvrcraft\Hvrcraft;

class HvrcraftService extends Component
{
	/**
	 * The instance of the Hvrcraft plugin.
	 * @var Hvrcraft
	 */
	private $plugin = null;

	/**
	 * The class constructer
	 */
	public function __construct()
	{
		$this->plugin = Hvrcraft::getInstance();
	}

	/**
	 * This method fetches a cleaned up array of installed plugin data.
	 * @return array
	 */
	public function getInstalledPlugins()
	{
		$pluginsService = Craft::$app->getPlugins();
		$plugins = $pluginsService->getAllPlugins();
		$cleaned = [];
		foreach($plugins as $handle => &$plugin) {
			$cleaned[$handle] = [
				'name' => $plugin->name,
				'description' => $plugin->description,
				'version' => $plugin->version,
				'developer' => $plugin->developer,
				'changelogUrl' => $plugin->changelogUrl
			];
		}
		return $cleaned;
	}

	/**
	 * This method fetches a cleaned up array of available updates. It only returns
	 * the latest update available for the CMS and each installed plugin.
	 * @return array
	 */
	public function getAvailableUpdates()
	{
		$updatesService = Craft::$app->getUpdates();
		$updates = $updatesService->getUpdates();
		$cleaned = [
			'cms' => [
				'version' => Craft::$app->getVersion()
			]
		];
		// Check for a CMS update.
		if ( isset($updates->cms->releases[0]) ) {
			$latestCmsUpdate = $updates->cms->releases[0];
			$cleaned['cms']['update'] = [
				'version' => $latestCmsUpdate->version,
				'releaseDate' => $latestCmsUpdate->date->format('Y-m-d'),
				'critical' => $latestCmsUpdate->critical
			];
		} else {
			$cleaned['cms']['update'] = null;
		}
		// Clean up any plugin updates.
		if ( isset($updates->plugins) ) {
			foreach($updates->plugins as $handle => &$updateInfo) {
				$latestPluginUpdate = $updateInfo->releases[0] ?? null;
				if ( $latestPluginUpdate ) {
					$cleaned[$handle] = [
						'version' => $latestPluginUpdate->version,
						'releaseDate' => $latestPluginUpdate->date ? $latestPluginUpdate->date->format('Y-m-d') : '',
						'critical' => $latestPluginUpdate->critical
					];
				}
			}
		}
		return $cleaned;
	}

	/**
	 * This method returns a cleaned up array of installed plugin data along with
	 * any available update data.
	 * @return array
	 */
	public function getPluginsAndUpdates()
	{
		$plugins = $this->getInstalledPlugins();
		$updates = $this->getAvailableUpdates();
		$formatted['cms'] = isset($updates['cms']) ? $updates['cms'] : null;
		$formatted['plugins'] = $plugins;
		foreach($formatted['plugins'] as $handle => &$plugin) {
			$plugin['update'] = isset($updates[$handle]) ? $updates[$handle] : null;
		}
		return $formatted;
	}

	/**
	 * This method sends a wakeup call to Hvrcraft telling it to fetch the updated
	 * plugin/update data from this site.
	 */
	public function wakeupHvrcraft()
	{
		$siteKey = $this->plugin->getSettings()->siteKey;
		$baseUrl = getenv('CRAFTENV_HVRCRAFT_BASE_URL') ?: 'https://www.hvrcraft.com/';
		$client = new Client(['base_uri' => $baseUrl]);
		try {
			$response = $client->request('GET', 'api/wake-up', [
				'query' => ['key' => $siteKey]
			]);
			$body = (string)$response->getBody();
		} catch (Exception $e) {
			//exit("ERROR REQUESTING : {$baseUrl}api/wake-up?key={$siteKey}");
		}
	}

}
