/**
 * PlutusDatasourcePort_PortType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public interface PlutusDatasourcePort_PortType extends java.rmi.Remote {
    public com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesResponse queryDatasources(com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.dat.GetDatasourceResponse getDatasource(com.woodwing.enterprise.interfaces.services.dat.GetDatasourceRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.dat.GetRecordsResponse getRecords(com.woodwing.enterprise.interfaces.services.dat.GetRecordsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object setRecords(com.woodwing.enterprise.interfaces.services.dat.SetRecordsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object hasUpdates(com.woodwing.enterprise.interfaces.services.dat.HasUpdatesRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object onSave(com.woodwing.enterprise.interfaces.services.dat.OnSaveRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.dat.GetUpdatesResponse getUpdates(com.woodwing.enterprise.interfaces.services.dat.GetUpdatesRequest parameters) throws java.rmi.RemoteException;
}
