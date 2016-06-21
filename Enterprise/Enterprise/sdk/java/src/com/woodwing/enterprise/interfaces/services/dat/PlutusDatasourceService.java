/**
 * PlutusDatasourceService.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public interface PlutusDatasourceService extends javax.xml.rpc.Service {

/**
 * Plutus Data source Web Service
 */
    public java.lang.String getPlutusDatasourcePortAddress();

    public com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourcePort_PortType getPlutusDatasourcePort() throws javax.xml.rpc.ServiceException;

    public com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourcePort_PortType getPlutusDatasourcePort(java.net.URL portAddress) throws javax.xml.rpc.ServiceException;
}
