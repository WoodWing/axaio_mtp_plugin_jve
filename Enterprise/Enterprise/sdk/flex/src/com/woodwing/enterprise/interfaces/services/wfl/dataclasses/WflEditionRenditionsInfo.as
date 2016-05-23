/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEditionRenditionsInfo")]

	public class WflEditionRenditionsInfo
	{
		private var _Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
		private var _Renditions:Array;

		public function WflEditionRenditionsInfo() {
		}

		public function get Edition():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition {
			return this._Edition;
		}
		public function set Edition(Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition):void {
			this._Edition = Edition;
		}

		public function get Renditions():Array {
			return this._Renditions;
		}
		public function set Renditions(Renditions:Array):void {
			this._Renditions = Renditions;
		}

	}
}
