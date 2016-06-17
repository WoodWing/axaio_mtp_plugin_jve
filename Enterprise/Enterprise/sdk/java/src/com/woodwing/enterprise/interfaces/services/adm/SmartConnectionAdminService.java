/**
 * SmartConnectionAdminService.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public interface SmartConnectionAdminService extends javax.xml.rpc.Service {

/**
 * Enterprise Server administration service
 */
    public java.lang.String getSmartConnectionAdminPortAddress();

    public com.woodwing.enterprise.interfaces.services.adm.SmartConnectionAdminPort_PortType getSmartConnectionAdminPort() throws javax.xml.rpc.ServiceException;

    public com.woodwing.enterprise.interfaces.services.adm.SmartConnectionAdminPort_PortType getSmartConnectionAdminPort(java.net.URL portAddress) throws javax.xml.rpc.ServiceException;
}
