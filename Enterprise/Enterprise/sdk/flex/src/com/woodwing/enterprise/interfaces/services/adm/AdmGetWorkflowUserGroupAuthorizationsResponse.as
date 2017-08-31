/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetWorkflowUserGroupAuthorizationsResponse")]

	public class AdmGetWorkflowUserGroupAuthorizationsResponse
	{
		private var _WorkflowUserGroupAuthorizations:Array;
		private var _UserGroups:Array;
		private var _Statuses:Array;
		private var _Sections:Array;

		public function AdmGetWorkflowUserGroupAuthorizationsResponse() {
		}

		public function get WorkflowUserGroupAuthorizations():Array {
			return this._WorkflowUserGroupAuthorizations;
		}
		public function set WorkflowUserGroupAuthorizations(WorkflowUserGroupAuthorizations:Array):void {
			this._WorkflowUserGroupAuthorizations = WorkflowUserGroupAuthorizations;
		}

		public function get UserGroups():Array {
			return this._UserGroups;
		}
		public function set UserGroups(UserGroups:Array):void {
			this._UserGroups = UserGroups;
		}

		public function get Statuses():Array {
			return this._Statuses;
		}
		public function set Statuses(Statuses:Array):void {
			this._Statuses = Statuses;
		}

		public function get Sections():Array {
			return this._Sections;
		}
		public function set Sections(Sections:Array):void {
			this._Sections = Sections;
		}

	}
}
