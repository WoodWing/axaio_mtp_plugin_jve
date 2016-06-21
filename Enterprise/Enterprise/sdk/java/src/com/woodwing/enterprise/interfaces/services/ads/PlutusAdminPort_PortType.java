/**
 * PlutusAdminPort_PortType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public interface PlutusAdminPort_PortType extends java.rmi.Remote {
    public com.woodwing.enterprise.interfaces.services.ads.GetPublicationsResponse getPublications(com.woodwing.enterprise.interfaces.services.ads.GetPublicationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoResponse getDatasourceInfo(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceResponse getDatasource(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetQueryResponse getQuery(com.woodwing.enterprise.interfaces.services.ads.GetQueryRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetQueriesResponse getQueries(com.woodwing.enterprise.interfaces.services.ads.GetQueriesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsResponse getQueryFields(com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesResponse getDatasourceTypes(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeResponse getDatasourceType(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsResponse getSettingsDetails(com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.GetSettingsResponse getSettings(com.woodwing.enterprise.interfaces.services.ads.GetSettingsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesResponse queryDatasources(com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.NewQueryResponse newQuery(com.woodwing.enterprise.interfaces.services.ads.NewQueryRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.NewDatasourceResponse newDatasource(com.woodwing.enterprise.interfaces.services.ads.NewDatasourceRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object savePublication(com.woodwing.enterprise.interfaces.services.ads.SavePublicationRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object saveQueryField(com.woodwing.enterprise.interfaces.services.ads.SaveQueryFieldRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object saveQuery(com.woodwing.enterprise.interfaces.services.ads.SaveQueryRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object saveDatasource(com.woodwing.enterprise.interfaces.services.ads.SaveDatasourceRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object saveSetting(com.woodwing.enterprise.interfaces.services.ads.SaveSettingRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deletePublication(com.woodwing.enterprise.interfaces.services.ads.DeletePublicationRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteQueryField(com.woodwing.enterprise.interfaces.services.ads.DeleteQueryFieldRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteQuery(com.woodwing.enterprise.interfaces.services.ads.DeleteQueryRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteDatasource(com.woodwing.enterprise.interfaces.services.ads.DeleteDatasourceRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceResponse copyDatasource(com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.ads.CopyQueryResponse copyQuery(com.woodwing.enterprise.interfaces.services.ads.CopyQueryRequest parameters) throws java.rmi.RemoteException;
}
