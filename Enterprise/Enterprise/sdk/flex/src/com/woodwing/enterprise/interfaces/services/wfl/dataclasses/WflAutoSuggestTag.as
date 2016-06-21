/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAutoSuggestTag")]

	public class WflAutoSuggestTag
	{
		private var _Value:String;
		private var _Score:Number;
		private var _StartPos:Number;
		private var _Length:Number;

		public function WflAutoSuggestTag() {
		}

		public function get Value():String {
			return this._Value;
		}
		public function set Value(Value:String):void {
			this._Value = Value;
		}

		public function get Score():Number {
			return this._Score;
		}
		public function set Score(Score:Number):void {
			this._Score = Score;
		}

		public function get StartPos():Number {
			return this._StartPos;
		}
		public function set StartPos(StartPos:Number):void {
			this._StartPos = StartPos;
		}

		public function get Length():Number {
			return this._Length;
		}
		public function set Length(Length:Number):void {
			this._Length = Length;
		}

	}
}
