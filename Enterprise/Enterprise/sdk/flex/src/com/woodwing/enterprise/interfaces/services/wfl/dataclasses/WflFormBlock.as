/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflFormBlock")]

	public class WflFormBlock
	{
		private var _Property:String;
		private var _Objects:Array;

		public function WflFormBlock() {
		}

		public function get Property():String {
			return this._Property;
		}
		public function set Property(Property:String):void {
			this._Property = Property;
		}

		public function get Objects():Array {
			return this._Objects;
		}
		public function set Objects(Objects:Array):void {
			this._Objects = Objects;
		}

	}
}
