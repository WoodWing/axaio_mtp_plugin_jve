/**
 * SmartEditorialPlanPort_PortType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public interface SmartEditorialPlanPort_PortType extends java.rmi.Remote {
    public com.woodwing.enterprise.interfaces.services.pln.LogOnResponse logOn(com.woodwing.enterprise.interfaces.services.pln.LogOn parameters) throws java.rmi.RemoteException;
    public void logOff(com.woodwing.enterprise.interfaces.services.pln.LogOff parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pln.CreateLayoutsResponse createLayouts(com.woodwing.enterprise.interfaces.services.pln.CreateLayouts parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pln.ModifyLayoutsResponse modifyLayouts(com.woodwing.enterprise.interfaces.services.pln.ModifyLayouts parameters) throws java.rmi.RemoteException;
    public void deleteLayouts(com.woodwing.enterprise.interfaces.services.pln.DeleteLayouts parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pln.CreateAdvertsResponse createAdverts(com.woodwing.enterprise.interfaces.services.pln.CreateAdverts parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pln.ModifyAdvertsResponse modifyAdverts(com.woodwing.enterprise.interfaces.services.pln.ModifyAdverts parameters) throws java.rmi.RemoteException;
    public void deleteAdverts(com.woodwing.enterprise.interfaces.services.pln.DeleteAdverts parameters) throws java.rmi.RemoteException;
}
