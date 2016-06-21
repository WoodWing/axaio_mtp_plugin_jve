/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubProgressPhase")]

	public class PubProgressPhase
	{
		private var _ID:String;
		private var _Label:String;
		private var _Maximum:Number;
		private var _Progress:Number;

		public function PubProgressPhase() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Label():String {
			return this._Label;
		}
		public function set Label(Label:String):void {
			this._Label = Label;
		}

		public function get Maximum():Number {
			return this._Maximum;
		}
		public function set Maximum(Maximum:Number):void {
			this._Maximum = Maximum;
		}

		public function get Progress():Number {
			return this._Progress;
		}
		public function set Progress(Progress:Number):void {
			this._Progress = Progress;
		}

	}
}
