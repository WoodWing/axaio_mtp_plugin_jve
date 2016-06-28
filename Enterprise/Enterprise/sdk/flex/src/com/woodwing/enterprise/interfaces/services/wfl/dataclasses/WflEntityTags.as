/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEntityTags")]

	public class WflEntityTags
	{
		private var _Entity:String;
		private var _Tags:Array;

		public function WflEntityTags() {
		}

		public function get Entity():String {
			return this._Entity;
		}
		public function set Entity(Entity:String):void {
			this._Entity = Entity;
		}

		public function get Tags():Array {
			return this._Tags;
		}
		public function set Tags(Tags:Array):void {
			this._Tags = Tags;
		}

	}
}
