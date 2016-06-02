/**
 * CopyPublicationsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class CopyPublicationsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes;

    private boolean duplicateIssues;

    private java.math.BigInteger sourcePubId;

    private com.woodwing.enterprise.interfaces.services.adm.Publication[] targetPubs;

    public CopyPublicationsRequest() {
    }

    public CopyPublicationsRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes,
           boolean duplicateIssues,
           java.math.BigInteger sourcePubId,
           com.woodwing.enterprise.interfaces.services.adm.Publication[] targetPubs) {
           this.ticket = ticket;
           this.requestModes = requestModes;
           this.duplicateIssues = duplicateIssues;
           this.sourcePubId = sourcePubId;
           this.targetPubs = targetPubs;
    }


    /**
     * Gets the ticket value for this CopyPublicationsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CopyPublicationsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the requestModes value for this CopyPublicationsRequest.
     * 
     * @return requestModes
     */
    public com.woodwing.enterprise.interfaces.services.adm.Mode[] getRequestModes() {
        return requestModes;
    }


    /**
     * Sets the requestModes value for this CopyPublicationsRequest.
     * 
     * @param requestModes
     */
    public void setRequestModes(com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes) {
        this.requestModes = requestModes;
    }


    /**
     * Gets the duplicateIssues value for this CopyPublicationsRequest.
     * 
     * @return duplicateIssues
     */
    public boolean isDuplicateIssues() {
        return duplicateIssues;
    }


    /**
     * Sets the duplicateIssues value for this CopyPublicationsRequest.
     * 
     * @param duplicateIssues
     */
    public void setDuplicateIssues(boolean duplicateIssues) {
        this.duplicateIssues = duplicateIssues;
    }


    /**
     * Gets the sourcePubId value for this CopyPublicationsRequest.
     * 
     * @return sourcePubId
     */
    public java.math.BigInteger getSourcePubId() {
        return sourcePubId;
    }


    /**
     * Sets the sourcePubId value for this CopyPublicationsRequest.
     * 
     * @param sourcePubId
     */
    public void setSourcePubId(java.math.BigInteger sourcePubId) {
        this.sourcePubId = sourcePubId;
    }


    /**
     * Gets the targetPubs value for this CopyPublicationsRequest.
     * 
     * @return targetPubs
     */
    public com.woodwing.enterprise.interfaces.services.adm.Publication[] getTargetPubs() {
        return targetPubs;
    }


    /**
     * Sets the targetPubs value for this CopyPublicationsRequest.
     * 
     * @param targetPubs
     */
    public void setTargetPubs(com.woodwing.enterprise.interfaces.services.adm.Publication[] targetPubs) {
        this.targetPubs = targetPubs;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CopyPublicationsRequest)) return false;
        CopyPublicationsRequest other = (CopyPublicationsRequest) obj;
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
            ((this.requestModes==null && other.getRequestModes()==null) || 
             (this.requestModes!=null &&
              java.util.Arrays.equals(this.requestModes, other.getRequestModes()))) &&
            this.duplicateIssues == other.isDuplicateIssues() &&
            ((this.sourcePubId==null && other.getSourcePubId()==null) || 
             (this.sourcePubId!=null &&
              this.sourcePubId.equals(other.getSourcePubId()))) &&
            ((this.targetPubs==null && other.getTargetPubs()==null) || 
             (this.targetPubs!=null &&
              java.util.Arrays.equals(this.targetPubs, other.getTargetPubs())));
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
        if (getRequestModes() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestModes());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestModes(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        _hashCode += (isDuplicateIssues() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getSourcePubId() != null) {
            _hashCode += getSourcePubId().hashCode();
        }
        if (getTargetPubs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargetPubs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargetPubs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(CopyPublicationsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyPublicationsRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestModes");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestModes"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Mode"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Mode"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("duplicateIssues");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DuplicateIssues"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sourcePubId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SourcePubId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targetPubs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TargetPubs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Publication"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Publication"));
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
