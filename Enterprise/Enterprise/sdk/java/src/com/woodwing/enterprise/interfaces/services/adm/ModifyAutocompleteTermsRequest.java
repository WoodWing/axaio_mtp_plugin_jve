/**
 * ModifyAutocompleteTermsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class ModifyAutocompleteTermsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity;

    private java.lang.String[] oldTerms;

    private java.lang.String[] newTerms;

    public ModifyAutocompleteTermsRequest() {
    }

    public ModifyAutocompleteTermsRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity,
           java.lang.String[] oldTerms,
           java.lang.String[] newTerms) {
           this.ticket = ticket;
           this.termEntity = termEntity;
           this.oldTerms = oldTerms;
           this.newTerms = newTerms;
    }


    /**
     * Gets the ticket value for this ModifyAutocompleteTermsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this ModifyAutocompleteTermsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the termEntity value for this ModifyAutocompleteTermsRequest.
     * 
     * @return termEntity
     */
    public com.woodwing.enterprise.interfaces.services.adm.TermEntity getTermEntity() {
        return termEntity;
    }


    /**
     * Sets the termEntity value for this ModifyAutocompleteTermsRequest.
     * 
     * @param termEntity
     */
    public void setTermEntity(com.woodwing.enterprise.interfaces.services.adm.TermEntity termEntity) {
        this.termEntity = termEntity;
    }


    /**
     * Gets the oldTerms value for this ModifyAutocompleteTermsRequest.
     * 
     * @return oldTerms
     */
    public java.lang.String[] getOldTerms() {
        return oldTerms;
    }


    /**
     * Sets the oldTerms value for this ModifyAutocompleteTermsRequest.
     * 
     * @param oldTerms
     */
    public void setOldTerms(java.lang.String[] oldTerms) {
        this.oldTerms = oldTerms;
    }


    /**
     * Gets the newTerms value for this ModifyAutocompleteTermsRequest.
     * 
     * @return newTerms
     */
    public java.lang.String[] getNewTerms() {
        return newTerms;
    }


    /**
     * Sets the newTerms value for this ModifyAutocompleteTermsRequest.
     * 
     * @param newTerms
     */
    public void setNewTerms(java.lang.String[] newTerms) {
        this.newTerms = newTerms;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ModifyAutocompleteTermsRequest)) return false;
        ModifyAutocompleteTermsRequest other = (ModifyAutocompleteTermsRequest) obj;
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
            ((this.oldTerms==null && other.getOldTerms()==null) || 
             (this.oldTerms!=null &&
              java.util.Arrays.equals(this.oldTerms, other.getOldTerms()))) &&
            ((this.newTerms==null && other.getNewTerms()==null) || 
             (this.newTerms!=null &&
              java.util.Arrays.equals(this.newTerms, other.getNewTerms())));
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
        if (getOldTerms() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getOldTerms());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getOldTerms(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getNewTerms() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getNewTerms());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getNewTerms(), i);
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
        new org.apache.axis.description.TypeDesc(ModifyAutocompleteTermsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAutocompleteTermsRequest"));
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
        elemField.setFieldName("oldTerms");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OldTerms"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("newTerms");
        elemField.setXmlName(new javax.xml.namespace.QName("", "NewTerms"));
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
