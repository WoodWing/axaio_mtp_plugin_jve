/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflActionProperty")]

	public class WflActionProperty
	{
		private var _Action:String;
		private var _ObjectType:String;
		private var _Properties:Array;

		public function WflActionProperty() {
		}

		public function get Action():String {
			return this._Action;
		}
		public function set Action(Action:String):void {
			this._Action = Action;
		}

		public function get ObjectType():String {
			return this._ObjectType;
		}
		public function set ObjectType(ObjectType:String):void {
			this._ObjectType = ObjectType;
		}

		public function get Properties():Array {
			return this._Properties;
		}
		public function set Properties(Properties:Array):void {
			this._Properties = Properties;
		}

	}
}
