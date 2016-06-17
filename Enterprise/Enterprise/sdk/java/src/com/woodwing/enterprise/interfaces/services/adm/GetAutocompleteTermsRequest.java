/**
 * GetAutocompleteTermsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class GetAutocompleteTermsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity;

    private java.lang.String typedValue;

    private java.math.BigInteger firstEntry;

    private java.math.BigInteger maxEntries;

    public GetAutocompleteTermsRequest() {
    }

    public GetAutocompleteTermsRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity,
           java.lang.String typedValue,
           java.math.BigInteger firstEntry,
           java.math.BigInteger maxEntries) {
           this.ticket = ticket;
           this.termEntity = termEntity;
           this.typedValue = typedValue;
           this.firstEntry = firstEntry;
           this.maxEntries = maxEntries;
    }


    /**
     * Gets the ticket value for this GetAutocompleteTermsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetAutocompleteTermsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the termEntity value for this GetAutocompleteTermsRequest.
     * 
     * @return termEntity
     */
    public com.woodwing.enterprise.interfaces.services.adm.TermEntity getTermEntity() {
        return termEntity;
    }


    /**
     * Sets the termEntity value for this GetAutocompleteTermsRequest.
     * 
     * @param termEntity
     */
    public void setTermEntity(com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity) {
        this.termEntity = termEntity;
    }


    /**
     * Gets the typedValue value for this GetAutocompleteTermsRequest.
     * 
     * @return typedValue
     */
    public java.lang.String getTypedValue() {
        return typedValue;
    }


    /**
     * Sets the typedValue value for this GetAutocompleteTermsRequest.
     * 
     * @param typedValue
     */
    public void setTypedValue(java.lang.String typedValue) {
        this.typedValue = typedValue;
    }


    /**
     * Gets the firstEntry value for this GetAutocompleteTermsRequest.
     * 
     * @return firstEntry
     */
    public java.math.BigInteger getFirstEntry() {
        return firstEntry;
    }


    /**
     * Sets the firstEntry value for this GetAutocompleteTermsRequest.
     * 
     * @param firstEntry
     */
    public void setFirstEntry(java.math.BigInteger firstEntry) {
        this.firstEntry = firstEntry;
    }


    /**
     * Gets the maxEntries value for this GetAutocompleteTermsRequest.
     * 
     * @return maxEntries
     */
    public java.math.BigInteger getMaxEntries() {
        return maxEntries;
    }


    /**
     * Sets the maxEntries value for this GetAutocompleteTermsRequest.
     * 
     * @param maxEntries
     */
    public void setMaxEntries(java.math.BigInteger maxEntries) {
        this.maxEntries = maxEntries;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetAutocompleteTermsRequest)) return false;
        GetAutocompleteTermsRequest other = (GetAutocompleteTermsRequest) obj;
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
            ((this.termEntity==null && other.getTermEntity()==null) || 
             (this.termEntity!=null &&
              this.termEntity.equals(other.getTermEntity()))) &&
            ((this.typedValue==null && other.getTypedValue()==null) || 
             (this.typedValue!=null &&
              this.typedValue.equals(other.getTypedValue()))) &&
            ((this.firstEntry==null && other.getFirstEntry()==null) || 
             (this.firstEntry!=null &&
              this.firstEntry.equals(other.getFirstEntry()))) &&
            ((this.maxEntries==null && other.getMaxEntries()==null) || 
             (this.maxEntries!=null &&
              this.maxEntries.equals(other.getMaxEntries())));
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
        if (getTermEntity() != null) {
            _hashCode += getTermEntity().hashCode();
        }
        if (getTypedValue() != null) {
            _hashCode += getTypedValue().hashCode();
        }
        if (getFirstEntry() != null) {
            _hashCode += getFirstEntry().hashCode();
        }
        if (getMaxEntries() != null) {
            _hashCode += getMaxEntries().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetAutocompleteTermsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermsRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("termEntity");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TermEntity"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "TermEntity"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("typedValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TypedValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("firstEntry");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FirstEntry"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("maxEntries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MaxEntries"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
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
