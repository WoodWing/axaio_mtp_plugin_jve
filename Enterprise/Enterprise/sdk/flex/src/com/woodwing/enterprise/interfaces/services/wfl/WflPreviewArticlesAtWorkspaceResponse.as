/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflPreviewArticlesAtWorkspaceResponse")]

	public class WflPreviewArticlesAtWorkspaceResponse
	{
		private var _Placements:Array;
		private var _Elements:Array;
		private var _Pages:Array;
		private var _LayoutVersion:String;
		private var _InDesignArticles:Array;
		private var _Relations:Array;

		public function WflPreviewArticlesAtWorkspaceResponse() {
		}

		public function get Placements():Array {
			return this._Placements;
		}
		public function set Placements(Placements:Array):void {
			this._Placements = Placements;
		}

		public function get Elements():Array {
			return this._Elements;
		}
		public function set Elements(Elements:Array):void {
			this._Elements = Elements;
		}

		public function get Pages():Array {
			return this._Pages;
		}
		public function set Pages(Pages:Array):void {
			this._Pages = Pages;
		}

		public function get LayoutVersion():String {
			return this._LayoutVersion;
		}
		public function set LayoutVersion(LayoutVersion:String):void {
			this._LayoutVersion = LayoutVersion;
		}

		public function get InDesignArticles():Array {
			return this._InDesignArticles;
		}
		public function set InDesignArticles(InDesignArticles:Array):void {
			this._InDesignArticles = InDesignArticles;
		}

		public function get Relations():Array {
			return this._Relations;
		}
		public function set Relations(Relations:Array):void {
			this._Relations = Relations;
		}

	}
}
