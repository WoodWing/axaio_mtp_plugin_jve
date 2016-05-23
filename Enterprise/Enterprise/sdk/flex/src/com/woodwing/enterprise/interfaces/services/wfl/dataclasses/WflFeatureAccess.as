/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflFeatureAccess")]

	public class WflFeatureAccess
	{
		private var _Profile:String;
		private var _Issue:String;
		private var _Section:String;
		private var _State:String;

		public function WflFeatureAccess() {
		}

		public function get Profile():String {
			return this._Profile;
		}
		public function set Profile(Profile:String):void {
			this._Profile = Profile;
		}

		public function get Issue():String {
			return this._Issue;
		}
		public function set Issue(Issue:String):void {
			this._Issue = Issue;
		}

		public function get Section():String {
			return this._Section;
		}
		public function set Section(Section:String):void {
			this._Section = Section;
		}

		public function get State():String {
			return this._State;
		}
		public function set State(State:String):void {
			this._State = State;
		}

	}
}
