/**
 * UnPublishDossiersRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class UnPublishDossiersRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String[] dossierIDs;

    private com.woodwing.enterprise.interfaces.services.pub.PublishTarget[] targets;

    private com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] publishedDossiers;

    private java.lang.String operationId;

    public UnPublishDossiersRequest() {
    }

    public UnPublishDossiersRequest(
           java.lang.String ticket,
           java.lang.String[] dossierIDs,
           com.woodwing.enterprise.interfaces.services.pub.PublishTarget[] targets,
           com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] publishedDossiers,
           java.lang.String operationId) {
           this.ticket = ticket;
           this.dossierIDs = dossierIDs;
           this.targets = targets;
           this.publishedDossiers = publishedDossiers;
           this.operationId = operationId;
    }


    /**
     * Gets the ticket value for this UnPublishDossiersRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this UnPublishDossiersRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the dossierIDs value for this UnPublishDossiersRequest.
     * 
     * @return dossierIDs
     */
    public java.lang.String[] getDossierIDs() {
        return dossierIDs;
    }


    /**
     * Sets the dossierIDs value for this UnPublishDossiersRequest.
     * 
     * @param dossierIDs
     */
    public void setDossierIDs(java.lang.String[] dossierIDs) {
        this.dossierIDs = dossierIDs;
    }


    /**
     * Gets the targets value for this UnPublishDossiersRequest.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishTarget[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this UnPublishDossiersRequest.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.pub.PublishTarget[] targets) {
        this.targets = targets;
    }


    /**
     * Gets the publishedDossiers value for this UnPublishDossiersRequest.
     * 
     * @return publishedDossiers
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] getPublishedDossiers() {
        return publishedDossiers;
    }


    /**
     * Sets the publishedDossiers value for this UnPublishDossiersRequest.
     * 
     * @param publishedDossiers
     */
    public void setPublishedDossiers(com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] publishedDossiers) {
        this.publishedDossiers = publishedDossiers;
    }


    /**
     * Gets the operationId value for this UnPublishDossiersRequest.
     * 
     * @return operationId
     */
    public java.lang.String getOperationId() {
        return operationId;
    }


    /**
     * Sets the operationId value for this UnPublishDossiersRequest.
     * 
     * @param operationId
     */
    public void setOperationId(java.lang.String operationId) {
        this.operationId = operationId;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof UnPublishDossiersRequest)) return false;
        UnPublishDossiersRequest other = (UnPublishDossiersRequest) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.dossierIDs==null && other.getDossierIDs()==null) || 
             (this.dossierIDs!=null &&
              java.util.Arrays.equals(this.dossierIDs, other.getDossierIDs()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets()))) &&
            ((this.publishedDossiers==null && other.getPublishedDossiers()==null) || 
             (this.publishedDossiers!=null &&
              java.util.Arrays.equals(this.publishedDossiers, other.getPublishedDossiers()))) &&
            ((this.operationId==null && other.getOperationId()==null) || 
             (this.operationId!=null &&
              this.operationId.equals(other.getOperationId())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getDossierIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDossierIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDossierIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTargets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPublishedDossiers() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPublishedDossiers());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPublishedDossiers(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getOperationId() != null) {
            _hashCode += getOperationId().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(UnPublishDossiersRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UnPublishDossiersRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dossierIDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DossierIDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Targets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishTarget"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PublishTarget"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedDossiers");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedDossiers"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedDossier"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PublishedDossier"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("operationId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OperationId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
