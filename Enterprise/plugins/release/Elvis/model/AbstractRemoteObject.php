<?php

abstract class AbstractRemoteObject {
	
	/**
	 * Return the name of the class
	 *
	 * @return string
     */
	public static function getName() {
		return get_called_class();
	}
	
}