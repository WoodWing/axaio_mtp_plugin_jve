/**
 * SmartConnectionService.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public interface SmartConnectionService extends javax.xml.rpc.Service {

/**
 * Enterprise Server workflow service
 */
    public java.lang.String getSmartConnectionPortNameAddress();

    public com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionPort getSmartConnectionPortName() throws javax.xml.rpc.ServiceException;

    public com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionPort getSmartConnectionPortName(java.net.URL portAddress) throws javax.xml.rpc.ServiceException;
}