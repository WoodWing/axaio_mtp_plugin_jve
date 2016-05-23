/**
 * SetPublishInfoRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class SetPublishInfoRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] publishedDossiers;

    private com.woodwing.enterprise.interfaces.services.pub.PublishedIssue publishedIssue;

    private java.lang.String[] requestInfo;

    public SetPublishInfoRequest() {
    }

    public SetPublishInfoRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] publishedDossiers,
           com.woodwing.enterprise.interfaces.services.pub.PublishedIssue publishedIssue,
           java.lang.String[] requestInfo) {
           this.ticket = ticket;
           this.publishedDossiers = publishedDossiers;
           this.publishedIssue = publishedIssue;
           this.requestInfo = requestInfo;
    }


    /**
     * Gets the ticket value for this SetPublishInfoRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this SetPublishInfoRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the publishedDossiers value for this SetPublishInfoRequest.
     * 
     * @return publishedDossiers
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] getPublishedDossiers() {
        return publishedDossiers;
    }


    /**
     * Sets the publishedDossiers value for this SetPublishInfoRequest.
     * 
     * @param publishedDossiers
     */
    public void setPublishedDossiers(com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[] publishedDossiers) {
        this.publishedDossiers = publishedDossiers;
    }


    /**
     * Gets the publishedIssue value for this SetPublishInfoRequest.
     * 
     * @return publishedIssue
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishedIssue getPublishedIssue() {
        return publishedIssue;
    }


    /**
     * Sets the publishedIssue value for this SetPublishInfoRequest.
     * 
     * @param publishedIssue
     */
    public void setPublishedIssue(com.woodwing.enterprise.interfaces.services.pub.PublishedIssue publishedIssue) {
        this.publishedIssue = publishedIssue;
    }


    /**
     * Gets the requestInfo value for this SetPublishInfoRequest.
     * 
     * @return requestInfo
     */
    public java.lang.String[] getRequestInfo() {
        return requestInfo;
    }


    /**
     * Sets the requestInfo value for this SetPublishInfoRequest.
     * 
     * @param requestInfo
     */
    public void setRequestInfo(java.lang.String[] requestInfo) {
        this.requestInfo = requestInfo;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof SetPublishInfoRequest)) return false;
        SetPublishInfoRequest other = (SetPublishInfoRequest) obj;
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
            ((this.publishedDossiers==null && other.getPublishedDossiers()==null) || 
             (this.publishedDossiers!=null &&
              java.util.Arrays.equals(this.publishedDossiers, other.getPublishedDossiers()))) &&
            ((this.publishedIssue==null && other.getPublishedIssue()==null) || 
             (this.publishedIssue!=null &&
              this.publishedIssue.equals(other.getPublishedIssue()))) &&
            ((this.requestInfo==null && other.getRequestInfo()==null) || 
             (this.requestInfo!=null &&
              java.util.Arrays.equals(this.requestInfo, other.getRequestInfo())));
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
        if (getPublishedIssue() != null) {
            _hashCode += getPublishedIssue().hashCode();
        }
        if (getRequestInfo() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestInfo());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestInfo(), i);
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
        new org.apache.axis.description.TypeDesc(SetPublishInfoRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">SetPublishInfoRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedDossiers");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedDossiers"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedDossier"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PublishedDossier"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedIssue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedIssue"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedIssue"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
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
