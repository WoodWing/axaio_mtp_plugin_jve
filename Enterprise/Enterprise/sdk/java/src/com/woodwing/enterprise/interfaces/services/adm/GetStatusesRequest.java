/**
 * GetStatusesRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class GetStatusesRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.math.BigInteger publicationId;

    private java.math.BigInteger issueId;

    private com.woodwing.enterprise.interfaces.services.adm.ObjectType objectType;

    private java.math.BigInteger[] statusIds;

    public GetStatusesRequest() {
    }

    public GetStatusesRequest(
           java.lang.String ticket,
           java.math.BigInteger publicationId,
           java.math.BigInteger issueId,
           com.woodwing.enterprise.interfaces.services.adm.ObjectType objectType,
           java.math.BigInteger[] statusIds) {
           this.ticket = ticket;
           this.publicationId = publicationId;
           this.issueId = issueId;
           this.objectType = objectType;
           this.statusIds = statusIds;
    }


    /**
     * Gets the ticket value for this GetStatusesRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetStatusesRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the publicationId value for this GetStatusesRequest.
     * 
     * @return publicationId
     */
    public java.math.BigInteger getPublicationId() {
        return publicationId;
    }


    /**
     * Sets the publicationId value for this GetStatusesRequest.
     * 
     * @param publicationId
     */
    public void setPublicationId(java.math.BigInteger publicationId) {
        this.publicationId = publicationId;
    }


    /**
     * Gets the issueId value for this GetStatusesRequest.
     * 
     * @return issueId
     */
    public java.math.BigInteger getIssueId() {
        return issueId;
    }


    /**
     * Sets the issueId value for this GetStatusesRequest.
     * 
     * @param issueId
     */
    public void setIssueId(java.math.BigInteger issueId) {
        this.issueId = issueId;
    }


    /**
     * Gets the objectType value for this GetStatusesRequest.
     * 
     * @return objectType
     */
    public com.woodwing.enterprise.interfaces.services.adm.ObjectType getObjectType() {
        return objectType;
    }


    /**
     * Sets the objectType value for this GetStatusesRequest.
     * 
     * @param objectType
     */
    public void setObjectType(com.woodwing.enterprise.interfaces.services.adm.ObjectType objectType) {
        this.objectType = objectType;
    }


    /**
     * Gets the statusIds value for this GetStatusesRequest.
     * 
     * @return statusIds
     */
    public java.math.BigInteger[] getStatusIds() {
        return statusIds;
    }


    /**
     * Sets the statusIds value for this GetStatusesRequest.
     * 
     * @param statusIds
     */
    public void setStatusIds(java.math.BigInteger[] statusIds) {
        this.statusIds = statusIds;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetStatusesRequest)) return false;
        GetStatusesRequest other = (GetStatusesRequest) obj;
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
            ((this.publicationId==null && other.getPublicationId()==null) || 
             (this.publicationId!=null &&
              this.publicationId.equals(other.getPublicationId()))) &&
            ((this.issueId==null && other.getIssueId()==null) || 
             (this.issueId!=null &&
              this.issueId.equals(other.getIssueId()))) &&
            ((this.objectType==null && other.getObjectType()==null) || 
             (this.objectType!=null &&
              this.objectType.equals(other.getObjectType()))) &&
            ((this.statusIds==null && other.getStatusIds()==null) || 
             (this.statusIds!=null &&
              java.util.Arrays.equals(this.statusIds, other.getStatusIds())));
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
        if (getPublicationId() != null) {
            _hashCode += getPublicationId().hashCode();
        }
        if (getIssueId() != null) {
            _hashCode += getIssueId().hashCode();
        }
        if (getObjectType() != null) {
            _hashCode += getObjectType().hashCode();
        }
        if (getStatusIds() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStatusIds());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStatusIds(), i);
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
        new org.apache.axis.description.TypeDesc(GetStatusesRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetStatusesRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publicationId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublicationId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issueId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IssueId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectType"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ObjectType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statusIds");
        elemField.setXmlName(new javax.xml.namespace.QName("", "StatusIds"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Id"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Id"));
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
