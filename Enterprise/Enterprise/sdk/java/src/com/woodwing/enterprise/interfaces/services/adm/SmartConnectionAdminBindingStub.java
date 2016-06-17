/**
 * SmartConnectionAdminBindingStub.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class SmartConnectionAdminBindingStub extends org.apache.axis.client.Stub implements com.woodwing.enterprise.interfaces.services.adm.SmartConnectionAdminPort_PortType {
    private java.util.Vector cachedSerClasses = new java.util.Vector();
    private java.util.Vector cachedSerQNames = new java.util.Vector();
    private java.util.Vector cachedSerFactories = new java.util.Vector();
    private java.util.Vector cachedDeserFactories = new java.util.Vector();

    static org.apache.axis.description.OperationDesc [] _operations;

    static {
        _operations = new org.apache.axis.description.OperationDesc[43];
        _initOperationDesc1();
        _initOperationDesc2();
        _initOperationDesc3();
        _initOperationDesc4();
        _initOperationDesc5();
    }

    private static void _initOperationDesc1(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("LogOn");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "LogOnRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">LogOnRequest"), com.woodwing.enterprise.interfaces.services.adm.LogOnRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">LogOnResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.LogOnResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "LogOnResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[0] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("LogOff");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "LogOffRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">LogOffRequest"), com.woodwing.enterprise.interfaces.services.adm.LogOffRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[1] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateUsers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateUsersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUsersRequest"), com.woodwing.enterprise.interfaces.services.adm.CreateUsersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUsersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreateUsersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateUsersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[2] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetUsers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetUsersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUsersRequest"), com.woodwing.enterprise.interfaces.services.adm.GetUsersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUsersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetUsersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetUsersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[3] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyUsers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyUsersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUsersRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyUsersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUsersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifyUsersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyUsersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[4] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteUsers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteUsersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteUsersRequest"), com.woodwing.enterprise.interfaces.services.adm.DeleteUsersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteUsersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[5] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateUserGroups");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateUserGroupsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUserGroupsRequest"), com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUserGroupsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateUserGroupsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[6] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetUserGroups");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetUserGroupsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUserGroupsRequest"), com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUserGroupsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetUserGroupsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[7] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyUserGroups");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyUserGroupsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUserGroupsRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUserGroupsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyUserGroupsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[8] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteUserGroups");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteUserGroupsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteUserGroupsRequest"), com.woodwing.enterprise.interfaces.services.adm.DeleteUserGroupsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteUserGroupsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[9] = oper;

    }

    private static void _initOperationDesc2(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("AddUsersToGroup");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "AddUsersToGroupRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">AddUsersToGroupRequest"), com.woodwing.enterprise.interfaces.services.adm.AddUsersToGroupRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[10] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("RemoveUsersFromGroup");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "RemoveUsersFromGroupRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">RemoveUsersFromGroupRequest"), com.woodwing.enterprise.interfaces.services.adm.RemoveUsersFromGroupRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[11] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("AddGroupsToUser");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "AddGroupsToUserRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">AddGroupsToUserRequest"), com.woodwing.enterprise.interfaces.services.adm.AddGroupsToUserRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[12] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("RemoveGroupsFromUser");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "RemoveGroupsFromUserRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">RemoveGroupsFromUserRequest"), com.woodwing.enterprise.interfaces.services.adm.RemoveGroupsFromUserRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[13] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreatePublications");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreatePublicationsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePublicationsRequest"), com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePublicationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreatePublicationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[14] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetPublications");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetPublicationsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPublicationsRequest"), com.woodwing.enterprise.interfaces.services.adm.GetPublicationsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPublicationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetPublicationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetPublicationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[15] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyPublications");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyPublicationsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPublicationsRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPublicationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyPublicationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[16] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeletePublications");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeletePublicationsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeletePublicationsRequest"), com.woodwing.enterprise.interfaces.services.adm.DeletePublicationsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[17] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreatePubChannels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreatePubChannelsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePubChannelsRequest"), com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePubChannelsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreatePubChannelsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[18] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetPubChannels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetPubChannelsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPubChannelsRequest"), com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPubChannelsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetPubChannelsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[19] = oper;

    }

    private static void _initOperationDesc3(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyPubChannels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyPubChannelsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPubChannelsRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPubChannelsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyPubChannelsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[20] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeletePubChannels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeletePubChannelsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeletePubChannelsRequest"), com.woodwing.enterprise.interfaces.services.adm.DeletePubChannelsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeletePubChannelsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[21] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateIssues");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateIssuesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateIssuesRequest"), com.woodwing.enterprise.interfaces.services.adm.CreateIssuesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateIssuesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreateIssuesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateIssuesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[22] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetIssues");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetIssuesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetIssuesRequest"), com.woodwing.enterprise.interfaces.services.adm.GetIssuesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetIssuesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetIssuesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetIssuesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[23] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyIssues");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyIssuesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyIssuesRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyIssuesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyIssuesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[24] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteIssues");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteIssuesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteIssuesRequest"), com.woodwing.enterprise.interfaces.services.adm.DeleteIssuesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteIssuesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[25] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CopyIssues");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CopyIssuesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyIssuesRequest"), com.woodwing.enterprise.interfaces.services.adm.CopyIssuesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyIssuesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CopyIssuesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CopyIssuesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[26] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateEditions");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateEditionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateEditionsRequest"), com.woodwing.enterprise.interfaces.services.adm.CreateEditionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateEditionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreateEditionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateEditionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[27] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetEditions");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetEditionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetEditionsRequest"), com.woodwing.enterprise.interfaces.services.adm.GetEditionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetEditionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetEditionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetEditionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[28] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyEditions");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyEditionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyEditionsRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyEditionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyEditionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[29] = oper;

    }

    private static void _initOperationDesc4(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteEditions");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteEditionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteEditionsRequest"), com.woodwing.enterprise.interfaces.services.adm.DeleteEditionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteEditionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[30] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateSections");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateSectionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateSectionsRequest"), com.woodwing.enterprise.interfaces.services.adm.CreateSectionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateSectionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreateSectionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateSectionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[31] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetSections");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetSectionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetSectionsRequest"), com.woodwing.enterprise.interfaces.services.adm.GetSectionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetSectionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetSectionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetSectionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[32] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifySections");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifySectionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifySectionsRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifySectionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifySectionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifySectionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifySectionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[33] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteSections");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteSectionsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteSectionsRequest"), com.woodwing.enterprise.interfaces.services.adm.DeleteSectionsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteSectionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[34] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateAutocompleteTermEntities");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateAutocompleteTermEntitiesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateAutocompleteTermEntitiesRequest"), com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateAutocompleteTermEntitiesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateAutocompleteTermEntitiesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[35] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetAutocompleteTermEntities");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetAutocompleteTermEntitiesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermEntitiesRequest"), com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermEntitiesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetAutocompleteTermEntitiesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[36] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyAutocompleteTermEntities");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyAutocompleteTermEntitiesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAutocompleteTermEntitiesRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAutocompleteTermEntitiesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyAutocompleteTermEntitiesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[37] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteAutocompleteTermEntities");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteAutocompleteTermEntitiesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteAutocompleteTermEntitiesRequest"), com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermEntitiesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteAutocompleteTermEntitiesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[38] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateAutocompleteTerms");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateAutocompleteTermsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateAutocompleteTermsRequest"), com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "CreateAutocompleteTermsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[39] = oper;

    }

    private static void _initOperationDesc5(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetAutocompleteTerms");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetAutocompleteTermsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermsRequest"), com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "GetAutocompleteTermsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[40] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyAutocompleteTerms");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyAutocompleteTermsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAutocompleteTermsRequest"), com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ModifyAutocompleteTermsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[41] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteAutocompleteTerms");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteAutocompleteTermsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteAutocompleteTermsRequest"), com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "DeleteAutocompleteTermsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[42] = oper;

    }

    public SmartConnectionAdminBindingStub() throws org.apache.axis.AxisFault {
         this(null);
    }

    public SmartConnectionAdminBindingStub(java.net.URL endpointURL, javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
         this(service);
         super.cachedEndpoint = endpointURL;
    }

    public SmartConnectionAdminBindingStub(javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
        if (service == null) {
            super.service = new org.apache.axis.client.Service();
        } else {
            super.service = service;
        }
        ((org.apache.axis.client.Service)super.service).setTypeMappingVersion("1.2");
            java.lang.Class cls;
            javax.xml.namespace.QName qName;
            javax.xml.namespace.QName qName2;
            java.lang.Class beansf = org.apache.axis.encoding.ser.BeanSerializerFactory.class;
            java.lang.Class beandf = org.apache.axis.encoding.ser.BeanDeserializerFactory.class;
            java.lang.Class enumsf = org.apache.axis.encoding.ser.EnumSerializerFactory.class;
            java.lang.Class enumdf = org.apache.axis.encoding.ser.EnumDeserializerFactory.class;
            java.lang.Class arraysf = org.apache.axis.encoding.ser.ArraySerializerFactory.class;
            java.lang.Class arraydf = org.apache.axis.encoding.ser.ArrayDeserializerFactory.class;
            java.lang.Class simplesf = org.apache.axis.encoding.ser.SimpleSerializerFactory.class;
            java.lang.Class simpledf = org.apache.axis.encoding.ser.SimpleDeserializerFactory.class;
            java.lang.Class simplelistsf = org.apache.axis.encoding.ser.SimpleListSerializerFactory.class;
            java.lang.Class simplelistdf = org.apache.axis.encoding.ser.SimpleListDeserializerFactory.class;
        addBindings0();
        addBindings1();
    }

    private void addBindings0() {
            java.lang.Class cls;
            javax.xml.namespace.QName qName;
            javax.xml.namespace.QName qName2;
            java.lang.Class beansf = org.apache.axis.encoding.ser.BeanSerializerFactory.class;
            java.lang.Class beandf = org.apache.axis.encoding.ser.BeanDeserializerFactory.class;
            java.lang.Class enumsf = org.apache.axis.encoding.ser.EnumSerializerFactory.class;
            java.lang.Class enumdf = org.apache.axis.encoding.ser.EnumDeserializerFactory.class;
            java.lang.Class arraysf = org.apache.axis.encoding.ser.ArraySerializerFactory.class;
            java.lang.Class arraydf = org.apache.axis.encoding.ser.ArrayDeserializerFactory.class;
            java.lang.Class simplesf = org.apache.axis.encoding.ser.SimpleSerializerFactory.class;
            java.lang.Class simpledf = org.apache.axis.encoding.ser.SimpleDeserializerFactory.class;
            java.lang.Class simplelistsf = org.apache.axis.encoding.ser.SimpleListSerializerFactory.class;
            java.lang.Class simplelistdf = org.apache.axis.encoding.ser.SimpleListDeserializerFactory.class;
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">AddGroupsToUserRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.AddGroupsToUserRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">AddUsersToGroupRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.AddUsersToGroupRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyIssuesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyIssuesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyIssuesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyIssuesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyPublicationsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyPublicationsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyPublicationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyPublicationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyUserGroupsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyUserGroupsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyUserGroupsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyUserGroupsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyUsersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyUsersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyUsersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CopyUsersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateAutocompleteTermEntitiesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateAutocompleteTermEntitiesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateAutocompleteTermsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateEditionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateEditionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateEditionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateEditionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateIssuesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateIssuesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateIssuesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateIssuesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePubChannelsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePubChannelsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePublicationsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePublicationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateSectionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateSectionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateSectionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateSectionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUserGroupsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUserGroupsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUsersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateUsersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUsersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.CreateUsersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteAutocompleteTermEntitiesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermEntitiesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteAutocompleteTermsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteEditionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteEditionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteIssuesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteIssuesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeletePubChannelsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeletePubChannelsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeletePublicationsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeletePublicationsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteSectionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteSectionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteStatusesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteStatusesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteUserGroupsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteUserGroupsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteUsersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DeleteUsersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermEntitiesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermEntitiesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetEditionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetEditionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetEditionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetEditionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetIssuesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetIssuesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetIssuesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetIssuesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPubChannelsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPubChannelsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPublicationsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetPublicationsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetPublicationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetPublicationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetSectionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetSectionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetSectionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetSectionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetStatusesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetStatusesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetStatusesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetStatusesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUserGroupsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUserGroupsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUsersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetUsersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetUsersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.GetUsersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">LogOffRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.LogOffRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">LogOnRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.LogOnRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">LogOnResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.LogOnResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAutocompleteTermEntitiesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAutocompleteTermEntitiesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAutocompleteTermsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyEditionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyEditionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyIssuesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyIssuesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPubChannelsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPubChannelsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPublicationsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyPublicationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifySectionsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifySectionsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifySectionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifySectionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyStatusesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyStatusesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyStatusesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyStatusesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUserGroupsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUserGroupsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUsersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyUsersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyUsersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ModifyUsersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">RemoveGroupsFromUserRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.RemoveGroupsFromUserRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">RemoveUsersFromGroupRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.RemoveUsersFromGroupRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfEdition");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Edition[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Edition");
            qName2 = new javax.xml.namespace.QName("", "Edition");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfExtraMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ExtraMetaData");
            qName2 = new javax.xml.namespace.QName("", "ExtraMetaData");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfId");
            cachedSerQNames.add(qName);
            cls = java.math.BigInteger[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Id");
            qName2 = new javax.xml.namespace.QName("", "Id");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfIdName");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.IdName[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName");
            qName2 = new javax.xml.namespace.QName("", "IdName");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfIssue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Issue[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Issue");
            qName2 = new javax.xml.namespace.QName("", "Issue");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfMode");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Mode[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Mode");
            qName2 = new javax.xml.namespace.QName("", "Mode");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfPubChannel");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.PubChannel[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "PubChannel");
            qName2 = new javax.xml.namespace.QName("", "PubChannel");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfPublication");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Publication[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Publication");
            qName2 = new javax.xml.namespace.QName("", "Publication");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfSection");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Section[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Section");
            qName2 = new javax.xml.namespace.QName("", "Section");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfStatus");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Status[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Status");
            qName2 = new javax.xml.namespace.QName("", "Status");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfString");
            cachedSerQNames.add(qName);
            cls = java.lang.String[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "String");
            qName2 = new javax.xml.namespace.QName("", "String");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfTermEntity");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.TermEntity[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "TermEntity");
            qName2 = new javax.xml.namespace.QName("", "TermEntity");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfUser");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.User[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "User");
            qName2 = new javax.xml.namespace.QName("", "User");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ArrayOfUserGroup");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.UserGroup[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "UserGroup");
            qName2 = new javax.xml.namespace.QName("", "UserGroup");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Color");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "dateTimeOrEmpty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Edition");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Edition.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "emptyString");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ExtraMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

    }
    private void addBindings1() {
            java.lang.Class cls;
            javax.xml.namespace.QName qName;
            javax.xml.namespace.QName qName2;
            java.lang.Class beansf = org.apache.axis.encoding.ser.BeanSerializerFactory.class;
            java.lang.Class beandf = org.apache.axis.encoding.ser.BeanDeserializerFactory.class;
            java.lang.Class enumsf = org.apache.axis.encoding.ser.EnumSerializerFactory.class;
            java.lang.Class enumdf = org.apache.axis.encoding.ser.EnumDeserializerFactory.class;
            java.lang.Class arraysf = org.apache.axis.encoding.ser.ArraySerializerFactory.class;
            java.lang.Class arraydf = org.apache.axis.encoding.ser.ArrayDeserializerFactory.class;
            java.lang.Class simplesf = org.apache.axis.encoding.ser.SimpleSerializerFactory.class;
            java.lang.Class simpledf = org.apache.axis.encoding.ser.SimpleDeserializerFactory.class;
            java.lang.Class simplelistsf = org.apache.axis.encoding.ser.SimpleListSerializerFactory.class;
            java.lang.Class simplelistdf = org.apache.axis.encoding.ser.SimpleListDeserializerFactory.class;
            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Id");
            cachedSerQNames.add(qName);
            cls = java.math.BigInteger.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.IdName.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Issue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Issue.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Mode");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Mode.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ObjectType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.ObjectType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "PubChannel");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.PubChannel.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "PubChannelType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.PubChannelType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Publication");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Publication.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Section");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Section.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Status");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.Status.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "StatusPhase");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.StatusPhase.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "String");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "TermEntity");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.TermEntity.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "User");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.User.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "UserGroup");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.adm.UserGroup.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

    }

    protected org.apache.axis.client.Call createCall() throws java.rmi.RemoteException {
        try {
            org.apache.axis.client.Call _call = super._createCall();
            if (super.maintainSessionSet) {
                _call.setMaintainSession(super.maintainSession);
            }
            if (super.cachedUsername != null) {
                _call.setUsername(super.cachedUsername);
            }
            if (super.cachedPassword != null) {
                _call.setPassword(super.cachedPassword);
            }
            if (super.cachedEndpoint != null) {
                _call.setTargetEndpointAddress(super.cachedEndpoint);
            }
            if (super.cachedTimeout != null) {
                _call.setTimeout(super.cachedTimeout);
            }
            if (super.cachedPortName != null) {
                _call.setPortName(super.cachedPortName);
            }
            java.util.Enumeration keys = super.cachedProperties.keys();
            while (keys.hasMoreElements()) {
                java.lang.String key = (java.lang.String) keys.nextElement();
                _call.setProperty(key, super.cachedProperties.get(key));
            }
            // All the type mapping information is registered
            // when the first call is made.
            // The type mapping information is actually registered in
            // the TypeMappingRegistry of the service, which
            // is the reason why registration is only needed for the first call.
            synchronized (this) {
                if (firstCall()) {
                    // must set encoding style before registering serializers
                    _call.setEncodingStyle(null);
                    for (int i = 0; i < cachedSerFactories.size(); ++i) {
                        java.lang.Class cls = (java.lang.Class) cachedSerClasses.get(i);
                        javax.xml.namespace.QName qName =
                                (javax.xml.namespace.QName) cachedSerQNames.get(i);
                        java.lang.Object x = cachedSerFactories.get(i);
                        if (x instanceof Class) {
                            java.lang.Class sf = (java.lang.Class)
                                 cachedSerFactories.get(i);
                            java.lang.Class df = (java.lang.Class)
                                 cachedDeserFactories.get(i);
                            _call.registerTypeMapping(cls, qName, sf, df, false);
                        }
                        else if (x instanceof javax.xml.rpc.encoding.SerializerFactory) {
                            org.apache.axis.encoding.SerializerFactory sf = (org.apache.axis.encoding.SerializerFactory)
                                 cachedSerFactories.get(i);
                            org.apache.axis.encoding.DeserializerFactory df = (org.apache.axis.encoding.DeserializerFactory)
                                 cachedDeserFactories.get(i);
                            _call.registerTypeMapping(cls, qName, sf, df, false);
                        }
                    }
                }
            }
            return _call;
        }
        catch (java.lang.Throwable _t) {
            throw new org.apache.axis.AxisFault("Failure trying to get the Call object", _t);
        }
    }

    public com.woodwing.enterprise.interfaces.services.adm.LogOnResponse logOn(com.woodwing.enterprise.interfaces.services.adm.LogOnRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[0]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#LogOn");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "LogOn"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.LogOnResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.LogOnResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.LogOnResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void logOff(com.woodwing.enterprise.interfaces.services.adm.LogOffRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[1]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#LogOff");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "LogOff"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreateUsersResponse createUsers(com.woodwing.enterprise.interfaces.services.adm.CreateUsersRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[2]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreateUsers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateUsers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateUsersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateUsersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreateUsersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetUsersResponse getUsers(com.woodwing.enterprise.interfaces.services.adm.GetUsersRequest parameter) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[3]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetUsers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetUsers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameter});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetUsersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetUsersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetUsersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifyUsersResponse modifyUsers(com.woodwing.enterprise.interfaces.services.adm.ModifyUsersRequest paramaters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[4]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyUsers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyUsers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {paramaters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyUsersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyUsersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifyUsersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteUsers(com.woodwing.enterprise.interfaces.services.adm.DeleteUsersRequest paramaters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[5]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeleteUsers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteUsers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {paramaters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsResponse createUserGroups(com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[6]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreateUserGroups");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateUserGroups"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsResponse getUserGroups(com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsRequest parameter) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[7]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetUserGroups");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetUserGroups"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameter});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsResponse modifyUserGroups(com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[8]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyUserGroups");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyUserGroups"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteUserGroups(com.woodwing.enterprise.interfaces.services.adm.DeleteUserGroupsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[9]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeleteUserGroups");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteUserGroups"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void addUsersToGroup(com.woodwing.enterprise.interfaces.services.adm.AddUsersToGroupRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[10]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#AddUsersToGroup");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "AddUsersToGroup"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void removeUsersFromGroup(com.woodwing.enterprise.interfaces.services.adm.RemoveUsersFromGroupRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[11]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#RemoveUsersFromGroup");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "RemoveUsersFromGroup"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void addGroupsToUser(com.woodwing.enterprise.interfaces.services.adm.AddGroupsToUserRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[12]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#AddGroupsToUser");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "AddGroupsToUser"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void removeGroupsFromUser(com.woodwing.enterprise.interfaces.services.adm.RemoveGroupsFromUserRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[13]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#RemoveGroupsFromUser");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "RemoveGroupsFromUser"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsResponse createPublications(com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[14]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreatePublications");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreatePublications"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetPublicationsResponse getPublications(com.woodwing.enterprise.interfaces.services.adm.GetPublicationsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[15]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetPublications");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetPublications"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetPublicationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetPublicationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetPublicationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsResponse modifyPublications(com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[16]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyPublications");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyPublications"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deletePublications(com.woodwing.enterprise.interfaces.services.adm.DeletePublicationsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[17]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeletePublications");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeletePublications"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsResponse createPubChannels(com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[18]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreatePubChannels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreatePubChannels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsResponse getPubChannels(com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[19]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetPubChannels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetPubChannels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsResponse modifyPubChannels(com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[20]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyPubChannels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyPubChannels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deletePubChannels(com.woodwing.enterprise.interfaces.services.adm.DeletePubChannelsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[21]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeletePubChannels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeletePubChannels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreateIssuesResponse createIssues(com.woodwing.enterprise.interfaces.services.adm.CreateIssuesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[22]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreateIssues");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateIssues"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateIssuesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateIssuesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreateIssuesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetIssuesResponse getIssues(com.woodwing.enterprise.interfaces.services.adm.GetIssuesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[23]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetIssues");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetIssues"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetIssuesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetIssuesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetIssuesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesResponse modifyIssues(com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[24]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyIssues");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyIssues"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteIssues(com.woodwing.enterprise.interfaces.services.adm.DeleteIssuesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[25]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeleteIssues");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteIssues"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CopyIssuesResponse copyIssues(com.woodwing.enterprise.interfaces.services.adm.CopyIssuesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[26]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CopyIssues");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CopyIssues"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CopyIssuesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CopyIssuesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CopyIssuesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreateEditionsResponse createEditions(com.woodwing.enterprise.interfaces.services.adm.CreateEditionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[27]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreateEditions");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateEditions"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateEditionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateEditionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreateEditionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetEditionsResponse getEditions(com.woodwing.enterprise.interfaces.services.adm.GetEditionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[28]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetEditions");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetEditions"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetEditionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetEditionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetEditionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsResponse modifyEditions(com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[29]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyEditions");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyEditions"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteEditions(com.woodwing.enterprise.interfaces.services.adm.DeleteEditionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[30]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeleteEditions");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteEditions"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreateSectionsResponse createSections(com.woodwing.enterprise.interfaces.services.adm.CreateSectionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[31]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreateSections");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateSections"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateSectionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateSectionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreateSectionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetSectionsResponse getSections(com.woodwing.enterprise.interfaces.services.adm.GetSectionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[32]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetSections");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetSections"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetSectionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetSectionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetSectionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifySectionsResponse modifySections(com.woodwing.enterprise.interfaces.services.adm.ModifySectionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[33]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifySections");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifySections"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifySectionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifySectionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifySectionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteSections(com.woodwing.enterprise.interfaces.services.adm.DeleteSectionsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[34]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeleteSections");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteSections"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesResponse createAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[35]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreateAutocompleteTermEntities");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateAutocompleteTermEntities"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesResponse getAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[36]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetAutocompleteTermEntities");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetAutocompleteTermEntities"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesResponse modifyAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[37]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyAutocompleteTermEntities");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyAutocompleteTermEntities"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[38]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeleteAutocompleteTermEntities");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteAutocompleteTermEntities"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object createAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[39]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#CreateAutocompleteTerms");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateAutocompleteTerms"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsResponse getAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[40]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#GetAutocompleteTerms");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetAutocompleteTerms"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object modifyAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[41]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#ModifyAutocompleteTerms");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyAutocompleteTerms"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[42]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnectionAdmin#DeleteAutocompleteTerms");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteAutocompleteTerms"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

}
