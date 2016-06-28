/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEditionPages")]

	public class WflEditionPages
	{
		private var _Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
		private var _PageObjects:Array;

		public function WflEditionPages() {
		}

		public function get Edition():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition {
			return this._Edition;
		}
		public function set Edition(Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition):void {
			this._Edition = Edition;
		}

		public function get PageObjects():Array {
			return this._PageObjects;
		}
		public function set PageObjects(PageObjects:Array):void {
			this._PageObjects = PageObjects;
		}

	}
}
