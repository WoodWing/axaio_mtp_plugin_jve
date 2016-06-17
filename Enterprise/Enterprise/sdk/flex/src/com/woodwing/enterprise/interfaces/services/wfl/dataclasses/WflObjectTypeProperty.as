/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectTypeProperty")]

	public class WflObjectTypeProperty
	{
		private var _Type:String;
		private var _Properties:Array;

		public function WflObjectTypeProperty() {
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Properties():Array {
			return this._Properties;
		}
		public function set Properties(Properties:Array):void {
			this._Properties = Properties;
		}

	}
}
