/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmModifySectionsResponse")]

	public class AdmModifySectionsResponse
	{
		private var _PublicationId:Number;
		private var _IssueId:Number;
		private var _Sections:Array;

		public function AdmModifySectionsResponse() {
		}

		public function get PublicationId():Number {
			return this._PublicationId;
		}
		public function set PublicationId(PublicationId:Number):void {
			this._PublicationId = PublicationId;
		}

		public function get IssueId():Number {
			return this._IssueId;
		}
		public function set IssueId(IssueId:Number):void {
			this._IssueId = IssueId;
		}

		public function get Sections():Array {
			return this._Sections;
		}
		public function set Sections(Sections:Array):void {
			this._Sections = Sections;
		}

	}
}
