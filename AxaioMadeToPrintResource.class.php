<?php
require_once dirname(__FILE__) . '/config.php';

class AxaioMadeToPrintResource
{
	public static function tr($string, $language='enUS')
	{
        $resourceFile = (file_exists(dirname(__FILE__).'/resources/'.$language.'.json')) ? file_get_contents(dirname(__FILE__).'/resources/'.$language.'.json') : file_get_contents(dirname(__FILE__).'/resources/enUS.json');
		$resourceJson = json_decode($resourceFile);
		$ressourceArray = (array) $resourceJson;
		$stringTranslated = strtr($string, $ressourceArray);

		return $stringTranslated;
	}
}
