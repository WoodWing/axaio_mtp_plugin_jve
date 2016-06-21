/*
	Enterprise Planning Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pln.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnLayout;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnLayoutFromTemplate")]

	public class PlnLayoutFromTemplate
	{
		private var _NewLayout:com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnLayout;
		private var _Template:String;

		public function PlnLayoutFromTemplate() {
		}

		public function get NewLayout():com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnLayout {
			return this._NewLayout;
		}
		public function set NewLayout(NewLayout:com.woodwing.enterprise.interfaces.services.pln.dataclasses.PlnLayout):void {
			this._NewLayout = NewLayout;
		}

		public function get Template():String {
			return this._Template;
		}
		public function set Template(Template:String):void {
			this._Template = Template;
		}

	}
}
