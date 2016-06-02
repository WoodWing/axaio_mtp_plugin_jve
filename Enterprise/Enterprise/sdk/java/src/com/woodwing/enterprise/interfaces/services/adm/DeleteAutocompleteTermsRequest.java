/**
 * DeleteAutocompleteTermsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class DeleteAutocompleteTermsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity;

    private java.lang.String[] terms;

    public DeleteAutocompleteTermsRequest() {
    }

    public DeleteAutocompleteTermsRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity,
           java.lang.String[] terms) {
           this.ticket = ticket;
           this.termEntity = termEntity;
           this.terms = terms;
    }


    /**
     * Gets the ticket value for this DeleteAutocompleteTermsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this DeleteAutocompleteTermsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the termEntity value for this DeleteAutocompleteTermsRequest.
     * 
     * @return termEntity
     */
    public com.woodwing.enterprise.interfaces.services.adm.TermEntity getTermEntity() {
        return termEntity;
    }


    /**
     * Sets the termEntity value for this DeleteAutocompleteTermsRequest.
     * 
     * @param termEntity
     */
    public void setTermEntity(com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity) {
        this.termEntity = termEntity;
    }


    /**
     * Gets the terms value for this DeleteAutocompleteTermsRequest.
     * 
     * @return terms
     */
    public java.lang.String[] getTerms() {
        return terms;
    }


    /**
     * Sets the terms value for this DeleteAutocompleteTermsRequest.
     * 
     * @param terms
     */
    public void setTerms(java.lang.String[] terms) {
        this.terms = terms;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof DeleteAutocompleteTermsRequest)) return false;
        DeleteAutocompleteTermsRequest other = (DeleteAutocompleteTermsRequest) obj;
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
            ((this.terms==null && other.getTerms()==null) || 
             (this.terms!=null &&
              java.util.Arrays.equals(this.terms, other.getTerms())));
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
        if (getTerms() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTerms());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTerms(), i);
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
        new org.apache.axis.description.TypeDesc(DeleteAutocompleteTermsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">DeleteAutocompleteTermsRequest"));
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
        elemField.setFieldName("terms");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Terms"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "String"));
        elemField.setNillable(false);
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
