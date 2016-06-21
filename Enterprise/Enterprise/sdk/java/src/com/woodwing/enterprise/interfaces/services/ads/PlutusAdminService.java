/**
 * PlutusAdminService.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public interface PlutusAdminService extends javax.xml.rpc.Service {

/**
 * Plutus Admin Web Service
 */
    public java.lang.String getPlutusAdminPortAddress();

    public com.woodwing.enterprise.interfaces.services.ads.PlutusAdminPort_PortType getPlutusAdminPort() throws javax.xml.rpc.ServiceException;

    public com.woodwing.enterprise.interfaces.services.ads.PlutusAdminPort_PortType getPlutusAdminPort(java.net.URL portAddress) throws javax.xml.rpc.ServiceException;
}
