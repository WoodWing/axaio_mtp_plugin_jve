<?php

class ElvisTypeConst {
	/**
	 * Used for the type in UpdateObjectOperation. For now only Layouts are supported.
	 */
	const OBJECT_TYPE_LAYOUT = 'Layout';
	const OBJECT_TYPE_DOSSIER = 'Dossier';

	/**
	 * Type used in ObjectRelation.
	 */
	const RELATION_TYPE_PLACED = 'Placed';
	const RELATION_TYPE_CONTAINED = 'Contained';
}
