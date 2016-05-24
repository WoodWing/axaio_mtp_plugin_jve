/**
 * EnterprisePublishingPort_PortType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public interface EnterprisePublishingPort_PortType extends java.rmi.Remote {
    public com.woodwing.enterprise.interfaces.services.pub.PublishDossiersResponse publishDossiers(com.woodwing.enterprise.interfaces.services.pub.PublishDossiersRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersResponse updateDossiers(com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersResponse unPublishDossiers(com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.GetDossierURLResponse getDossierURL(com.woodwing.enterprise.interfaces.services.pub.GetDossierURLRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoResponse getPublishInfo(com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoResponse setPublishInfo(com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersResponse previewDossiers(com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderResponse getDossierOrder(com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderResponse updateDossierOrder(com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderRequest parameters) throws java.rmi.RemoteException;
    public void abortOperation(com.woodwing.enterprise.interfaces.services.pub.AbortOperationRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.pub.OperationProgressResponse operationProgress(com.woodwing.enterprise.interfaces.services.pub.OperationProgressRequest parameters) throws java.rmi.RemoteException;
}
