<?php

namespace Masuga\Hvrcraft\models;

use craft\base\Model;

class Settings extends Model
{

	/**
	 * The Hvrcraft.com site key.
	 * @var string
	 */
	public $siteKey = null;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['siteKey'], 'required'],
		];
	}

}
