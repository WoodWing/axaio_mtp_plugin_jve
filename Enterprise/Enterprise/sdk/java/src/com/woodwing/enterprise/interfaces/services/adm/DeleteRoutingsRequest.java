/**
 * DeleteRoutingsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class DeleteRoutingsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.math.BigInteger publicationId;

    private java.math.BigInteger issueId;

    private java.math.BigInteger sectionId;

    private java.math.BigInteger[] routingIds;

    public DeleteRoutingsRequest() {
    }

    public DeleteRoutingsRequest(
           java.lang.String ticket,
           java.math.BigInteger publicationId,
           java.math.BigInteger issueId,
           java.math.BigInteger sectionId,
           java.math.BigInteger[] routingIds) {
           this.ticket = ticket;
           this.publicationId = publicationId;
           this.issueId = issueId;
           this.sectionId = sectionId;
           this.routingIds = routingIds;
    }


    /**
     * Gets the ticket value for this DeleteRoutingsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this DeleteRoutingsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the publicationId value for this DeleteRoutingsRequest.
     * 
     * @return publicationId
     */
    public java.math.BigInteger getPublicationId() {
        return publicationId;
    }


    /**
     * Sets the publicationId value for this DeleteRoutingsRequest.
     * 
     * @param publicationId
     */
    public void setPublicationId(java.math.BigInteger publicationId) {
        this.publicationId = publicationId;
    }


    /**
     * Gets the issueId value for this DeleteRoutingsRequest.
     * 
     * @return issueId
     */
    public java.math.BigInteger getIssueId() {
        return issueId;
    }


    /**
     * Sets the issueId value for this DeleteRoutingsRequest.
     * 
     * @param issueId
     */
    public void setIssueId(java.math.BigInteger issueId) {
        this.issueId = issueId;
    }


    /**
     * Gets the sectionId value for this DeleteRoutingsRequest.
     * 
     * @return sectionId
     */
    public java.math.BigInteger getSectionId() {
        return sectionId;
    }


    /**
     * Sets the sectionId value for this DeleteRoutingsRequest.
     * 
     * @param sectionId
     */
    public void setSectionId(java.math.BigInteger sectionId) {
        this.sectionId = sectionId;
    }


    /**
     * Gets the routingIds value for this DeleteRoutingsRequest.
     * 
     * @return routingIds
     */
    public java.math.BigInteger[] getRoutingIds() {
        return routingIds;
    }


    /**
     * Sets the routingIds value for this DeleteRoutingsRequest.
     * 
     * @param routingIds
     */
    public void setRoutingIds(java.math.BigInteger[] routingIds) {
        this.routingIds = routingIds;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof DeleteRoutingsRequest)) return false;
        DeleteRoutingsRequest other = (DeleteRoutingsRequest) obj;
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
            ((this.sectionId==null && other.getSectionId()==null) || 
             (this.sectionId!=null &&
              this.sectionId.equals(other.getSectionId()))) &&
            ((this.routingIds==null && other.getRoutingIds()==null) || 
             (this.routingIds!=null &&
              java.util.Arrays.equals(this.routingIds, other.getRoutingIds())));
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
        if (getSectionId() != null) {
            _hashCode += getSectionId().hashCode();
        }
        if (getRoutingIds() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRoutingIds());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRoutingIds(), i);
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
        new org.apache.axis.description.TypeDesc(DeleteRoutingsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteRoutingsRequest"));
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
        elemField.setFieldName("sectionId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SectionId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routingIds");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RoutingIds"));
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