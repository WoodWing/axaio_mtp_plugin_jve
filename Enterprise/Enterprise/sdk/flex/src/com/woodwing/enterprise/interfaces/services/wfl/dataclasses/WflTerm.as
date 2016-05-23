/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflTerm")]

	public class WflTerm
	{
		private var _Term:String;
		private var _Translation:String;

		public function WflTerm() {
		}

		public function get Term():String {
			return this._Term;
		}
		public function set Term(Term:String):void {
			this._Term = Term;
		}

		public function get Translation():String {
			return this._Translation;
		}
		public function set Translation(Translation:String):void {
			this._Translation = Translation;
		}

	}
}
