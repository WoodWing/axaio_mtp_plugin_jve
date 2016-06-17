/**
 * ModifyEditionsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class ModifyEditionsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.math.BigInteger publicationId;

    private java.math.BigInteger pubChannelId;

    private java.math.BigInteger issueId;

    private com.woodwing.enterprise.interfaces.services.adm.Edition[] editions;

    public ModifyEditionsRequest() {
    }

    public ModifyEditionsRequest(
           java.lang.String ticket,
           java.math.BigInteger publicationId,
           java.math.BigInteger pubChannelId,
           java.math.BigInteger issueId,
           com.woodwing.enterprise.interfaces.services.adm.Edition[] editions) {
           this.ticket = ticket;
           this.publicationId = publicationId;
           this.pubChannelId = pubChannelId;
           this.issueId = issueId;
           this.editions = editions;
    }


    /**
     * Gets the ticket value for this ModifyEditionsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this ModifyEditionsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the publicationId value for this ModifyEditionsRequest.
     * 
     * @return publicationId
     */
    public java.math.BigInteger getPublicationId() {
        return publicationId;
    }


    /**
     * Sets the publicationId value for this ModifyEditionsRequest.
     * 
     * @param publicationId
     */
    public void setPublicationId(java.math.BigInteger publicationId) {
        this.publicationId = publicationId;
    }


    /**
     * Gets the pubChannelId value for this ModifyEditionsRequest.
     * 
     * @return pubChannelId
     */
    public java.math.BigInteger getPubChannelId() {
        return pubChannelId;
    }


    /**
     * Sets the pubChannelId value for this ModifyEditionsRequest.
     * 
     * @param pubChannelId
     */
    public void setPubChannelId(java.math.BigInteger pubChannelId) {
        this.pubChannelId = pubChannelId;
    }


    /**
     * Gets the issueId value for this ModifyEditionsRequest.
     * 
     * @return issueId
     */
    public java.math.BigInteger getIssueId() {
        return issueId;
    }


    /**
     * Sets the issueId value for this ModifyEditionsRequest.
     * 
     * @param issueId
     */
    public void setIssueId(java.math.BigInteger issueId) {
        this.issueId = issueId;
    }


    /**
     * Gets the editions value for this ModifyEditionsRequest.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.adm.Edition[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this ModifyEditionsRequest.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.adm.Edition[] editions) {
        this.editions = editions;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ModifyEditionsRequest)) return false;
        ModifyEditionsRequest other = (ModifyEditionsRequest) obj;
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
            ((this.pubChannelId==null && other.getPubChannelId()==null) || 
             (this.pubChannelId!=null &&
              this.pubChannelId.equals(other.getPubChannelId()))) &&
            ((this.issueId==null && other.getIssueId()==null) || 
             (this.issueId!=null &&
              this.issueId.equals(other.getIssueId()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions())));
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
        if (getPubChannelId() != null) {
            _hashCode += getPubChannelId().hashCode();
        }
        if (getIssueId() != null) {
            _hashCode += getIssueId().hashCode();
        }
        if (getEditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditions(), i);
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
        new org.apache.axis.description.TypeDesc(ModifyEditionsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyEditionsRequest"));
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
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannelId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannelId"));
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
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Edition"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Edition"));
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
