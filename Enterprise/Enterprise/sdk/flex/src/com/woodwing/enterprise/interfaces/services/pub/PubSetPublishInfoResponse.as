/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub
{
	import com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishedIssue;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.PubSetPublishInfoResponse")]

	public class PubSetPublishInfoResponse
	{
		private var _PublishedDossiers:Array;
		private var _PublishedIssue:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishedIssue;

		public function PubSetPublishInfoResponse() {
		}

		public function get PublishedDossiers():Array {
			return this._PublishedDossiers;
		}
		public function set PublishedDossiers(PublishedDossiers:Array):void {
			this._PublishedDossiers = PublishedDossiers;
		}

		public function get PublishedIssue():com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishedIssue {
			return this._PublishedIssue;
		}
		public function set PublishedIssue(PublishedIssue:com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishedIssue):void {
			this._PublishedIssue = PublishedIssue;
		}

	}
}
