/**
 * EnterprisePublishingService.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public interface EnterprisePublishingService extends javax.xml.rpc.Service {

/**
 * WoodWing Enterprise Channel Publishing Web Service
 */
    public java.lang.String getEnterprisePublishingPortAddress();

    public com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingPort_PortType getEnterprisePublishingPort() throws javax.xml.rpc.ServiceException;

    public com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingPort_PortType getEnterprisePublishingPort(java.net.URL portAddress) throws javax.xml.rpc.ServiceException;
}
